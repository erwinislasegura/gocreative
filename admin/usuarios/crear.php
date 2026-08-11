<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$currentAdmin = require_permission('users.create');
$roles = db()->query('SELECT id, name FROM roles ORDER BY name')->fetchAll();
$form = ['name' => '', 'email' => '', 'role_id' => '', 'status' => 'active'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'email' => mb_strtolower(trim((string) ($_POST['email'] ?? ''))),
        'role_id' => (int) ($_POST['role_id'] ?? 0),
        'status' => (string) ($_POST['status'] ?? 'active'),
    ];
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (mb_strlen($form['name']) < 3 || mb_strlen($form['name']) > 100) $errors[] = 'El nombre debe tener entre 3 y 100 caracteres.';
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Ingresa un correo electrónico válido.';
    if (!in_array($form['status'], ['active', 'inactive'], true)) $errors[] = 'Selecciona un estado válido.';
    $roleCheck = db()->prepare('SELECT COUNT(*) FROM roles WHERE id = :id');
    $roleCheck->execute(['id' => $form['role_id']]);
    if ((int) $roleCheck->fetchColumn() !== 1) $errors[] = 'Selecciona un rol válido.';
    $errors = array_merge($errors, validate_password_strength($password));
    if ($password !== $confirmation) $errors[] = 'La confirmación de contraseña no coincide.';
    $emailCheck = db()->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
    $emailCheck->execute(['email' => $form['email']]);
    if ((int) $emailCheck->fetchColumn() > 0) $errors[] = 'Ya existe un usuario con ese correo.';

    if ($errors === []) {
        $insert = db()->prepare(
            'INSERT INTO users (role_id, name, email, password_hash, status, must_change_password)
             VALUES (:role_id, :name, :email, :password_hash, :status, 1)'
        );
        $insert->execute([
            'role_id' => $form['role_id'],
            'name' => $form['name'],
            'email' => $form['email'],
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'status' => $form['status'],
        ]);
        $userId = (int) db()->lastInsertId();
        audit_log('created', 'user', $userId, 'Usuario creado: ' . $form['email']);
        flash('success', 'Usuario creado. Deberá cambiar su contraseña en el primer ingreso.');
        redirect_admin('usuarios/');
    }
}

$pageTitle = 'Crear usuario';
$activeMenu = 'users';
$formAction = admin_url('usuarios/crear.php');
$isEdit = false;
$lockOwnAccess = false;
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading"><div><span class="page-heading__eyebrow">Equipo y accesos</span><h1>Nuevo usuario</h1><p>Crea una cuenta y define desde el inicio su nivel de responsabilidad.</p></div></div>
<?php require __DIR__ . '/_form.php'; ?>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
