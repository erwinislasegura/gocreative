<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Services/hosting_helpers.php';

$currentAdmin = require_permission('hosting.view');
$query = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$allowedStatuses = ['active', 'suspended', 'cancelled'];
$where = [];
$params = [];
if ($query !== '') {
    $like = '%' . $query . '%';
    $where[] = '(c.name LIKE :q_name OR c.email LIKE :q_email OR hs.domain LIKE :q_domain OR hs.plan_name LIKE :q_plan)';
    $params = ['q_name' => $like, 'q_email' => $like, 'q_domain' => $like, 'q_plan' => $like];
}
if (in_array($status, $allowedStatuses, true)) {
    $where[] = 'hs.status = :status';
    $params['status'] = $status;
}
$sql = 'SELECT hs.*, c.name AS customer_name, c.email AS customer_email
        FROM hosting_services hs INNER JOIN customers c ON c.id = hs.customer_id';
if ($where !== []) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY (hs.status = \'active\') DESC, hs.due_date ASC, hs.created_at DESC';
$statement = db()->prepare($sql);
$statement->execute($params);
$services = $statement->fetchAll();

$stats = db()->query(
    "SELECT COUNT(*) AS total,
            SUM(status = 'active' AND due_date < CURDATE()) AS overdue,
            SUM(status = 'active' AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS upcoming,
            COALESCE(SUM(CASE WHEN status = 'active' THEN amount ELSE 0 END), 0) AS portfolio
     FROM hosting_services"
)->fetch();

$pageTitle = 'Servicios de hosting';
$activeMenu = 'hosting';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading">
    <div><span class="page-heading__eyebrow">Renovaciones y cobros</span><h1>Hosting</h1><p>Controla ciclos, vencimientos, avisos y pagos de alojamiento desde un registro único.</p></div>
    <?php if (can('hosting.create')): ?><a class="btn btn-primary" href="<?= e(admin_url('hosting/crear.php')) ?>">+ Registrar hosting</a><?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><section class="admin-card stat-card stat-card--dark"><span class="stat-card__label">Servicios</span><strong class="stat-card__value"><?= (int) $stats['total'] ?></strong><span class="stat-card__note">Registrados</span></section></div>
    <div class="col-6 col-xl-3"><section class="admin-card stat-card stat-card--danger"><span class="stat-card__label">Vencidos</span><strong class="stat-card__value"><?= (int) $stats['overdue'] ?></strong><span class="stat-card__note">Requieren gestión</span></section></div>
    <div class="col-6 col-xl-3"><section class="admin-card stat-card"><span class="stat-card__label">Próximos 30 días</span><strong class="stat-card__value"><?= (int) $stats['upcoming'] ?></strong><span class="stat-card__note">Preparar aviso</span></section></div>
    <div class="col-6 col-xl-3"><section class="admin-card stat-card"><span class="stat-card__label">Cartera activa</span><strong class="stat-card__value stat-card__value--money">$<?= number_format((int) $stats['portfolio'], 0, ',', '.') ?></strong><span class="stat-card__note">Valor por ciclo</span></section></div>
</div>

<section class="admin-card">
    <form class="filter-bar filter-bar--hosting" method="get" action="<?= e(admin_url('hosting/')) ?>">
        <input class="form-control" type="search" name="q" value="<?= e($query) ?>" placeholder="Cliente, correo, dominio o plan" aria-label="Buscar hosting">
        <select class="form-select" name="status"><option value="">Todos los estados</option><option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Activos</option><option value="suspended"<?= $status === 'suspended' ? ' selected' : '' ?>>Suspendidos</option><option value="cancelled"<?= $status === 'cancelled' ? ' selected' : '' ?>>Cancelados</option></select>
        <div class="d-flex gap-2"><button class="btn btn-dark" type="submit">Filtrar</button><a class="btn btn-outline-dark" href="<?= e(admin_url('hosting/')) ?>">Limpiar</a></div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle hosting-table"><thead><tr><th>Servicio</th><th>Cliente</th><th>Ciclo</th><th>Valor</th><th>Vencimiento</th><th>Avisos</th><th class="text-end">Acción</th></tr></thead><tbody>
        <?php if ($services === []): ?><tr><td colspan="7" class="py-5 text-center text-secondary">No hay servicios de hosting registrados.</td></tr><?php endif; ?>
        <?php foreach ($services as $service): $dueMeta = hosting_due_meta($service); ?>
            <tr class="<?= $dueMeta['class'] === 'overdue' ? 'hosting-row--overdue' : '' ?>">
                <td><strong><?= e($service['domain'] ?: $service['service_name']) ?></strong><small class="d-block text-secondary"><?= e($service['plan_name']) ?></small></td>
                <td><div class="user-cell"><span class="user-cell__avatar"><?= e(initials($service['customer_name'])) ?></span><span class="user-cell__copy"><strong><?= e($service['customer_name']) ?></strong><span><?= e($service['customer_email']) ?></span></span></div></td>
                <td><span class="role-badge"><?= $service['billing_cycle'] === 'semiannual' ? 'Semestral' : 'Anual' ?></span></td>
                <td><strong><?= e(payment_format_amount((int) $service['amount'], $service['currency'])) ?></strong></td>
                <td><span class="due-badge due-badge--<?= e($dueMeta['class']) ?>"><?= e($dueMeta['label']) ?></span><small class="d-block mt-1 text-secondary"><?= e(date('d-m-Y', strtotime($service['due_date']))) ?></small></td>
                <td><span class="notice-level"><?= (int) $service['last_notice_level'] ?> / 3</span></td>
                <td class="text-end">
                    <div class="hosting-row-actions">
                        <a class="btn btn-sm btn-outline-dark" href="<?= e(admin_url('hosting/ver.php?id=' . (int) $service['id'])) ?>">Gestionar</a>
                        <?php if (can('hosting.delete')): ?>
                            <form method="post" action="<?= e(admin_url('hosting/eliminar.php')) ?>" data-confirm="¿Eliminar definitivamente el hosting <?= e($service['domain'] ?: $service['service_name']) ?>? También se borrará su historial de avisos. Esta acción no se puede deshacer.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
