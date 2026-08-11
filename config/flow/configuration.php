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

function flow_is_configured(): bool
{
    try {
        $config = flow_config();
    } catch (Throwable $exception) {
        return false;
    }

    $placeholders = ['REEMPLAZA_CON_TU_API_KEY', 'REEMPLAZA_CON_TU_SECRET_KEY'];
    return $config['api_key'] !== ''
        && $config['secret_key'] !== ''
        && $config['public_url'] !== ''
        && !in_array($config['api_key'], $placeholders, true)
        && !in_array($config['secret_key'], $placeholders, true)
        && extension_loaded('curl');
}

function flow_client(): FlowClient
{
    if (!flow_is_configured()) {
        throw new RuntimeException('Flow aun no esta configurado o la extension cURL no esta habilitada.');
    }

    $config = flow_config();
    return new FlowClient($config['api_url'], $config['api_key'], $config['secret_key']);
}
