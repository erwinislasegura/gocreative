<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Services/hosting_helpers.php';
$currentAdmin = require_permission('hosting.edit');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Método no permitido.'); }
verify_csrf();
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT); $service = $id ? hosting_find_by_id((int) $id) : null;
if (!$service) { flash('warning', 'El servicio no existe.'); redirect_admin('hosting/'); }
try { hosting_advance_due_date((int) $service['id']); audit_log('manual_payment', 'hosting_service', (int) $service['id'], 'Renovación manual registrada'); flash('success', 'Pago registrado y próximo vencimiento actualizado.'); }
catch (Throwable $exception) { error_log('Error renovando hosting manualmente: ' . $exception->getMessage()); flash('danger', 'No se pudo actualizar la renovación.'); }
redirect_admin('hosting/ver.php?id=' . (int) $service['id']);
