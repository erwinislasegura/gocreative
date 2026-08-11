<?php
declare(strict_types=1);

/**
 * Central Google Analytics configuration.
 *
 * Values saved from the panel live in analytics.local.php, which is ignored
 * by Git. Environment variables have priority when they are available.
 */
function analytics_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $local = [];
    $localFile = __DIR__ . '/analytics.local.php';
    if (is_file($localFile)) {
        $loaded = require $localFile;
        if (!is_array($loaded)) {
            throw new RuntimeException('config/analytics/analytics.local.php debe retornar un arreglo.');
        }
        $local = $loaded;
    }

    $config = array_replace([
        'enabled' => true,
        'tag_id' => 'GT-TXZH8NNL',
        'account_id' => '161497159',
        'property_id' => '490278227',
    ], $local);

    $environmentValues = [
        'tag_id' => getenv('GC_ANALYTICS_TAG_ID'),
        'account_id' => getenv('GC_ANALYTICS_ACCOUNT_ID'),
        'property_id' => getenv('GC_ANALYTICS_PROPERTY_ID'),
    ];
    foreach ($environmentValues as $key => $value) {
        if (is_string($value) && trim($value) !== '') {
            $config[$key] = $value;
        }
    }

    $environmentEnabled = getenv('GC_ANALYTICS_ENABLED');
    if (is_string($environmentEnabled) && $environmentEnabled !== '') {
        $config['enabled'] = filter_var($environmentEnabled, FILTER_VALIDATE_BOOL);
    }

    $config['enabled'] = (bool) $config['enabled'];
    $config['tag_id'] = strtoupper(trim((string) $config['tag_id']));
    $config['account_id'] = trim((string) $config['account_id']);
    $config['property_id'] = trim((string) $config['property_id']);

    return $config;
}

function analytics_is_enabled(): bool
{
    $config = analytics_config();
    return $config['enabled'] && $config['tag_id'] !== '';
}

function analytics_environment_overrides(): array
{
    $overrides = [];
    foreach ([
        'GC_ANALYTICS_ENABLED',
        'GC_ANALYTICS_TAG_ID',
        'GC_ANALYTICS_ACCOUNT_ID',
        'GC_ANALYTICS_PROPERTY_ID',
    ] as $name) {
        $value = getenv($name);
        if (is_string($value) && $value !== '') {
            $overrides[] = $name;
        }
    }

    return $overrides;
}
