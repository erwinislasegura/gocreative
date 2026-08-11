<?php
declare(strict_types=1);

namespace GoCreative\Mail;

use RuntimeException;

final class SmtpTransport
{
    /** @var resource|null */
    private $socket = null;
    private int $timeout = 12;

    public function send(array $config, string $fromEmail, string $to, string $rawMessage): void
    {
        $host = trim((string) ($config['host'] ?? ''));
        $port = (int) ($config['port'] ?? 587);
        $encryption = strtolower((string) ($config['encryption'] ?? 'tls'));
        $username = trim((string) ($config['username'] ?? ''));
        $password = (string) ($config['password'] ?? '');
        $this->timeout = max(3, min(30, (int) ($config['timeout'] ?? 12)));

        if ($host === '' || preg_match('/[\r\n]/', $host) || $port < 1 || $port > 65535) {
            throw new RuntimeException('La dirección o el puerto SMTP no son válidos.');
        }
        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            throw new RuntimeException('El tipo de cifrado SMTP no es válido.');
        }
        if ($username !== '' && $password === '') {
            throw new RuntimeException('Falta la contraseña de la cuenta SMTP.');
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'peer_name' => $host,
                'SNI_enabled' => true,
            ],
        ]);
        $scheme = $encryption === 'ssl' ? 'ssl' : 'tcp';
        $errorNumber = 0;
        $errorMessage = '';
        $socket = @stream_socket_client(
            $scheme . '://' . $host . ':' . $port,
            $errorNumber,
            $errorMessage,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (!is_resource($socket)) {
            throw new RuntimeException('No fue posible conectar con el servidor SMTP (' . $errorNumber . ').');
        }
        $this->socket = $socket;
        stream_set_timeout($this->socket, $this->timeout);

        try {
            $this->expect([220]);
            $clientName = preg_replace('/[^a-z0-9.-]/i', '', (string) gethostname()) ?: 'localhost';
            $this->command('EHLO ' . $clientName, [250]);

            if ($encryption === 'tls') {
                $this->command('STARTTLS', [220]);
                if (!@stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('No fue posible establecer el cifrado TLS con el servidor SMTP.');
                }
                $this->command('EHLO ' . $clientName, [250]);
            }

            if ($username !== '') {
                $this->command('AUTH LOGIN', [334]);
                $this->command(base64_encode($username), [334], true);
                $this->command(base64_encode($password), [235], true);
            }

            $this->command('MAIL FROM:<' . $fromEmail . '>', [250]);
            $this->command('RCPT TO:<' . $to . '>', [250, 251]);
            $this->command('DATA', [354]);

            $normalized = str_replace(["\r\n", "\r"], "\n", $rawMessage);
            $normalized = str_replace("\n", "\r\n", $normalized);
            $normalized = preg_replace('/^\./m', '..', $normalized) ?? $normalized;
            $this->write(rtrim($normalized, "\r\n") . "\r\n.\r\n");
            $this->expect([250]);
            try {
                $this->command('QUIT', [221]);
            } catch (RuntimeException $exception) {
                // The message was already accepted with code 250. A server may
                // close the connection before answering QUIT without affecting
                // delivery, so this must not create a false failed notice.
            }
        } finally {
            if (is_resource($this->socket)) {
                fclose($this->socket);
            }
            $this->socket = null;
        }
    }

    private function command(string $command, array $expectedCodes, bool $sensitive = false): void
    {
        $this->write($command . "\r\n");
        try {
            $this->expect($expectedCodes);
        } catch (RuntimeException $exception) {
            if ($sensitive) {
                throw new RuntimeException('El servidor SMTP rechazó las credenciales configuradas.');
            }
            throw $exception;
        }
    }

    private function expect(array $expectedCodes): void
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('La conexión SMTP no está disponible.');
        }

        $response = '';
        $code = 0;
        do {
            $line = fgets($this->socket, 1024);
            if ($line === false) {
                $metadata = stream_get_meta_data($this->socket);
                throw new RuntimeException(!empty($metadata['timed_out'])
                    ? 'El servidor SMTP agotó el tiempo de espera.'
                    : 'El servidor SMTP cerró la conexión inesperadamente.');
            }
            $response .= $line;
            if (preg_match('/^(\d{3})([ -])/', $line, $matches)) {
                $code = (int) $matches[1];
                $finished = $matches[2] === ' ';
            } else {
                $finished = true;
            }
        } while (!$finished);

        if (!in_array($code, $expectedCodes, true)) {
            $summary = trim(preg_replace('/\s+/', ' ', $response) ?? 'respuesta desconocida');
            throw new RuntimeException('SMTP respondió ' . $code . ': ' . mb_substr($summary, 0, 220));
        }
    }

    private function write(string $data): void
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('La conexión SMTP no está disponible.');
        }

        $length = strlen($data);
        $written = 0;
        while ($written < $length) {
            $bytes = fwrite($this->socket, substr($data, $written));
            if ($bytes === false || $bytes === 0) {
                throw new RuntimeException('No fue posible transferir el mensaje al servidor SMTP.');
            }
            $written += $bytes;
        }
    }
}
