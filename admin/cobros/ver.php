<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Payments/payment_helpers.php';

$currentAdmin = require_permission('payments.view');
$orderId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$order = $orderId ? payment_find_by_id((int) $orderId) : null;
if ($order === null) {
    flash('warning', 'El cobro solicitado no existe.');
    redirect_admin('cobros/');
}

$eventsStatement = db()->prepare('SELECT * FROM payment_events WHERE payment_order_id = :payment_order_id ORDER BY created_at DESC, id DESC');
$eventsStatement->execute(['payment_order_id' => $order['id']]);
$events = $eventsStatement->fetchAll();
$meta = payment_status_meta($order['status']);
$publicUrl = payment_public_url($order);
$canPay = $order['status'] === 'pending' && !empty($order['checkout_url']);

$pageTitle = 'Detalle del cobro';
$activeMenu = 'payments';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading">
    <div><span class="page-heading__eyebrow">Cobros Flow</span><h1><?= e($order['commerce_order']) ?></h1><p><?= e($order['subject']) ?></p></div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-dark" href="<?= e(admin_url('cobros/')) ?>">Volver</a>
        <?php if (can('payments.sync') && !empty($order['token'])): ?>
            <form method="post" action="<?= e(admin_url('cobros/sincronizar.php')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><button class="btn btn-dark" type="submit">Actualizar desde Flow</button></form>
        <?php endif; ?>
    </div>
</div>

<section class="payment-hero admin-card payment-hero--<?= e($meta['class']) ?> mb-4">
    <div>
        <span class="payment-status payment-status--<?= e($meta['class']) ?>"><?= e($meta['label']) ?></span>
        <h2><?= e(payment_format_amount((int) $order['amount'], $order['currency'])) ?></h2>
        <p><?= e($meta['message']) ?></p>
    </div>
    <div class="payment-hero__meta">
        <span>Orden Flow<strong><?= $order['flow_order'] ? '#' . (int) $order['flow_order'] : 'Sin asignar' ?></strong></span>
        <span>Ultima consulta<strong><?= e(format_admin_date($order['last_synced_at'])) ?></strong></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-xl-7">
        <section class="admin-card mb-4">
            <div class="admin-card__header"><h2>Cliente y orden</h2><span class="order-code"><?= e($order['commerce_order']) ?></span></div>
            <div class="admin-card__body">
                <dl class="payment-details">
                    <div><dt>Cliente</dt><dd><?= e($order['customer_name']) ?></dd></div>
                    <div><dt>Correo</dt><dd><?= e($order['customer_email']) ?></dd></div>
                    <div><dt>Concepto</dt><dd><?= e($order['subject']) ?></dd></div>
                    <div><dt>Monto</dt><dd><?= e(payment_format_amount((int) $order['amount'], $order['currency'])) ?></dd></div>
                    <div><dt>Medio de pago</dt><dd><?= e($order['payment_method'] ?: 'Aun no informado') ?></dd></div>
                    <div><dt>Creada por</dt><dd><?= e($order['created_by_name'] ?: 'Sistema') ?></dd></div>
                    <div><dt>Fecha de creacion</dt><dd><?= e(format_admin_date($order['created_at'])) ?></dd></div>
                    <div><dt>Pago confirmado</dt><dd><?= e(format_admin_date($order['paid_at'])) ?></dd></div>
                </dl>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card__header"><h2>Historial</h2><span class="status-badge status-badge--active"><?= count($events) ?> eventos</span></div>
            <div class="admin-card__body payment-timeline">
                <?php foreach ($events as $event): ?>
                    <article><span class="payment-timeline__dot"></span><div><strong><?= e($event['message']) ?></strong><small><?= e($event['event_type']) ?></small></div><time><?= e(format_admin_date($event['created_at'])) ?></time></article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <div class="col-xl-5">
        <section class="admin-card payment-link-card mb-4">
            <div class="admin-card__body">
                <span class="page-heading__eyebrow">Enlace para el cliente</span>
                <h2 class="h5 fw-bold">Comparte la pagina de pago</h2>
                <p class="text-secondary">El cliente vera el monto y continuara al checkout seguro de Flow.</p>
                <label class="form-label" for="public_payment_url">URL PUBLICA</label>
                <div class="payment-copy"><input class="form-control" id="public_payment_url" value="<?= e($publicUrl) ?>" readonly><button class="btn btn-dark" type="button" data-copy-target="public_payment_url">Copiar</button></div>
                <div class="d-grid gap-2 mt-3">
                    <a class="btn btn-primary" href="<?= e($publicUrl) ?>" target="_blank" rel="noopener">Abrir pagina del cliente ↗</a>
                    <?php if ($canPay): ?><a class="btn btn-outline-dark" href="<?= e($order['checkout_url']) ?>" target="_blank" rel="noopener noreferrer">Abrir checkout Flow ↗</a><?php endif; ?>
                </div>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card__body">
                <span class="page-heading__eyebrow">Control de seguridad</span>
                <h2 class="h5 fw-bold">Verificacion servidor a servidor</h2>
                <p class="text-secondary mb-0">El estado mostrado se obtiene consultando la API firmada de Flow. Nunca se confia solamente en la redireccion del navegador.</p>
                <?php if ($order['last_error']): ?><div class="alert alert-danger mt-3 mb-0"><strong>Ultimo error:</strong><br><?= e($order['last_error']) ?></div><?php endif; ?>
            </div>
        </section>
    </div>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
