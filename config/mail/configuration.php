<?php
declare(strict_types=1);

/**
 * Central outgoing email configuration.
 *
 * The panel writes mail.local.php, which is ignored by Git. Environment
 * variables take priority and are useful on managed production servers.
 */
function mail_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $local = [];
    $localFile = __DIR__ . '/mail.local.php';
    if (is_file($localFile)) {
        $loaded = require $localFile;
        if (!is_array($loaded)) {
            throw new RuntimeException('config/mail/mail.local.php debe retornar un arreglo.');
        }
        $local = $loaded;
    }

    $config = array_replace([
        'transport' => 'mail',
        'host' => '',
        'port' => 587,
        'encryption' => 'tls',
        'username' => '',
        'password' => '',
        'from_email' => 'contacto@gocreative.cl',
        'from_name' => 'Go Creative',
        'timeout' => 12,
    ], $local);

    $environment = [
        'transport' => getenv('GC_MAIL_TRANSPORT'),
        'host' => getenv('GC_SMTP_HOST'),
        'port' => getenv('GC_SMTP_PORT'),
        'encryption' => getenv('GC_SMTP_ENCRYPTION'),
        'username' => getenv('GC_SMTP_USERNAME'),
        'password' => getenv('GC_SMTP_PASSWORD'),
        'from_email' => getenv('GC_MAIL_FROM_EMAIL'),
        'from_name' => getenv('GC_MAIL_FROM_NAME'),
        'timeout' => getenv('GC_SMTP_TIMEOUT'),
    ];
    foreach ($environment as $key => $value) {
        if (is_string($value) && trim($value) !== '') {
            $config[$key] = $value;
        }
    }

    $config['transport'] = in_array(strtolower((string) $config['transport']), ['mail', 'smtp'], true)
        ? strtolower((string) $config['transport'])
        : 'mail';
    $config['host'] = trim((string) $config['host']);
    $config['port'] = max(1, min(65535, (int) $config['port']));
    $config['encryption'] = in_array(strtolower((string) $config['encryption']), ['tls', 'ssl', 'none'], true)
        ? strtolower((string) $config['encryption'])
        : 'tls';
    $config['username'] = trim((string) $config['username']);
    $config['password'] = (string) $config['password'];
    $config['from_email'] = trim((string) $config['from_email']);
    $config['from_name'] = trim((string) $config['from_name']);
    $config['timeout'] = max(3, min(30, (int) $config['timeout']));

    return $config;
}

function mail_uses_smtp(?array $config = null): bool
{
    $config ??= mail_config();
    return ($config['transport'] ?? 'mail') === 'smtp';
}

function mail_smtp_is_configured(?array $config = null): bool
{
    $config ??= mail_config();
    return mail_uses_smtp($config)
        && trim((string) ($config['host'] ?? '')) !== ''
        && filter_var((string) ($config['from_email'] ?? ''), FILTER_VALIDATE_EMAIL) !== false;
}

function mail_environment_overrides(): array
{
    $overrides = [];
    foreach ([
        'GC_MAIL_TRANSPORT', 'GC_SMTP_HOST', 'GC_SMTP_PORT',
        'GC_SMTP_ENCRYPTION', 'GC_SMTP_USERNAME', 'GC_SMTP_PASSWORD',
        'GC_MAIL_FROM_EMAIL', 'GC_MAIL_FROM_NAME', 'GC_SMTP_TIMEOUT',
    ] as $name) {
        $value = getenv($name);
        if (is_string($value) && trim($value) !== '') {
            $overrides[] = $name;
        }
    }

    return $overrides;
}
