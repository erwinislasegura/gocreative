<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Quotes/quote_helpers.php';

$currentAdmin = require_permission('quotes.delete');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}
verify_csrf();

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$quote = $id ? quote_find_by_id((int) $id) : null;
if ($quote === null) {
    flash('warning', 'La cotización no existe o ya fue eliminada.');
    redirect_admin('cotizaciones/');
}

try {
    quote_delete((int) $quote['id']);
    audit_log(
        'deleted',
        'quote',
        (int) $quote['id'],
        'Cotización ' . $quote['quote_number'] . ' eliminada; cliente conservado: ' . $quote['customer_email']
    );
    flash('success', 'La cotización ' . $quote['quote_number'] . ' fue eliminada. El cliente se conservó.');
} catch (Throwable $exception) {
    error_log('Error eliminando cotización: ' . $exception->getMessage());
    flash('danger', 'No se pudo eliminar la cotización. Inténtalo nuevamente.');
}

redirect_admin('cotizaciones/');
