<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/configuration.php';

$currentAdmin = require_permission('settings.manage');
$configuration = recaptcha_config();
$environmentOverrides = recaptcha_environment_overrides();
$localPath = dirname(__DIR__, 2) . '/config/recaptcha/recaptcha.local.php';
$localConfiguration = [];
if (is_file($localPath)) {
    $loadedLocalConfiguration = require $localPath;
    if (is_array($loadedLocalConfiguration)) {
        $localConfiguration = $loadedLocalConfiguration;
    }
}
$existingLocalSecret = trim((string) ($localConfiguration['secret_key'] ?? ''));
$environmentSecret = getenv('GC_RECAPTCHA_SECRET_KEY');
$hasEnvironmentSecret = is_string($environmentSecret) && trim($environmentSecret) !== '';
$errors = [];
$form = [
    'protect_login' => (bool) $configuration['protect_login'],
    'protect_contact' => (bool) $configuration['protect_contact'],
    'site_key' => (string) $configuration['site_key'],
    'allowed_hosts' => implode("\n", (array) $configuration['allowed_hosts']),
    'timeout' => (int) $configuration['timeout'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $secretInput = trim((string) ($_POST['secret_key'] ?? ''));
    $hostInput = preg_split('/[\s,;]+/', strtolower((string) ($_POST['allowed_hosts'] ?? ''))) ?: [];
    $allowedHosts = array_values(array_unique(array_filter(array_map(
        static fn (string $host): string => trim($host, " \t\n\r\0\x0B."),
        $hostInput
    ))));
    $form = [
        'protect_login' => isset($_POST['protect_login']),
        'protect_contact' => isset($_POST['protect_contact']),
        'site_key' => trim((string) ($_POST['site_key'] ?? '')),
        'allowed_hosts' => implode("\n", $allowedHosts),
        'timeout' => max(2, min(15, (int) ($_POST['timeout'] ?? 8))),
    ];
    $secretToSave = $secretInput !== '' ? $secretInput : $existingLocalSecret;
    $hasEffectiveSecret = $secretToSave !== '' || $hasEnvironmentSecret;

    if ($form['site_key'] !== '' && (strlen($form['site_key']) < 20 || strlen($form['site_key']) > 255)) {
        $errors[] = 'La clave pública no tiene una longitud válida.';
    }
    if ($secretInput !== '' && (strlen($secretInput) < 20 || strlen($secretInput) > 255)) {
        $errors[] = 'La clave secreta no tiene una longitud válida.';
    }
    if (($form['protect_login'] || $form['protect_contact']) && ($form['site_key'] === '' || !$hasEffectiveSecret)) {
        $errors[] = 'Para proteger formularios debes ingresar la clave pública y la clave secreta.';
    }
    if ($allowedHosts === []) {
        $errors[] = 'Agrega al menos un dominio permitido.';
    }
    foreach ($allowedHosts as $host) {
        if ($host !== 'localhost' && filter_var($host, FILTER_VALIDATE_IP) === false && !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $host)) {
            $errors[] = 'El dominio «' . $host . '» no es válido. Escríbelo sin https:// ni rutas.';
        }
    }

    if ($errors === []) {
        try {
            save_local_configuration($localPath, [
                'protect_login' => $form['protect_login'],
                'protect_contact' => $form['protect_contact'],
                'site_key' => $form['site_key'],
                'secret_key' => $secretToSave,
                'allowed_hosts' => $allowedHosts,
                'timeout' => $form['timeout'],
            ]);
            audit_log('updated', 'recaptcha_settings', null, 'Configuración de reCAPTCHA v2 actualizada');
            flash('success', 'reCAPTCHA v2 fue actualizado. La clave secreta permanece oculta.');
            redirect_admin('configuracion/recaptcha.php');
        } catch (Throwable $exception) {
            error_log('No se pudo guardar reCAPTCHA: ' . $exception->getMessage());
            $errors[] = 'No fue posible guardar. Revisa los permisos de la carpeta config/recaptcha.';
        }
    }
}

$isConfigured = $form['site_key'] !== '' && ($existingLocalSecret !== '' || $hasEnvironmentSecret || (isset($secretInput) && $secretInput !== ''));
$pageTitle = 'reCAPTCHA v2';
$activeMenu = 'recaptcha';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading">
    <div>
        <span class="page-heading__eyebrow">Protección de formularios</span>
        <h1>reCAPTCHA v2</h1>
        <p>Configura la casilla “No soy un robot” para el login y el formulario de contacto.</p>
    </div>
    <span class="settings-state <?= $isConfigured ? 'is-active' : 'is-inactive' ?>"><?= $isConfigured ? 'Claves configuradas' : 'Configuración pendiente' ?></span>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert"><strong>Revisa la configuración:</strong><ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<?php if ($environmentOverrides !== []): ?>
    <div class="alert alert-warning" role="alert">Las variables <?= e(implode(', ', $environmentOverrides)) ?> tienen prioridad. La clave secreta definida en el servidor no se copia al archivo local.</div>
<?php endif; ?>

<form method="post" action="<?= e(admin_url('configuracion/recaptcha.php')) ?>" autocomplete="off">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-xl-8">
            <section class="form-section">
                <div class="form-section__heading"><h2>Claves de Google reCAPTCHA</h2><p>Usa credenciales de tipo reCAPTCHA v2 con casilla de verificación.</p></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="site_key">CLAVE PÚBLICA DEL SITIO</label>
                        <input class="form-control font-monospace" id="site_key" name="site_key" value="<?= e($form['site_key']) ?>" maxlength="255" autocomplete="off">
                        <div class="form-text">Se utiliza para dibujar el widget en el navegador.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="secret_key">CLAVE SECRETA</label>
                        <input class="form-control font-monospace" id="secret_key" name="secret_key" type="password" maxlength="255" autocomplete="new-password" placeholder="<?= $existingLocalSecret !== '' || $hasEnvironmentSecret ? '•••••••••••••••••••• — dejar en blanco para conservar' : 'Ingresa la clave secreta' ?>">
                        <div class="form-text">Nunca se muestra nuevamente ni se publica en GitHub.</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="allowed_hosts">DOMINIOS PERMITIDOS</label>
                        <textarea class="form-control font-monospace" id="allowed_hosts" name="allowed_hosts" rows="4" spellcheck="false"><?= e($form['allowed_hosts']) ?></textarea>
                        <div class="form-text">Uno por línea, sin protocolo. Incluye localhost para XAMPP.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="timeout">TIEMPO DE VERIFICACIÓN</label>
                        <div class="input-group"><input class="form-control" id="timeout" name="timeout" type="number" min="2" max="15" value="<?= (int) $form['timeout'] ?>"><span class="input-group-text">segundos</span></div>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="form-section settings-side-card">
                <div class="form-section__heading"><h2>Formularios protegidos</h2><p>Puedes gestionar cada punto de acceso por separado.</p></div>
                <label class="settings-switch" for="protect_login">
                    <span><strong>Login del panel</strong><small>Protege el acceso administrativo.</small></span>
                    <input class="form-check-input" type="checkbox" role="switch" id="protect_login" name="protect_login" value="1"<?= $form['protect_login'] ? ' checked' : '' ?>>
                </label>
                <label class="settings-switch" for="protect_contact">
                    <span><strong>Formulario de contacto</strong><small>Evita envíos automáticos y spam.</small></span>
                    <input class="form-check-input" type="checkbox" role="switch" id="protect_contact" name="protect_contact" value="1"<?= $form['protect_contact'] ? ' checked' : '' ?>>
                </label>
                <div class="settings-note"><strong>Estado seguro</strong><p>Si el contacto está activo pero faltan claves, el envío permanece bloqueado. El login conserva acceso temporal para que un superadministrador pueda completar esta pantalla.</p></div>
                <button class="btn btn-primary w-100 mt-3" type="submit">Guardar reCAPTCHA</button>
            </section>
        </div>
    </div>
</form>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
