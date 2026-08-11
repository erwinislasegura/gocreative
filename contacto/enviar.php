<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/config.php';

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
$body = "Nueva solicitud desde gocreative.cl\n\n"
    . "Nombre: {$nombre}\n"
    . "Empresa: {$empresa}\n"
    . "Correo: {$email}\n"
    . "Teléfono: {$telefono}\n"
    . "Servicio: {$servicio}\n\n"
    . "Mensaje:\n{$mensaje}\n";

$headers = [
    'From: Go Creative Web <' . SITE_EMAIL . '>',
    'Reply-To: ' . $email,
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . PHP_VERSION,
];

if (!mail(SITE_EMAIL, $subject, $body, implode("\r\n", $headers))) {
    fail();
}

unset($_SESSION['csrf_token']);
$_SESSION['contact_success_event'] = true;
header('Location: ' . site_path('/contacto/?estado=ok'));
exit;
