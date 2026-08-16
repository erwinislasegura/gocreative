<?php
declare(strict_types=1);

final class WhatsAppWebhook
{
    public function __construct(
        private WhatsAppRepository $repository,
        private WhatsAppBot $bot
    ) {}

    public static function verify(array $query, array $config): ?string
    {
        $mode = (string) ($query['hub_mode'] ?? $query['hub.mode'] ?? '');
        $token = (string) ($query['hub_verify_token'] ?? $query['hub.verify_token'] ?? '');
        $challenge = (string) ($query['hub_challenge'] ?? $query['hub.challenge'] ?? '');
        if ($mode !== 'subscribe' || $challenge === '' || ($config['verify_token'] ?? '') === '') return null;
        return hash_equals((string) $config['verify_token'], $token) ? $challenge : null;
    }

    public static function validSignature(string $rawBody, string $header, string $appSecret): bool
    {
        if ($appSecret === '' || !str_starts_with($header, 'sha256=')) return false;
        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);
        return hash_equals($expected, $header);
    }

    public function process(array $payload): void
    {
        if (($payload['object'] ?? '') !== 'whatsapp_business_account') return;
        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                if (!is_array($value)) continue;
                $this->processStatuses($value['statuses'] ?? []);
                $contacts = [];
                foreach (($value['contacts'] ?? []) as $item) {
                    if (isset($item['wa_id'])) $contacts[(string) $item['wa_id']] = (string) ($item['profile']['name'] ?? '');
                }
                foreach (($value['messages'] ?? []) as $message) {
                    try {
                        $this->processMessage($message, $contacts);
                    } catch (Throwable $exception) {
                        error_log('Error procesando mensaje WhatsApp: ' . $exception->getMessage());
                    }
                }
            }
        }
    }

    private function processMessage(array $message, array $contacts): void
    {
        $from = preg_replace('/\D+/', '', (string) ($message['from'] ?? '')) ?? '';
        $messageId = (string) ($message['id'] ?? '');
        if ($from === '' || $messageId === '') return;
        [$type, $text] = $this->extractText($message);
        $contact = $this->repository->upsertContact($from, $contacts[$from] ?? '');
        $conversation = $this->repository->activeConversation((int) $contact['id']);
        if (!$this->repository->recordIncoming((int) $conversation['id'], $messageId, $type, $text, $message)) return;
        if ($text === '') {
            $this->bot->handleUnsupported($contact, $conversation, $messageId);
            return;
        }
        $this->bot->handle($contact, $conversation, $text, $messageId);
    }

    private function processStatuses(array $statuses): void
    {
        foreach ($statuses as $status) {
            $messageId = (string) ($status['id'] ?? '');
            if ($messageId === '') continue;
            $error = isset($status['errors'][0]['title']) ? (string) $status['errors'][0]['title'] : null;
            $this->repository->updateMessageStatus($messageId, (string) ($status['status'] ?? ''), $error);
        }
    }

    private function extractText(array $message): array
    {
        $type = (string) ($message['type'] ?? 'unknown');
        if ($type === 'text') return [$type, trim((string) ($message['text']['body'] ?? ''))];
        if ($type === 'button') return [$type, trim((string) ($message['button']['text'] ?? $message['button']['payload'] ?? ''))];
        if ($type === 'interactive') {
            $interactiveType = (string) ($message['interactive']['type'] ?? '');
            $reply = $message['interactive'][$interactiveType] ?? [];
            return [$type, trim((string) ($reply['title'] ?? $reply['id'] ?? ''))];
        }
        return [$type, ''];
    }
}
