<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/configuration.php';
require_once dirname(__DIR__, 2) . '/config/flow/configuration.php';

$currentAdmin = require_permission('settings.manage');
$configuration = flow_config();
$errors = [];
$form = [
    'environment' => (string) $configuration['environment'],
    'api_key' => (string) $configuration['api_key'],
    'secret_key' => (string) $configuration['secret_key'],
    'public_url' => (string) $configuration['public_url'],
    'payment_method' => (int) $configuration['payment_method'],
    'timeout' => (int) $configuration['timeout'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form = [
        'environment' => strtolower(trim((string) ($_POST['environment'] ?? 'sandbox'))),
        'api_key' => trim((string) ($_POST['api_key'] ?? '')),
        'secret_key' => trim((string) ($_POST['secret_key'] ?? '')),
        'public_url' => rtrim(trim((string) ($_POST['public_url'] ?? '')), '/'),
        'payment_method' => (int) ($_POST['payment_method'] ?? 9),
        'timeout' => (int) ($_POST['timeout'] ?? 172800),
    ];

    if (!in_array($form['environment'], ['sandbox', 'production'], true)) {
        $errors[] = 'Selecciona un ambiente válido para Flow.';
    }
    if ($form['api_key'] === '') {
        $errors[] = 'Ingresa la API Key de Flow.';
    }
    if ($form['secret_key'] === '') {
        $errors[] = 'Ingresa la Secret Key de Flow.';
    }
    if ($form['public_url'] === '' || filter_var($form['public_url'], FILTER_VALIDATE_URL) === false) {
        $errors[] = 'Ingresa una URL pública válida.';
    } else {
        $parts = parse_url($form['public_url']);
        if (strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) {
            $errors[] = 'La URL pública debe utilizar HTTPS.';
        }
    }
    if ($form['payment_method'] < 1) {
        $errors[] = 'El método de pago debe ser un número válido.';
    }
    if ($form['timeout'] < 0 || $form['timeout'] > 2592000) {
        $errors[] = 'El tiempo de expiración debe estar entre 0 y 2.592.000 segundos.';
    }

    if ($errors === []) {
        try {
            save_local_configuration(dirname(__DIR__, 2) . '/config/flow/flow.local.php', $form);
            audit_log('updated', 'flow_settings', null, 'Configuración de Flow Checkout actualizada');
            flash('success', 'Flow Checkout fue actualizado correctamente.');
            redirect_admin('configuracion/flow.php');
        } catch (Throwable $exception) {
            error_log('No se pudo guardar Flow: ' . $exception->getMessage());
            $errors[] = 'No fue posible guardar. Revisa los permisos de la carpeta config/flow.';
        }
    }
}

$pageTitle = 'Flow Checkout';
$activeMenu = 'flow';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading">
    <div>
        <span class="page-heading__eyebrow">Pasarela de pago</span>
        <h1>Flow Checkout</h1>
        <p>Configura las credenciales y el ambiente utilizados por el checkout de Flow.</p>
    </div>
    <span class="settings-state <?= flow_is_configured() ? 'is-active' : 'is-inactive' ?>">
        <?= flow_is_configured() ? 'Flow configurado' : 'Configuración pendiente' ?>
    </span>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert"><strong>Revisa la configuración:</strong><ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" action="<?= e(admin_url('configuracion/flow.php')) ?>" autocomplete="off">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-xl-8">
            <section class="form-section">
                <div class="form-section__heading"><h2>Credenciales de Flow</h2><p>Las claves se guardan en un archivo local protegido y excluido de Git.</p></div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="environment">AMBIENTE</label>
                        <select class="form-select" id="environment" name="environment">
                            <option value="sandbox"<?= $form['environment'] === 'sandbox' ? ' selected' : '' ?>>Sandbox / pruebas</option>
                            <option value="production"<?= $form['environment'] === 'production' ? ' selected' : '' ?>>Producción</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="public_url">URL PÚBLICA DEL SITIO</label>
                        <input class="form-control" id="public_url" name="public_url" type="url" value="<?= e($form['public_url']) ?>" placeholder="https://gocreative.cl" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="api_key">API KEY</label>
                        <input class="form-control font-monospace" id="api_key" name="api_key" value="<?= e($form['api_key']) ?>" required spellcheck="false">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="secret_key">SECRET KEY</label>
                        <div class="input-group">
                            <input class="form-control font-monospace" id="secret_key" name="secret_key" type="password" value="<?= e($form['secret_key']) ?>" required spellcheck="false">
                            <button class="btn btn-outline-secondary" type="button" onclick="const f=document.getElementById('secret_key'); f.type=f.type==='password'?'text':'password';">Mostrar</button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="form-section settings-side-card">
                <div class="form-section__heading"><h2>Checkout</h2><p>Parámetros operativos de la orden de pago.</p></div>
                <label class="form-label" for="payment_method">MÉTODO DE PAGO</label>
                <input class="form-control" id="payment_method" name="payment_method" type="number" min="1" value="<?= e((string) $form['payment_method']) ?>">
                <div class="form-text mb-3">9 permite que Flow muestre las opciones disponibles en Checkout.</div>
                <label class="form-label" for="timeout">EXPIRACIÓN (SEGUNDOS)</label>
                <input class="form-control" id="timeout" name="timeout" type="number" min="0" max="2592000" value="<?= e((string) $form['timeout']) ?>">
                <div class="settings-note mt-3"><strong>Seguridad</strong><p>La Secret Key nunca debe publicarse en el repositorio. Este módulo escribe config/flow/flow.local.php, que ya está ignorado por Git.</p></div>
                <button class="btn btn-primary w-100 mt-3" type="submit">Guardar Flow</button>
            </section>
        </div>
    </div>
</form>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
