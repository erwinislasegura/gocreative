<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/app/Mail/HtmlMailer.php';
require_once dirname(__DIR__) . '/app/Mail/email_templates.php';

use GoCreative\Mail\HtmlMailer;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function fail(string $status = 'error'): void
{
    $allowed = ['error', 'captcha'];
    header('Location: ' . site_path('/contacto/?estado=' . (in_array($status, $allowed, true) ? $status : 'error')));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail();
}

if (!empty($_POST['website'])) {
    header('Location: ' . site_path('/contacto/?estado=ok'));
    exit;
}

$token = (string) ($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    fail();
}

$recaptchaConfiguration = recaptcha_config();
if ((bool) $recaptchaConfiguration['protect_contact']) {
    $recaptchaToken = (string) ($_POST['g-recaptcha-response'] ?? '');
    if (!recaptcha_verify($recaptchaToken, (string) ($_SERVER['REMOTE_ADDR'] ?? ''))) {
        fail('captcha');
    }
}

$nombre = trim(strip_tags((string) ($_POST['nombre'] ?? '')));
$empresa = trim(strip_tags((string) ($_POST['empresa'] ?? '')));
$email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$telefono = preg_replace('/[^0-9+()\-\s]/', '', (string) ($_POST['telefono'] ?? ''));
$servicio = trim(strip_tags((string) ($_POST['servicio'] ?? 'Otro')));
$mensaje = trim(strip_tags((string) ($_POST['mensaje'] ?? '')));

if ($nombre === '' || !$email || $telefono === '' || $mensaje === '' || mb_strlen($mensaje) > 2500) {
    fail();
}

$subject = 'Nueva consulta web: ' . $servicio;
$content = '<p style="margin:0 0 20px;color:#4e6068;font-size:15px;line-height:1.7">Se recibió una nueva solicitud desde el formulario público de gocreative.cl.</p>'
    . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 22px;border:1px solid #d7e0da">'
    . '<tr><td style="padding:11px 14px;color:#718087;font-size:11px;border-bottom:1px solid #e2e8e4">NOMBRE</td><td style="padding:11px 14px;font-size:13px;font-weight:700;border-bottom:1px solid #e2e8e4">' . email_escape($nombre) . '</td></tr>'
    . '<tr><td style="padding:11px 14px;color:#718087;font-size:11px;border-bottom:1px solid #e2e8e4">EMPRESA</td><td style="padding:11px 14px;font-size:13px;border-bottom:1px solid #e2e8e4">' . email_escape($empresa !== '' ? $empresa : 'No indicada') . '</td></tr>'
    . '<tr><td style="padding:11px 14px;color:#718087;font-size:11px;border-bottom:1px solid #e2e8e4">CORREO</td><td style="padding:11px 14px;font-size:13px;border-bottom:1px solid #e2e8e4">' . email_escape((string) $email) . '</td></tr>'
    . '<tr><td style="padding:11px 14px;color:#718087;font-size:11px;border-bottom:1px solid #e2e8e4">TELÉFONO</td><td style="padding:11px 14px;font-size:13px;border-bottom:1px solid #e2e8e4">' . email_escape($telefono) . '</td></tr>'
    . '<tr><td style="padding:11px 14px;color:#718087;font-size:11px">SERVICIO</td><td style="padding:11px 14px;font-size:13px;font-weight:700">' . email_escape($servicio) . '</td></tr></table>'
    . '<div style="padding:18px;color:#24363e;background:#eef4f0;border-left:4px solid #8bea38;font-size:14px;line-height:1.7">' . nl2br(email_escape($mensaje)) . '</div>'
    . '<p style="margin:18px 0 0;color:#7b888d;font-size:11px">Responde manualmente a ' . email_escape((string) $email) . ' para continuar la conversación.</p>';

try {
    $mailer = new HtmlMailer(SITE_EMAIL, 'Go Creative Web');
    $mailer->send(SITE_EMAIL, $subject, email_layout('Nueva consulta desde gocreative.cl', 'Formulario de contacto', 'Nueva solicitud comercial.', $content), [], (string) $email);
} catch (Throwable $exception) {
    error_log('No se pudo enviar el formulario de contacto: ' . $exception->getMessage());
    fail();
}

unset($_SESSION['csrf_token']);
$_SESSION['contact_success_event'] = true;
header('Location: ' . site_path('/contacto/?estado=ok'));
exit;
