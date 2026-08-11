<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Payments/payment_helpers.php';

$currentAdmin = require_permission('payments.view');
$query = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$allowedStatuses = ['created', 'pending', 'paid', 'rejected', 'cancelled', 'error'];

$where = [];
$params = [];
if ($query !== '') {
    $where[] = '(po.commerce_order LIKE :query_order OR po.customer_name LIKE :query_name OR po.customer_email LIKE :query_email OR po.subject LIKE :query_subject)';
    $like = '%' . $query . '%';
    $params = ['query_order' => $like, 'query_name' => $like, 'query_email' => $like, 'query_subject' => $like];
}
if (in_array($status, $allowedStatuses, true)) {
    $where[] = 'po.status = :status';
    $params['status'] = $status;
}

$sql = 'SELECT po.*, u.name AS created_by_name
        FROM payment_orders po
        LEFT JOIN users u ON u.id = po.created_by';
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY po.created_at DESC LIMIT 250';
$statement = db()->prepare($sql);
$statement->execute($params);
$orders = $statement->fetchAll();

$stats = db()->query(
    "SELECT COUNT(*) AS total,
            SUM(status = 'pending') AS pending,
            SUM(status = 'paid') AS paid,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) AS paid_amount
     FROM payment_orders"
)->fetch();

$flowReady = flow_is_configured();
$flowEnvironment = 'sin configurar';
try {
    $flowEnvironment = flow_config()['environment'] === 'production' ? 'produccion' : 'sandbox';
} catch (Throwable $exception) {
    // The setup notice below is enough; credentials and parser details stay private.
}

$pageTitle = 'Cobros Flow';
$activeMenu = 'payments';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading">
    <div>
        <span class="page-heading__eyebrow">Pagos y seguimiento</span>
        <h1>Cobros Flow</h1>
        <p>Crea enlaces de pago, consulta su avance y confirma cada transaccion desde un solo lugar.</p>
    </div>
    <?php if (can('payments.create')): ?>
        <a class="btn btn-primary<?= $flowReady ? '' : ' disabled' ?>" href="<?= e(admin_url('cobros/crear.php')) ?>"<?= $flowReady ? '' : ' aria-disabled="true"' ?>>+ Nuevo cobro</a>
    <?php endif; ?>
</div>

<?php if (!$flowReady): ?>
    <div class="alert alert-warning d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2" role="alert">
        <span><strong>Flow aun no esta activo.</strong> Copia <code>config/flow/flow.example.php</code> como <code>flow.local.php</code>, agrega tus claves y verifica que cURL este habilitado.</span>
        <span class="status-badge status-badge--inactive">Configuracion pendiente</span>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><section class="admin-card stat-card stat-card--dark"><span class="stat-card__label">Ordenes</span><strong class="stat-card__value"><?= (int) ($stats['total'] ?? 0) ?></strong><span class="stat-card__note">Registro historico</span></section></div>
    <div class="col-6 col-xl-3"><section class="admin-card stat-card"><span class="stat-card__label">Pendientes</span><strong class="stat-card__value"><?= (int) ($stats['pending'] ?? 0) ?></strong><span class="stat-card__note">Esperando confirmacion</span></section></div>
    <div class="col-6 col-xl-3"><section class="admin-card stat-card"><span class="stat-card__label">Pagadas</span><strong class="stat-card__value"><?= (int) ($stats['paid'] ?? 0) ?></strong><span class="stat-card__note">Confirmadas por Flow</span></section></div>
    <div class="col-6 col-xl-3"><section class="admin-card stat-card"><span class="stat-card__label">Total confirmado</span><strong class="stat-card__value stat-card__value--money">$<?= number_format((int) ($stats['paid_amount'] ?? 0), 0, ',', '.') ?></strong><span class="stat-card__note">Pesos chilenos</span></section></div>
</div>

<section class="admin-card">
    <form class="filter-bar filter-bar--payments" method="get" action="<?= e(admin_url('cobros/')) ?>">
        <input class="form-control" type="search" name="q" value="<?= e($query) ?>" placeholder="Cliente, correo, concepto u orden" aria-label="Buscar cobros">
        <select class="form-select" name="status" aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            <?php foreach ($allowedStatuses as $option): $meta = payment_status_meta($option); ?>
                <option value="<?= e($option) ?>"<?= $status === $option ? ' selected' : '' ?>><?= e($meta['label']) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="d-flex gap-2"><button class="btn btn-dark" type="submit">Filtrar</button><a class="btn btn-outline-dark" href="<?= e(admin_url('cobros/')) ?>">Limpiar</a></div>
        <span class="flow-environment">Flow · <?= e($flowEnvironment) ?></span>
    </form>
    <div class="table-responsive">
        <table class="table align-middle payment-table">
            <thead><tr><th>Orden</th><th>Cliente</th><th>Concepto</th><th>Monto</th><th>Estado</th><th>Creada</th><th class="text-end">Accion</th></tr></thead>
            <tbody>
            <?php if ($orders === []): ?><tr><td colspan="7" class="py-5 text-center text-secondary">No hay cobros para mostrar.</td></tr><?php endif; ?>
            <?php foreach ($orders as $order): $meta = payment_status_meta($order['status']); ?>
                <tr>
                    <td><strong class="order-code"><?= e($order['commerce_order']) ?></strong><?php if ($order['flow_order']): ?><small class="d-block text-secondary">Flow #<?= (int) $order['flow_order'] ?></small><?php endif; ?></td>
                    <td><div class="user-cell"><span class="user-cell__avatar"><?= e(initials($order['customer_name'])) ?></span><span class="user-cell__copy"><strong><?= e($order['customer_name']) ?></strong><span><?= e($order['customer_email']) ?></span></span></div></td>
                    <td><span class="payment-subject"><?= e($order['subject']) ?></span></td>
                    <td><strong><?= e(payment_format_amount((int) $order['amount'], $order['currency'])) ?></strong></td>
                    <td><span class="payment-status payment-status--<?= e($meta['class']) ?>"><?= e($meta['label']) ?></span></td>
                    <td class="text-secondary small"><?= e(format_admin_date($order['created_at'])) ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="<?= e(admin_url('cobros/ver.php?id=' . (int) $order['id'])) ?>">Ver detalle</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
