<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/bootstrap.php';require_once dirname(__DIR__,2).'/app/Quotes/quote_helpers.php';
$currentAdmin=require_permission('quotes.send');if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: POST');exit('Método no permitido.');}verify_csrf();
$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);$quote=$id?quote_find_by_id((int)$id):null;if(!$quote){flash('warning','La cotización no existe.');redirect_admin('cotizaciones/');}
try{quote_send($quote);audit_log('sent','quote',(int)$quote['id'],'Cotización enviada a '.$quote['customer_email']);flash('success','Cotización y PDF enviados a '.$quote['customer_email'].'.');}
catch(Throwable $exception){error_log('Error enviando cotización: '.$exception->getMessage());flash('danger','No se pudo enviar el correo: '.$exception->getMessage());}
redirect_admin('cotizaciones/ver.php?id='.(int)$quote['id']);
