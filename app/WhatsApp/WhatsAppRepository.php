<?php
declare(strict_types=1);

final class WhatsAppRepository
{
    public function __construct(private PDO $db) {}

    public function upsertContact(string $waId, string $profileName = ''): array
    {
        $statement = $this->db->prepare(
            'INSERT INTO whatsapp_contacts (wa_id, profile_name, last_seen_at)
             VALUES (:wa_id, :profile_name, NOW())
             ON DUPLICATE KEY UPDATE profile_name = IF(VALUES(profile_name) <> \'\', VALUES(profile_name), profile_name), last_seen_at = NOW()'
        );
        $statement->execute(['wa_id' => $waId, 'profile_name' => mb_substr($profileName, 0, 120)]);
        $query = $this->db->prepare('SELECT * FROM whatsapp_contacts WHERE wa_id = :wa_id LIMIT 1');
        $query->execute(['wa_id' => $waId]);
        $contact = $query->fetch();
        if (!$contact) throw new RuntimeException('No fue posible registrar el contacto de WhatsApp.');
        return $contact;
    }

    public function activeConversation(int $contactId): array
    {
        $this->db->beginTransaction();
        try {
            // Serializes concurrent webhook deliveries for the same contact so
            // they cannot open two conversations at the same time.
            $lock = $this->db->prepare('SELECT id FROM whatsapp_contacts WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $contactId]);
            $query = $this->db->prepare("SELECT * FROM whatsapp_conversations WHERE contact_id = :contact_id AND status <> 'closed' ORDER BY id DESC LIMIT 1");
            $query->execute(['contact_id' => $contactId]);
            $conversation = $query->fetch();
            if (!$conversation) {
                $insert = $this->db->prepare('INSERT INTO whatsapp_conversations (contact_id, last_message_at) VALUES (:contact_id, NOW())');
                $insert->execute(['contact_id' => $contactId]);
                $id = (int) $this->db->lastInsertId();
                $query = $this->db->prepare('SELECT * FROM whatsapp_conversations WHERE id = :id');
                $query->execute(['id' => $id]);
                $conversation = $query->fetch();
            }
            $this->db->commit();
            if (!$conversation) throw new RuntimeException('No fue posible abrir la conversación de WhatsApp.');
            return $conversation;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $exception;
        }
    }

    public function recordIncoming(int $conversationId, string $messageId, string $type, string $body, array $payload): bool
    {
        try {
            $this->db->beginTransaction();
            $statement = $this->db->prepare(
                "INSERT INTO whatsapp_messages (conversation_id, meta_message_id, direction, message_type, body, status, payload_json, created_at)
                 VALUES (:conversation_id, :message_id, 'incoming', :message_type, :body, 'received', :payload_json, NOW())"
            );
            $statement->execute([
                'conversation_id' => $conversationId,
                'message_id' => $messageId,
                'message_type' => mb_substr($type, 0, 30),
                'body' => $body,
                'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            $update = $this->db->prepare('UPDATE whatsapp_conversations SET unread_count = unread_count + 1, last_message_at = NOW(), updated_at = NOW() WHERE id = :id');
            $update->execute(['id' => $conversationId]);
            $this->db->commit();
            return true;
        } catch (PDOException $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ((string) $exception->getCode() === '23000') return false;
            throw $exception;
        }
    }

    public function recordOutgoing(int $conversationId, string $body, ?string $messageId, string $status = 'sent', ?string $error = null): void
    {
        $statement = $this->db->prepare(
            "INSERT INTO whatsapp_messages (conversation_id, meta_message_id, direction, message_type, body, status, error_message, created_at)
             VALUES (:conversation_id, :message_id, 'outgoing', 'text', :body, :status, :error_message, NOW())"
        );
        $statement->execute([
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'body' => $body,
            'status' => $status,
            'error_message' => $error,
        ]);
        $update = $this->db->prepare('UPDATE whatsapp_conversations SET last_message_at = NOW(), updated_at = NOW() WHERE id = :id');
        $update->execute(['id' => $conversationId]);
    }

    public function updateMessageStatus(string $messageId, string $status, ?string $error = null): void
    {
        if (!in_array($status, ['sent', 'delivered', 'read', 'failed'], true)) return;
        $statement = $this->db->prepare('UPDATE whatsapp_messages SET status = :status, error_message = :error WHERE meta_message_id = :message_id');
        $statement->execute(['status' => $status, 'error' => $error, 'message_id' => $messageId]);
    }

    public function setFlow(int $conversationId, string $step, array $data): void
    {
        $statement = $this->db->prepare('UPDATE whatsapp_conversations SET flow_step = :step, flow_data_json = :data, updated_at = NOW() WHERE id = :id');
        $statement->execute([
            'step' => mb_substr($step, 0, 40),
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'id' => $conversationId,
        ]);
    }

    public function setMode(int $conversationId, string $mode, ?int $assignedTo = null): void
    {
        if (!in_array($mode, ['bot', 'human'], true)) throw new InvalidArgumentException('Modo de conversación no válido.');
        $statement = $this->db->prepare('UPDATE whatsapp_conversations SET mode = :mode, assigned_to = :assigned_to, updated_at = NOW() WHERE id = :id');
        $statement->execute(['mode' => $mode, 'assigned_to' => $assignedTo, 'id' => $conversationId]);
    }

    public function setContactOptOut(int $contactId, bool $optOut): void
    {
        $statement = $this->db->prepare('UPDATE whatsapp_contacts SET opt_out = :opt_out, updated_at = NOW() WHERE id = :id');
        $statement->execute(['opt_out' => $optOut ? 1 : 0, 'id' => $contactId]);
    }

    public function knowledgeMatch(string $text): ?array
    {
        $normalized = $this->normalize($text);
        $items = $this->db->query("SELECT * FROM whatsapp_knowledge WHERE status = 'active' ORDER BY priority DESC, id ASC")->fetchAll();
        $best = null;
        $bestScore = 0;
        foreach ($items as $item) {
            $score = 0;
            $keywords = preg_split('/[,;\n]+/u', (string) $item['keywords']) ?: [];
            foreach ($keywords as $keyword) {
                $keyword = $this->normalize($keyword);
                if ($keyword !== '' && str_contains($normalized, $keyword)) $score += max(1, mb_strlen($keyword));
            }
            if ($score > $bestScore) {
                $best = $item;
                $bestScore = $score;
            }
        }
        return $bestScore > 0 ? $best : null;
    }

    public function saveLead(int $contactId, int $conversationId, array $data): int
    {
        $statement = $this->db->prepare(
            "INSERT INTO whatsapp_leads (contact_id, conversation_id, name, service, budget, timeframe, status)
             VALUES (:contact_id, :conversation_id, :name, :service, :budget, :timeframe, 'new')
             ON DUPLICATE KEY UPDATE name = VALUES(name), service = VALUES(service), budget = VALUES(budget), timeframe = VALUES(timeframe), updated_at = NOW()"
        );
        $statement->execute([
            'contact_id' => $contactId,
            'conversation_id' => $conversationId,
            'name' => mb_substr((string) ($data['name'] ?? ''), 0, 120),
            'service' => mb_substr((string) ($data['service'] ?? ''), 0, 120),
            'budget' => mb_substr((string) ($data['budget'] ?? ''), 0, 80),
            'timeframe' => mb_substr((string) ($data['timeframe'] ?? ''), 0, 80),
        ]);
        $query = $this->db->prepare('SELECT id FROM whatsapp_leads WHERE conversation_id = :conversation_id');
        $query->execute(['conversation_id' => $conversationId]);
        return (int) $query->fetchColumn();
    }

    public function recentlySent(int $conversationId, string $body, int $hours = 12): bool
    {
        $statement = $this->db->prepare(
            "SELECT COUNT(*) FROM whatsapp_messages WHERE conversation_id = :conversation_id AND direction = 'outgoing' AND body = :body AND created_at >= :cutoff"
        );
        $statement->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
        $statement->bindValue(':body', $body);
        $statement->bindValue(':cutoff', date('Y-m-d H:i:s', time() - (max(1, $hours) * 3600)));
        $statement->execute();
        return (int) $statement->fetchColumn() > 0;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $converted = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : false;
        if (is_string($converted)) $value = $converted;
        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }
}
