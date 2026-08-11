<?php
declare(strict_types=1);

require_once __DIR__ . '/customer_helpers.php';
require_once dirname(__DIR__) . '/Payments/payment_helpers.php';
require_once dirname(__DIR__) . '/Mail/HtmlMailer.php';
require_once dirname(__DIR__) . '/Mail/email_templates.php';

use GoCreative\Mail\HtmlMailer;

function hosting_find_by_id(int $id): ?array
{
    $statement = db()->prepare(
        'SELECT hs.*, c.name AS customer_name, c.company AS customer_company, c.email AS customer_email,
                c.phone AS customer_phone, po.status AS payment_status, po.checkout_url, po.public_key AS payment_public_key,
                u.name AS created_by_name
         FROM hosting_services hs
         INNER JOIN customers c ON c.id = hs.customer_id
         LEFT JOIN payment_orders po ON po.id = hs.current_payment_order_id
         LEFT JOIN users u ON u.id = hs.created_by
         WHERE hs.id = :id LIMIT 1'
    );
    $statement->execute(['id' => $id]);
    $service = $statement->fetch();
    return $service ?: null;
}

function hosting_due_meta(array $service): array
{
    if ($service['status'] === 'cancelled') {
        return ['class' => 'cancelled', 'label' => 'Cancelado', 'days' => null];
    }
    if ($service['status'] === 'suspended') {
        return ['class' => 'overdue', 'label' => 'Suspendido', 'days' => null];
    }

    $today = new DateTimeImmutable('today');
    $due = new DateTimeImmutable((string) $service['due_date']);
    $days = (int) $today->diff($due)->format('%r%a');
    if ($days < 0) return ['class' => 'overdue', 'label' => 'Vencido hace ' . abs($days) . ' dias', 'days' => $days];
    if ($days === 0) return ['class' => 'warning', 'label' => 'Vence hoy', 'days' => 0];
    if ($days <= 30) return ['class' => 'warning', 'label' => 'Vence en ' . $days . ' dias', 'days' => $days];
    return ['class' => 'active', 'label' => 'Vigente', 'days' => $days];
}

function hosting_save(array $data, int $createdBy, ?int $id = null): int
{
    $customerId = customer_find_or_create($data['customer']);
    $params = [
        'customer_id' => $customerId,
        'service_name' => mb_substr(trim((string) $data['service_name']), 0, 150),
        'domain' => mb_substr(trim((string) $data['domain']), 0, 190) ?: null,
        'plan_name' => mb_substr(trim((string) $data['plan_name']), 0, 120),
        'billing_cycle' => $data['billing_cycle'],
        'start_date' => $data['start_date'],
        'due_date' => $data['due_date'],
        'amount' => (int) $data['amount'],
        'currency' => 'CLP',
        'status' => $data['status'],
        'notes' => mb_substr(trim((string) $data['notes']), 0, 1500) ?: null,
    ];

    if ($id === null) {
        $statement = db()->prepare(
            'INSERT INTO hosting_services
             (customer_id, created_by, service_name, domain, plan_name, billing_cycle, start_date, due_date, amount, currency, status, notes)
             VALUES
             (:customer_id, :created_by, :service_name, :domain, :plan_name, :billing_cycle, :start_date, :due_date, :amount, :currency, :status, :notes)'
        );
        $params['created_by'] = $createdBy;
        $statement->execute($params);
        return (int) db()->lastInsertId();
    }

    $params['id'] = $id;
    $statement = db()->prepare(
        'UPDATE hosting_services
         SET customer_id = :customer_id, service_name = :service_name, domain = :domain,
             plan_name = :plan_name, billing_cycle = :billing_cycle, start_date = :start_date,
             due_date = :due_date, amount = :amount, currency = :currency, status = :status, notes = :notes
         WHERE id = :id'
    );
    $statement->execute($params);
    return $id;
}

function hosting_prepare_payment(array $service, int $createdBy): array
{
    if (!empty($service['current_payment_order_id'])) {
        $existing = payment_find_by_id((int) $service['current_payment_order_id']);
        if ($existing && $existing['status'] === 'pending'
            && (int) $existing['amount'] === (int) $service['amount']
            && (empty($existing['expires_at']) || strtotime((string) $existing['expires_at']) > time())) {
            return $existing;
        }
    }

    $orderId = payment_create_order([
        'customer_name' => $service['customer_name'],
        'customer_email' => $service['customer_email'],
        'subject' => 'Renovacion ' . $service['plan_name'] . ($service['domain'] ? ' · ' . $service['domain'] : ''),
        'amount' => (int) $service['amount'],
        'reference_type' => 'hosting',
        'reference_id' => (int) $service['id'],
        'timeout' => 2592000,
    ], $createdBy);

    $update = db()->prepare('UPDATE hosting_services SET current_payment_order_id = :payment_order_id WHERE id = :id');
    $update->execute(['payment_order_id' => $orderId, 'id' => $service['id']]);
    $order = payment_find_by_id($orderId);
    if ($order === null) {
        throw new RuntimeException('No fue posible recuperar la orden de pago creada.');
    }
    return $order;
}

function hosting_send_notice(array $service, int $level, int $createdBy): int
{
    if (!in_array($level, [1, 2, 3], true)) {
        throw new RuntimeException('El nivel de aviso no es valido.');
    }
    $lastLevel = (int) $service['last_notice_level'];
    if ($level > $lastLevel + 1) {
        throw new RuntimeException('Debes enviar los avisos en orden: primero, segundo y ultimo.');
    }

    $order = hosting_prepare_payment($service, $createdBy);
    $service['payment_order_id'] = $order['id'];
    $paymentUrl = payment_public_url($order);
    $email = hosting_notice_email($service, $level, $paymentUrl);
    $mailer = new HtmlMailer(SITE_EMAIL, SITE_NAME);

    $notice = db()->prepare(
        'INSERT INTO hosting_notices
         (hosting_service_id, payment_order_id, notice_level, recipient, subject, status)
         VALUES (:hosting_service_id, :payment_order_id, :notice_level, :recipient, :subject, :status)'
    );
    $notice->execute([
        'hosting_service_id' => $service['id'], 'payment_order_id' => $order['id'], 'notice_level' => $level,
        'recipient' => $service['customer_email'], 'subject' => $email['subject'], 'status' => 'pending',
    ]);
    $noticeId = (int) db()->lastInsertId();

    try {
        $mailer->send($service['customer_email'], $email['subject'], $email['html']);
        $updateNotice = db()->prepare('UPDATE hosting_notices SET status = :status, sent_at = NOW() WHERE id = :id');
        $updateNotice->execute(['status' => 'sent', 'id' => $noticeId]);
        $updateService = db()->prepare('UPDATE hosting_services SET last_notice_level = GREATEST(last_notice_level, :level), last_notice_at = NOW() WHERE id = :id');
        $updateService->execute(['level' => $level, 'id' => $service['id']]);
    } catch (Throwable $exception) {
        $updateNotice = db()->prepare('UPDATE hosting_notices SET status = :status, error_message = :error_message WHERE id = :id');
        $updateNotice->execute(['status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 500), 'id' => $noticeId]);
        throw $exception;
    }

    return $noticeId;
}

function hosting_advance_due_date(int $serviceId): void
{
    $service = hosting_find_by_id($serviceId);
    if ($service === null) {
        throw new RuntimeException('El servicio de hosting ya no existe.');
    }
    $today = new DateTimeImmutable('today');
    $due = new DateTimeImmutable((string) $service['due_date']);
    $base = $due > $today ? $due : $today;
    $next = $service['billing_cycle'] === 'semiannual' ? $base->modify('+6 months') : $base->modify('+1 year');
    $update = db()->prepare(
        'UPDATE hosting_services
         SET due_date = :due_date, status = :status, last_notice_level = 0, last_notice_at = NULL,
             current_payment_order_id = NULL, last_paid_at = NOW()
         WHERE id = :id'
    );
    $update->execute(['due_date' => $next->format('Y-m-d'), 'status' => 'active', 'id' => $serviceId]);
}
