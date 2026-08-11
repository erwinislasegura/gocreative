<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_permission('roles.edit');
$roleId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$roleId) {
    flash('danger', 'Rol no válido.');
    redirect_admin('roles/');
}
$statement = db()->prepare('SELECT id, name, slug, description, is_system FROM roles WHERE id = :id LIMIT 1');
$statement->execute(['id' => $roleId]);
$role = $statement->fetch();
if (!$role) {
    flash('danger', 'El rol solicitado no existe.');
    redirect_admin('roles/');
}

$permissions = db()->query('SELECT id, name, slug, description, group_name FROM permissions ORDER BY group_name, id')->fetchAll();
$permissionGroups = [];
foreach ($permissions as $permission) $permissionGroups[$permission['group_name']][] = $permission;
$assigned = db()->prepare('SELECT permission_id FROM role_permissions WHERE role_id = :role_id');
$assigned->execute(['role_id' => $roleId]);
$selectedPermissions = array_map('intval', array_column($assigned->fetchAll(), 'permission_id'));
$form = $role;
$errors = [];
$isSystem = (int) $role['is_system'] === 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form['name'] = $isSystem ? $role['name'] : trim((string) ($_POST['name'] ?? ''));
    $form['description'] = trim((string) ($_POST['description'] ?? ''));
    if (!$isSystem) $selectedPermissions = array_values(array_unique(array_map('intval', (array) ($_POST['permissions'] ?? []))));
    if (mb_strlen($form['name']) < 3 || mb_strlen($form['name']) > 80) $errors[] = 'El nombre debe tener entre 3 y 80 caracteres.';
    if (mb_strlen($form['description']) > 500) $errors[] = 'La descripción no puede superar 500 caracteres.';
    if (!$isSystem && $selectedPermissions === []) $errors[] = 'Selecciona al menos un permiso.';
    $validPermissionIds = array_map('intval', array_column($permissions, 'id'));
    if (array_diff($selectedPermissions, $validPermissionIds) !== []) $errors[] = 'La selección contiene un permiso no válido.';
    $check = db()->prepare('SELECT COUNT(*) FROM roles WHERE name = :name AND id <> :id');
    $check->execute(['name' => $form['name'], 'id' => $role['id']]);
    if ((int) $check->fetchColumn() > 0) $errors[] = 'Ya existe otro rol con ese nombre.';

    if ($errors === []) {
        db()->beginTransaction();
        try {
            $update = db()->prepare('UPDATE roles SET name = :name, description = :description WHERE id = :id');
            $update->execute(['name' => $form['name'], 'description' => $form['description'], 'id' => $role['id']]);
            if (!$isSystem) {
                $delete = db()->prepare('DELETE FROM role_permissions WHERE role_id = :role_id');
                $delete->execute(['role_id' => $role['id']]);
                $assign = db()->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)');
                foreach ($selectedPermissions as $permissionId) $assign->execute(['role_id' => $role['id'], 'permission_id' => $permissionId]);
            }
            db()->commit();
            audit_log('updated', 'role', (int) $role['id'], 'Rol actualizado: ' . $form['name']);
            flash('success', 'Rol actualizado correctamente.');
            redirect_admin('roles/');
        } catch (Throwable $exception) {
            db()->rollBack();
            throw $exception;
        }
    }
}

$pageTitle = 'Editar rol';
$activeMenu = 'roles';
$formAction = admin_url('roles/editar.php?id=' . (int) $role['id']);
$isEdit = true;
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading"><div><span class="page-heading__eyebrow">Gobierno de acceso</span><h1>Editar rol</h1><p>Ajusta el alcance de este rol y conserva una estructura de acceso clara.</p></div></div>
<?php require __DIR__ . '/_form.php'; ?>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
