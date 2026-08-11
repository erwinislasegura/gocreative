<?php
declare(strict_types=1);

/**
 * Writes a PHP configuration file atomically and restricts its permissions.
 */
function save_local_configuration(string $path, array $values): void
{
    $directory = dirname($path);
    if (!is_dir($directory) || !is_writable($directory)) {
        throw new RuntimeException('La carpeta de configuración no tiene permisos de escritura.');
    }

    $contents = "<?php\ndeclare(strict_types=1);\n\n// Generado desde el panel privado de Go Creative. No publicar en Git.\nreturn "
        . var_export($values, true)
        . ";\n";
    // The temporary file also ends in .php so a web server never serves its
    // source as plain text during the atomic replacement.
    $temporaryPath = $path . '.tmp-' . bin2hex(random_bytes(6)) . '.php';

    try {
        $bytes = file_put_contents($temporaryPath, $contents, LOCK_EX);
        if ($bytes === false || $bytes !== strlen($contents)) {
            throw new RuntimeException('No fue posible escribir la configuración completa.');
        }
        @chmod($temporaryPath, 0600);
        if (!rename($temporaryPath, $path)) {
            throw new RuntimeException('No fue posible activar el nuevo archivo de configuración.');
        }
        @chmod($path, 0600);
    } finally {
        if (is_file($temporaryPath)) {
            @unlink($temporaryPath);
        }
    }
}
