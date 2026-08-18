<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/config/whatsapp/configuration.php';
require_once dirname(__DIR__, 2) . '/app/WhatsApp/WhatsAppClient.php';
require_once dirname(__DIR__, 2) . '/app/WhatsApp/WhatsAppRepository.php';
$currentAdmin = require_permission('whatsapp.view');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { flash('warning', 'Selecciona una conversación válida.'); redirect_admin('whatsapp/'); }

$loadConversation = static function (int $id): ?array {
    $statement = db()->prepare('SELECT wc.*, ct.wa_id, ct.profile_name, ct.opt_out, u.name AS assigned_name FROM whatsapp_conversations wc INNER JOIN whatsapp_contacts ct ON ct.id = wc.contact_id LEFT JOIN users u ON u.id = wc.assigned_to WHERE wc.id = :id');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
};
$conversation = $loadConversation((int) $id);
if (!$conversation) { flash('warning', 'La conversación ya no existe.'); redirect_admin('whatsapp/'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('whatsapp.manage'); verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $repository = new WhatsAppRepository(db());
    try {
        if ($action === 'human') {
            $repository->setMode((int) $id, 'human', (int) $currentAdmin['id']);
            audit_log('updated', 'whatsapp_conversation', (int) $id, 'Conversación asignada a atención humana');
            flash('success', 'El bot quedó pausado y la conversación fue asignada.');
        } elseif ($action === 'bot') {
            $repository->setMode((int) $id, 'bot');
            audit_log('updated', 'whatsapp_conversation', (int) $id, 'Bot reactivado en conversación');
            flash('success', 'El bot volverá a responder el próximo mensaje.');
        } elseif ($action === 'close') {
            $statement = db()->prepare("UPDATE whatsapp_conversations SET status = 'closed', unread_count = 0, updated_at = NOW() WHERE id = :id");
            $statement->execute(['id' => $id]);
            audit_log('closed', 'whatsapp_conversation', (int) $id, 'Conversación cerrada');
            flash('success', 'La conversación fue cerrada. El siguiente mensaje abrirá una nueva.');
            redirect_admin('whatsapp/');
        } elseif ($action === 'send') {
            $body = trim((string) ($_POST['body'] ?? ''));
            if ($body === '' || mb_strlen($body) > 4096) throw new RuntimeException('Escribe un mensaje de hasta 4.096 caracteres.');
            $lastInbound = db()->prepare("SELECT created_at FROM whatsapp_messages WHERE conversation_id = :id AND direction = 'incoming' ORDER BY id DESC LIMIT 1");
            $lastInbound->execute(['id' => $id]);
            $date = $lastInbound->fetchColumn();
            if (!$date || strtotime((string) $date) < time() - 86400) throw new RuntimeException('La ventana gratuita de atención de 24 horas terminó. Para iniciar otra conversación debes usar una plantilla aprobada por Meta.');
            $repository->setMode((int) $id, 'human', (int) $currentAdmin['id']);
            try {
                $result = (new WhatsAppClient(whatsapp_config()))->sendText((string) $conversation['wa_id'], $body);
                $messageId = isset($result['messages'][0]['id']) ? (string) $result['messages'][0]['id'] : null;
                $repository->recordOutgoing((int) $id, $body, $messageId);
                audit_log('sent', 'whatsapp_conversation', (int) $id, 'Respuesta enviada por WhatsApp');
                flash('success', 'Mensaje enviado por WhatsApp.');
            } catch (Throwable $exception) {
                $repository->recordOutgoing((int) $id, $body, null, 'failed', mb_substr($exception->getMessage(), 0, 500));
                throw $exception;
            }
        }
    } catch (Throwable $exception) {
        error_log('Acción WhatsApp fallida: ' . $exception->getMessage());
        flash('danger', $exception->getMessage());
    }
    redirect_admin('whatsapp/conversacion.php?id=' . (int) $id);
}

db()->prepare('UPDATE whatsapp_conversations SET unread_count = 0 WHERE id = :id')->execute(['id' => $id]);
$messagesStatement = db()->prepare('SELECT * FROM whatsapp_messages WHERE conversation_id = :id ORDER BY id ASC LIMIT 500');
$messagesStatement->execute(['id' => $id]);
$messages = $messagesStatement->fetchAll();
$leadStatement = db()->prepare('SELECT * FROM whatsapp_leads WHERE conversation_id = :id LIMIT 1');
$leadStatement->execute(['id' => $id]);
$lead = $leadStatement->fetch();
$lastIncoming = null;
foreach ($messages as $message) if ($message['direction'] === 'incoming') $lastIncoming = $message['created_at'];
$windowOpen = $lastIncoming && strtotime((string) $lastIncoming) >= time() - 86400;

$pageTitle = $conversation['profile_name'] ?: ('+' . $conversation['wa_id']); $activeMenu = 'whatsapp';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading"><div><span class="page-heading__eyebrow">Conversación #<?= (int) $conversation['id'] ?></span><h1><?= e($conversation['profile_name'] ?: ('+' . $conversation['wa_id'])) ?></h1><p>+<?= e($conversation['wa_id']) ?> · <?= $windowOpen ? 'ventana de atención disponible' : 'ventana de 24 horas cerrada' ?></p></div><a class="btn btn-outline-dark" href="<?= e(admin_url('whatsapp/')) ?>">← Conversaciones</a></div>
<div class="whatsapp-conversation-layout"><section class="admin-card whatsapp-thread"><div class="admin-card__header"><h2>Mensajes</h2><span class="status-badge <?= $conversation['mode'] === 'bot' ? 'status-badge--active' : 'status-badge--inactive' ?>"><?= $conversation['mode'] === 'bot' ? 'Atiende el bot' : 'Atención humana' ?></span></div><div class="whatsapp-thread__messages">
<?php if ($messages === []): ?><p class="text-secondary text-center py-5">Aún no hay mensajes.</p><?php endif; ?>
<?php foreach ($messages as $message): ?><article class="whatsapp-message whatsapp-message--<?= e($message['direction']) ?>"><div><?= nl2br(e((string) ($message['body'] ?: '[' . $message['message_type'] . ']'))) ?></div><footer><time><?= e(date('d-m-Y H:i', strtotime($message['created_at']))) ?></time><?php if ($message['direction'] === 'outgoing'): ?><span><?= e($message['status']) ?></span><?php endif; ?></footer><?php if ($message['error_message']): ?><small><?= e($message['error_message']) ?></small><?php endif; ?></article><?php endforeach; ?>
</div><?php if (can('whatsapp.manage')): ?><form class="whatsapp-reply" method="post" action="<?= e(admin_url('whatsapp/conversacion.php?id=' . (int) $id)) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="action" value="send"><textarea class="form-control" name="body" rows="3" maxlength="4096" placeholder="Escribe una respuesta..."<?= $windowOpen ? '' : ' disabled' ?> required></textarea><button class="btn btn-primary" type="submit"<?= $windowOpen ? '' : ' disabled' ?>>Enviar</button></form><?php endif; ?></section>
<aside class="whatsapp-sidebar-stack">
    <section class="form-section"><div class="form-section__heading"><h2>Control</h2><p><?= $conversation['assigned_name'] ? 'Asignada a ' . e($conversation['assigned_name']) : 'Sin asesor asignado' ?></p></div><?php if (can('whatsapp.manage')): ?><div class="d-grid gap-2"><?php if ($conversation['mode'] === 'bot'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="action" value="human"><button class="btn btn-dark w-100" type="submit">Tomar conversación</button></form><?php else: ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="action" value="bot"><button class="btn btn-outline-dark w-100" type="submit">Devolver al bot</button></form><?php endif; ?><form method="post" data-confirm="¿Cerrar esta conversación?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="action" value="close"><button class="btn btn-outline-danger w-100" type="submit">Cerrar conversación</button></form></div><?php endif; ?></section>
    <section class="form-section"><div class="form-section__heading"><h2>Calificación</h2><p>Datos reunidos automáticamente.</p></div><?php if ($lead): ?><dl class="whatsapp-lead-summary"><dt>Nombre</dt><dd><?= e($lead['name']) ?></dd><dt>Servicio</dt><dd><?= e($lead['service']) ?></dd><dt>Presupuesto</dt><dd><?= e((string) $lead['budget']) ?></dd><dt>Plazo</dt><dd><?= e((string) $lead['timeframe']) ?></dd><dt>Estado</dt><dd><?= e($lead['status']) ?></dd></dl><?php else: ?><p class="text-secondary mb-0">La calificación todavía no está completa.</p><?php endif; ?></section>
</aside></div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
