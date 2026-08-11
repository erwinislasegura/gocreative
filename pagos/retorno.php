<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/config/database/connection.php';
require_once dirname(__DIR__) . '/app/Payments/payment_helpers.php';

header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; img-src 'self'; style-src 'self'; script-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
header('Cache-Control: no-store, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

$token = trim((string) ($_POST['token'] ?? ''));
$order = null;
$verificationError = false;

try {
    $order = payment_find_by_token($token);
    if ($order === null) {
        http_response_code(404);
    } else {
        try {
            $order = payment_sync_order($order, 'return');
        } catch (Throwable $exception) {
            error_log('No se pudo verificar el checkout Flow durante el retorno: ' . $exception->getMessage());
            $verificationError = true;
        }
    }
} catch (Throwable $exception) {
    error_log('Error en retorno del checkout Flow: ' . $exception->getMessage());
    $verificationError = true;
    http_response_code(503);
}

$status = (string) ($order['status'] ?? 'error');
$meta = payment_status_meta($status);
$isPaid = $status === 'paid';
$title = $isPaid ? 'Pago confirmado' : ($verificationError ? 'Estamos verificando tu pago' : $meta['label']);
?>
<!doctype html>
<html lang="es-CL">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#07111f">
    <title><?= e($title) ?> | Go Creative</title>
    <link rel="icon" href="<?= e(site_path('/assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(site_path('/assets/css/checkout-result.css?v=1.0.0')) ?>">
</head>
<body>
<main class="checkout-result">
    <section class="checkout-result__panel">
        <a class="checkout-result__brand" href="<?= e(site_path('/')) ?>" aria-label="Go Creative Chile">
            <img src="<?= e(site_path('/assets/img/logo-white.webp')) ?>" width="620" height="224" alt="Go Creative Chile">
        </a>
        <div class="checkout-result__content">
            <span class="checkout-result__eyebrow">Checkout seguro · Flow.cl</span>
            <span class="checkout-result__state checkout-result__state--<?= e($meta['class']) ?>"><?= e($meta['label']) ?></span>
            <h1><?= e($title) ?></h1>
            <?php if ($isPaid): ?>
                <p>Flow confirmó correctamente la renovación. El próximo vencimiento del hosting fue actualizado de forma automática.</p>
            <?php elseif ($verificationError): ?>
                <p>No pudimos consultar el resultado definitivo en este momento. No realices un segundo pago: nuestro equipo revisará la operación.</p>
            <?php else: ?>
                <p><?= e($meta['message']) ?></p>
            <?php endif; ?>

            <?php if ($order): ?>
                <dl>
                    <div><dt>Concepto</dt><dd><?= e((string) $order['subject']) ?></dd></div>
                    <div><dt>Monto</dt><dd><?= e(payment_format_amount((int) $order['amount'], (string) $order['currency'])) ?></dd></div>
                    <div><dt>Orden</dt><dd><?= e((string) $order['commerce_order']) ?></dd></div>
                </dl>
            <?php endif; ?>

            <div class="checkout-result__actions">
                <a class="checkout-result__primary" href="<?= e(site_path('/')) ?>">Volver a Go Creative →</a>
                <?php if (!$isPaid): ?><a href="<?= e(whatsapp_url('Hola Go Creative, necesito ayuda para verificar un pago de hosting.')) ?>">Solicitar ayuda</a><?php endif; ?>
            </div>
        </div>
    </section>
</main>
</body>
</html>
