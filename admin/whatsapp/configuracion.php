<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/configuration.php';
require_once dirname(__DIR__, 2) . '/config/whatsapp/configuration.php';
require_once dirname(__DIR__, 2) . '/app/WhatsApp/WhatsAppClient.php';
$currentAdmin = require_permission('whatsapp.manage');
$config = whatsapp_config();
$errors = [];
$days = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
$form = $config;
$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'test') {
        try {
            $testResult = (new WhatsAppClient($config))->phoneProfile();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    } else {
        $form = [
            'enabled' => isset($_POST['enabled']),
            'graph_version' => trim((string) ($_POST['graph_version'] ?? 'v26.0')),
            'phone_number_id' => preg_replace('/\D+/', '', (string) ($_POST['phone_number_id'] ?? '')) ?? '',
            'business_account_id' => preg_replace('/\D+/', '', (string) ($_POST['business_account_id'] ?? '')) ?? '',
            'access_token' => trim((string) ($_POST['access_token'] ?? '')) ?: (string) $config['access_token'],
            'verify_token' => trim((string) ($_POST['verify_token'] ?? '')) ?: (string) $config['verify_token'],
            'app_secret' => trim((string) ($_POST['app_secret'] ?? '')) ?: (string) $config['app_secret'],
            'timezone' => trim((string) ($_POST['timezone'] ?? 'America/Santiago')),
            'business_days' => array_map('intval', (array) ($_POST['business_days'] ?? [])),
            'business_start' => trim((string) ($_POST['business_start'] ?? '09:00')),
            'business_end' => trim((string) ($_POST['business_end'] ?? '18:00')),
            'business_name' => trim((string) ($_POST['business_name'] ?? 'Go Creative')),
            'greeting' => trim((string) ($_POST['greeting'] ?? '')),
            'outside_hours_reply' => trim((string) ($_POST['outside_hours_reply'] ?? '')),
            'handoff_reply' => trim((string) ($_POST['handoff_reply'] ?? '')),
            'fallback_reply' => trim((string) ($_POST['fallback_reply'] ?? '')),
        ];
        if (!preg_match('/^v\d+\.\d+$/', $form['graph_version'])) $errors[] = 'La versión de Graph API debe tener un formato como v26.0.';
        if ($form['enabled'] && ($form['phone_number_id'] === '' || $form['access_token'] === '' || $form['verify_token'] === '' || $form['app_secret'] === '')) $errors[] = 'Completa ID del número, token de acceso, token de verificación y App Secret para activar.';
        try { new DateTimeZone($form['timezone']); } catch (Throwable $exception) { $errors[] = 'Selecciona una zona horaria válida.'; }
        if (!preg_match('/^\d{2}:\d{2}$/', $form['business_start']) || !preg_match('/^\d{2}:\d{2}$/', $form['business_end']) || $form['business_start'] >= $form['business_end']) $errors[] = 'El horario de inicio debe ser anterior al horario de cierre.';
        foreach (['greeting', 'outside_hours_reply', 'handoff_reply', 'fallback_reply'] as $key) if ($form[$key] === '' || mb_strlen($form[$key]) > 1000) $errors[] = 'Completa las respuestas automáticas (máximo 1.000 caracteres).';
        if ($errors === []) {
            try {
                save_local_configuration(dirname(__DIR__, 2) . '/config/whatsapp/whatsapp.local.php', $form);
                audit_log('updated', 'whatsapp_settings', null, 'Configuración de WhatsApp actualizada');
                flash('success', 'La configuración de WhatsApp fue guardada.');
                redirect_admin('whatsapp/configuracion.php');
            } catch (Throwable $exception) {
                error_log('No se pudo guardar WhatsApp: ' . $exception->getMessage());
                $errors[] = 'No fue posible guardar. Revisa los permisos de config/whatsapp.';
            }
        }
    }
}
$status = whatsapp_configuration_status();
$overrides = whatsapp_environment_overrides();
$webhookUrl = canonical('/whatsapp/webhook.php');
$pageTitle = 'Configurar WhatsApp'; $activeMenu = 'whatsapp-settings';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading"><div><span class="page-heading__eyebrow">WhatsApp Cloud API</span><h1>Configuración</h1><p>Conecta Meta, define el horario y personaliza las respuestas del asistente.</p></div><span class="settings-state <?= $status['configured'] ? 'is-active' : 'is-inactive' ?>"><?= $status['configured'] ? 'Integración activa' : 'Configuración pendiente' ?></span></div>
<?php if ($errors !== []): ?><div class="alert alert-danger"><strong>Revisa estos datos:</strong><ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if ($testResult): ?><div class="alert alert-success"><strong>Conexión correcta.</strong> <?= e((string) ($testResult['verified_name'] ?? 'Número verificado')) ?> · <?= e((string) ($testResult['display_phone_number'] ?? '')) ?> · calidad <?= e((string) ($testResult['quality_rating'] ?? 'sin dato')) ?>.</div><?php endif; ?>
<?php if ($overrides !== []): ?><div class="alert alert-info">Estas variables del servidor tienen prioridad: <code><?= e(implode(', ', $overrides)) ?></code>.</div><?php endif; ?>
<form method="post" action="<?= e(admin_url('whatsapp/configuracion.php')) ?>" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="action" value="save">
<div class="row g-3"><div class="col-xl-8">
    <section class="form-section"><div class="form-section__heading"><h2>Credenciales de Meta</h2><p>Los secretos se guardan localmente y nunca se publican en Git.</p></div>
        <div class="form-check form-switch mb-3"><input class="form-check-input" id="enabled" name="enabled" type="checkbox"<?= $form['enabled'] ? ' checked' : '' ?>><label class="form-check-label fw-bold" for="enabled">Activar respuestas automáticas</label></div>
        <div class="row g-3"><div class="col-md-4"><label class="form-label" for="graph_version">GRAPH API</label><input class="form-control" id="graph_version" name="graph_version" value="<?= e((string) $form['graph_version']) ?>" required></div><div class="col-md-4"><label class="form-label" for="phone_number_id">ID DEL NÚMERO</label><input class="form-control" id="phone_number_id" name="phone_number_id" inputmode="numeric" value="<?= e((string) $form['phone_number_id']) ?>"></div><div class="col-md-4"><label class="form-label" for="business_account_id">ID WABA</label><input class="form-control" id="business_account_id" name="business_account_id" inputmode="numeric" value="<?= e((string) $form['business_account_id']) ?>"></div>
        <div class="col-12"><label class="form-label" for="access_token">TOKEN DE ACCESO</label><input class="form-control font-monospace" id="access_token" name="access_token" type="password" placeholder="<?= $config['access_token'] !== '' ? 'Guardado; deja vacío para conservarlo' : 'Token permanente del usuario del sistema' ?>" autocomplete="new-password"></div><div class="col-md-6"><label class="form-label" for="verify_token">TOKEN DE VERIFICACIÓN</label><input class="form-control font-monospace" id="verify_token" name="verify_token" type="password" placeholder="<?= $config['verify_token'] !== '' ? 'Guardado; deja vacío para conservarlo' : 'Crea una frase secreta larga' ?>" autocomplete="new-password"></div><div class="col-md-6"><label class="form-label" for="app_secret">APP SECRET</label><input class="form-control font-monospace" id="app_secret" name="app_secret" type="password" placeholder="<?= $config['app_secret'] !== '' ? 'Guardado; deja vacío para conservarlo' : 'Secreto de la app de Meta' ?>" autocomplete="new-password"></div></div>
    </section>
    <section class="form-section"><div class="form-section__heading"><h2>Mensajes automáticos</h2><p>La calificación de servicio, presupuesto, plazo y nombre ya viene incorporada.</p></div>
        <label class="form-label" for="greeting">SALUDO</label><textarea class="form-control mb-3" id="greeting" name="greeting" rows="3" maxlength="1000" required><?= e((string) $form['greeting']) ?></textarea><label class="form-label" for="outside_hours_reply">FUERA DE HORARIO</label><textarea class="form-control mb-3" id="outside_hours_reply" name="outside_hours_reply" rows="3" maxlength="1000" required><?= e((string) $form['outside_hours_reply']) ?></textarea><label class="form-label" for="handoff_reply">DERIVACIÓN A ASESOR</label><textarea class="form-control mb-3" id="handoff_reply" name="handoff_reply" rows="3" maxlength="1000" required><?= e((string) $form['handoff_reply']) ?></textarea><label class="form-label" for="fallback_reply">RESPUESTA NO ENTENDIDA</label><textarea class="form-control" id="fallback_reply" name="fallback_reply" rows="3" maxlength="1000" required><?= e((string) $form['fallback_reply']) ?></textarea>
    </section>
</div><div class="col-xl-4">
    <section class="form-section settings-side-card"><div class="form-section__heading"><h2>Webhook de Meta</h2><p>Copia exactamente esta dirección.</p></div><label class="form-label">URL DE DEVOLUCIÓN</label><input class="form-control font-monospace mb-2" value="<?= e($webhookUrl) ?>" readonly><p class="form-text">En Meta selecciona el campo <strong>messages</strong>. El token de verificación es el que guardas aquí.</p></section>
    <section class="form-section settings-side-card"><div class="form-section__heading"><h2>Horario</h2><p>Zona horaria y atención humana.</p></div><label class="form-label" for="timezone">ZONA HORARIA</label><input class="form-control mb-3" id="timezone" name="timezone" value="<?= e((string) $form['timezone']) ?>" required><div class="row g-2 mb-3"><div class="col-6"><label class="form-label" for="business_start">INICIO</label><input class="form-control" id="business_start" name="business_start" type="time" value="<?= e((string) $form['business_start']) ?>"></div><div class="col-6"><label class="form-label" for="business_end">CIERRE</label><input class="form-control" id="business_end" name="business_end" type="time" value="<?= e((string) $form['business_end']) ?>"></div></div><div class="whatsapp-days"><?php foreach ($days as $number => $label): ?><label><input type="checkbox" name="business_days[]" value="<?= $number ?>"<?= in_array($number, $form['business_days'], true) ? ' checked' : '' ?>> <?= e($label) ?></label><?php endforeach; ?></div><label class="form-label mt-3" for="business_name">NOMBRE DEL NEGOCIO</label><input class="form-control" id="business_name" name="business_name" value="<?= e((string) $form['business_name']) ?>" maxlength="120"></section>
    <button class="btn btn-primary w-100 mb-2" type="submit">Guardar configuración</button>
</div></div></form>
<?php if ($config['phone_number_id'] !== '' && $config['access_token'] !== ''): ?><form method="post" action="<?= e(admin_url('whatsapp/configuracion.php')) ?>" class="mt-3"><?= csrf_field() ?><input type="hidden" name="action" value="test"><button class="btn btn-outline-dark" type="submit">Probar conexión con Meta</button></form><?php endif; ?>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
