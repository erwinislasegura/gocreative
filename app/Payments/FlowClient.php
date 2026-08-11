<?php
declare(strict_types=1);

namespace GoCreative\Payments;

use JsonException;
use RuntimeException;

final class FlowApiException extends RuntimeException
{
}

final class FlowClient
{
    private string $apiUrl;
    private string $apiKey;
    private string $secretKey;

    public function __construct(string $apiUrl, string $apiKey, string $secretKey)
    {
        $allowedUrls = ['https://sandbox.flow.cl/api', 'https://www.flow.cl/api'];
        if (!in_array($apiUrl, $allowedUrls, true)) {
            throw new RuntimeException('Servidor de Flow no permitido.');
        }

        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
    }

    public function createPayment(array $order): array
    {
        $params = [
            'apiKey' => $this->apiKey,
            'commerceOrder' => (string) $order['commerce_order'],
            'subject' => (string) $order['subject'],
            'currency' => (string) ($order['currency'] ?? 'CLP'),
            'amount' => (int) $order['amount'],
            'email' => (string) $order['email'],
            'paymentMethod' => (int) ($order['payment_method'] ?? 9),
            'urlConfirmation' => (string) $order['url_confirmation'],
            'urlReturn' => (string) $order['url_return'],
        ];

        if (!empty($order['optional'])) {
            $params['optional'] = json_encode($order['optional'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }
        if (!empty($order['timeout'])) {
            $params['timeout'] = (int) $order['timeout'];
        }

        $response = $this->request('POST', '/payment/create', $params);
        foreach (['url', 'token', 'flowOrder'] as $key) {
            if (!array_key_exists($key, $response) || $response[$key] === '') {
                throw new FlowApiException('Flow entrego una respuesta incompleta al crear la orden.');
            }
        }

        return $response;
    }

    public function getPaymentStatus(string $token): array
    {
        $response = $this->request('GET', '/payment/getStatus', [
            'apiKey' => $this->apiKey,
            'token' => $token,
        ]);

        foreach (['flowOrder', 'commerceOrder', 'status', 'amount', 'currency'] as $key) {
            if (!array_key_exists($key, $response)) {
                throw new FlowApiException('Flow entrego una respuesta de estado incompleta.');
            }
        }

        return $response;
    }

    private function request(string $method, string $endpoint, array $params): array
    {
        if (!extension_loaded('curl')) {
            throw new FlowApiException('La extension cURL de PHP es necesaria para conectar con Flow.');
        }

        $params['s'] = $this->signature($params);
        $url = $this->apiUrl . $endpoint;
        $curl = curl_init();
        if ($curl === false) {
            throw new FlowApiException('No fue posible iniciar la conexion con Flow.');
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'GoCreative-Flow/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ];

        if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
        }

        if ($method === 'POST') {
            $options[CURLOPT_URL] = $url;
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
            $options[CURLOPT_HTTPHEADER] = [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ];
        } else {
            $options[CURLOPT_URL] = $url . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        curl_setopt_array($curl, $options);
        $body = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($body === false) {
            throw new FlowApiException('No se pudo conectar con Flow. Revisa la conexion e intentalo nuevamente.');
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new FlowApiException('Flow entrego una respuesta que no se pudo interpretar.');
        }

        if (!is_array($decoded)) {
            throw new FlowApiException('Flow entrego una respuesta vacia.');
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = trim((string) ($decoded['message'] ?? $decoded['error'] ?? 'Solicitud rechazada por Flow.'));
            $message = mb_substr(strip_tags($message), 0, 300);
            throw new FlowApiException($message !== '' ? $message : 'Solicitud rechazada por Flow.', $statusCode);
        }

        return $decoded;
    }

    private function signature(array $params): string
    {
        unset($params['s']);
        ksort($params, SORT_STRING);
        $toSign = '';
        foreach ($params as $key => $value) {
            $toSign .= $key . (string) $value;
        }

        return hash_hmac('sha256', $toSign, $this->secretKey);
    }
}
