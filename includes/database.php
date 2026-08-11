<?php
declare(strict_types=1);

/**
 * Central MySQL connection for the administration panel.
 *
 * Credentials live in an ignored database.local.php file or in GC_DB_*
 * environment variables. They are never stored in the repository.
 */
function database_config(): array
{
    $localConfigPath = __DIR__ . '/database.local.php';
    if (is_file($localConfigPath)) {
        $localConfig = require $localConfigPath;
        if (!is_array($localConfig)) {
            throw new RuntimeException('includes/database.local.php debe retornar un arreglo de configuración.');
        }
        foreach (['host', 'port', 'name', 'user', 'password'] as $requiredKey) {
            if (!array_key_exists($requiredKey, $localConfig)) {
                throw new RuntimeException('Falta la clave ' . $requiredKey . ' en includes/database.local.php.');
            }
        }
        return $localConfig;
    }

    $databaseUser = getenv('GC_DB_USER');
    $databasePassword = getenv('GC_DB_PASSWORD');
    if ($databaseUser === false || $databasePassword === false) {
        throw new RuntimeException('Configura includes/database.local.php o las variables GC_DB_* antes de usar el panel.');
    }

    $host = getenv('GC_DB_HOST');
    $port = getenv('GC_DB_PORT');
    $name = getenv('GC_DB_NAME');

    return [
        'host' => $host === false ? '127.0.0.1' : $host,
        'port' => $port === false ? '3306' : $port,
        'name' => $name === false ? 'gocreative' : $name,
        'user' => $databaseUser,
        'password' => $databasePassword,
    ];
}

function db(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $config = database_config();
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $config['name']
    );

    $connection = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ]);

    return $connection;
}
