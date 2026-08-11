<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__,2).'/app/Quotes/quote_helpers.php';
require_permission('quotes.view');
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);$quote=$id?quote_find_by_id((int)$id):null;
if(!$quote){http_response_code(404);exit('Cotización no encontrada.');}
$pdf=quote_pdf_binary($quote,quote_items((int)$quote['id']));
$filename='Cotizacion-'.preg_replace('/[^A-Za-z0-9_-]/','-',$quote['quote_number']).'.pdf';
header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.$filename.'"');header('Content-Length: '.strlen($pdf));header('X-Content-Type-Options: nosniff');echo $pdf;exit;
