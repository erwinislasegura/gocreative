<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect_admin('roles/');
verify_csrf();
require_permission('roles.delete');
$roleId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$roleId || ($_POST['action'] ?? '') !== 'delete') {
    flash('danger', 'Acción no válida.');
    redirect_admin('roles/');
}

$statement = db()->prepare(
    'SELECT r.id, r.name, r.is_system, COUNT(u.id) AS user_count
     FROM roles r LEFT JOIN users u ON u.role_id = r.id
     WHERE r.id = :id GROUP BY r.id LIMIT 1'
);
$statement->execute(['id' => $roleId]);
$role = $statement->fetch();
if (!$role) {
    flash('danger', 'El rol ya no existe.');
} elseif ((int) $role['is_system'] === 1) {
    flash('danger', 'Los roles protegidos no se pueden eliminar.');
} elseif ((int) $role['user_count'] > 0) {
    flash('danger', 'Reasigna los usuarios de este rol antes de eliminarlo.');
} else {
    $delete = db()->prepare('DELETE FROM roles WHERE id = :id');
    $delete->execute(['id' => $role['id']]);
    audit_log('deleted', 'role', (int) $role['id'], 'Rol eliminado: ' . $role['name']);
    flash('success', 'Rol eliminado correctamente.');
}

redirect_admin('roles/');
