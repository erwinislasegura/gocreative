<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/config.php';require_once dirname(__DIR__).'/config/database/connection.php';require_once dirname(__DIR__).'/app/Quotes/quote_helpers.php';
header('X-Robots-Tag: noindex,nofollow,noarchive',true);header('X-Content-Type-Options: nosniff');header('Cache-Control:no-store,max-age=0');
$code=strtolower(trim((string)($_GET['codigo']??'')));$quote=quote_find_by_public_key($code);if(!$quote){http_response_code(404);exit('Cotización no encontrada.');}
$pdf=quote_pdf_binary($quote,quote_items((int)$quote['id']));$filename='Cotizacion-'.preg_replace('/[^A-Za-z0-9_-]/','-',$quote['quote_number']).'.pdf';header('Content-Type:application/pdf');header('Content-Disposition:attachment; filename="'.$filename.'"');header('Content-Length:'.strlen($pdf));echo $pdf;exit;
