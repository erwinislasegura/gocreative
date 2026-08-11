<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/config/database/connection.php';
require_once dirname(__DIR__) . '/app/Payments/payment_helpers.php';

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

$token = trim((string) ($_POST['token'] ?? ''));
try {
    $order = payment_find_by_token($token);
    if ($order === null) {
        http_response_code(404);
        exit('Order Not Found');
    }

    payment_sync_order($order, 'confirmation');
    http_response_code(200);
    echo 'OK';
} catch (Throwable $exception) {
    error_log('Error en confirmacion Flow: ' . $exception->getMessage());
    http_response_code(503);
    echo 'Temporary Error';
}
