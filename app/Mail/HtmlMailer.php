<?php
declare(strict_types=1);

namespace GoCreative\Mail;

use RuntimeException;

require_once __DIR__ . '/SmtpTransport.php';

final class HtmlMailer
{
    private string $fromEmail;
    private string $fromName;
    private array $transportConfig;

    public function __construct(string $fromEmail, string $fromName = 'Go Creative', ?array $transportConfig = null)
    {
        $transportConfig ??= function_exists('\\mail_config') ? \mail_config() : ['transport' => 'mail'];
        $configuredFrom = trim((string) ($transportConfig['from_email'] ?? ''));
        if ($configuredFrom !== '') {
            $fromEmail = $configuredFrom;
        }
        $configuredName = trim((string) ($transportConfig['from_name'] ?? ''));
        if ($configuredName !== '') {
            $fromName = $configuredName;
        }
        if (filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('El correo remitente no es valido.');
        }
        $this->fromEmail = $fromEmail;
        $this->fromName = trim(preg_replace('/[\r\n]+/', ' ', $fromName) ?? 'Go Creative');
        $this->transportConfig = $transportConfig;
    }

    public function send(string $to, string $subject, string $html, array $attachments = [], ?string $replyTo = null): void
    {
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false || preg_match('/[\r\n]/', $to)) {
            throw new RuntimeException('El correo del destinatario no es valido.');
        }

        $subject = trim(preg_replace('/[\r\n]+/', ' ', $subject) ?? '');
        if ($subject === '') {
            throw new RuntimeException('El asunto del correo no puede estar vacio.');
        }
        $replyTo = trim((string) ($replyTo ?? $this->fromEmail));
        if (filter_var($replyTo, FILTER_VALIDATE_EMAIL) === false || preg_match('/[\r\n]/', $replyTo)) {
            throw new RuntimeException('El correo de respuesta no es válido.');
        }

        $mixedBoundary = 'gc_mixed_' . bin2hex(random_bytes(12));
        $alternativeBoundary = 'gc_alt_' . bin2hex(random_bytes(12));
        $plain = $this->plainText($html);

        $headers = [
            'MIME-Version: 1.0',
            'From: ' . $this->encodeHeader($this->fromName) . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $replyTo,
            'Content-Type: multipart/mixed; boundary="' . $mixedBoundary . '"',
            'X-Mailer: GoCreative/PHP-' . PHP_VERSION,
        ];

        $body = '--' . $mixedBoundary . "\r\n"
            . 'Content-Type: multipart/alternative; boundary="' . $alternativeBoundary . '"' . "\r\n\r\n"
            . '--' . $alternativeBoundary . "\r\n"
            . 'Content-Type: text/plain; charset=UTF-8' . "\r\n"
            . 'Content-Transfer-Encoding: base64' . "\r\n\r\n"
            . chunk_split(base64_encode($plain))
            . '--' . $alternativeBoundary . "\r\n"
            . 'Content-Type: text/html; charset=UTF-8' . "\r\n"
            . 'Content-Transfer-Encoding: base64' . "\r\n\r\n"
            . chunk_split(base64_encode($html))
            . '--' . $alternativeBoundary . '--' . "\r\n";

        foreach ($attachments as $attachment) {
            $name = $this->safeFilename((string) ($attachment['name'] ?? 'archivo.bin'));
            $content = (string) ($attachment['content'] ?? '');
            $mime = preg_match('~^[a-z0-9.+-]+/[a-z0-9.+-]+$~i', (string) ($attachment['mime'] ?? ''))
                ? (string) $attachment['mime']
                : 'application/octet-stream';
            if ($content === '') {
                continue;
            }

            $body .= '--' . $mixedBoundary . "\r\n"
                . 'Content-Type: ' . $mime . '; name="' . $name . '"' . "\r\n"
                . 'Content-Transfer-Encoding: base64' . "\r\n"
                . 'Content-Disposition: attachment; filename="' . $name . '"' . "\r\n\r\n"
                . chunk_split(base64_encode($content));
        }

        $body .= '--' . $mixedBoundary . '--' . "\r\n";

        if (($this->transportConfig['transport'] ?? 'mail') === 'smtp') {
            $domain = substr(strrchr($this->fromEmail, '@') ?: '@gocreative.cl', 1);
            $smtpHeaders = array_merge([
                'Date: ' . date(DATE_RFC2822),
                'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $domain . '>',
                'To: <' . $to . '>',
                'Subject: ' . $this->encodeHeader($subject),
            ], $headers);
            (new SmtpTransport())->send(
                $this->transportConfig,
                $this->fromEmail,
                $to,
                implode("\r\n", $smtpHeaders) . "\r\n\r\n" . $body
            );
            return;
        }

        if (!mail($to, $this->encodeHeader($subject), $body, implode("\r\n", $headers))) {
            throw new RuntimeException('PHP mail() rechazó el envío. Configura Correo SMTP en el panel, especialmente si estás usando XAMPP.');
        }
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function safeFilename(string $filename): string
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?? 'archivo.bin';
        return substr(trim($filename, '-'), 0, 120) ?: 'archivo.bin';
    }

    private function plainText(string $html): string
    {
        $text = preg_replace('~<br\s*/?>~i', "\n", $html) ?? $html;
        $text = preg_replace('~</(p|div|h[1-6]|li|tr)>~i', "\n", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        return trim($text);
    }
}
