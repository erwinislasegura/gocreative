<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$currentAdmin = require_permission('users.view');
$query = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$roleId = filter_var($_GET['role'] ?? null, FILTER_VALIDATE_INT) ?: null;
$allowedStatuses = ['active', 'inactive'];

$where = [];
$params = [];
if ($query !== '') {
    $where[] = '(u.name LIKE :query_name OR u.email LIKE :query_email)';
    $params['query_name'] = '%' . $query . '%';
    $params['query_email'] = '%' . $query . '%';
}
if (in_array($status, $allowedStatuses, true)) {
    $where[] = 'u.status = :status';
    $params['status'] = $status;
}
if ($roleId !== null) {
    $where[] = 'u.role_id = :role_id';
    $params['role_id'] = $roleId;
}

$sql = 'SELECT u.id, u.name, u.email, u.status, u.last_login_at, u.created_at,
               r.name AS role_name, r.slug AS role_slug
        FROM users u
        INNER JOIN roles r ON r.id = u.role_id';
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY u.created_at DESC';

$statement = db()->prepare($sql);
$statement->execute($params);
$users = $statement->fetchAll();
$roles = db()->query('SELECT id, name FROM roles ORDER BY name')->fetchAll();

$pageTitle = 'Usuarios';
$activeMenu = 'users';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading">
    <div>
        <span class="page-heading__eyebrow">Equipo y accesos</span>
        <h1>Usuarios</h1>
        <p>Administra quién puede ingresar y qué responsabilidad tiene dentro del panel.</p>
    </div>
    <?php if (can('users.create')): ?><a class="btn btn-primary" href="<?= e(admin_url('usuarios/crear.php')) ?>">+ Nuevo usuario</a><?php endif; ?>
</div>

<section class="admin-card">
    <form class="filter-bar" method="get" action="<?= e(admin_url('usuarios/')) ?>">
        <input class="form-control" type="search" name="q" value="<?= e($query) ?>" placeholder="Buscar por nombre o correo" aria-label="Buscar usuarios">
        <select class="form-select" name="role" aria-label="Filtrar por rol">
            <option value="">Todos los roles</option>
            <?php foreach ($roles as $role): ?><option value="<?= (int) $role['id'] ?>"<?= $roleId === (int) $role['id'] ? ' selected' : '' ?>><?= e($role['name']) ?></option><?php endforeach; ?>
        </select>
        <select class="form-select" name="status" aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            <option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Activos</option>
            <option value="inactive"<?= $status === 'inactive' ? ' selected' : '' ?>>Inactivos</option>
        </select>
        <div class="d-flex gap-2"><button class="btn btn-dark" type="submit">Filtrar</button><a class="btn btn-outline-dark" href="<?= e(admin_url('usuarios/')) ?>">Limpiar</a></div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Usuario</th><th>Rol</th><th>Estado</th><th>Último acceso</th><th>Creado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
            <?php if ($users === []): ?>
                <tr><td colspan="6" class="py-5 text-center text-secondary">No encontramos usuarios con esos filtros.</td></tr>
            <?php endif; ?>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><div class="user-cell"><span class="user-cell__avatar"><?= e(initials($user['name'])) ?></span><span class="user-cell__copy"><strong><?= e($user['name']) ?></strong><span><?= e($user['email']) ?></span></span></div></td>
                    <td><span class="role-badge"><?= e($user['role_name']) ?></span></td>
                    <td><span class="status-badge status-badge--<?= e($user['status']) ?>"><?= $user['status'] === 'active' ? 'Activo' : 'Inactivo' ?></span></td>
                    <td class="text-secondary small"><?= e(format_admin_date($user['last_login_at'])) ?></td>
                    <td class="text-secondary small"><?= e(format_admin_date($user['created_at'])) ?></td>
                    <td>
                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                            <?php if (can('users.edit')): ?><a class="btn btn-sm btn-outline-dark" href="<?= e(admin_url('usuarios/editar.php?id=' . (int) $user['id'])) ?>">Editar</a><?php endif; ?>
                            <?php if (can('users.edit') && (int) $user['id'] !== (int) $currentAdmin['id']): ?>
                                <form method="post" action="<?= e(admin_url('usuarios/accion.php')) ?>" data-confirm="¿Confirmas el cambio de estado de este usuario?">
                                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $user['id'] ?>"><input type="hidden" name="action" value="<?= $user['status'] === 'active' ? 'deactivate' : 'activate' ?>">
                                    <button class="btn btn-sm btn-outline-dark" type="submit"><?= $user['status'] === 'active' ? 'Desactivar' : 'Activar' ?></button>
                                </form>
                            <?php endif; ?>
                            <?php if (can('users.delete') && (int) $user['id'] !== (int) $currentAdmin['id']): ?>
                                <form method="post" action="<?= e(admin_url('usuarios/accion.php')) ?>" data-confirm="Esta acción eliminará al usuario de forma permanente. ¿Continuar?">
                                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $user['id'] ?>"><input type="hidden" name="action" value="delete">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
