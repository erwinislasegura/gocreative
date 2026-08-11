<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_permission('roles.create');
$permissions = db()->query('SELECT id, name, slug, description, group_name FROM permissions ORDER BY group_name, id')->fetchAll();
$permissionGroups = [];
foreach ($permissions as $permission) $permissionGroups[$permission['group_name']][] = $permission;
$form = ['name' => '', 'description' => ''];
$selectedPermissions = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form = ['name' => trim((string) ($_POST['name'] ?? '')), 'description' => trim((string) ($_POST['description'] ?? ''))];
    $selectedPermissions = array_values(array_unique(array_map('intval', (array) ($_POST['permissions'] ?? []))));
    $slug = role_slug($form['name']);
    if (mb_strlen($form['name']) < 3 || mb_strlen($form['name']) > 80) $errors[] = 'El nombre debe tener entre 3 y 80 caracteres.';
    if ($slug === '') $errors[] = 'El nombre no permite generar un identificador válido.';
    if (mb_strlen($form['description']) > 500) $errors[] = 'La descripción no puede superar 500 caracteres.';
    if ($selectedPermissions === []) $errors[] = 'Selecciona al menos un permiso.';
    $validPermissionIds = array_map('intval', array_column($permissions, 'id'));
    if (array_diff($selectedPermissions, $validPermissionIds) !== []) $errors[] = 'La selección contiene un permiso no válido.';
    $check = db()->prepare('SELECT COUNT(*) FROM roles WHERE name = :name OR slug = :slug');
    $check->execute(['name' => $form['name'], 'slug' => $slug]);
    if ((int) $check->fetchColumn() > 0) $errors[] = 'Ya existe un rol con ese nombre.';

    if ($errors === []) {
        db()->beginTransaction();
        try {
            $insert = db()->prepare('INSERT INTO roles (name, slug, description) VALUES (:name, :slug, :description)');
            $insert->execute(['name' => $form['name'], 'slug' => $slug, 'description' => $form['description']]);
            $roleId = (int) db()->lastInsertId();
            $assign = db()->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)');
            foreach ($selectedPermissions as $permissionId) $assign->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
            db()->commit();
            audit_log('created', 'role', $roleId, 'Rol creado: ' . $form['name']);
            flash('success', 'Rol creado correctamente.');
            redirect_admin('roles/');
        } catch (Throwable $exception) {
            db()->rollBack();
            throw $exception;
        }
    }
}

$pageTitle = 'Crear rol';
$activeMenu = 'roles';
$formAction = admin_url('roles/crear.php');
$isEdit = false;
$isSystem = false;
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading"><div><span class="page-heading__eyebrow">Gobierno de acceso</span><h1>Nuevo rol</h1><p>Combina permisos para representar una responsabilidad real dentro del equipo.</p></div></div>
<?php require __DIR__ . '/_form.php'; ?>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
