<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/config.php';require_once dirname(__DIR__).'/config/database/connection.php';require_once dirname(__DIR__).'/app/Quotes/quote_helpers.php';
if(session_status()===PHP_SESSION_NONE){session_name('gocreative_quote');session_set_cookie_params(['lifetime'=>0,'path'=>site_path('/cotizacion/'),'secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);session_start();}
header('X-Robots-Tag:noindex,nofollow,noarchive',true);header('Cache-Control:no-store,max-age=0');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: POST');exit('Método no permitido.');}
$csrf=(string)($_POST['csrf_token']??'');if(empty($_SESSION['quote_csrf'])||!hash_equals((string)$_SESSION['quote_csrf'],$csrf)){http_response_code(419);exit('La sesión expiró. Vuelve a abrir la cotización.');}
$code=strtolower(trim((string)($_POST['codigo']??'')));$action=(string)($_POST['action']??'');$quote=quote_find_by_public_key($code);
if(!$quote||!in_array($action,['accepted','rejected'],true)){http_response_code(400);exit('Solicitud no válida.');}
if(strtotime($quote['valid_until'])<strtotime(date('Y-m-d'))){http_response_code(409);exit('Esta cotización ya venció.');}
if(!in_array($quote['status'],['draft','sent',$action],true)){http_response_code(409);exit('La cotización ya tiene una respuesta.');}
$update=db()->prepare('UPDATE quotes SET status=:status,responded_at=NOW() WHERE id=:id');$update->execute(['status'=>$action,'id'=>$quote['id']]);unset($_SESSION['quote_csrf']);header('Location:'.site_path('/cotizacion/?codigo='.rawurlencode($quote['public_key']).'&respuesta=ok'),true,303);exit;
