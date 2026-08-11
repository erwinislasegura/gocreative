<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/configuration.php';

$currentAdmin = require_permission('settings.manage');
$configuration = analytics_config();
$environmentOverrides = analytics_environment_overrides();
$errors = [];
$form = [
    'enabled' => (bool) $configuration['enabled'],
    'tag_id' => (string) $configuration['tag_id'],
    'account_id' => (string) $configuration['account_id'],
    'property_id' => (string) $configuration['property_id'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form = [
        'enabled' => isset($_POST['enabled']),
        'tag_id' => strtoupper(trim((string) ($_POST['tag_id'] ?? ''))),
        'account_id' => trim((string) ($_POST['account_id'] ?? '')),
        'property_id' => trim((string) ($_POST['property_id'] ?? '')),
    ];

    if ($form['tag_id'] !== '' && !preg_match('/^(?:G|GT)-[A-Z0-9]{5,30}$/', $form['tag_id'])) {
        $errors[] = 'La etiqueta debe comenzar con G- o GT- y contener solamente letras y números.';
    }
    if ($form['enabled'] && $form['tag_id'] === '') {
        $errors[] = 'Ingresa la etiqueta de Google antes de activar Analytics.';
    }
    if ($form['account_id'] !== '' && !preg_match('/^\d{4,30}$/', $form['account_id'])) {
        $errors[] = 'El ID de cuenta debe contener solamente números.';
    }
    if ($form['property_id'] !== '' && !preg_match('/^\d{4,30}$/', $form['property_id'])) {
        $errors[] = 'El ID de propiedad debe contener solamente números.';
    }

    if ($errors === []) {
        try {
            save_local_configuration(dirname(__DIR__, 2) . '/config/analytics/analytics.local.php', $form);
            audit_log('updated', 'analytics_settings', null, 'Configuración de Google Analytics actualizada');
            flash('success', 'Google Analytics fue actualizado correctamente.');
            redirect_admin('configuracion/analytics.php');
        } catch (Throwable $exception) {
            error_log('No se pudo guardar Analytics: ' . $exception->getMessage());
            $errors[] = 'No fue posible guardar. Revisa los permisos de la carpeta config/analytics.';
        }
    }
}

$pageTitle = 'Google Analytics';
$activeMenu = 'analytics';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading">
    <div>
        <span class="page-heading__eyebrow">Medición del sitio</span>
        <h1>Google Analytics</h1>
        <p>Administra la etiqueta que se carga centralmente en todas las páginas públicas.</p>
    </div>
    <span class="settings-state <?= $form['enabled'] && $form['tag_id'] !== '' ? 'is-active' : 'is-inactive' ?>">
        <?= $form['enabled'] && $form['tag_id'] !== '' ? 'Medición activa' : 'Medición detenida' ?>
    </span>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert"><strong>Revisa la configuración:</strong><ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<?php if ($environmentOverrides !== []): ?>
    <div class="alert alert-warning" role="alert">Las variables <?= e(implode(', ', $environmentOverrides)) ?> tienen prioridad sobre los valores guardados en esta pantalla.</div>
<?php endif; ?>

<form method="post" action="<?= e(admin_url('configuracion/analytics.php')) ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-xl-8">
            <section class="form-section">
                <div class="form-section__heading"><h2>Identificadores de medición</h2><p>La etiqueta pública se instala una sola vez desde el encabezado central del proyecto.</p></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="tag_id">GOOGLE TAG ID</label>
                        <input class="form-control font-monospace" id="tag_id" name="tag_id" value="<?= e($form['tag_id']) ?>" maxlength="33" placeholder="GT-TXZH8NNL" autocomplete="off">
                        <div class="form-text">Identificador G- o GT- que entrega Google.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="account_id">ID DE CUENTA</label>
                        <input class="form-control font-monospace" id="account_id" name="account_id" inputmode="numeric" value="<?= e($form['account_id']) ?>" maxlength="30" placeholder="161497159">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="property_id">ID DE PROPIEDAD</label>
                        <input class="form-control font-monospace" id="property_id" name="property_id" inputmode="numeric" value="<?= e($form['property_id']) ?>" maxlength="30" placeholder="490278227">
                    </div>
                </div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="form-section settings-side-card">
                <div class="form-section__heading"><h2>Estado</h2><p>Detén la medición sin eliminar los identificadores.</p></div>
                <label class="settings-switch" for="enabled">
                    <span><strong>Analytics activo</strong><small>Carga gtag.js en el sitio público.</small></span>
                    <input class="form-check-input" type="checkbox" role="switch" id="enabled" name="enabled" value="1"<?= $form['enabled'] ? ' checked' : '' ?>>
                </label>
                <div class="settings-note"><strong>Privacidad</strong><p>El panel y las cotizaciones privadas quedan fuera de la medición. El evento del formulario no envía nombre, correo ni teléfono.</p></div>
                <button class="btn btn-primary w-100 mt-3" type="submit">Guardar Analytics</button>
            </section>
        </div>
    </div>
</form>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
