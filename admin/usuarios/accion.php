<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('usuarios/');
}
verify_csrf();

$currentAdmin = require_auth();
$userId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$action = (string) ($_POST['action'] ?? '');
if (!$userId || !in_array($action, ['activate', 'deactivate', 'delete'], true)) {
    flash('danger', 'Acción no válida.');
    redirect_admin('usuarios/');
}
if ((int) $userId === (int) $currentAdmin['id']) {
    flash('danger', 'No puedes desactivar o eliminar tu propia cuenta.');
    redirect_admin('usuarios/');
}

$statement = db()->prepare(
    'SELECT u.id, u.email, u.status, r.slug AS role_slug
     FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.id = :id LIMIT 1'
);
$statement->execute(['id' => $userId]);
$target = $statement->fetch();
if (!$target) {
    flash('danger', 'El usuario ya no existe.');
    redirect_admin('usuarios/');
}

if ($action === 'delete') {
    require_permission('users.delete');
} else {
    require_permission('users.edit');
}

if ($target['role_slug'] === 'superadministrador' && in_array($action, ['deactivate', 'delete'], true) && is_last_active_superadmin((int) $target['id'])) {
    flash('danger', 'Debe existir al menos un superadministrador activo.');
    redirect_admin('usuarios/');
}

if ($action === 'delete') {
    $delete = db()->prepare('DELETE FROM users WHERE id = :id');
    $delete->execute(['id' => $target['id']]);
    audit_log('deleted', 'user', (int) $target['id'], 'Usuario eliminado: ' . $target['email']);
    flash('success', 'Usuario eliminado correctamente.');
} else {
    $newStatus = $action === 'activate' ? 'active' : 'inactive';
    $update = db()->prepare('UPDATE users SET status = :status WHERE id = :id');
    $update->execute(['status' => $newStatus, 'id' => $target['id']]);
    audit_log('status_changed', 'user', (int) $target['id'], 'Estado de ' . $target['email'] . ' cambiado a ' . $newStatus);
    flash('success', $newStatus === 'active' ? 'Usuario activado.' : 'Usuario desactivado.');
}

redirect_admin('usuarios/');
