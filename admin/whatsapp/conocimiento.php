<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
$currentAdmin = require_permission('whatsapp.view');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('whatsapp.manage'); verify_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    try {
        if ($action === 'delete' && $id) {
            db()->prepare('DELETE FROM whatsapp_knowledge WHERE id = :id')->execute(['id' => $id]);
            audit_log('deleted', 'whatsapp_knowledge', (int) $id, 'Respuesta automática eliminada');
        } else {
            $title = trim((string) ($_POST['title'] ?? '')); $keywords = trim((string) ($_POST['keywords'] ?? '')); $answer = trim((string) ($_POST['answer'] ?? '')); $priority = max(0, min(1000, (int) ($_POST['priority'] ?? 100))); $status = isset($_POST['status']) ? 'active' : 'inactive';
            if (mb_strlen($title) < 3 || mb_strlen($title) > 160 || $keywords === '' || mb_strlen($keywords) > 1000 || $answer === '' || mb_strlen($answer) > 4000) throw new RuntimeException('Completa título, palabras clave y respuesta dentro de los límites indicados.');
            if ($id) {
                $statement = db()->prepare('UPDATE whatsapp_knowledge SET title=:title, keywords=:keywords, answer=:answer, priority=:priority, status=:status WHERE id=:id');
                $statement->execute(compact('title', 'keywords', 'answer', 'priority', 'status', 'id'));
                audit_log('updated', 'whatsapp_knowledge', (int) $id, 'Respuesta automática actualizada');
            } else {
                $statement = db()->prepare('INSERT INTO whatsapp_knowledge (title, keywords, answer, priority, status) VALUES (:title,:keywords,:answer,:priority,:status)');
                $statement->execute(compact('title', 'keywords', 'answer', 'priority', 'status'));
                audit_log('created', 'whatsapp_knowledge', (int) db()->lastInsertId(), 'Respuesta automática creada');
            }
        }
        flash('success', 'Las respuestas automáticas fueron actualizadas.');
    } catch (Throwable $exception) { flash('danger', $exception->getMessage()); }
    redirect_admin('whatsapp/conocimiento.php');
}
$items = db()->query('SELECT * FROM whatsapp_knowledge ORDER BY priority DESC, id ASC')->fetchAll();
$pageTitle = 'Respuestas de WhatsApp'; $activeMenu = 'whatsapp'; require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading"><div><span class="page-heading__eyebrow">Base de conocimiento</span><h1>Respuestas automáticas</h1><p>El bot compara estas palabras clave con cada consulta antes de iniciar la calificación.</p></div><a class="btn btn-outline-dark" href="<?= e(admin_url('whatsapp/')) ?>">← WhatsApp</a></div>
<?php if (can('whatsapp.manage')): ?><section class="form-section mb-3"><div class="form-section__heading"><h2>Nueva respuesta</h2><p>Separa sinónimos o frases con comas. Las coincidencias más específicas tienen prioridad.</p></div><form method="post" class="row g-3"><?= csrf_field() ?><input type="hidden" name="action" value="save"><div class="col-md-8"><label class="form-label">TÍTULO</label><input class="form-control" name="title" maxlength="160" required></div><div class="col-md-4"><label class="form-label">PRIORIDAD</label><input class="form-control" name="priority" type="number" min="0" max="1000" value="100"></div><div class="col-12"><label class="form-label">PALABRAS CLAVE</label><input class="form-control" name="keywords" maxlength="1000" placeholder="precio web, cuánto cuesta, valor página" required></div><div class="col-12"><label class="form-label">RESPUESTA</label><textarea class="form-control" name="answer" rows="3" maxlength="4000" required></textarea></div><div class="col-12 d-flex justify-content-between align-items-center"><label class="form-check"><input class="form-check-input" type="checkbox" name="status" checked> Activa</label><button class="btn btn-primary" type="submit">Agregar respuesta</button></div></form></section><?php endif; ?>
<div class="whatsapp-knowledge-grid"><?php foreach ($items as $item): ?><section class="form-section"><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="action" value="save"><div class="d-flex justify-content-between gap-2 mb-2"><input class="form-control fw-bold" name="title" maxlength="160" value="<?= e($item['title']) ?>"<?= can('whatsapp.manage') ? '' : ' readonly' ?> required><input class="form-control whatsapp-priority" name="priority" type="number" min="0" max="1000" value="<?= (int) $item['priority'] ?>"<?= can('whatsapp.manage') ? '' : ' readonly' ?>></div><label class="form-label">PALABRAS CLAVE</label><input class="form-control mb-2" name="keywords" maxlength="1000" value="<?= e($item['keywords']) ?>"<?= can('whatsapp.manage') ? '' : ' readonly' ?> required><label class="form-label">RESPUESTA</label><textarea class="form-control" name="answer" rows="4" maxlength="4000"<?= can('whatsapp.manage') ? '' : ' readonly' ?> required><?= e($item['answer']) ?></textarea><?php if (can('whatsapp.manage')): ?><div class="d-flex justify-content-between align-items-center mt-3"><label class="form-check"><input class="form-check-input" type="checkbox" name="status"<?= $item['status'] === 'active' ? ' checked' : '' ?>> Activa</label><div class="d-flex gap-2"><button class="btn btn-sm btn-primary" type="submit">Guardar</button><button class="btn btn-sm btn-outline-danger" type="submit" name="action" value="delete" data-confirm="¿Eliminar esta respuesta?">Eliminar</button></div></div><?php endif; ?></form></section><?php endforeach; ?></div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
