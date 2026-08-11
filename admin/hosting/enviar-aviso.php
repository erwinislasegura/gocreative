<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Services/hosting_helpers.php';
$currentAdmin = require_permission('hosting.send');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Método no permitido.'); }
verify_csrf();
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT); $level = filter_var($_POST['level'] ?? null, FILTER_VALIDATE_INT);
$service = $id ? hosting_find_by_id((int) $id) : null;
if (!$service || !in_array((int) $level, [1,2,3], true)) { flash('warning', 'Servicio o aviso no válido.'); redirect_admin('hosting/'); }
try {
    hosting_send_notice($service, (int) $level, (int) $currentAdmin['id']);
    audit_log('notice_sent', 'hosting_service', (int) $service['id'], 'Aviso de hosting nivel ' . (int) $level . ' enviado');
    flash('success', 'Aviso enviado a ' . $service['customer_email'] . ' con el botón de pago Flow.');
} catch (Throwable $exception) {
    error_log('Error enviando aviso de hosting: ' . $exception->getMessage());
    flash('danger', $exception instanceof PDOException ? 'No fue posible registrar el aviso.' : 'No se pudo enviar: ' . $exception->getMessage());
}
redirect_admin('hosting/ver.php?id=' . (int) $service['id']);
