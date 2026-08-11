<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/configuration.php';
require_once dirname(__DIR__, 2) . '/app/Mail/HtmlMailer.php';
require_once dirname(__DIR__, 2) . '/app/Mail/email_templates.php';

use GoCreative\Mail\HtmlMailer;

$currentAdmin = require_permission('settings.manage');
$configuration = mail_config();
$environmentOverrides = mail_environment_overrides();
$localPath = dirname(__DIR__, 2) . '/config/mail/mail.local.php';
$localConfiguration = [];
if (is_file($localPath)) {
    $loadedLocalConfiguration = require $localPath;
    if (is_array($loadedLocalConfiguration)) {
        $localConfiguration = $loadedLocalConfiguration;
    }
}
$existingLocalPassword = (string) ($localConfiguration['password'] ?? '');
$environmentPassword = getenv('GC_SMTP_PASSWORD');
$hasEnvironmentPassword = is_string($environmentPassword) && $environmentPassword !== '';
$errors = [];
$form = [
    'transport' => (string) $configuration['transport'],
    'host' => (string) $configuration['host'],
    'port' => (int) $configuration['port'],
    'encryption' => (string) $configuration['encryption'],
    'username' => (string) $configuration['username'],
    'from_email' => (string) $configuration['from_email'],
    'from_name' => (string) $configuration['from_name'],
    'timeout' => (int) $configuration['timeout'],
    'test_recipient' => (string) $currentAdmin['email'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    $passwordInput = (string) ($_POST['password'] ?? '');
    $passwordToSave = $passwordInput !== '' ? $passwordInput : $existingLocalPassword;
    $form = [
        'transport' => strtolower(trim((string) ($_POST['transport'] ?? 'mail'))),
        'host' => trim((string) ($_POST['host'] ?? '')),
        'port' => (int) ($_POST['port'] ?? 587),
        'encryption' => strtolower(trim((string) ($_POST['encryption'] ?? 'tls'))),
        'username' => trim((string) ($_POST['username'] ?? '')),
        'from_email' => mb_strtolower(trim((string) ($_POST['from_email'] ?? ''))),
        'from_name' => trim((string) ($_POST['from_name'] ?? '')),
        'timeout' => (int) ($_POST['timeout'] ?? 12),
        'test_recipient' => mb_strtolower(trim((string) ($_POST['test_recipient'] ?? ''))),
    ];

    if (!in_array($form['transport'], ['mail', 'smtp'], true)) {
        $errors[] = 'Selecciona un transporte de correo válido.';
    }
    if (!filter_var($form['from_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ingresa un correo remitente válido.';
    }
    if (mb_strlen($form['from_name']) < 2 || mb_strlen($form['from_name']) > 100) {
        $errors[] = 'El nombre del remitente debe tener entre 2 y 100 caracteres.';
    }
    if ($form['port'] < 1 || $form['port'] > 65535) {
        $errors[] = 'El puerto SMTP no es válido.';
    }
    if (!in_array($form['encryption'], ['tls', 'ssl', 'none'], true)) {
        $errors[] = 'Selecciona un tipo de cifrado válido.';
    }
    if ($form['timeout'] < 3 || $form['timeout'] > 30) {
        $errors[] = 'El tiempo de espera debe estar entre 3 y 30 segundos.';
    }
    if ($form['transport'] === 'smtp') {
        if ($form['host'] === '' || strlen($form['host']) > 190 || preg_match('~[\s/:]~', $form['host'])) {
            $errors[] = 'Escribe el servidor SMTP sin protocolo, puerto ni rutas; por ejemplo mail.gocreative.cl.';
        }
        $effectivePassword = $passwordToSave !== '' ? $passwordToSave : ($hasEnvironmentPassword ? (string) $environmentPassword : '');
        if ($form['username'] !== '' && $effectivePassword === '') {
            $errors[] = 'Ingresa la contraseña de la cuenta SMTP.';
        }
        if ($form['username'] === '' && $effectivePassword !== '') {
            $errors[] = 'Ingresa el usuario SMTP asociado a la contraseña.';
        }
    }
    if ($action === 'test' && !filter_var($form['test_recipient'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ingresa un destinatario válido para el correo de prueba.';
    }

    $settingsToSave = [
        'transport' => $form['transport'],
        'host' => $form['host'],
        'port' => $form['port'],
        'encryption' => $form['encryption'],
        'username' => $form['username'],
        'password' => $passwordToSave,
        'from_email' => $form['from_email'],
        'from_name' => $form['from_name'],
        'timeout' => $form['timeout'],
    ];

    if ($errors === []) {
        $configurationWasSaved = false;
        try {
            save_local_configuration($localPath, $settingsToSave);
            $configurationWasSaved = true;
            $existingLocalPassword = $passwordToSave;
            audit_log('updated', 'mail_settings', null, 'Configuración de correo saliente actualizada');

            if ($action === 'test') {
                $testSettings = $settingsToSave;
                if ($testSettings['password'] === '' && $hasEnvironmentPassword) {
                    $testSettings['password'] = (string) $environmentPassword;
                }
                $content = '<p style="margin:0 0 18px;color:#4e6068;font-size:15px;line-height:1.7">La conexión de correo saliente de Go Creative está funcionando correctamente.</p>'
                    . '<div style="padding:18px;background:#eef4f0;border-left:4px solid #8bea38;color:#29434a;font-size:13px;line-height:1.6">Transporte: <strong>' . email_escape(mb_strtoupper($form['transport'])) . '</strong><br>Servidor: ' . email_escape($form['transport'] === 'smtp' ? $form['host'] . ':' . $form['port'] : 'PHP mail()') . '</div>';
                $mailer = new HtmlMailer($form['from_email'], $form['from_name'], $testSettings);
                $mailer->send($form['test_recipient'], 'Prueba de correo · Go Creative', email_layout('Configuración de correo correcta', 'Prueba del sistema', 'El correo saliente está operativo.', $content));
                audit_log('tested', 'mail_settings', null, 'Correo de prueba enviado correctamente');
                flash('success', 'Configuración guardada y correo de prueba enviado a ' . $form['test_recipient'] . '.');
            } else {
                flash('success', 'La configuración de correo fue guardada correctamente.');
            }
            redirect_admin('configuracion/correo.php');
        } catch (Throwable $exception) {
            error_log('No se pudo configurar el correo: ' . $exception->getMessage());
            $errors[] = ($configurationWasSaved
                ? 'La configuración quedó guardada, pero la prueba falló: '
                : 'No fue posible guardar la configuración: ')
                . $exception->getMessage();
        }
    }
}

$smtpReady = $form['transport'] === 'smtp'
    && $form['host'] !== ''
    && filter_var($form['from_email'], FILTER_VALIDATE_EMAIL);
$pageTitle = 'Correo saliente';
$activeMenu = 'mail';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading">
    <div>
        <span class="page-heading__eyebrow">Entrega de mensajes</span>
        <h1>Correo SMTP</h1>
        <p>Configura un buzón autenticado para Hosting, cotizaciones y contacto.</p>
    </div>
    <span class="settings-state <?= $smtpReady ? 'is-active' : 'is-inactive' ?>"><?= $smtpReady ? 'SMTP configurado' : 'Usando PHP mail()' ?></span>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert"><strong>No se pudo completar la configuración:</strong><ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<?php if ($environmentOverrides !== []): ?>
    <div class="alert alert-warning" role="alert">Las variables <?= e(implode(', ', $environmentOverrides)) ?> tienen prioridad sobre los valores del panel.</div>
<?php endif; ?>
<?php if ($form['transport'] === 'mail'): ?>
    <div class="alert alert-warning" role="alert"><strong>XAMPP:</strong> PHP mail() normalmente no entrega correos sin configurar sendmail. Selecciona SMTP para usar directamente un buzón real.</div>
<?php endif; ?>

<form method="post" action="<?= e(admin_url('configuracion/correo.php')) ?>" autocomplete="off">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-xl-8">
            <section class="form-section">
                <div class="form-section__heading"><h2>Servidor de salida</h2><p>Utiliza los mismos datos SMTP que entrega cPanel al configurar una cuenta de correo.</p></div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="transport">TRANSPORTE</label>
                        <select class="form-select" id="transport" name="transport"><option value="smtp"<?= $form['transport'] === 'smtp' ? ' selected' : '' ?>>SMTP autenticado</option><option value="mail"<?= $form['transport'] === 'mail' ? ' selected' : '' ?>>PHP mail()</option></select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="host">SERVIDOR SMTP</label>
                        <input class="form-control" id="host" name="host" value="<?= e($form['host']) ?>" maxlength="190" placeholder="mail.gocreative.cl" autocomplete="off">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="port">PUERTO</label>
                        <input class="form-control" id="port" name="port" type="number" min="1" max="65535" value="<?= (int) $form['port'] ?>">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label" for="encryption">CIFRADO</label>
                        <select class="form-select" id="encryption" name="encryption"><option value="tls"<?= $form['encryption'] === 'tls' ? ' selected' : '' ?>>TLS / STARTTLS</option><option value="ssl"<?= $form['encryption'] === 'ssl' ? ' selected' : '' ?>>SSL</option><option value="none"<?= $form['encryption'] === 'none' ? ' selected' : '' ?>>Sin cifrado</option></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="username">USUARIO SMTP</label>
                        <input class="form-control" id="username" name="username" value="<?= e($form['username']) ?>" maxlength="190" autocomplete="username" placeholder="contacto@gocreative.cl">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="password">CONTRASEÑA SMTP</label>
                        <input class="form-control" id="password" name="password" type="password" maxlength="255" autocomplete="new-password" placeholder="<?= $existingLocalPassword !== '' || $hasEnvironmentPassword ? '•••••••• — dejar en blanco para conservar' : 'Contraseña del buzón' ?>">
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section__heading"><h2>Identidad del remitente</h2><p>Debe coincidir con una dirección autorizada por el servidor para evitar rechazos o spam.</p></div>
                <div class="row g-3">
                    <div class="col-md-5"><label class="form-label" for="from_email">CORREO REMITENTE</label><input class="form-control" id="from_email" name="from_email" type="email" value="<?= e($form['from_email']) ?>" maxlength="190" required></div>
                    <div class="col-md-4"><label class="form-label" for="from_name">NOMBRE REMITENTE</label><input class="form-control" id="from_name" name="from_name" value="<?= e($form['from_name']) ?>" maxlength="100" required></div>
                    <div class="col-md-3"><label class="form-label" for="timeout">ESPERA MÁXIMA</label><div class="input-group"><input class="form-control" id="timeout" name="timeout" type="number" min="3" max="30" value="<?= (int) $form['timeout'] ?>"><span class="input-group-text">seg.</span></div></div>
                </div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="form-section settings-side-card">
                <div class="form-section__heading"><h2>Comprobar entrega</h2><p>Guarda la configuración y envía un mensaje real de diagnóstico.</p></div>
                <label class="form-label" for="test_recipient">DESTINATARIO DE PRUEBA</label>
                <input class="form-control" id="test_recipient" name="test_recipient" type="email" value="<?= e($form['test_recipient']) ?>" maxlength="190">
                <div class="settings-note"><strong>Configuración recomendada</strong><p>En la mayoría de los hosting cPanel: puerto 587 con TLS o puerto 465 con SSL. El usuario suele ser el correo completo.</p></div>
                <div class="d-grid gap-2 mt-3"><button class="btn btn-primary" type="submit" name="action" value="test">Guardar y enviar prueba</button><button class="btn btn-outline-dark" type="submit" name="action" value="save">Solo guardar</button></div>
                <p class="form-text mt-3 mb-0">La contraseña se almacena en un archivo privado excluido de GitHub y nunca vuelve a mostrarse.</p>
            </section>
        </div>
    </div>
</form>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
