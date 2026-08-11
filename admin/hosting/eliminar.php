<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Services/hosting_helpers.php';

$currentAdmin = require_permission('hosting.delete');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

verify_csrf();
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$service = $id ? hosting_find_by_id((int) $id) : null;
if ($service === null) {
    flash('warning', 'El servicio de hosting ya no existe.');
    redirect_admin('hosting/');
}

$activeCheckout = db()->prepare(
    "SELECT COUNT(*)
     FROM payment_orders
     WHERE reference_type = 'hosting'
       AND reference_id = :id
       AND status IN ('created', 'pending')
       AND (expires_at IS NULL OR expires_at > NOW())"
);
$activeCheckout->execute(['id' => $service['id']]);
if ((int) $activeCheckout->fetchColumn() > 0) {
    flash('warning', 'No se eliminó el hosting porque tiene un checkout Flow vigente. Espera su vencimiento o confirma primero el pago.');
    redirect_admin('hosting/ver.php?id=' . (int) $service['id']);
}

$serviceLabel = (string) ($service['domain'] ?: $service['service_name']);
db()->beginTransaction();
try {
    $detachOrders = db()->prepare(
        "UPDATE payment_orders
         SET reference_type = 'hosting_deleted'
         WHERE reference_type = 'hosting' AND reference_id = :id"
    );
    $detachOrders->execute(['id' => $service['id']]);

    $delete = db()->prepare('DELETE FROM hosting_services WHERE id = :id');
    $delete->execute(['id' => $service['id']]);
    if ($delete->rowCount() !== 1) {
        throw new RuntimeException('El registro cambió antes de completar la eliminación.');
    }

    audit_log(
        'deleted',
        'hosting_service',
        (int) $service['id'],
        'Hosting eliminado: ' . $serviceLabel . ' por ' . $currentAdmin['email']
    );
    db()->commit();
    flash('success', 'El hosting ' . $serviceLabel . ' fue eliminado correctamente.');
} catch (Throwable $exception) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    error_log('Error eliminando hosting: ' . $exception->getMessage());
    flash('danger', 'No fue posible eliminar el hosting. Inténtalo nuevamente.');
}

redirect_admin('hosting/');
