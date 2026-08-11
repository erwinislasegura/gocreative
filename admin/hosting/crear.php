<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Services/hosting_helpers.php';
require_once __DIR__ . '/form_support.php';

$currentAdmin = require_permission('hosting.create');
$form = hosting_form_defaults();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form = hosting_form_from_post();
    $errors = hosting_form_errors($form);
    if ($errors === []) {
        try {
            $id = hosting_save(hosting_form_payload($form), (int) $currentAdmin['id']);
            audit_log('created', 'hosting_service', $id, 'Servicio de hosting registrado: ' . ($form['domain'] ?: $form['customer_email']));
            flash('success', 'Servicio registrado. Ya puedes gestionar sus avisos de renovación.');
            redirect_admin('hosting/ver.php?id=' . $id);
        } catch (Throwable $exception) {
            error_log('Error al crear hosting: ' . $exception->getMessage());
            $errors[] = $exception instanceof PDOException ? 'No se pudo guardar. Revisa la migración comercial.' : $exception->getMessage();
        }
    }
}
$pageTitle = 'Registrar hosting'; $activeMenu = 'hosting'; $formAction = admin_url('hosting/crear.php'); $isEdit = false;
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading"><div><span class="page-heading__eyebrow">Hosting y renovaciones</span><h1>Nuevo servicio</h1><p>Registra el cliente, el plan y la próxima fecha de cobro.</p></div></div>
<?php require __DIR__ . '/_form.php'; require dirname(__DIR__) . '/includes/footer.php'; ?>
