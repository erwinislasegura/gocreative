<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database/connection.php';
require_once dirname(__DIR__) . '/config/whatsapp/configuration.php';
require_once dirname(__DIR__) . '/app/WhatsApp/WhatsAppClient.php';
require_once dirname(__DIR__) . '/app/WhatsApp/WhatsAppRepository.php';
require_once dirname(__DIR__) . '/app/WhatsApp/WhatsAppBot.php';
require_once dirname(__DIR__) . '/app/WhatsApp/WhatsAppWebhook.php';

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
$config = whatsapp_config();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $challenge = WhatsAppWebhook::verify($_GET, $config);
    if ($challenge === null) {
        http_response_code(403);
        exit('Verification failed');
    }
    header('Content-Type: text/plain; charset=utf-8');
    exit($challenge);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit;
}

$rawBody = file_get_contents('php://input');
if (!is_string($rawBody) || !WhatsAppWebhook::validSignature($rawBody, (string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? ''), (string) $config['app_secret'])) {
    http_response_code(401);
    exit('Invalid signature');
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    http_response_code(400);
    exit('Invalid JSON');
}

// Acknowledge first so Meta does not retry while the shared hosting calls the
// Graph API. The unique message ID still protects against legitimate retries.
ignore_user_abort(true);
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
$acknowledgement = '{"status":"ok"}';
header('Content-Length: ' . strlen($acknowledgement));
header('Connection: close');
echo $acknowledgement;
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
else { @ob_flush(); flush(); }

if ($config['enabled']) {
    try {
        $repository = new WhatsAppRepository(db());
        $client = new WhatsAppClient($config);
        $webhook = new WhatsAppWebhook($repository, new WhatsAppBot($repository, $client, $config));
        $webhook->process($payload);
    } catch (Throwable $exception) {
        error_log('WhatsApp webhook error: ' . $exception->getMessage());
    }
}
