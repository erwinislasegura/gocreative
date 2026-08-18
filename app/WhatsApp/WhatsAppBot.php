<?php
declare(strict_types=1);

final class WhatsAppBot
{
    public function __construct(
        private WhatsAppRepository $repository,
        private WhatsAppClient $client,
        private array $config
    ) {}

    public function handle(array $contact, array $conversation, string $text, string $incomingMessageId): void
    {
        try {
            $this->client->markAsRead($incomingMessageId);
        } catch (Throwable $exception) {
            error_log('WhatsApp no pudo marcar mensaje como leído: ' . $exception->getMessage());
        }

        $normalized = $this->normalize($text);
        if ($this->matches($normalized, ['salir', 'detener', 'no quiero mensajes', 'baja'])) {
            $this->repository->setContactOptOut((int) $contact['id'], true);
            $this->reply($conversation, $contact['wa_id'], 'Entendido. Detuvimos las respuestas automáticas. Si deseas reactivarlas, escribe “INICIAR”.');
            return;
        }
        if ((int) $contact['opt_out'] === 1) {
            if ($this->matches($normalized, ['iniciar', 'reactivar', 'hola'])) {
                $this->repository->setContactOptOut((int) $contact['id'], false);
                $this->repository->setMode((int) $conversation['id'], 'bot');
                $this->repository->setFlow((int) $conversation['id'], '', []);
                $conversation['flow_step'] = '';
                $conversation['flow_data_json'] = null;
                $conversation['mode'] = 'bot';
            } else {
                return;
            }
        }

        if ($this->matches($normalized, ['asesor', 'persona', 'humano', 'ejecutivo', 'hablar con alguien'])) {
            $this->repository->setMode((int) $conversation['id'], 'human');
            $this->reply($conversation, $contact['wa_id'], $this->config['handoff_reply']);
            return;
        }
        if (($conversation['mode'] ?? 'bot') === 'human') return;

        if (!$this->insideBusinessHours() && !$this->repository->recentlySent((int) $conversation['id'], $this->config['outside_hours_reply'], 12)) {
            $this->reply($conversation, $contact['wa_id'], $this->config['outside_hours_reply']);
        }

        $step = (string) ($conversation['flow_step'] ?? '');
        $data = json_decode((string) ($conversation['flow_data_json'] ?? ''), true);
        if (!is_array($data)) $data = [];

        $knowledge = $this->repository->knowledgeMatch($text);
        if ($knowledge !== null && !in_array($step, ['budget', 'timeframe', 'name'], true)) {
            $this->reply($conversation, $contact['wa_id'], (string) $knowledge['answer']);
            if ($step === '') {
                $this->repository->setFlow((int) $conversation['id'], 'service', $data);
            }
            if ($step === '' || $step === 'service') $this->reply($conversation, $contact['wa_id'], $this->serviceQuestion());
            return;
        }

        switch ($step) {
            case 'service':
                $service = $this->parseService($normalized);
                if ($service === null) {
                    $this->reply($conversation, $contact['wa_id'], $this->config['fallback_reply'] . "\n\n" . $this->serviceQuestion());
                    return;
                }
                $data['service'] = $service;
                $this->repository->setFlow((int) $conversation['id'], 'budget', $data);
                $this->reply($conversation, $contact['wa_id'], "Excelente, te interesa *{$service}*. ¿Qué presupuesto aproximado tienes?\n\n1. Hasta $150.000\n2. $150.000 a $400.000\n3. $400.000 a $800.000\n4. Más de $800.000\n5. Aún no lo defino");
                return;

            case 'budget':
                $budget = $this->parseBudget($normalized);
                if ($budget === null) {
                    $this->reply($conversation, $contact['wa_id'], 'Indícame una opción del 1 al 5 para el presupuesto, por favor.');
                    return;
                }
                $data['budget'] = $budget;
                $this->repository->setFlow((int) $conversation['id'], 'timeframe', $data);
                $this->reply($conversation, $contact['wa_id'], "¿Cuándo te gustaría comenzar?\n\n1. Lo antes posible\n2. Durante este mes\n3. En 1 a 3 meses\n4. Solo estoy cotizando");
                return;

            case 'timeframe':
                $timeframe = $this->parseTimeframe($normalized);
                if ($timeframe === null) {
                    $this->reply($conversation, $contact['wa_id'], 'Indícame una opción del 1 al 4 para el plazo, por favor.');
                    return;
                }
                $data['timeframe'] = $timeframe;
                $this->repository->setFlow((int) $conversation['id'], 'name', $data);
                $this->reply($conversation, $contact['wa_id'], 'Para terminar, ¿cuál es tu nombre o el nombre de tu empresa?');
                return;

            case 'name':
                $name = trim($text);
                if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
                    $this->reply($conversation, $contact['wa_id'], 'Escribe un nombre de entre 2 y 120 caracteres, por favor.');
                    return;
                }
                $data['name'] = $name;
                $leadId = $this->repository->saveLead((int) $contact['id'], (int) $conversation['id'], $data);
                $this->repository->setFlow((int) $conversation['id'], 'completed', $data);
                $this->repository->setMode((int) $conversation['id'], 'human');
                $this->reply($conversation, $contact['wa_id'], "¡Gracias, {$name}! ✅ Registré tu solicitud #{$leadId}:\n• Servicio: {$data['service']}\n• Presupuesto: {$data['budget']}\n• Plazo: {$data['timeframe']}\n\nUn asesor revisará estos datos y continuará contigo por aquí.");
                return;

            case 'completed':
                $this->repository->setMode((int) $conversation['id'], 'human');
                $this->reply($conversation, $contact['wa_id'], $this->config['handoff_reply']);
                return;

            default:
                $this->repository->setFlow((int) $conversation['id'], 'service', []);
                $this->reply($conversation, $contact['wa_id'], $this->config['greeting'] . "\n\n" . $this->serviceQuestion());
        }
    }

    public function handleUnsupported(array $contact, array $conversation, string $incomingMessageId): void
    {
        try { $this->client->markAsRead($incomingMessageId); } catch (Throwable $exception) {
            error_log('WhatsApp no pudo marcar multimedia como leído: ' . $exception->getMessage());
        }
        if ((int) $contact['opt_out'] === 1 || ($conversation['mode'] ?? 'bot') === 'human') return;
        $message = 'Por ahora puedo procesar respuestas de texto, botones y listas. Escribe tu consulta o responde con el número de una opción.';
        if ((string) ($conversation['flow_step'] ?? '') === '') {
            $this->repository->setFlow((int) $conversation['id'], 'service', []);
            $message = $this->config['greeting'] . "\n\n" . $message . "\n\n" . $this->serviceQuestion();
        }
        $this->reply($conversation, (string) $contact['wa_id'], $message);
    }

    private function reply(array $conversation, string $to, string $body): void
    {
        try {
            $result = $this->client->sendText($to, $body);
            $messageId = isset($result['messages'][0]['id']) ? (string) $result['messages'][0]['id'] : null;
            $this->repository->recordOutgoing((int) $conversation['id'], $body, $messageId);
        } catch (Throwable $exception) {
            $this->repository->recordOutgoing((int) $conversation['id'], $body, null, 'failed', mb_substr($exception->getMessage(), 0, 500));
            throw $exception;
        }
    }

    private function insideBusinessHours(): bool
    {
        $now = new DateTimeImmutable('now', new DateTimeZone($this->config['timezone']));
        if (!in_array((int) $now->format('N'), $this->config['business_days'], true)) return false;
        $time = $now->format('H:i');
        return $time >= $this->config['business_start'] && $time < $this->config['business_end'];
    }

    private function serviceQuestion(): string
    {
        return "¿Qué necesitas?\n\n1. Página web profesional\n2. Tienda online\n3. Software a medida\n4. Marketing / Meta Ads\n5. Diseño gráfico\n6. Soporte o hosting\n7. Otro proyecto\n\nTambién puedes escribir *asesor* en cualquier momento.";
    }

    private function parseService(string $text): ?string
    {
        $options = [
            '1' => 'Página web profesional', 'pagina' => 'Página web profesional', 'web' => 'Página web profesional',
            '2' => 'Tienda online', 'tienda' => 'Tienda online', 'ecommerce' => 'Tienda online',
            '3' => 'Software a medida', 'software' => 'Software a medida', 'sistema' => 'Software a medida',
            '4' => 'Marketing / Meta Ads', 'marketing' => 'Marketing / Meta Ads', 'meta ads' => 'Marketing / Meta Ads', 'publicidad' => 'Marketing / Meta Ads',
            '5' => 'Diseño gráfico', 'diseno' => 'Diseño gráfico', 'logo' => 'Diseño gráfico',
            '6' => 'Soporte o hosting', 'soporte' => 'Soporte o hosting', 'hosting' => 'Soporte o hosting',
            '7' => 'Otro proyecto', 'otro' => 'Otro proyecto',
        ];
        foreach ($options as $needle => $label) if ($text === $needle || str_contains($text, $needle)) return $label;
        return null;
    }

    private function parseBudget(string $text): ?string
    {
        foreach (['1' => 'Hasta $150.000', '2' => '$150.000 a $400.000', '3' => '$400.000 a $800.000', '4' => 'Más de $800.000', '5' => 'Aún no definido'] as $key => $label) {
            if (preg_match('/^' . $key . '(?:\D|$)/', $text)) return $label;
        }
        if (str_contains($text, 'no defin')) return 'Aún no definido';
        return null;
    }

    private function parseTimeframe(string $text): ?string
    {
        foreach (['1' => 'Lo antes posible', '2' => 'Durante este mes', '3' => 'En 1 a 3 meses', '4' => 'Solo cotizando'] as $key => $label) {
            if (preg_match('/^' . $key . '(?:\D|$)/', $text)) return $label;
        }
        return null;
    }

    private function matches(string $text, array $needles): bool
    {
        foreach ($needles as $needle) if ($text === $needle || str_contains($text, $needle)) return true;
        return false;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $converted = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : false;
        return is_string($converted) ? $converted : $value;
    }
}
