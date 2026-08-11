<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$errors = [];
$databaseError = '';
$form = ['name' => '', 'email' => ''];

try {
    if ((int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
        redirect_admin('login.php');
    }
} catch (Throwable $exception) {
    error_log('Error de base de datos en instalación: ' . $exception->getMessage());
    $databaseError = 'Primero importa database/gocreative.sql y confirma la configuración de conexión.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $databaseError === '') {
    verify_csrf();
    $form = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'email' => mb_strtolower(trim((string) ($_POST['email'] ?? ''))),
    ];
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (mb_strlen($form['name']) < 3 || mb_strlen($form['name']) > 100) {
        $errors[] = 'El nombre debe tener entre 3 y 100 caracteres.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ingresa un correo electrónico válido.';
    }
    $errors = array_merge($errors, validate_password_strength($password));
    if ($password !== $confirmation) {
        $errors[] = 'La confirmación de contraseña no coincide.';
    }

    if ($errors === []) {
        db()->beginTransaction();
        try {
            if ((int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
                db()->rollBack();
                redirect_admin('login.php');
            }

            $roleStatement = db()->prepare("SELECT id FROM roles WHERE slug = 'superadministrador' LIMIT 1");
            $roleStatement->execute();
            $roleId = (int) $roleStatement->fetchColumn();
            if ($roleId < 1) {
                throw new RuntimeException('No existe el rol superadministrador. Importa nuevamente la base de datos.');
            }

            $insert = db()->prepare(
                'INSERT INTO users
                    (role_id, name, email, password_hash, status, must_change_password, password_changed_at)
                 VALUES
                    (:role_id, :name, :email, :password_hash, \'active\', 0, NOW())'
            );
            $insert->execute([
                'role_id' => $roleId,
                'name' => $form['name'],
                'email' => $form['email'],
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $userId = (int) db()->lastInsertId();
            db()->commit();

            session_regenerate_id(true);
            $_SESSION['admin_user_id'] = $userId;
            unset($_SESSION['admin_csrf']);
            current_user(true);
            audit_log('installed', 'user', $userId, 'Primer superadministrador creado mediante el instalador seguro');
            flash('success', 'Panel configurado correctamente. El instalador quedó bloqueado.');
            redirect_admin();
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            error_log('No se pudo crear el administrador inicial: ' . $exception->getMessage());
            $errors[] = 'No fue posible crear la cuenta. Revisa la base de datos e inténtalo nuevamente.';
        }
    }
}
?>
<!doctype html>
<html lang="es-CL">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#07111f">
    <title>Configurar panel | Go Creative</title>
    <link rel="icon" href="<?= e(site_path('/assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="shortcut icon" href="<?= e(site_path('/favicon.ico')) ?>">
    <link rel="stylesheet" href="<?= e(admin_url('assets/vendor/bootstrap/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(admin_url('assets/css/admin.css?v=1.4.0')) ?>">
</head>
<body class="login-body">
<main class="login-shell">
    <section class="login-form-panel">
        <a class="login-brand" href="<?= e(site_path('/')) ?>" aria-label="Ir al sitio de Go Creative">
            <img src="<?= e(site_path('/assets/img/logo.webp')) ?>" width="620" height="224" alt="Go Creative Chile">
        </a>
        <span class="login-kicker">Configuración segura</span>
        <h1>Crea el acceso principal.</h1>
        <p>Estos datos se guardarán directamente en tu base de datos. La contraseña no forma parte del código ni del repositorio.</p>

        <div class="login-form">
            <?php if ($databaseError !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= e($databaseError) ?></div>
            <?php endif; ?>
            <?php if ($errors !== []): ?>
                <div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <form method="post" action="<?= e(admin_url('instalar.php')) ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">NOMBRE COMPLETO</label>
                        <input class="form-control" id="name" name="name" value="<?= e($form['name']) ?>" maxlength="100" autocomplete="name" required autofocus>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">CORREO ELECTRÓNICO</label>
                        <input class="form-control" id="email" name="email" type="email" value="<?= e($form['email']) ?>" maxlength="190" autocomplete="email" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">CONTRASEÑA</label>
                        <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirmation">CONFIRMAR CONTRASEÑA</label>
                        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="form-text mt-2">Usa al menos 12 caracteres, mayúsculas, minúsculas, un número y un símbolo.</div>
                <button class="btn btn-primary w-100 mt-4" type="submit"<?= $databaseError !== '' ? ' disabled' : '' ?>>Crear superadministrador →</button>
            </form>
        </div>
    </section>
    <section class="login-visual" aria-label="Seguridad del panel Go Creative">
        <div class="login-visual__content">
            <span class="login-visual__label">Instalación privada</span>
            <h2>Tu contraseña nunca se publica.</h2>
            <p>El instalador solo funciona mientras no exista ningún usuario y se cierra automáticamente al crear la primera cuenta.</p>
        </div>
    </section>
</main>
<script src="<?= e(admin_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"></script>
</body>
</html>
