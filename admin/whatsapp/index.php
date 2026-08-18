<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/config/whatsapp/configuration.php';
$currentAdmin = require_permission('whatsapp.view');

$query = trim((string) ($_GET['q'] ?? ''));
$mode = (string) ($_GET['mode'] ?? '');
$params = [];
$where = ["wc.status = 'open'"];
if ($query !== '') {
    $where[] = '(ct.profile_name LIKE :query OR ct.wa_id LIKE :query)';
    $params['query'] = '%' . $query . '%';
}
if (in_array($mode, ['bot', 'human'], true)) {
    $where[] = 'wc.mode = :mode';
    $params['mode'] = $mode;
}
$sql = "SELECT wc.*, ct.wa_id, ct.profile_name, ct.opt_out,
               (SELECT body FROM whatsapp_messages wm WHERE wm.conversation_id = wc.id ORDER BY wm.id DESC LIMIT 1) AS last_body,
               (SELECT direction FROM whatsapp_messages wm WHERE wm.conversation_id = wc.id ORDER BY wm.id DESC LIMIT 1) AS last_direction
        FROM whatsapp_conversations wc
        INNER JOIN whatsapp_contacts ct ON ct.id = wc.contact_id
        WHERE " . implode(' AND ', $where) . ' ORDER BY wc.last_message_at DESC, wc.id DESC LIMIT 200';
$statement = db()->prepare($sql);
$statement->execute($params);
$conversations = $statement->fetchAll();
$stats = db()->query(
    "SELECT COUNT(*) AS open_count,
            SUM(status = 'open' AND mode = 'human') AS human_count,
            SUM(status = 'open' AND unread_count > 0) AS unread_count
     FROM whatsapp_conversations"
)->fetch();
$leadStats = db()->query("SELECT COUNT(*) AS total, SUM(status = 'new') AS new_count FROM whatsapp_leads")->fetch();
$waStatus = whatsapp_configuration_status();

$pageTitle = 'WhatsApp';
$activeMenu = 'whatsapp';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading"><div><span class="page-heading__eyebrow">Atención y oportunidades</span><h1>WhatsApp</h1><p>Revisa conversaciones, toma el control del bot y gestiona los clientes captados.</p></div><div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-dark" href="<?= e(admin_url('whatsapp/conocimiento.php')) ?>">Respuestas</a><a class="btn btn-primary" href="<?= e(admin_url('whatsapp/leads.php')) ?>">Ver oportunidades</a></div></div>
<?php if (!$waStatus['configured']): ?><div class="alert alert-warning" role="alert"><strong>Integración pendiente.</strong> <?= e($waStatus['message']) ?> <?php if (can('whatsapp.manage')): ?><a href="<?= e(admin_url('whatsapp/configuracion.php')) ?>">Completar configuración</a><?php endif; ?></div><?php endif; ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><section class="admin-card stat-card stat-card--dark"><span class="stat-card__label">Conversaciones</span><strong class="stat-card__value"><?= (int) $stats['open_count'] ?></strong><span class="stat-card__note">Abiertas</span></section></div>
    <div class="col-6 col-xl-3"><section class="admin-card stat-card <?= (int) $stats['unread_count'] > 0 ? 'stat-card--danger' : '' ?>"><span class="stat-card__label">Sin revisar</span><strong class="stat-card__value"><?= (int) $stats['unread_count'] ?></strong><span class="stat-card__note">Requieren atención</span></section></div>
    <div class="col-6 col-xl-3"><section class="admin-card stat-card"><span class="stat-card__label">Con asesor</span><strong class="stat-card__value"><?= (int) $stats['human_count'] ?></strong><span class="stat-card__note">Bot pausado</span></section></div>
    <div class="col-6 col-xl-3"><section class="admin-card stat-card"><span class="stat-card__label">Oportunidades</span><strong class="stat-card__value"><?= (int) $leadStats['total'] ?></strong><span class="stat-card__note"><?= (int) $leadStats['new_count'] ?> nuevas</span></section></div>
</div>
<section class="admin-card">
    <form class="filter-bar filter-bar--whatsapp" method="get" action="<?= e(admin_url('whatsapp/')) ?>"><input class="form-control" type="search" name="q" value="<?= e($query) ?>" placeholder="Nombre o número"><select class="form-select" name="mode"><option value="">Bot y asesores</option><option value="bot"<?= $mode === 'bot' ? ' selected' : '' ?>>Atiende el bot</option><option value="human"<?= $mode === 'human' ? ' selected' : '' ?>>Atiende un asesor</option></select><div class="d-flex gap-2"><button class="btn btn-dark" type="submit">Filtrar</button><a class="btn btn-outline-dark" href="<?= e(admin_url('whatsapp/')) ?>">Limpiar</a></div></form>
    <div class="whatsapp-list">
        <?php if ($conversations === []): ?><div class="admin-empty py-5"><h2>No hay conversaciones abiertas</h2><p>Los mensajes aparecerán aquí cuando Meta entregue el primer webhook.</p></div><?php endif; ?>
        <?php foreach ($conversations as $item): ?>
            <a class="whatsapp-list__item" href="<?= e(admin_url('whatsapp/conversacion.php?id=' . (int) $item['id'])) ?>">
                <span class="user-cell__avatar"><?= e(initials($item['profile_name'] ?: $item['wa_id'])) ?></span>
                <span class="whatsapp-list__copy"><strong><?= e($item['profile_name'] ?: ('+' . $item['wa_id'])) ?></strong><small><?= e(mb_substr((string) ($item['last_body'] ?: 'Mensaje no textual'), 0, 120)) ?></small></span>
                <span class="whatsapp-list__meta"><span class="status-badge <?= $item['mode'] === 'bot' ? 'status-badge--active' : 'status-badge--inactive' ?>"><?= $item['mode'] === 'bot' ? 'Bot' : 'Asesor' ?></span><time><?= $item['last_message_at'] ? e(date('d-m H:i', strtotime($item['last_message_at']))) : '' ?></time><?php if ((int) $item['unread_count'] > 0): ?><b><?= (int) $item['unread_count'] ?></b><?php endif; ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
