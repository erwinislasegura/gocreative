<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Services/hosting_helpers.php';

$currentAdmin = require_permission('hosting.view');
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$service = $id ? hosting_find_by_id((int) $id) : null;
if (!$service) {
    flash('warning', 'El servicio no existe.');
    redirect_admin('hosting/');
}

$noticesStatement = db()->prepare(
    'SELECT hn.*, po.commerce_order
     FROM hosting_notices hn
     LEFT JOIN payment_orders po ON po.id = hn.payment_order_id
     WHERE hn.hosting_service_id = :id
     ORDER BY hn.created_at DESC, hn.id DESC'
);
$noticesStatement->execute(['id' => $service['id']]);
$notices = $noticesStatement->fetchAll();

$dueMeta = hosting_due_meta($service);
$noticeLabels = [1 => 'Aviso 1', 2 => 'Aviso 2', 3 => 'Aviso de suspensión'];
$noticeStatusLabels = ['pending' => 'Preparando', 'sent' => 'Enviado', 'failed' => 'Fallido'];
$sentNotices = array_values(array_filter($notices, static fn (array $notice): bool => $notice['status'] === 'sent'));
$failedNotices = array_values(array_filter($notices, static fn (array $notice): bool => $notice['status'] === 'failed'));
$noticeProgress = min(3, max(0, (int) $service['last_notice_level']));
$nextLevel = $noticeProgress < 3 ? $noticeProgress + 1 : null;

$paymentStatus = (string) ($service['payment_status'] ?? '');
if ($paymentStatus === 'pending') {
    $paymentState = [
        'class' => 'pending',
        'label' => 'Pago pendiente',
        'title' => 'Checkout enviado, esperando confirmación',
        'copy' => 'Flow todavía no confirma el pago de esta renovación.',
    ];
} elseif ($paymentStatus === 'paid') {
    $paymentState = [
        'class' => 'paid',
        'label' => 'Pago confirmado',
        'title' => 'Renovación pagada correctamente',
        'copy' => 'Flow confirmó el cobro y el sistema procesará el nuevo vencimiento.',
    ];
} elseif (in_array($paymentStatus, ['rejected', 'cancelled', 'error'], true)) {
    $paymentState = [
        'class' => 'error',
        'label' => 'Pago no completado',
        'title' => 'La orden requiere atención',
        'copy' => 'El último checkout no terminó correctamente. Revisa el estado antes de continuar.',
    ];
} elseif (!empty($service['last_paid_at'])) {
    $paymentState = [
        'class' => 'paid',
        'label' => 'Último pago registrado',
        'title' => 'Existe una renovación confirmada',
        'copy' => 'Último pago: ' . format_admin_date($service['last_paid_at']) . '.',
    ];
} else {
    $paymentState = [
        'class' => 'idle',
        'label' => 'Sin orden activa',
        'title' => 'Aún no se ha generado el checkout',
        'copy' => 'El enlace de Flow se creará automáticamente al enviar el próximo aviso.',
    ];
}

$pageTitle = 'Detalle de hosting';
$activeMenu = 'hosting';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading page-heading--compact">
    <div>
        <span class="page-heading__eyebrow">Renovación de hosting</span>
        <h1><?= e($service['domain'] ?: $service['service_name']) ?></h1>
        <p><?= e($service['customer_name']) ?> · <?= e($service['plan_name']) ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-dark" href="<?= e(admin_url('hosting/')) ?>">Volver</a>
        <?php if (can('hosting.edit')): ?>
            <a class="btn btn-dark" href="<?= e(admin_url('hosting/editar.php?id=' . (int) $service['id'])) ?>">Editar servicio</a>
        <?php endif; ?>
        <?php if (can('hosting.delete')): ?>
            <form method="post" action="<?= e(admin_url('hosting/eliminar.php')) ?>" data-confirm="¿Eliminar definitivamente el hosting <?= e($service['domain'] ?: $service['service_name']) ?>? También se borrará su historial de avisos. Esta acción no se puede deshacer.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                <button class="btn btn-outline-danger" type="submit">Eliminar hosting</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<section class="hosting-overview hosting-overview--<?= e($dueMeta['class']) ?> admin-card mb-3">
    <div class="hosting-overview__amount">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="due-badge due-badge--<?= e($dueMeta['class']) ?>"><?= e($dueMeta['label']) ?></span>
            <span class="hosting-overview__cycle"><?= $service['billing_cycle'] === 'semiannual' ? 'Ciclo semestral' : 'Ciclo anual' ?></span>
        </div>
        <strong><?= e(payment_format_amount((int) $service['amount'], $service['currency'])) ?></strong>
        <small>Vencimiento <?= e(date('d-m-Y', strtotime($service['due_date']))) ?></small>
    </div>
    <div class="hosting-overview__notices">
        <div class="hosting-overview__notices-head">
            <span>Secuencia de cobranza</span>
            <b><?= $noticeProgress ?> de 3 enviados</b>
        </div>
        <ol class="hosting-notice-steps" aria-label="Progreso de avisos de cobro">
            <?php foreach ($noticeLabels as $level => $label): ?>
                <?php $stepClass = $noticeProgress >= $level ? 'is-done' : ($nextLevel === $level ? 'is-current' : ''); ?>
                <li class="<?= e($stepClass) ?>">
                    <span><?= str_pad((string) $level, 2, '0', STR_PAD_LEFT) ?></span>
                    <small><?= e($label) ?></small>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<section class="hosting-payment-state hosting-payment-state--<?= e($paymentState['class']) ?> mb-3" role="status">
    <span class="hosting-payment-state__icon" aria-hidden="true"></span>
    <div>
        <small><?= e($paymentState['label']) ?></small>
        <strong><?= e($paymentState['title']) ?></strong>
        <p><?= e($paymentState['copy']) ?></p>
    </div>
    <?php if ($paymentStatus === 'pending' && !empty($service['checkout_url'])): ?>
        <a class="btn btn-sm btn-outline-dark" href="<?= e($service['checkout_url']) ?>" target="_blank" rel="noopener">Abrir checkout ↗</a>
    <?php endif; ?>
</section>

<div class="row g-3 align-items-start hosting-detail-grid">
    <div class="col-xl-8">
        <section class="admin-card mb-3">
            <div class="admin-card__header">
                <h2>Datos del servicio</h2>
                <span class="role-badge"><?= e($service['status']) ?></span>
            </div>
            <div class="admin-card__body admin-card__body--flush">
                <dl class="payment-details payment-details--compact">
                    <div><dt>Cliente</dt><dd><?= e($service['customer_name']) ?></dd></div>
                    <div><dt>Correo</dt><dd><a href="mailto:<?= e($service['customer_email']) ?>"><?= e($service['customer_email']) ?></a></dd></div>
                    <div><dt>Dominio</dt><dd><?= e($service['domain'] ?: 'Sin dominio') ?></dd></div>
                    <div><dt>Plan</dt><dd><?= e($service['plan_name']) ?></dd></div>
                    <div><dt>Inicio</dt><dd><?= e(date('d-m-Y', strtotime($service['start_date']))) ?></dd></div>
                    <div><dt>Último pago</dt><dd><?= e(format_admin_date($service['last_paid_at'])) ?></dd></div>
                </dl>
                <?php if ($service['notes']): ?>
                    <div class="hosting-note"><strong>Nota interna</strong><p><?= nl2br(e($service['notes'])) ?></p></div>
                <?php endif; ?>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card__header">
                <div><h2>Historial de avisos</h2><small class="admin-card__subtitle">Solo los correos entregados avanzan el contador.</small></div>
                <span class="status-badge status-badge--active"><?= min(3, count($sentNotices)) ?> de 3</span>
            </div>
            <div class="admin-card__body payment-timeline">
                <?php if ($notices === []): ?>
                    <div class="hosting-empty-state"><span>0/3</span><p>Aún no se han enviado avisos de cobro.</p></div>
                <?php endif; ?>
                <?php foreach ($notices as $notice): ?>
                    <?php $noticeStatus = (string) $notice['status']; ?>
                    <article>
                        <span class="payment-timeline__dot payment-timeline__dot--<?= e($noticeStatus) ?>"></span>
                        <div>
                            <strong><?= e($noticeLabels[(int) $notice['notice_level']] ?? 'Aviso de cobro') ?></strong>
                            <small><?= e($noticeStatusLabels[$noticeStatus] ?? $noticeStatus) ?> · <?= e($notice['recipient']) ?><?= $notice['commerce_order'] ? ' · ' . e($notice['commerce_order']) : '' ?></small>
                            <?php if ($notice['error_message']): ?><p class="text-danger small mb-0 mt-1"><?= e($notice['error_message']) ?></p><?php endif; ?>
                        </div>
                        <time><?= e(format_admin_date($notice['sent_at'] ?: $notice['created_at'])) ?></time>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if ($failedNotices !== []): ?>
                <div class="hosting-history-note"><?= count($failedNotices) ?> intento<?= count($failedNotices) === 1 ? '' : 's' ?> fallido<?= count($failedNotices) === 1 ? '' : 's' ?> no se contabiliza<?= count($failedNotices) === 1 ? '' : 'n' ?> como aviso enviado.</div>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-xl-4">
        <div class="hosting-sidebar-stack">
            <section class="admin-card hosting-actions-card">
                <div class="admin-card__body">
                    <span class="page-heading__eyebrow">Cobranza por correo</span>
                    <?php if ($nextLevel !== null): ?>
                        <div class="hosting-action-count"><?= $noticeProgress ?><span>/3</span></div>
                        <h2>Enviar <?= e($noticeLabels[$nextLevel]) ?></h2>
                        <p>El correo incluye el resumen del servicio y un botón directo al checkout seguro de Flow.</p>
                        <?php if (can('hosting.send')): ?>
                            <form method="post" action="<?= e(admin_url('hosting/enviar-aviso.php')) ?>" data-confirm="¿Enviar <?= e($noticeLabels[$nextLevel]) ?> a <?= e($service['customer_email']) ?>?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                                <input type="hidden" name="level" value="<?= $nextLevel ?>">
                                <button class="btn <?= $nextLevel === 3 ? 'btn-danger' : 'btn-primary' ?> w-100" type="submit">Enviar <?= e($noticeLabels[$nextLevel]) ?> →</button>
                            </form>
                        <?php else: ?>
                            <div class="hosting-permission-note">Tu rol puede revisar el historial, pero no enviar avisos.</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="hosting-sequence-complete" aria-hidden="true">✓</div>
                        <h2>Secuencia completada</h2>
                        <p>Ya se enviaron los tres avisos permitidos. No se pueden generar más correos para este ciclo.</p>
                        <span class="hosting-limit-badge">3 de 3 avisos enviados</span>
                    <?php endif; ?>
                </div>
            </section>

            <?php if (can('hosting.edit')): ?>
                <section class="admin-card hosting-manual-payment">
                    <div class="admin-card__body">
                        <span class="page-heading__eyebrow">Pago externo</span>
                        <h2>Registrar renovación manual</h2>
                        <p>Para transferencias u otro medio. Actualiza el vencimiento y reinicia la secuencia de avisos.</p>
                        <form method="post" action="<?= e(admin_url('hosting/marcar-pagado.php')) ?>" data-confirm="¿Confirmas que esta renovación fue pagada por otro medio?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                            <button class="btn btn-outline-dark w-100" type="submit">Marcar como pagado</button>
                        </form>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
