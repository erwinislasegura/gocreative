<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Services/hosting_helpers.php';

$currentAdmin = require_permission('hosting.view');
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$service = $id ? hosting_find_by_id((int) $id) : null;
if (!$service) { flash('warning', 'El servicio no existe.'); redirect_admin('hosting/'); }
$noticesStatement = db()->prepare('SELECT hn.*, po.commerce_order FROM hosting_notices hn LEFT JOIN payment_orders po ON po.id = hn.payment_order_id WHERE hn.hosting_service_id = :id ORDER BY hn.created_at DESC, hn.id DESC');
$noticesStatement->execute(['id' => $service['id']]);
$notices = $noticesStatement->fetchAll();
$dueMeta = hosting_due_meta($service);
$noticeLabels = [1 => 'Primer aviso', 2 => 'Segundo aviso', 3 => 'Último aviso'];
$nextLevel = min(3, (int) $service['last_notice_level'] + 1);
$pageTitle = 'Detalle de hosting'; $activeMenu = 'hosting';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading">
    <div><span class="page-heading__eyebrow">Renovación de hosting</span><h1><?= e($service['domain'] ?: $service['service_name']) ?></h1><p><?= e($service['customer_name']) ?> · <?= e($service['plan_name']) ?></p></div>
    <div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-dark" href="<?= e(admin_url('hosting/')) ?>">Volver</a><?php if (can('hosting.edit')): ?><a class="btn btn-dark" href="<?= e(admin_url('hosting/editar.php?id=' . (int) $service['id'])) ?>">Editar servicio</a><?php endif; ?></div>
</div>

<section class="hosting-hero hosting-hero--<?= e($dueMeta['class']) ?> admin-card mb-4">
    <div><span class="due-badge due-badge--<?= e($dueMeta['class']) ?>"><?= e($dueMeta['label']) ?></span><h2><?= e(payment_format_amount((int) $service['amount'], $service['currency'])) ?></h2><p>Renovación <?= $service['billing_cycle'] === 'semiannual' ? 'semestral' : 'anual' ?> · vence el <?= e(date('d-m-Y', strtotime($service['due_date']))) ?></p></div>
    <div class="hosting-hero__steps"><span class="<?= (int) $service['last_notice_level'] >= 1 ? 'is-done' : '' ?>">01<small>Primer aviso</small></span><span class="<?= (int) $service['last_notice_level'] >= 2 ? 'is-done' : '' ?>">02<small>Segundo aviso</small></span><span class="<?= (int) $service['last_notice_level'] >= 3 ? 'is-done' : '' ?>">03<small>Último aviso</small></span></div>
</section>

<div class="row g-4">
    <div class="col-xl-7">
        <section class="admin-card mb-4"><div class="admin-card__header"><h2>Datos del servicio</h2><span class="role-badge"><?= e($service['status']) ?></span></div><div class="admin-card__body"><dl class="payment-details"><div><dt>Cliente</dt><dd><?= e($service['customer_name']) ?></dd></div><div><dt>Correo</dt><dd><?= e($service['customer_email']) ?></dd></div><div><dt>Dominio</dt><dd><?= e($service['domain'] ?: 'Sin dominio') ?></dd></div><div><dt>Plan</dt><dd><?= e($service['plan_name']) ?></dd></div><div><dt>Inicio</dt><dd><?= e(date('d-m-Y', strtotime($service['start_date']))) ?></dd></div><div><dt>Último pago</dt><dd><?= e(format_admin_date($service['last_paid_at'])) ?></dd></div></dl><?php if ($service['notes']): ?><div class="hosting-note"><strong>Nota interna</strong><p><?= nl2br(e($service['notes'])) ?></p></div><?php endif; ?></div></section>
        <section class="admin-card"><div class="admin-card__header"><h2>Historial de avisos</h2><span class="status-badge status-badge--active"><?= count($notices) ?> envíos</span></div><div class="admin-card__body payment-timeline">
            <?php if ($notices === []): ?><p class="text-secondary mb-0">Aún no se han enviado avisos.</p><?php endif; ?>
            <?php foreach ($notices as $notice): ?><article><span class="payment-timeline__dot"></span><div><strong><?= e($noticeLabels[(int) $notice['notice_level']] ?? 'Aviso') ?> · <?= e($notice['recipient']) ?></strong><small><?= e($notice['status']) ?><?= $notice['commerce_order'] ? ' · ' . e($notice['commerce_order']) : '' ?></small><?php if ($notice['error_message']): ?><p class="text-danger small mb-0 mt-1"><?= e($notice['error_message']) ?></p><?php endif; ?></div><time><?= e(format_admin_date($notice['sent_at'] ?: $notice['created_at'])) ?></time></article><?php endforeach; ?>
        </div></section>
    </div>
    <div class="col-xl-5">
        <section class="admin-card hosting-actions-card mb-4"><div class="admin-card__body"><span class="page-heading__eyebrow">Cobranza por correo</span><h2 class="h5 fw-bold">Enviar <?= e(mb_strtolower($noticeLabels[$nextLevel])) ?></h2><p class="text-secondary">Generaremos o reutilizaremos una orden Flow y el correo incluirá un botón directo al checkout seguro.</p>
            <?php if (can('hosting.send')): ?><form method="post" action="<?= e(admin_url('hosting/enviar-aviso.php')) ?>" data-confirm="¿Enviar <?= e(mb_strtolower($noticeLabels[$nextLevel])) ?> a <?= e($service['customer_email']) ?>?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><input type="hidden" name="level" value="<?= $nextLevel ?>"><button class="btn <?= $nextLevel === 3 ? 'btn-danger' : 'btn-primary' ?> w-100" type="submit">Enviar <?= e(mb_strtolower($noticeLabels[$nextLevel])) ?> →</button></form><?php endif; ?>
            <?php if ((int) $service['last_notice_level'] > 0 && can('hosting.send')): ?><details class="hosting-resend"><summary>Reenviar aviso anterior</summary><div class="d-grid gap-2 mt-2"><?php for ($level = 1; $level <= (int) $service['last_notice_level']; $level++): ?><form method="post" action="<?= e(admin_url('hosting/enviar-aviso.php')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><input type="hidden" name="level" value="<?= $level ?>"><button class="btn btn-sm btn-outline-dark w-100" type="submit"><?= e($noticeLabels[$level]) ?></button></form><?php endfor; ?></div></details><?php endif; ?>
        </div></section>
        <?php if (can('hosting.edit')): ?><section class="admin-card"><div class="admin-card__body"><span class="page-heading__eyebrow">Pago externo</span><h2 class="h5 fw-bold">Registrar renovación manual</h2><p class="text-secondary">Úsalo si el cliente pagó por transferencia u otro medio. Avanzará seis o doce meses según el ciclo.</p><form method="post" action="<?= e(admin_url('hosting/marcar-pagado.php')) ?>" data-confirm="¿Confirmas que esta renovación fue pagada por otro medio?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><button class="btn btn-outline-dark w-100" type="submit">Marcar como pagado</button></form></div></section><?php endif; ?>
    </div>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
