<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/customer_helpers.php';
require_once dirname(__DIR__) . '/Payments/payment_helpers.php';
require_once dirname(__DIR__) . '/Mail/HtmlMailer.php';
require_once dirname(__DIR__) . '/Mail/email_templates.php';
require_once __DIR__ . '/QuotePdf.php';

use GoCreative\Mail\HtmlMailer;

function quote_status_meta(string $status): array
{
    $map = [
        'draft' => ['label' => 'Borrador', 'class' => 'created'],
        'sent' => ['label' => 'Enviada', 'class' => 'pending'],
        'accepted' => ['label' => 'Aceptada', 'class' => 'paid'],
        'rejected' => ['label' => 'Rechazada', 'class' => 'rejected'],
        'expired' => ['label' => 'Vencida', 'class' => 'cancelled'],
    ];
    return $map[$status] ?? $map['draft'];
}

function quote_find_by_id(int $id): ?array
{
    $statement = db()->prepare(
        'SELECT q.*, c.name AS customer_name, c.company AS customer_company, c.tax_id AS customer_tax_id,
                c.email AS customer_email, c.phone AS customer_phone, c.address AS customer_address,
                c.city AS customer_city, u.name AS created_by_name
         FROM quotes q
         INNER JOIN customers c ON c.id = q.customer_id
         LEFT JOIN users u ON u.id = q.created_by
         WHERE q.id = :id LIMIT 1'
    );
    $statement->execute(['id' => $id]);
    $quote = $statement->fetch();
    return $quote ?: null;
}

function quote_find_by_public_key(string $publicKey): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $publicKey)) {
        return null;
    }
    $statement = db()->prepare(
        'SELECT q.*, c.name AS customer_name, c.company AS customer_company, c.tax_id AS customer_tax_id,
                c.email AS customer_email, c.phone AS customer_phone, c.address AS customer_address, c.city AS customer_city
         FROM quotes q INNER JOIN customers c ON c.id = q.customer_id
         WHERE q.public_key = :public_key LIMIT 1'
    );
    $statement->execute(['public_key' => $publicKey]);
    $quote = $statement->fetch();
    return $quote ?: null;
}

function quote_items(int $quoteId): array
{
    $statement = db()->prepare('SELECT * FROM quote_items WHERE quote_id = :quote_id ORDER BY sort_order, id');
    $statement->execute(['quote_id' => $quoteId]);
    return $statement->fetchAll();
}

function quote_prepare_items(array $raw): array
{
    $items = [];
    $names = (array) ($raw['name'] ?? []);
    foreach ($names as $index => $name) {
        $name = trim((string) $name);
        if ($name === '') continue;
        $type = (($raw['item_type'][$index] ?? 'service') === 'product') ? 'product' : 'service';
        $quantityRaw = str_replace(',', '.', (string) ($raw['quantity'][$index] ?? '1'));
        $quantity = round((float) $quantityRaw, 2);
        $unitPriceRaw = preg_replace('/[^0-9]/', '', (string) ($raw['unit_price'][$index] ?? '0'));
        $unitPrice = (int) $unitPriceRaw;
        if ($quantity <= 0 || $quantity > 9999) throw new RuntimeException('Cada cantidad debe ser mayor a cero.');
        if ($unitPrice < 0 || $unitPrice > 1000000000) throw new RuntimeException('Revisa los valores unitarios.');
        $items[] = [
            'item_type' => $type,
            'name' => mb_substr($name, 0, 180),
            'description' => mb_substr(trim((string) ($raw['description'][$index] ?? '')), 0, 1000),
            'quantity' => number_format($quantity, 2, '.', ''),
            'unit_price' => $unitPrice,
            'line_total' => (int) round($quantity * $unitPrice),
            'sort_order' => count($items) + 1,
        ];
    }
    if ($items === []) throw new RuntimeException('Agrega al menos un servicio o producto.');
    return $items;
}

function quote_calculate(array $items, int $discountAmount, int $taxPercent): array
{
    $subtotal = array_sum(array_column($items, 'line_total'));
    $discountAmount = max(0, min($discountAmount, $subtotal));
    $net = $subtotal - $discountAmount;
    $taxAmount = (int) round($net * ($taxPercent / 100));
    return ['subtotal' => $subtotal, 'discount_amount' => $discountAmount, 'tax_amount' => $taxAmount, 'total' => $net + $taxAmount];
}

function quote_save(array $data, array $items, int $createdBy, ?int $id = null): int
{
    $customerId = customer_find_or_create($data['customer']);
    $totals = quote_calculate($items, (int) $data['discount_amount'], (int) $data['tax_percent']);

    db()->beginTransaction();
    try {
        if ($id === null) {
            $temporaryNumber = 'TMP-' . bin2hex(random_bytes(8));
            $statement = db()->prepare(
                'INSERT INTO quotes
                 (customer_id, created_by, quote_number, public_key, title, introduction, issue_date, valid_until,
                  status, currency, subtotal, discount_amount, tax_percent, tax_amount, total, terms, notes)
                 VALUES
                 (:customer_id, :created_by, :quote_number, :public_key, :title, :introduction, :issue_date, :valid_until,
                  :status, :currency, :subtotal, :discount_amount, :tax_percent, :tax_amount, :total, :terms, :notes)'
            );
            $statement->execute([
                'customer_id' => $customerId, 'created_by' => $createdBy, 'quote_number' => $temporaryNumber,
                'public_key' => bin2hex(random_bytes(32)), 'title' => $data['title'], 'introduction' => $data['introduction'] ?: null,
                'issue_date' => $data['issue_date'], 'valid_until' => $data['valid_until'], 'status' => 'draft', 'currency' => 'CLP',
                'subtotal' => $totals['subtotal'], 'discount_amount' => $totals['discount_amount'],
                'tax_percent' => $data['tax_percent'], 'tax_amount' => $totals['tax_amount'], 'total' => $totals['total'],
                'terms' => $data['terms'] ?: null, 'notes' => $data['notes'] ?: null,
            ]);
            $id = (int) db()->lastInsertId();
            $quoteNumber = 'GC-' . date('Y', strtotime((string) $data['issue_date'])) . '-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
            $numberUpdate = db()->prepare('UPDATE quotes SET quote_number = :quote_number WHERE id = :id');
            $numberUpdate->execute(['quote_number' => $quoteNumber, 'id' => $id]);
        } else {
            $statement = db()->prepare(
                'UPDATE quotes SET customer_id = :customer_id, title = :title, introduction = :introduction,
                 issue_date = :issue_date, valid_until = :valid_until, currency = :currency, subtotal = :subtotal,
                 discount_amount = :discount_amount, tax_percent = :tax_percent, tax_amount = :tax_amount,
                 total = :total, terms = :terms, notes = :notes
                 WHERE id = :id'
            );
            $statement->execute([
                'customer_id' => $customerId, 'title' => $data['title'], 'introduction' => $data['introduction'] ?: null,
                'issue_date' => $data['issue_date'], 'valid_until' => $data['valid_until'], 'currency' => 'CLP',
                'subtotal' => $totals['subtotal'], 'discount_amount' => $totals['discount_amount'],
                'tax_percent' => $data['tax_percent'], 'tax_amount' => $totals['tax_amount'], 'total' => $totals['total'],
                'terms' => $data['terms'] ?: null, 'notes' => $data['notes'] ?: null, 'id' => $id,
            ]);
            $delete = db()->prepare('DELETE FROM quote_items WHERE quote_id = :quote_id');
            $delete->execute(['quote_id' => $id]);
        }

        $insertItem = db()->prepare(
            'INSERT INTO quote_items (quote_id, item_type, name, description, quantity, unit_price, line_total, sort_order)
             VALUES (:quote_id, :item_type, :name, :description, :quantity, :unit_price, :line_total, :sort_order)'
        );
        foreach ($items as $item) {
            $item['quote_id'] = $id;
            $insertItem->execute($item);
        }
        db()->commit();
        return (int) $id;
    } catch (Throwable $exception) {
        if (db()->inTransaction()) db()->rollBack();
        throw $exception;
    }
}

function quote_public_url(array $quote): string
{
    return canonical('/cotizacion/?codigo=' . rawurlencode((string) $quote['public_key']));
}

function quote_delete(int $quoteId): void
{
    db()->beginTransaction();
    try {
        $statement = db()->prepare('DELETE FROM quotes WHERE id = :id');
        $statement->execute(['id' => $quoteId]);
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('La cotización no existe o ya fue eliminada.');
        }
        db()->commit();
    } catch (Throwable $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $exception;
    }
}

function quote_send(array $quote): int
{
    $items = quote_items((int) $quote['id']);
    $pdf = quote_pdf_binary($quote, $items);
    $publicUrl = quote_public_url($quote);
    $email = quote_email($quote, $publicUrl);
    $mailer = new HtmlMailer(SITE_EMAIL, SITE_NAME);

    $log = db()->prepare(
        'INSERT INTO quote_emails (quote_id, recipient, subject, status)
         VALUES (:quote_id, :recipient, :subject, :status)'
    );
    $log->execute(['quote_id' => $quote['id'], 'recipient' => $quote['customer_email'], 'subject' => $email['subject'], 'status' => 'pending']);
    $logId = (int) db()->lastInsertId();

    try {
        $mailer->send($quote['customer_email'], $email['subject'], $email['html'], [[
            'name' => 'Cotizacion-' . $quote['quote_number'] . '.pdf', 'mime' => 'application/pdf', 'content' => $pdf,
        ]]);
        $update = db()->prepare('UPDATE quote_emails SET status = :status, sent_at = NOW() WHERE id = :id');
        $update->execute(['status' => 'sent', 'id' => $logId]);
        $updateQuote = db()->prepare("UPDATE quotes SET status = CASE WHEN status = 'draft' THEN 'sent' ELSE status END, sent_at = NOW() WHERE id = :id");
        $updateQuote->execute(['id' => $quote['id']]);
    } catch (Throwable $exception) {
        $update = db()->prepare('UPDATE quote_emails SET status = :status, error_message = :error_message WHERE id = :id');
        $update->execute(['status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 500), 'id' => $logId]);
        throw $exception;
    }
    return $logId;
}
