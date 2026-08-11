<?php
declare(strict_types=1);

function quote_form_defaults(): array
{
    return [
        'customer_name' => '', 'customer_email' => '', 'customer_company' => '', 'customer_tax_id' => '',
        'customer_phone' => '', 'customer_address' => '', 'customer_city' => '',
        'title' => 'Propuesta de servicios digitales',
        'introduction' => 'Esta propuesta reúne el alcance, entregables y condiciones necesarias para desarrollar el proyecto con claridad, calidad técnica y acompañamiento profesional.',
        'issue_date' => date('Y-m-d'), 'valid_until' => date('Y-m-d', strtotime('+15 days')),
        'discount_amount' => '0', 'tax_percent' => '19',
        'terms' => "Validez: 15 días desde la fecha de emisión.\nForma de pago: 50% para iniciar y 50% contra entrega, salvo acuerdo diferente.\nLos plazos comienzan al recibir el pago inicial y todos los contenidos solicitados.\nIncluye hasta dos rondas de ajustes sobre el alcance aprobado.",
        'notes' => 'Dominio y hosting se cotizan por separado cuando no estén incluidos expresamente en los ítems.',
        'send_now' => '1',
    ];
}

function quote_form_from_post(): array
{
    $form = quote_form_defaults();
    foreach (array_keys($form) as $key) $form[$key] = trim((string) ($_POST[$key] ?? ($key === 'send_now' ? '0' : $form[$key])));
    $form['customer_email'] = mb_strtolower($form['customer_email']);
    return $form;
}

function quote_form_errors(array $form): array
{
    $errors = [];
    if (mb_strlen($form['customer_name']) < 2 || mb_strlen($form['customer_name']) > 100) $errors[] = 'El nombre del cliente debe tener entre 2 y 100 caracteres.';
    if (!filter_var($form['customer_email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['customer_email']) > 190) $errors[] = 'Ingresa un correo válido.';
    if (mb_strlen($form['title']) < 5 || mb_strlen($form['title']) > 180) $errors[] = 'El título debe tener entre 5 y 180 caracteres.';
    if (mb_strlen($form['introduction']) > 1500 || mb_strlen($form['terms']) > 2000 || mb_strlen($form['notes']) > 1500) $errors[] = 'Uno de los textos supera el máximo permitido.';
    foreach (['issue_date' => 'emisión', 'valid_until' => 'vigencia'] as $field => $label) {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $form[$field]);
        if (!$date || $date->format('Y-m-d') !== $form[$field]) $errors[] = 'La fecha de ' . $label . ' no es válida.';
    }
    if ($form['valid_until'] < $form['issue_date']) $errors[] = 'La vigencia no puede terminar antes de la emisión.';
    if (!preg_match('/^[0-9]+$/', $form['discount_amount']) || (int) $form['discount_amount'] > 1000000000) $errors[] = 'El descuento no es válido.';
    if (!in_array($form['tax_percent'], ['0', '19'], true)) $errors[] = 'Selecciona una opción de IVA válida.';
    return $errors;
}

function quote_form_items_from_post(): array
{
    $items = [];
    foreach ((array) ($_POST['name'] ?? []) as $index => $name) {
        $items[] = [
            'item_type' => (($_POST['item_type'][$index] ?? 'service') === 'product') ? 'product' : 'service',
            'name' => mb_substr(trim((string) $name), 0, 180),
            'description' => mb_substr(trim((string) ($_POST['description'][$index] ?? '')), 0, 1000),
            'quantity' => mb_substr(trim((string) ($_POST['quantity'][$index] ?? '1')), 0, 20),
            'unit_price' => mb_substr(trim((string) ($_POST['unit_price'][$index] ?? '0')), 0, 20),
        ];
    }

    return $items ?: [['item_type' => 'service', 'name' => '', 'description' => '', 'quantity' => '1', 'unit_price' => '0']];
}

function quote_form_payload(array $form): array
{
    return [
        'customer' => [
            'name' => $form['customer_name'], 'email' => $form['customer_email'], 'company' => $form['customer_company'],
            'tax_id' => $form['customer_tax_id'], 'phone' => $form['customer_phone'], 'address' => $form['customer_address'], 'city' => $form['customer_city'],
        ],
        'title' => $form['title'], 'introduction' => $form['introduction'], 'issue_date' => $form['issue_date'],
        'valid_until' => $form['valid_until'], 'discount_amount' => (int) $form['discount_amount'],
        'tax_percent' => (int) $form['tax_percent'], 'terms' => $form['terms'], 'notes' => $form['notes'],
    ];
}
