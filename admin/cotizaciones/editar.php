<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Quotes/quote_helpers.php';
require_once __DIR__ . '/form_support.php';

$currentAdmin = require_permission('quotes.edit');
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT); $quote = $id ? quote_find_by_id((int) $id) : null;
if (!$quote) { flash('warning', 'La cotización no existe.'); redirect_admin('cotizaciones/'); }
$catalog = db()->query("SELECT * FROM catalog_items WHERE status = 'active' ORDER BY sort_order, name")->fetchAll();
$form = [
    'customer_name' => $quote['customer_name'], 'customer_email' => $quote['customer_email'], 'customer_company' => $quote['customer_company'] ?? '',
    'customer_tax_id' => $quote['customer_tax_id'] ?? '', 'customer_phone' => $quote['customer_phone'] ?? '',
    'customer_address' => $quote['customer_address'] ?? '', 'customer_city' => $quote['customer_city'] ?? '',
    'title' => $quote['title'], 'introduction' => $quote['introduction'] ?? '', 'issue_date' => $quote['issue_date'],
    'valid_until' => $quote['valid_until'], 'discount_amount' => (string) $quote['discount_amount'], 'tax_percent' => (string) $quote['tax_percent'],
    'terms' => $quote['terms'] ?? '', 'notes' => $quote['notes'] ?? '', 'send_now' => '0',
];
$items = quote_items((int) $quote['id']); $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); $form = quote_form_from_post();
    try { $items = quote_prepare_items($_POST); } catch (Throwable $exception) { $items = quote_form_items_from_post(); $errors[] = $exception->getMessage(); }
    $errors = array_merge($errors, quote_form_errors($form));
    if ($errors === []) {
        try {
            quote_save(quote_form_payload($form), $items, (int) $currentAdmin['id'], (int) $quote['id']);
            audit_log('updated', 'quote', (int) $quote['id'], 'Cotización comercial actualizada');
            $message = 'Cotización actualizada.';
            if ($form['send_now'] === '1' && can('quotes.send')) {
                try { $fresh = quote_find_by_id((int) $quote['id']); if ($fresh) quote_send($fresh); $message = 'Cotización actualizada y reenviada.'; }
                catch (Throwable $mailException) { error_log('Cotización actualizada, pero no enviada: ' . $mailException->getMessage()); flash('warning', 'Los cambios se guardaron, pero el correo no pudo enviarse.'); }
            }
            flash('success', $message); redirect_admin('cotizaciones/ver.php?id=' . (int) $quote['id']);
        } catch (Throwable $exception) { error_log('Error editando cotización: ' . $exception->getMessage()); $errors[] = $exception instanceof PDOException ? 'No se pudo guardar la cotización.' : $exception->getMessage(); }
    }
}
$pageTitle = 'Editar cotización'; $activeMenu = 'quotes'; $formAction = admin_url('cotizaciones/editar.php?id=' . (int) $quote['id']); $isEdit = true; $pageScripts = ['quotes.js?v=1.0.0'];
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading"><div><span class="page-heading__eyebrow"><?= e($quote['quote_number']) ?></span><h1>Editar cotización</h1><p>Actualiza alcance, valores o condiciones y genera una nueva versión del PDF.</p></div></div>
<?php require __DIR__ . '/_form.php'; require dirname(__DIR__) . '/includes/footer.php'; ?>
