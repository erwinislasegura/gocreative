<?php
declare(strict_types=1);

final class WhatsAppClient
{
    public function __construct(private array $config) {}

    public function sendText(string $to, string $body, bool $previewUrl = false): array
    {
        return $this->request('POST', '/' . $this->config['phone_number_id'] . '/messages', [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => ['preview_url' => $previewUrl, 'body' => $body],
        ]);
    }

    public function markAsRead(string $messageId): array
    {
        return $this->request('POST', '/' . $this->config['phone_number_id'] . '/messages', [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ]);
    }

    public function phoneProfile(): array
    {
        return $this->request('GET', '/' . $this->config['phone_number_id'], null, 'fields=display_phone_number,verified_name,quality_rating');
    }

    private function request(string $method, string $path, ?array $payload = null, string $query = ''): array
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('La extensión PHP cURL no está disponible.');
        }
        if ($this->config['access_token'] === '' || $this->config['phone_number_id'] === '') {
            throw new RuntimeException('WhatsApp Cloud API no tiene credenciales completas.');
        }

        $url = 'https://graph.facebook.com/' . rawurlencode($this->config['graph_version']) . $path;
        if ($query !== '') $url .= '?' . $query;
        $curl = curl_init($url);
        $headers = ['Authorization: Bearer ' . $this->config['access_token'], 'Accept: application/json'];
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        if ($payload !== null) {
            $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $options[CURLOPT_POSTFIELDS] = $encoded;
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
        }
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false || $error !== '') {
            throw new RuntimeException('No fue posible conectar con Meta: ' . ($error ?: 'respuesta vacía'));
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Meta devolvió una respuesta no válida (HTTP ' . $status . ').');
        }
        if ($status < 200 || $status >= 300 || isset($decoded['error'])) {
            $message = (string) ($decoded['error']['message'] ?? ('HTTP ' . $status));
            throw new RuntimeException('Meta rechazó la solicitud: ' . $message);
        }
        return $decoded;
    }
}
