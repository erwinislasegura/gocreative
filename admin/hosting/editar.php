<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Services/hosting_helpers.php';
require_once __DIR__ . '/form_support.php';

$currentAdmin = require_permission('hosting.edit');
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$service = $id ? hosting_find_by_id((int) $id) : null;
if (!$service) { flash('warning', 'El servicio no existe.'); redirect_admin('hosting/'); }
$form = [
    'customer_name' => $service['customer_name'], 'customer_email' => $service['customer_email'],
    'customer_company' => $service['customer_company'] ?? '', 'customer_phone' => $service['customer_phone'] ?? '',
    'service_name' => $service['service_name'], 'domain' => $service['domain'] ?? '', 'plan_name' => $service['plan_name'],
    'billing_cycle' => $service['billing_cycle'], 'start_date' => $service['start_date'], 'due_date' => $service['due_date'],
    'amount' => (string) $service['amount'], 'status' => $service['status'], 'notes' => $service['notes'] ?? '',
];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); $form = hosting_form_from_post(); $errors = hosting_form_errors($form);
    if ($errors === []) {
        try {
            hosting_save(hosting_form_payload($form), (int) $currentAdmin['id'], (int) $service['id']);
            audit_log('updated', 'hosting_service', (int) $service['id'], 'Servicio de hosting actualizado');
            flash('success', 'Servicio actualizado correctamente.');
            redirect_admin('hosting/ver.php?id=' . (int) $service['id']);
        } catch (Throwable $exception) {
            error_log('Error al editar hosting: ' . $exception->getMessage());
            $errors[] = $exception instanceof PDOException ? 'No se pudo guardar. Revisa la base de datos.' : $exception->getMessage();
        }
    }
}
$pageTitle = 'Editar hosting'; $activeMenu = 'hosting'; $formAction = admin_url('hosting/editar.php?id=' . (int) $service['id']); $isEdit = true;
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading"><div><span class="page-heading__eyebrow">Hosting y renovaciones</span><h1>Editar servicio</h1><p><?= e($service['domain'] ?: $service['service_name']) ?></p></div></div>
<?php require __DIR__ . '/_form.php'; require dirname(__DIR__) . '/includes/footer.php'; ?>
