<?php
declare(strict_types=1);

require_once __DIR__ . '/hosting_helpers.php';

function payment_apply_reference(array $order): void
{
    if ($order['status'] !== 'paid' || empty($order['reference_id'])) {
        return;
    }

    if ($order['reference_type'] === 'hosting') {
        hosting_advance_due_date((int) $order['reference_id']);
    }
}
