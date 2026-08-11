<?php
declare(strict_types=1);

function hosting_form_defaults(): array
{
    return [
        'customer_name' => '', 'customer_email' => '', 'customer_company' => '', 'customer_phone' => '',
        'service_name' => 'Hosting administrado', 'domain' => '', 'plan_name' => 'Hosting Business',
        'billing_cycle' => 'annual', 'start_date' => date('Y-m-d'), 'due_date' => date('Y-m-d', strtotime('+1 year')),
        'amount' => '120000', 'status' => 'active', 'notes' => '',
    ];
}

function hosting_form_from_post(): array
{
    $form = hosting_form_defaults();
    foreach (array_keys($form) as $key) $form[$key] = trim((string) ($_POST[$key] ?? $form[$key]));
    $form['customer_email'] = mb_strtolower($form['customer_email']);
    return $form;
}

function hosting_form_errors(array $form): array
{
    $errors = [];
    if (mb_strlen($form['customer_name']) < 2 || mb_strlen($form['customer_name']) > 100) $errors[] = 'El nombre del cliente debe tener entre 2 y 100 caracteres.';
    if (!filter_var($form['customer_email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['customer_email']) > 190) $errors[] = 'Ingresa un correo válido.';
    if (mb_strlen($form['service_name']) < 3 || mb_strlen($form['service_name']) > 150) $errors[] = 'Ingresa un nombre de servicio válido.';
    if (mb_strlen($form['plan_name']) < 2 || mb_strlen($form['plan_name']) > 120) $errors[] = 'Ingresa el nombre del plan.';
    if (!in_array($form['billing_cycle'], ['semiannual', 'annual'], true)) $errors[] = 'Selecciona un ciclo válido.';
    if (!in_array($form['status'], ['active', 'suspended', 'cancelled'], true)) $errors[] = 'Selecciona un estado válido.';
    foreach (['start_date' => 'inicio', 'due_date' => 'vencimiento'] as $field => $label) {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $form[$field]);
        if (!$date || $date->format('Y-m-d') !== $form[$field]) $errors[] = 'La fecha de ' . $label . ' no es válida.';
    }
    if (!preg_match('/^[0-9]+$/', $form['amount']) || (int) $form['amount'] < 100 || (int) $form['amount'] > 100000000) $errors[] = 'El valor debe estar entre $100 y $100.000.000 CLP.';
    if ($form['domain'] !== '' && !preg_match('/^(?=.{1,190}$)(?!-)(?:[a-z0-9-]+\.)+[a-z]{2,63}$/i', $form['domain'])) $errors[] = 'Escribe el dominio sin https:// ni rutas adicionales.';
    return $errors;
}

function hosting_form_payload(array $form): array
{
    return [
        'customer' => ['name' => $form['customer_name'], 'email' => $form['customer_email'], 'company' => $form['customer_company'], 'phone' => $form['customer_phone']],
        'service_name' => $form['service_name'], 'domain' => mb_strtolower($form['domain']), 'plan_name' => $form['plan_name'],
        'billing_cycle' => $form['billing_cycle'], 'start_date' => $form['start_date'], 'due_date' => $form['due_date'],
        'amount' => (int) $form['amount'], 'status' => $form['status'], 'notes' => $form['notes'],
    ];
}
