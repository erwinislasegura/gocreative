<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/config/database/connection.php';
require_once dirname(__DIR__) . '/app/Payments/payment_helpers.php';

header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
header('Cache-Control: no-store, max-age=0');

$publicKey = strtolower(trim((string) ($_GET['orden'] ?? '')));
$order = null;
$serviceError = false;
try {
    $order = payment_find_by_public_key($publicKey);
} catch (Throwable $exception) {
    error_log('No se pudo abrir la pagina publica de pago: ' . $exception->getMessage());
    $serviceError = true;
}

if ($order === null && !$serviceError) {
    http_response_code(404);
}
if ($serviceError) {
    http_response_code(503);
}

$meta = $order ? payment_status_meta($order['status']) : null;
$canPay = $order
    && $order['status'] === 'pending'
    && !empty($order['checkout_url'])
    && (empty($order['expires_at']) || strtotime((string) $order['expires_at']) > time());
$firstName = $order ? (preg_split('/\s+/', trim((string) $order['customer_name']))[0] ?? 'Cliente') : '';
?>
<!doctype html>
<html lang="es-CL">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#07111f">
    <title><?= $order ? 'Pago ' . e($order['commerce_order']) : 'Orden no disponible' ?> | Go Creative</title>
    <link rel="icon" href="<?= e(site_path('/assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="icon" href="<?= e(site_path('/assets/img/favicon-32x32.png')) ?>" type="image/png" sizes="32x32">
    <link rel="stylesheet" href="<?= e(site_path('/assets/css/payment.css?v=1.0.0')) ?>">
</head>
<body class="payment-page">
<div class="payment-shell">
    <header class="payment-header">
        <a href="<?= e(site_path('/')) ?>" aria-label="Go Creative Chile">
            <img src="<?= e(site_path('/assets/img/logo-white.webp')) ?>" width="620" height="224" alt="Go Creative Chile">
        </a>
        <span>Pago protegido · Flow.cl</span>
    </header>

    <main class="payment-main">
        <?php if ($order): ?>
            <section class="payment-copy-panel">
                <span class="payment-eyebrow">Orden <?= e($order['commerce_order']) ?></span>
                <?php if ($order['status'] === 'paid'): ?>
                    <h1>Pago confirmado.<br><em>Gracias, <?= e($firstName) ?>.</em></h1>
                <?php elseif ($order['status'] === 'rejected'): ?>
                    <h1>El pago no se completo.<br><em>Podemos ayudarte.</em></h1>
                <?php elseif ($order['status'] === 'cancelled'): ?>
                    <h1>Esta orden fue anulada.<br><em>Solicita un nuevo enlace.</em></h1>
                <?php else: ?>
                    <h1>Tu proyecto avanza<br><em>con un pago seguro.</em></h1>
                <?php endif; ?>
                <p><?= e($meta['message']) ?></p>

                <div class="payment-trust">
                    <span><i>01</i> Checkout seguro en Flow</span>
                    <span><i>02</i> Confirmacion automatica</span>
                    <span><i>03</i> Sin datos de tarjeta en Go Creative</span>
                </div>
            </section>

            <section class="payment-order-card payment-order-card--<?= e($meta['class']) ?>">
                <div class="payment-order-card__top">
                    <span class="payment-state payment-state--<?= e($meta['class']) ?>"><?= e($meta['label']) ?></span>
                    <small>GO CREATIVE · COBRO DIGITAL</small>
                </div>
                <div class="payment-order-card__amount"><span>Total a pagar</span><strong><?= e(payment_format_amount((int) $order['amount'], $order['currency'])) ?></strong></div>
                <dl>
                    <div><dt>Cliente</dt><dd><?= e($order['customer_name']) ?></dd></div>
                    <div><dt>Concepto</dt><dd><?= e($order['subject']) ?></dd></div>
                    <div><dt>Orden</dt><dd><?= e($order['commerce_order']) ?></dd></div>
                    <?php if ($order['paid_at']): ?><div><dt>Confirmado</dt><dd><?= e(date('d-m-Y H:i', strtotime($order['paid_at']))) ?></dd></div><?php endif; ?>
                </dl>

                <?php if ($canPay): ?>
                    <a class="payment-button" href="<?= e($order['checkout_url']) ?>" rel="nofollow noopener noreferrer">Continuar al pago <span>↗</span></a>
                    <p class="payment-fineprint">Seras redirigido al sitio seguro de Flow para elegir el medio de pago.</p>
                <?php elseif ($order['status'] === 'paid'): ?>
                    <div class="payment-result payment-result--success"><strong>Transaccion verificada</strong><span>No necesitas realizar otra accion.</span></div>
                <?php elseif ($order['status'] === 'pending'): ?>
                    <div class="payment-result"><strong>Enlace vencido</strong><span>Solicita una nueva orden a Go Creative.</span></div>
                <?php else: ?>
                    <a class="payment-button payment-button--dark" href="<?= e(whatsapp_url('Hola Go Creative, necesito ayuda con la orden ' . $order['commerce_order'] . '.')) ?>" rel="noopener">Solicitar ayuda <span>↗</span></a>
                <?php endif; ?>

                <a class="payment-refresh" href="<?= e(site_path('/pagar/?orden=' . rawurlencode($order['public_key']))) ?>">Actualizar estado</a>
            </section>
        <?php else: ?>
            <section class="payment-not-found">
                <span class="payment-eyebrow"><?= $serviceError ? 'Servicio temporalmente no disponible' : 'Orden no encontrada' ?></span>
                <h1><?= $serviceError ? 'No pudimos abrir el cobro.' : 'Este enlace no es valido.' ?></h1>
                <p><?= $serviceError ? 'Intentalo nuevamente en unos minutos o comunicate con nuestro equipo.' : 'Revisa el enlace recibido o solicita una nueva orden de pago.' ?></p>
                <a class="payment-button payment-button--compact" href="<?= e(whatsapp_url('Hola Go Creative, necesito ayuda con un enlace de pago.')) ?>">Hablar con Go Creative ↗</a>
            </section>
        <?php endif; ?>
    </main>

    <footer class="payment-footer"><span>© <?= date('Y') ?> Go Creative Chile</span><span>El procesamiento del pago se realiza en Flow.cl</span></footer>
</div>
</body>
</html>
