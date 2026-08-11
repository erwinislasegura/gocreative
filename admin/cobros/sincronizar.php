<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Payments/payment_helpers.php';

$currentAdmin = require_permission('payments.sync');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metodo no permitido.');
}

verify_csrf();
$orderId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$order = $orderId ? payment_find_by_id((int) $orderId) : null;
if ($order === null) {
    flash('warning', 'El cobro solicitado no existe.');
    redirect_admin('cobros/');
}

try {
    $fresh = payment_sync_order($order, 'manual');
    $meta = payment_status_meta($fresh['status']);
    audit_log('synced', 'payment_order', (int) $order['id'], 'Cobro actualizado desde Flow: ' . $meta['label']);
    flash('success', 'Estado actualizado desde Flow: ' . $meta['label'] . '.');
} catch (Throwable $exception) {
    error_log('No se pudo sincronizar el cobro Flow #' . (int) $order['id'] . ': ' . $exception->getMessage());
    flash('danger', 'No fue posible consultar Flow en este momento. Intentalo nuevamente.');
}

redirect_admin('cobros/ver.php?id=' . (int) $order['id']);
