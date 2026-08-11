<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$currentAdmin = require_permission('users.edit');
$userId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$userId) {
    flash('danger', 'Usuario no válido.');
    redirect_admin('usuarios/');
}

$statement = db()->prepare(
    'SELECT u.id, u.name, u.email, u.role_id, u.status, r.slug AS role_slug
     FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.id = :id LIMIT 1'
);
$statement->execute(['id' => $userId]);
$targetUser = $statement->fetch();
if (!$targetUser) {
    flash('danger', 'El usuario solicitado no existe.');
    redirect_admin('usuarios/');
}

$roles = db()->query('SELECT id, name, slug FROM roles ORDER BY name')->fetchAll();
$form = $targetUser;
$errors = [];
$lockOwnAccess = (int) $targetUser['id'] === (int) $currentAdmin['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form = array_merge($targetUser, [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'email' => mb_strtolower(trim((string) ($_POST['email'] ?? ''))),
        'role_id' => (int) ($_POST['role_id'] ?? 0),
        'status' => (string) ($_POST['status'] ?? 'active'),
    ]);
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    if ($lockOwnAccess) {
        $form['role_id'] = (int) $targetUser['role_id'];
        $form['status'] = 'active';
    }
    if (mb_strlen($form['name']) < 3 || mb_strlen($form['name']) > 100) $errors[] = 'El nombre debe tener entre 3 y 100 caracteres.';
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Ingresa un correo electrónico válido.';
    if (!in_array($form['status'], ['active', 'inactive'], true)) $errors[] = 'Selecciona un estado válido.';
    $roleCheck = db()->prepare('SELECT slug FROM roles WHERE id = :id');
    $roleCheck->execute(['id' => $form['role_id']]);
    $newRoleSlug = $roleCheck->fetchColumn();
    if (!$newRoleSlug) $errors[] = 'Selecciona un rol válido.';
    if ($targetUser['role_slug'] === 'superadministrador' && ($newRoleSlug !== 'superadministrador' || $form['status'] !== 'active') && is_last_active_superadmin((int) $targetUser['id'])) {
        $errors[] = 'No puedes desactivar o cambiar de rol al último superadministrador activo.';
    }
    $emailCheck = db()->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id');
    $emailCheck->execute(['email' => $form['email'], 'id' => $targetUser['id']]);
    if ((int) $emailCheck->fetchColumn() > 0) $errors[] = 'Ya existe otro usuario con ese correo.';
    if ($password !== '') {
        $errors = array_merge($errors, validate_password_strength($password));
        if ($password !== $confirmation) $errors[] = 'La confirmación de contraseña no coincide.';
    } elseif ($confirmation !== '') {
        $errors[] = 'Ingresa la nueva contraseña antes de confirmarla.';
    }

    if ($errors === []) {
        $sql = 'UPDATE users SET role_id = :role_id, name = :name, email = :email, status = :status';
        $params = ['role_id' => $form['role_id'], 'name' => $form['name'], 'email' => $form['email'], 'status' => $form['status'], 'id' => $targetUser['id']];
        if ($password !== '') {
            $sql .= ', password_hash = :password_hash, must_change_password = 1, password_changed_at = NULL';
            $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id = :id';
        $update = db()->prepare($sql);
        $update->execute($params);
        audit_log('updated', 'user', (int) $targetUser['id'], 'Usuario actualizado: ' . $form['email']);
        flash('success', 'Los datos del usuario fueron actualizados.');
        redirect_admin('usuarios/');
    }
}

$pageTitle = 'Editar usuario';
$activeMenu = 'users';
$formAction = admin_url('usuarios/editar.php?id=' . (int) $targetUser['id']);
$isEdit = true;
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading"><div><span class="page-heading__eyebrow">Equipo y accesos</span><h1>Editar usuario</h1><p>Actualiza la identidad, el rol o las credenciales de esta cuenta.</p></div></div>
<?php require __DIR__ . '/_form.php'; ?>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
