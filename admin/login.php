<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$error = '';
$databaseError = false;
$email = '';
$recaptchaConfigured = recaptcha_is_configured();

try {
    if (current_user() !== null) {
        redirect_admin();
    }

    if ((int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        redirect_admin('instalar.php');
    }
} catch (Throwable $exception) {
    error_log('Error de base de datos en login: ' . $exception->getMessage());
    $databaseError = true;
    $error = 'No fue posible conectar con la base de datos. Importa database/gocreative.sql y revisa la configuración.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$databaseError) {
    verify_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!$recaptchaConfigured) {
        $error = 'reCAPTCHA todavía no está configurado. Revisa config/recaptcha/recaptcha.local.php.';
    } elseif (!recaptcha_verify((string) ($_POST['g-recaptcha-response'] ?? ''), client_ip())) {
        $error = 'Confirma que no eres un robot e intenta nuevamente.';
    } else {
        try {
            $result = attempt_login($email, $password);
            if ($result['ok']) {
                $user = current_user(true);
                redirect_admin((int) ($user['must_change_password'] ?? 0) === 1 ? 'cambiar-clave.php' : '');
            }
            $error = $result['message'];
        } catch (PDOException $exception) {
            error_log('Error de base de datos en login: ' . $exception->getMessage());
            $databaseError = true;
            $error = 'No fue posible conectar con la base de datos. Importa database/gocreative.sql y revisa la configuración.';
        }
    }
}

$flashes = pull_flashes();
?>
<!doctype html>
<html lang="es-CL">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#07111f">
    <title>Acceso administrativo | Go Creative</title>
    <link rel="icon" href="<?= e(site_path('/assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="shortcut icon" href="<?= e(site_path('/favicon.ico')) ?>">
    <link rel="stylesheet" href="<?= e(admin_url('assets/vendor/bootstrap/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(admin_url('assets/css/admin.css?v=1.5.0')) ?>">
    <?php if ($recaptchaConfigured): ?>
        <link rel="preconnect" href="https://www.google.com">
        <link rel="preconnect" href="https://www.gstatic.com" crossorigin>
        <script src="https://www.google.com/recaptcha/api.js?hl=es-419" async defer></script>
    <?php endif; ?>
</head>
<body class="login-body">
<main class="login-shell">
    <section class="login-form-panel">
        <a class="login-brand" href="<?= e(site_path('/')) ?>" aria-label="Ir al sitio de Go Creative">
            <img src="<?= e(site_path('/assets/img/logo.webp')) ?>" width="620" height="224" alt="Go Creative Chile">
        </a>
        <span class="login-kicker">Área privada</span>
        <h1>Orden para crear y crecer.</h1>
        <p>Gestiona usuarios, responsabilidades y accesos desde un panel diseñado para el equipo Go Creative.</p>

        <div class="login-form">
            <?php foreach ($flashes as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
            <?php endforeach; ?>
            <?php if ($error !== ''): ?>
                <div class="alert alert-<?= $databaseError ? 'danger' : 'warning' ?>" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(admin_url('login.php')) ?>" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="email">CORREO ELECTRÓNICO</label>
                    <input class="form-control" id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="username" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">CONTRASEÑA</label>
                    <div class="password-field">
                        <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                        <button class="password-toggle" type="button" data-password-toggle="password">Ver</button>
                    </div>
                </div>
                <?php if ($recaptchaConfigured): ?>
                    <div class="login-recaptcha"><div class="g-recaptcha" data-sitekey="<?= e(recaptcha_site_key()) ?>" data-theme="light"></div></div>
                <?php else: ?>
                    <div class="alert alert-warning small" role="alert">Configura las claves reCAPTCHA para habilitar el acceso.</div>
                <?php endif; ?>
                <button class="btn btn-primary w-100" type="submit"<?= ($databaseError || !$recaptchaConfigured) ? ' disabled' : '' ?>>Ingresar al panel →</button>
            </form>
            <a class="d-inline-block mt-4 text-secondary small text-decoration-none" href="<?= e(site_path('/')) ?>">← Volver al sitio público</a>
        </div>
    </section>
    <section class="login-visual" aria-label="Estudio digital Go Creative">
        <div class="login-visual__content">
            <span class="login-visual__label">Diseño + tecnología + crecimiento</span>
            <h2>Menos fricción.<br>Más claridad operativa.</h2>
            <p>Un espacio sobrio y seguro para que cada persona acceda únicamente a lo que necesita.</p>
        </div>
    </section>
</main>
<script src="<?= e(admin_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(admin_url('assets/js/admin.js?v=1.0.0')) ?>"></script>
</body>
</html>
