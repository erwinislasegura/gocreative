<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/Payments/FlowClient.php';

use GoCreative\Payments\FlowClient;

/**
 * Loads Flow credentials from an ignored local file or GC_FLOW_* variables.
 * The API host is selected internally and cannot be overridden by user input.
 */
function flow_config(): array
{
    static $config = null;

    if (is_array($config)) {
        return $config;
    }

    $localPath = __DIR__ . '/flow.local.php';
    if (is_file($localPath)) {
        $local = require $localPath;
        if (!is_array($local)) {
            throw new RuntimeException('config/flow/flow.local.php debe retornar un arreglo de configuracion.');
        }
        $raw = $local;
    } else {
        $raw = [
            'environment' => getenv('GC_FLOW_ENVIRONMENT') ?: 'sandbox',
            'api_key' => getenv('GC_FLOW_API_KEY') ?: '',
            'secret_key' => getenv('GC_FLOW_SECRET_KEY') ?: '',
            'public_url' => getenv('GC_FLOW_PUBLIC_URL') ?: (defined('SITE_URL') ? SITE_URL : ''),
            'payment_method' => getenv('GC_FLOW_PAYMENT_METHOD') ?: '9',
            'timeout' => getenv('GC_FLOW_TIMEOUT') ?: '172800',
        ];
    }

    $environment = strtolower(trim((string) ($raw['environment'] ?? 'sandbox')));
    if (!in_array($environment, ['sandbox', 'production'], true)) {
        throw new RuntimeException('GC_FLOW_ENVIRONMENT debe ser sandbox o production.');
    }

    $publicUrl = rtrim(trim((string) ($raw['public_url'] ?? '')), '/');
    if ($publicUrl !== '' && filter_var($publicUrl, FILTER_VALIDATE_URL) === false) {
        throw new RuntimeException('public_url de Flow debe ser una URL valida.');
    }
    $publicParts = $publicUrl !== '' ? parse_url($publicUrl) : [];
    if ($publicUrl !== '' && (
        strtolower((string) ($publicParts['scheme'] ?? '')) !== 'https'
        || empty($publicParts['host'])
        || isset($publicParts['user'])
        || isset($publicParts['pass'])
        || isset($publicParts['query'])
        || isset($publicParts['fragment'])
    )) {
        throw new RuntimeException('public_url de Flow debe usar HTTPS y no incluir credenciales.');
    }

    $paymentMethod = filter_var($raw['payment_method'] ?? 9, FILTER_VALIDATE_INT);
    $timeout = filter_var($raw['timeout'] ?? 172800, FILTER_VALIDATE_INT);

    $config = [
        'environment' => $environment,
        'api_url' => $environment === 'production'
            ? 'https://www.flow.cl/api'
            : 'https://sandbox.flow.cl/api',
        'api_key' => trim((string) ($raw['api_key'] ?? '')),
        'secret_key' => trim((string) ($raw['secret_key'] ?? '')),
        'public_url' => $publicUrl,
        'payment_method' => $paymentMethod !== false && $paymentMethod > 0 ? $paymentMethod : 9,
        'timeout' => $timeout !== false && $timeout >= 0 ? min($timeout, 2592000) : 172800,
    ];

    return $config;
}

/**
 * Returns a detailed diagnostic instead of collapsing every problem into
 * "Flow not configured". This is also used by the admin screen.
 */
function flow_configuration_status(): array
{
    try {
        $config = flow_config();
    } catch (Throwable $exception) {
        return [
            'configured' => false,
            'credentials' => false,
            'curl' => extension_loaded('curl') && function_exists('curl_init'),
            'message' => $exception->getMessage(),
        ];
    }

    $placeholders = ['REEMPLAZA_CON_TU_API_KEY', 'REEMPLAZA_CON_TU_SECRET_KEY'];
    $credentials = $config['api_key'] !== ''
        && $config['secret_key'] !== ''
        && $config['public_url'] !== ''
        && !in_array($config['api_key'], $placeholders, true)
        && !in_array($config['secret_key'], $placeholders, true);
    $curl = extension_loaded('curl') && function_exists('curl_init');

    if (!$credentials) {
        $message = 'Faltan credenciales de Flow o la URL pública del sitio.';
    } elseif (!$curl) {
        $message = 'Las credenciales están guardadas, pero PHP cURL no está habilitado en el servidor.';
    } else {
        $message = 'Flow está listo para crear checkout.';
    }

    return [
        'configured' => $credentials && $curl,
        'credentials' => $credentials,
        'curl' => $curl,
        'message' => $message,
    ];
}

function flow_is_configured(): bool
{
    return (bool) flow_configuration_status()['configured'];
}

function flow_client(): FlowClient
{
    $status = flow_configuration_status();
    if (!$status['configured']) {
        throw new RuntimeException((string) $status['message']);
    }

    $config = flow_config();
    return new FlowClient($config['api_url'], $config['api_key'], $config['secret_key']);
}
