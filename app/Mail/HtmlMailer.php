<?php
declare(strict_types=1);

namespace GoCreative\Mail;

use RuntimeException;

final class HtmlMailer
{
    private string $fromEmail;
    private string $fromName;

    public function __construct(string $fromEmail, string $fromName = 'Go Creative')
    {
        if (filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('El correo remitente no es valido.');
        }
        $this->fromEmail = $fromEmail;
        $this->fromName = trim(preg_replace('/[\r\n]+/', ' ', $fromName) ?? 'Go Creative');
    }

    public function send(string $to, string $subject, string $html, array $attachments = []): void
    {
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false || preg_match('/[\r\n]/', $to)) {
            throw new RuntimeException('El correo del destinatario no es valido.');
        }

        $subject = trim(preg_replace('/[\r\n]+/', ' ', $subject) ?? '');
        if ($subject === '') {
            throw new RuntimeException('El asunto del correo no puede estar vacio.');
        }

        $mixedBoundary = 'gc_mixed_' . bin2hex(random_bytes(12));
        $alternativeBoundary = 'gc_alt_' . bin2hex(random_bytes(12));
        $plain = $this->plainText($html);

        $headers = [
            'MIME-Version: 1.0',
            'From: ' . $this->encodeHeader($this->fromName) . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
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

        if (!mail($to, $this->encodeHeader($subject), $body, implode("\r\n", $headers))) {
            throw new RuntimeException('El servidor no acepto el envio del correo.');
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
