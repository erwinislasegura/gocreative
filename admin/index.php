<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$currentAdmin = require_permission('dashboard.view');

$stats = [
    'active_users' => (int) db()->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
    'roles' => (int) db()->query('SELECT COUNT(*) FROM roles')->fetchColumn(),
    'logins_today' => (int) db()->query("SELECT COUNT(*) FROM login_attempts WHERE successful = 1 AND DATE(attempted_at) = CURDATE()")->fetchColumn(),
    'failed_logins' => (int) db()->query("SELECT COUNT(*) FROM login_attempts WHERE successful = 0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn(),
];

$recentUsers = db()->query(
    'SELECT u.id, u.name, u.email, u.status, u.last_login_at, r.name AS role_name
     FROM users u
     INNER JOIN roles r ON r.id = u.role_id
     ORDER BY u.created_at DESC
     LIMIT 5'
)->fetchAll();

$recentActivity = db()->query(
    'SELECT a.action, a.description, a.created_at, u.name AS user_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC
     LIMIT 7'
)->fetchAll();

$pageTitle = 'Resumen general';
$activeMenu = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading">
    <div>
        <span class="page-heading__eyebrow">Panel de control</span>
        <h1>Hola, <?= e(explode(' ', trim($currentAdmin['name']))[0]) ?>.</h1>
        <p>Una vista breve del equipo, los accesos y la actividad reciente.</p>
    </div>
    <?php if (can('users.create')): ?>
        <a class="btn btn-primary" href="<?= e(admin_url('usuarios/crear.php')) ?>">+ Crear usuario</a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <section class="admin-card stat-card stat-card--dark">
            <span class="stat-card__label">Usuarios activos</span>
            <strong class="stat-card__value"><?= $stats['active_users'] ?></strong>
            <span class="stat-card__note">Con acceso vigente</span>
        </section>
    </div>
    <div class="col-6 col-xl-3">
        <section class="admin-card stat-card">
            <span class="stat-card__label">Roles</span>
            <strong class="stat-card__value"><?= $stats['roles'] ?></strong>
            <span class="stat-card__note">Niveles configurados</span>
        </section>
    </div>
    <div class="col-6 col-xl-3">
        <section class="admin-card stat-card">
            <span class="stat-card__label">Ingresos hoy</span>
            <strong class="stat-card__value"><?= $stats['logins_today'] ?></strong>
            <span class="stat-card__note">Sesiones correctas</span>
        </section>
    </div>
    <div class="col-6 col-xl-3">
        <section class="admin-card stat-card">
            <span class="stat-card__label">Alertas 24 h</span>
            <strong class="stat-card__value"><?= $stats['failed_logins'] ?></strong>
            <span class="stat-card__note">Intentos rechazados</span>
        </section>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <section class="admin-card">
            <div class="admin-card__header">
                <h2>Usuarios recientes</h2>
                <?php if (can('users.view')): ?><a class="btn btn-sm btn-outline-dark" href="<?= e(admin_url('usuarios/')) ?>">Ver todos</a><?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Usuario</th><th>Rol</th><th>Estado</th><th>Último acceso</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td><div class="user-cell"><span class="user-cell__avatar"><?= e(initials($user['name'])) ?></span><span class="user-cell__copy"><strong><?= e($user['name']) ?></strong><span><?= e($user['email']) ?></span></span></div></td>
                            <td><span class="role-badge"><?= e($user['role_name']) ?></span></td>
                            <td><span class="status-badge status-badge--<?= e($user['status']) ?>"><?= $user['status'] === 'active' ? 'Activo' : 'Inactivo' ?></span></td>
                            <td class="text-secondary small"><?= e(format_admin_date($user['last_login_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="col-xl-5">
        <section class="admin-card">
            <div class="admin-card__header"><h2>Actividad reciente</h2><span class="status-badge status-badge--active">En línea</span></div>
            <div class="admin-card__body activity-list">
                <?php if ($recentActivity === []): ?>
                    <p class="text-secondary mb-0">Todavía no hay actividad registrada.</p>
                <?php endif; ?>
                <?php foreach ($recentActivity as $activity): ?>
                    <article class="activity-item">
                        <span class="activity-item__dot"></span>
                        <div><strong><?= e($activity['user_name'] ?? 'Sistema') ?></strong><p><?= e($activity['description']) ?></p></div>
                        <time><?= e(format_admin_date($activity['created_at'])) ?></time>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
