<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/config/database/connection.php';
require_once dirname(__DIR__) . '/app/Payments/payment_helpers.php';

header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metodo no permitido.');
}

$token = trim((string) ($_POST['token'] ?? ''));
try {
    $order = payment_find_by_token($token);
    if ($order === null) {
        http_response_code(404);
        exit('Orden no encontrada.');
    }

    try {
        $order = payment_sync_order($order, 'return');
    } catch (Throwable $exception) {
        error_log('No se pudo consultar Flow durante el retorno: ' . $exception->getMessage());
        // Keep the last verified state and still return the customer to a useful page.
    }

    header('Location: ' . site_path('/pagar/?orden=' . rawurlencode((string) $order['public_key']) . '&retorno=1'), true, 303);
    exit;
} catch (Throwable $exception) {
    error_log('Error en retorno Flow: ' . $exception->getMessage());
    http_response_code(503);
    exit('No fue posible verificar el pago. Intentalo nuevamente.');
}
