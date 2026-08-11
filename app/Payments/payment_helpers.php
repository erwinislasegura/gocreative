<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/flow/configuration.php';

function payment_status_meta(string $status): array
{
    $statuses = [
        'created' => ['label' => 'Preparando', 'class' => 'created', 'message' => 'Estamos preparando el enlace de pago.'],
        'pending' => ['label' => 'Pendiente', 'class' => 'pending', 'message' => 'La orden esta disponible y todavia no registra un pago confirmado.'],
        'paid' => ['label' => 'Pagada', 'class' => 'paid', 'message' => 'El pago fue confirmado correctamente por Flow.'],
        'rejected' => ['label' => 'Rechazada', 'class' => 'rejected', 'message' => 'Flow informo que el intento de pago fue rechazado.'],
        'cancelled' => ['label' => 'Anulada', 'class' => 'cancelled', 'message' => 'La orden fue anulada y ya no esta disponible para pago.'],
        'error' => ['label' => 'Con error', 'class' => 'error', 'message' => 'No fue posible completar la creacion de la orden.'],
    ];

    return $statuses[$status] ?? $statuses['error'];
}

function payment_format_amount(int $amount, string $currency = 'CLP'): string
{
    if ($currency === 'CLP') {
        return '$' . number_format($amount, 0, ',', '.') . ' CLP';
    }

    return number_format($amount, 2, ',', '.') . ' ' . $currency;
}

function payment_generate_commerce_order(): string
{
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $value = 'GC-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $statement = db()->prepare('SELECT COUNT(*) FROM payment_orders WHERE commerce_order = :commerce_order');
        $statement->execute(['commerce_order' => $value]);
        if ((int) $statement->fetchColumn() === 0) {
            return $value;
        }
    }

    throw new RuntimeException('No fue posible generar un numero de orden unico.');
}

function payment_create_order(array $data, int $createdBy): int
{
    $config = flow_config();
    if (!flow_is_configured()) {
        throw new RuntimeException('Configura las credenciales de Flow antes de crear un cobro.');
    }

    $commerceOrder = payment_generate_commerce_order();
    $publicKey = bin2hex(random_bytes(32));
    $currency = 'CLP';
    $referenceType = (string) ($data['reference_type'] ?? 'manual');
    if (!in_array($referenceType, ['manual', 'hosting', 'quote'], true)) {
        $referenceType = 'manual';
    }
    $timeout = isset($data['timeout']) ? (int) $data['timeout'] : (int) $config['timeout'];
    $timeout = max(0, min($timeout, 2592000));

    $insert = db()->prepare(
        'INSERT INTO payment_orders
         (created_by, commerce_order, public_key, customer_name, customer_email, subject, amount, currency, status,
          reference_type, reference_id, expires_at)
         VALUES
         (:created_by, :commerce_order, :public_key, :customer_name, :customer_email, :subject, :amount, :currency, :status,
          :reference_type, :reference_id,
          CASE WHEN :timeout_seconds > 0 THEN DATE_ADD(NOW(), INTERVAL :timeout_seconds_copy SECOND) ELSE NULL END)'
    );
    $insert->execute([
        'created_by' => $createdBy,
        'commerce_order' => $commerceOrder,
        'public_key' => $publicKey,
        'customer_name' => $data['customer_name'],
        'customer_email' => $data['customer_email'],
        'subject' => $data['subject'],
        'amount' => $data['amount'],
        'currency' => $currency,
        'status' => 'created',
        'reference_type' => $referenceType,
        'reference_id' => !empty($data['reference_id']) ? (int) $data['reference_id'] : null,
        'timeout_seconds' => $timeout,
        'timeout_seconds_copy' => $timeout,
    ]);
    $orderId = (int) db()->lastInsertId();
    payment_record_event($orderId, 'created', null, 'Orden creada en Go Creative.', null);

    try {
        $response = flow_client()->createPayment([
            'commerce_order' => $commerceOrder,
            'subject' => $data['subject'],
            'currency' => $currency,
            'amount' => $data['amount'],
            'email' => $data['customer_email'],
            'payment_method' => $config['payment_method'],
            'url_confirmation' => $config['public_url'] . '/pagos/confirmacion.php',
            'url_return' => $config['public_url'] . '/pagos/retorno.php',
            'optional' => ['order' => $commerceOrder],
            'timeout' => $timeout,
        ]);

        $checkoutUrl = payment_checkout_url((string) $response['url'], (string) $response['token']);
        $update = db()->prepare(
            'UPDATE payment_orders
             SET flow_order = :flow_order, token = :token, checkout_url = :checkout_url,
                 status = :status, flow_status = 1, last_error = NULL, last_synced_at = NOW()
             WHERE id = :id'
        );
        $update->execute([
            'flow_order' => (int) $response['flowOrder'],
            'token' => (string) $response['token'],
            'checkout_url' => $checkoutUrl,
            'status' => 'pending',
            'id' => $orderId,
        ]);
        payment_record_event($orderId, 'flow_created', 1, 'Orden registrada en Flow y lista para pago.', [
            'flowOrder' => (int) $response['flowOrder'],
        ]);
    } catch (Throwable $exception) {
        $message = payment_safe_error($exception->getMessage());
        $update = db()->prepare('UPDATE payment_orders SET status = :status, last_error = :last_error WHERE id = :id');
        $update->execute(['status' => 'error', 'last_error' => $message, 'id' => $orderId]);
        payment_record_event($orderId, 'flow_error', null, $message, null);
        throw new RuntimeException($message, 0, $exception);
    }

    return $orderId;
}

function payment_checkout_url(string $baseUrl, string $token): string
{
    $parts = parse_url($baseUrl);
    $host = strtolower((string) ($parts['host'] ?? ''));
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if ($scheme !== 'https' || ($host !== 'flow.cl' && !str_ends_with($host, '.flow.cl'))) {
        throw new RuntimeException('Flow entrego una URL de pago no valida.');
    }
    if ($token === '' || strlen($token) > 255) {
        throw new RuntimeException('Flow entrego un token de pago no valido.');
    }

    $separator = str_contains($baseUrl, '?') ? '&' : '?';
    return $baseUrl . $separator . 'token=' . rawurlencode($token);
}

function payment_find_by_id(int $id): ?array
{
    $statement = db()->prepare(
        'SELECT po.*, u.name AS created_by_name
         FROM payment_orders po
         LEFT JOIN users u ON u.id = po.created_by
         WHERE po.id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $id]);
    $order = $statement->fetch();
    return $order ?: null;
}

function payment_find_by_public_key(string $publicKey): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $publicKey)) {
        return null;
    }

    $statement = db()->prepare('SELECT * FROM payment_orders WHERE public_key = :public_key LIMIT 1');
    $statement->execute(['public_key' => $publicKey]);
    $order = $statement->fetch();
    return $order ?: null;
}

function payment_find_by_token(string $token): ?array
{
    if ($token === '' || strlen($token) > 255 || !preg_match('/^[A-Za-z0-9_-]+$/', $token)) {
        return null;
    }

    $statement = db()->prepare('SELECT * FROM payment_orders WHERE token = :token LIMIT 1');
    $statement->execute(['token' => $token]);
    $order = $statement->fetch();
    return $order ?: null;
}

function payment_public_url(array $order): string
{
    return flow_config()['public_url'] . '/pagar/?orden=' . rawurlencode((string) $order['public_key']);
}

function payment_sync_order(array $order, string $source): array
{
    if (empty($order['token'])) {
        throw new RuntimeException('Esta orden no tiene un token de Flow para consultar.');
    }

    $response = flow_client()->getPaymentStatus((string) $order['token']);
    if (!hash_equals((string) $order['commerce_order'], (string) $response['commerceOrder'])) {
        throw new RuntimeException('La orden informada por Flow no coincide con el cobro local.');
    }
    if (strtoupper((string) $response['currency']) !== (string) $order['currency']) {
        throw new RuntimeException('La moneda informada por Flow no coincide con el cobro local.');
    }
    if ((int) round((float) $response['amount']) !== (int) $order['amount']) {
        throw new RuntimeException('El monto informado por Flow no coincide con el cobro local.');
    }

    $flowStatus = (int) $response['status'];
    $statusMap = [1 => 'pending', 2 => 'paid', 3 => 'rejected', 4 => 'cancelled'];
    if (!isset($statusMap[$flowStatus])) {
        throw new RuntimeException('Flow informo un estado de pago desconocido.');
    }

    $newStatus = $statusMap[$flowStatus];
    if ($order['status'] === 'paid') {
        $newStatus = 'paid';
        $flowStatus = 2;
    }

    $paymentData = is_array($response['paymentData'] ?? null) ? $response['paymentData'] : [];
    $paymentMethod = mb_substr(trim((string) ($paymentData['media'] ?? '')), 0, 80);
    $paidAt = null;
    if ($newStatus === 'paid') {
        $candidate = (string) ($paymentData['date'] ?? '');
        $paidAt = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $candidate) ? $candidate : date('Y-m-d H:i:s');
    }

    $safeResponse = payment_safe_flow_response($response);
    $update = db()->prepare(
        'UPDATE payment_orders
         SET flow_order = :flow_order, flow_status = :flow_status, status = :status,
             payment_method = :payment_method, paid_at = COALESCE(paid_at, :paid_at),
             flow_response_json = :flow_response_json, last_error = NULL, last_synced_at = NOW()
         WHERE id = :id'
    );
    $update->execute([
        'flow_order' => (int) $response['flowOrder'],
        'flow_status' => $flowStatus,
        'status' => $newStatus,
        'payment_method' => $paymentMethod !== '' ? $paymentMethod : null,
        'paid_at' => $paidAt,
        'flow_response_json' => json_encode($safeResponse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'id' => $order['id'],
    ]);

    if ($newStatus !== $order['status'] || $source === 'manual') {
        $meta = payment_status_meta($newStatus);
        payment_record_event(
            (int) $order['id'],
            'status_' . $source,
            $flowStatus,
            'Flow confirmo el estado: ' . $meta['label'] . '.',
            $safeResponse
        );
    }

    $fresh = payment_find_by_id((int) $order['id']);
    if ($fresh === null) {
        throw new RuntimeException('La orden ya no esta disponible.');
    }

    if ($fresh['status'] === 'paid' && empty($fresh['reference_processed_at'])) {
        require_once dirname(__DIR__) . '/Services/payment_hooks.php';
        db()->beginTransaction();
        try {
            $lock = db()->prepare('SELECT reference_processed_at FROM payment_orders WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $fresh['id']]);
            if (!$lock->fetchColumn()) {
                payment_apply_reference($fresh);
                $markProcessed = db()->prepare('UPDATE payment_orders SET reference_processed_at = NOW() WHERE id = :id');
                $markProcessed->execute(['id' => $fresh['id']]);
            }
            db()->commit();
            $fresh['reference_processed_at'] = date('Y-m-d H:i:s');
        } catch (Throwable $exception) {
            if (db()->inTransaction()) db()->rollBack();
            throw $exception;
        }
    }
    return $fresh;
}

function payment_record_event(int $orderId, string $eventType, ?int $flowStatus, string $message, ?array $payload): void
{
    $statement = db()->prepare(
        'INSERT INTO payment_events (payment_order_id, event_type, flow_status, message, payload_json)
         VALUES (:payment_order_id, :event_type, :flow_status, :message, :payload_json)'
    );
    $statement->execute([
        'payment_order_id' => $orderId,
        'event_type' => mb_substr($eventType, 0, 80),
        'flow_status' => $flowStatus,
        'message' => mb_substr($message, 0, 500),
        'payload_json' => $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
}

function payment_safe_flow_response(array $response): array
{
    $paymentData = is_array($response['paymentData'] ?? null) ? $response['paymentData'] : [];
    return [
        'flowOrder' => (int) ($response['flowOrder'] ?? 0),
        'commerceOrder' => mb_substr((string) ($response['commerceOrder'] ?? ''), 0, 64),
        'requestDate' => mb_substr((string) ($response['requestDate'] ?? ''), 0, 30),
        'status' => (int) ($response['status'] ?? 0),
        'subject' => mb_substr((string) ($response['subject'] ?? ''), 0, 255),
        'currency' => mb_substr((string) ($response['currency'] ?? ''), 0, 3),
        'amount' => (int) round((float) ($response['amount'] ?? 0)),
        'paymentData' => [
            'date' => mb_substr((string) ($paymentData['date'] ?? ''), 0, 30),
            'media' => mb_substr((string) ($paymentData['media'] ?? ''), 0, 80),
            'amount' => (int) round((float) ($paymentData['amount'] ?? 0)),
            'currency' => mb_substr((string) ($paymentData['currency'] ?? ''), 0, 3),
        ],
    ];
}

function payment_safe_error(string $message): string
{
    $message = trim(strip_tags($message));
    return mb_substr($message !== '' ? $message : 'No fue posible completar la operacion con Flow.', 0, 500);
}
