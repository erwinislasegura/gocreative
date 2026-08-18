<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
$currentAdmin = require_permission('whatsapp.view');
$allowed = ['new', 'contacted', 'qualified', 'won', 'lost'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('whatsapp.manage'); verify_csrf();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT); $status = (string) ($_POST['status'] ?? '');
    if ($id && in_array($status, $allowed, true)) {
        db()->prepare('UPDATE whatsapp_leads SET status = :status, updated_at = NOW() WHERE id = :id')->execute(['status' => $status, 'id' => $id]);
        audit_log('updated', 'whatsapp_lead', (int) $id, 'Estado de oportunidad actualizado a ' . $status);
        flash('success', 'Estado actualizado.');
    }
    redirect_admin('whatsapp/leads.php');
}
$filter = (string) ($_GET['status'] ?? ''); $params = []; $where = '';
if (in_array($filter, $allowed, true)) { $where = 'WHERE wl.status = :status'; $params['status'] = $filter; }
$statement = db()->prepare("SELECT wl.*, ct.wa_id, ct.profile_name FROM whatsapp_leads wl INNER JOIN whatsapp_contacts ct ON ct.id = wl.contact_id {$where} ORDER BY wl.created_at DESC LIMIT 500"); $statement->execute($params); $leads = $statement->fetchAll();
$labels = ['new'=>'Nueva','contacted'=>'Contactada','qualified'=>'Calificada','won'=>'Ganada','lost'=>'Perdida'];
$pageTitle = 'Oportunidades de WhatsApp'; $activeMenu = 'whatsapp'; require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading"><div><span class="page-heading__eyebrow">Embudo comercial</span><h1>Oportunidades</h1><p>Clientes calificados automáticamente desde las conversaciones de WhatsApp.</p></div><a class="btn btn-outline-dark" href="<?= e(admin_url('whatsapp/')) ?>">← Conversaciones</a></div>
<section class="admin-card"><form class="filter-bar" method="get"><select class="form-select" name="status"><option value="">Todos los estados</option><?php foreach ($labels as $value=>$label): ?><option value="<?= e($value) ?>"<?= $filter===$value?' selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select><button class="btn btn-dark" type="submit">Filtrar</button></form><div class="table-responsive"><table class="table align-middle admin-table-cards"><thead><tr><th>Cliente</th><th>Servicio</th><th>Presupuesto</th><th>Plazo</th><th>Estado</th><th class="text-end">Conversación</th></tr></thead><tbody><?php if ($leads===[]): ?><tr class="admin-table-empty"><td colspan="6">No hay oportunidades en este estado.</td></tr><?php endif; ?><?php foreach ($leads as $lead): ?><tr><td data-label="Cliente"><strong><?= e($lead['name']) ?></strong><small class="d-block text-secondary">+<?= e($lead['wa_id']) ?></small></td><td data-label="Servicio"><?= e($lead['service']) ?></td><td data-label="Presupuesto"><?= e((string)$lead['budget']) ?></td><td data-label="Plazo"><?= e((string)$lead['timeframe']) ?></td><td data-label="Estado"><?php if (can('whatsapp.manage')): ?><form method="post" class="d-flex gap-2"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$lead['id'] ?>"><select class="form-select form-select-sm" name="status" onchange="this.form.submit()"><?php foreach($labels as $value=>$label): ?><option value="<?= e($value) ?>"<?= $lead['status']===$value?' selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></form><?php else: ?><?= e($labels[$lead['status']] ?? $lead['status']) ?><?php endif; ?></td><td data-label="Conversación" class="text-end"><a class="btn btn-sm btn-outline-dark" href="<?= e(admin_url('whatsapp/conversacion.php?id='.(int)$lead['conversation_id'])) ?>">Abrir</a></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
