<?php
declare(strict_types=1);

/**
 * WhatsApp Cloud API configuration.
 *
 * Secrets belong in whatsapp.local.php (ignored by Git) or GC_WHATSAPP_*
 * environment variables. Environment variables always take priority.
 */
function whatsapp_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $local = [];
    $localFile = __DIR__ . '/whatsapp.local.php';
    if (is_file($localFile)) {
        $loaded = require $localFile;
        if (!is_array($loaded)) {
            throw new RuntimeException('config/whatsapp/whatsapp.local.php debe retornar un arreglo.');
        }
        $local = $loaded;
    }

    $config = array_replace([
        'enabled' => false,
        'graph_version' => 'v26.0',
        'phone_number_id' => '',
        'business_account_id' => '',
        'access_token' => '',
        'verify_token' => '',
        'app_secret' => '',
        'timezone' => 'America/Santiago',
        'business_days' => [1, 2, 3, 4, 5],
        'business_start' => '09:00',
        'business_end' => '18:00',
        'business_name' => 'Go Creative',
        'greeting' => "¡Hola! 👋 Soy el asistente de Go Creative. Te ayudaré a cotizar tu proyecto.",
        'outside_hours_reply' => 'Ahora estamos fuera de horario, pero puedo registrar tus datos. Un asesor continuará contigo en el próximo horario hábil.',
        'handoff_reply' => 'Perfecto. Dejé la conversación asignada a un asesor de Go Creative, quien continuará por este mismo WhatsApp.',
        'fallback_reply' => 'No alcancé a entenderlo. Responde con el número de una opción o escribe “asesor” para hablar con una persona.',
    ], $local);

    $environment = [
        'enabled' => getenv('GC_WHATSAPP_ENABLED'),
        'graph_version' => getenv('GC_WHATSAPP_GRAPH_VERSION'),
        'phone_number_id' => getenv('GC_WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => getenv('GC_WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'access_token' => getenv('GC_WHATSAPP_ACCESS_TOKEN'),
        'verify_token' => getenv('GC_WHATSAPP_VERIFY_TOKEN'),
        'app_secret' => getenv('GC_WHATSAPP_APP_SECRET'),
        'timezone' => getenv('GC_WHATSAPP_TIMEZONE'),
        'business_start' => getenv('GC_WHATSAPP_BUSINESS_START'),
        'business_end' => getenv('GC_WHATSAPP_BUSINESS_END'),
    ];
    foreach ($environment as $key => $value) {
        if (is_string($value) && trim($value) !== '') {
            $config[$key] = $value;
        }
    }

    $config['enabled'] = filter_var($config['enabled'], FILTER_VALIDATE_BOOLEAN);
    $config['graph_version'] = preg_match('/^v\d+\.\d+$/', (string) $config['graph_version'])
        ? (string) $config['graph_version'] : 'v26.0';
    foreach (['phone_number_id', 'business_account_id', 'access_token', 'verify_token', 'app_secret', 'business_name'] as $key) {
        $config[$key] = trim((string) $config[$key]);
    }
    $config['business_days'] = array_values(array_unique(array_filter(
        array_map('intval', is_array($config['business_days']) ? $config['business_days'] : []),
        static fn (int $day): bool => $day >= 1 && $day <= 7
    )));
    foreach (['business_start', 'business_end'] as $key) {
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', (string) $config[$key])) {
            $config[$key] = $key === 'business_start' ? '09:00' : '18:00';
        }
    }
    try {
        new DateTimeZone((string) $config['timezone']);
    } catch (Throwable $exception) {
        $config['timezone'] = 'America/Santiago';
    }

    return $config;
}

function whatsapp_is_configured(?array $config = null): bool
{
    $config ??= whatsapp_config();
    return $config['enabled']
        && $config['phone_number_id'] !== ''
        && $config['access_token'] !== ''
        && $config['verify_token'] !== ''
        && $config['app_secret'] !== ''
        && extension_loaded('curl');
}

function whatsapp_configuration_status(?array $config = null): array
{
    $config ??= whatsapp_config();
    $missing = [];
    foreach (['phone_number_id' => 'ID del número', 'access_token' => 'token de acceso', 'verify_token' => 'token de verificación', 'app_secret' => 'App Secret'] as $key => $label) {
        if ($config[$key] === '') $missing[] = $label;
    }
    if (!extension_loaded('curl')) $missing[] = 'extensión PHP cURL';

    return [
        'configured' => whatsapp_is_configured($config),
        'enabled' => (bool) $config['enabled'],
        'missing' => $missing,
        'message' => $missing === [] ? 'Credenciales y servidor disponibles.' : 'Falta: ' . implode(', ', $missing) . '.',
    ];
}

function whatsapp_environment_overrides(): array
{
    $active = [];
    foreach (['GC_WHATSAPP_ENABLED', 'GC_WHATSAPP_GRAPH_VERSION', 'GC_WHATSAPP_PHONE_NUMBER_ID', 'GC_WHATSAPP_BUSINESS_ACCOUNT_ID', 'GC_WHATSAPP_ACCESS_TOKEN', 'GC_WHATSAPP_VERIFY_TOKEN', 'GC_WHATSAPP_APP_SECRET', 'GC_WHATSAPP_TIMEZONE', 'GC_WHATSAPP_BUSINESS_START', 'GC_WHATSAPP_BUSINESS_END'] as $name) {
        $value = getenv($name);
        if (is_string($value) && trim($value) !== '') $active[] = $name;
    }
    return $active;
}
