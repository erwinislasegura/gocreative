<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Quotes/quote_helpers.php';
require_once __DIR__ . '/form_support.php';

$currentAdmin = require_permission('quotes.create');
$catalog = db()->query("SELECT * FROM catalog_items WHERE status = 'active' ORDER BY sort_order, name")->fetchAll();
$form = quote_form_defaults();
$items = [['item_type' => 'service', 'name' => '', 'description' => '', 'quantity' => '1', 'unit_price' => '0']];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); $form = quote_form_from_post();
    try { $items = quote_prepare_items($_POST); } catch (Throwable $exception) { $items = quote_form_items_from_post(); $errors[] = $exception->getMessage(); }
    $errors = array_merge($errors, quote_form_errors($form));
    if ($errors === []) {
        try {
            $id = quote_save(quote_form_payload($form), $items, (int) $currentAdmin['id']);
            audit_log('created', 'quote', $id, 'Cotización comercial creada');
            $message = 'Cotización creada correctamente.';
            if ($form['send_now'] === '1' && can('quotes.send')) {
                try { $quote = quote_find_by_id($id); if ($quote) quote_send($quote); $message = 'Cotización creada y enviada con su PDF.'; }
                catch (Throwable $mailException) { error_log('Cotización creada, pero no enviada: ' . $mailException->getMessage()); flash('warning', 'La cotización se guardó, pero el servidor no pudo enviar el correo. Puedes reenviarlo desde el detalle.'); }
            }
            flash('success', $message); redirect_admin('cotizaciones/ver.php?id=' . $id);
        } catch (Throwable $exception) {
            error_log('Error creando cotización: ' . $exception->getMessage());
            $errors[] = $exception instanceof PDOException ? 'No se pudo guardar. Revisa la migración comercial.' : $exception->getMessage();
        }
    }
}
$pageTitle = 'Nueva cotización'; $activeMenu = 'quotes'; $formAction = admin_url('cotizaciones/crear.php'); $isEdit = false; $pageScripts = ['quotes.js?v=1.0.0'];
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading"><div><span class="page-heading__eyebrow">Propuestas comerciales</span><h1>Nueva cotización</h1><p>Construye una propuesta clara, visual y lista para enviar en pocos minutos.</p></div></div>
<?php require __DIR__ . '/_form.php'; require dirname(__DIR__) . '/includes/footer.php'; ?>
