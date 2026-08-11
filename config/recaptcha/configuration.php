<?php
declare(strict_types=1);

/**
 * Central reCAPTCHA v2 configuration.
 *
 * Production can use GC_RECAPTCHA_SITE_KEY and GC_RECAPTCHA_SECRET_KEY.
 * XAMPP/cPanel can instead copy recaptcha.example.php to recaptcha.local.php.
 */
function recaptcha_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $local = [];
    $localFile = __DIR__ . '/recaptcha.local.php';
    if (is_file($localFile)) {
        $loaded = require $localFile;
        if (!is_array($loaded)) {
            throw new RuntimeException('config/recaptcha/recaptcha.local.php debe retornar un arreglo.');
        }
        $local = $loaded;
    }

    $config = array_replace([
        'protect_login' => true,
        'protect_contact' => true,
        'site_key' => '',
        'secret_key' => '',
        'allowed_hosts' => ['gocreative.cl', 'www.gocreative.cl', 'localhost', '127.0.0.1'],
        'timeout' => 8,
    ], $local);

    $environmentSiteKey = getenv('GC_RECAPTCHA_SITE_KEY');
    $environmentSecretKey = getenv('GC_RECAPTCHA_SECRET_KEY');
    if (is_string($environmentSiteKey) && trim($environmentSiteKey) !== '') {
        $config['site_key'] = $environmentSiteKey;
    }
    if (is_string($environmentSecretKey) && trim($environmentSecretKey) !== '') {
        $config['secret_key'] = $environmentSecretKey;
    }

    $config['site_key'] = trim((string) $config['site_key']);
    $config['secret_key'] = trim((string) $config['secret_key']);
    $config['protect_login'] = (bool) $config['protect_login'];
    $config['protect_contact'] = (bool) $config['protect_contact'];
    $config['timeout'] = max(2, min(15, (int) $config['timeout']));
    $config['allowed_hosts'] = array_values(array_unique(array_filter(array_map(
        static fn ($host): string => strtolower(trim((string) $host)),
        (array) $config['allowed_hosts']
    ))));

    return $config;
}

function recaptcha_login_enabled(): bool
{
    $config = recaptcha_config();
    return $config['protect_login'] && recaptcha_is_configured();
}

function recaptcha_contact_enabled(): bool
{
    $config = recaptcha_config();
    return $config['protect_contact'] && recaptcha_is_configured();
}

function recaptcha_environment_overrides(): array
{
    $overrides = [];
    foreach (['GC_RECAPTCHA_SITE_KEY', 'GC_RECAPTCHA_SECRET_KEY'] as $name) {
        $value = getenv($name);
        if (is_string($value) && trim($value) !== '') {
            $overrides[] = $name;
        }
    }

    return $overrides;
}

function recaptcha_is_configured(): bool
{
    $config = recaptcha_config();
    return $config['site_key'] !== '' && $config['secret_key'] !== '';
}

function recaptcha_site_key(): string
{
    return (string) recaptcha_config()['site_key'];
}

function recaptcha_verify(string $responseToken, ?string $remoteIp = null): bool
{
    if (!recaptcha_is_configured() || !function_exists('curl_init')) {
        return false;
    }

    $responseToken = trim($responseToken);
    if ($responseToken === '' || strlen($responseToken) > 4096) {
        return false;
    }

    $config = recaptcha_config();
    $payload = [
        'secret' => $config['secret_key'],
        'response' => $responseToken,
    ];
    if ($remoteIp !== null && filter_var($remoteIp, FILTER_VALIDATE_IP)) {
        $payload['remoteip'] = $remoteIp;
    }

    $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
    if ($curl === false) {
        return false;
    }

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload, '', '&', PHP_QUERY_RFC3986),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => $config['timeout'],
        CURLOPT_TIMEOUT => $config['timeout'],
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $rawResponse = curl_exec($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if (!is_string($rawResponse) || $httpCode !== 200) {
        error_log('No fue posible verificar reCAPTCHA con Google.');
        return false;
    }

    $result = json_decode($rawResponse, true);
    if (!is_array($result) || empty($result['success'])) {
        $codes = is_array($result['error-codes'] ?? null) ? implode(',', $result['error-codes']) : 'respuesta-invalida';
        error_log('reCAPTCHA rechazado: ' . $codes);
        return false;
    }

    $hostname = strtolower(trim((string) ($result['hostname'] ?? '')));
    return $hostname !== '' && in_array($hostname, $config['allowed_hosts'], true);
}
