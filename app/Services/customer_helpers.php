<?php
declare(strict_types=1);

function customer_find_or_create(array $data): int
{
    $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new RuntimeException('El correo del cliente no es valido.');
    }

    $statement = db()->prepare('SELECT id FROM customers WHERE email = :email LIMIT 1');
    $statement->execute(['email' => $email]);
    $existingId = (int) $statement->fetchColumn();

    $params = [
        'name' => mb_substr(trim((string) ($data['name'] ?? '')), 0, 100),
        'company' => mb_substr(trim((string) ($data['company'] ?? '')), 0, 150) ?: null,
        'tax_id' => mb_substr(trim((string) ($data['tax_id'] ?? '')), 0, 30) ?: null,
        'email' => $email,
        'phone' => mb_substr(trim((string) ($data['phone'] ?? '')), 0, 30) ?: null,
        'address' => mb_substr(trim((string) ($data['address'] ?? '')), 0, 255) ?: null,
        'city' => mb_substr(trim((string) ($data['city'] ?? '')), 0, 100) ?: null,
    ];

    if ($params['name'] === '') {
        throw new RuntimeException('El nombre del cliente es obligatorio.');
    }

    if ($existingId > 0) {
        $update = db()->prepare(
            'UPDATE customers
             SET name = :name, company = :company, tax_id = :tax_id, phone = :phone,
                 address = :address, city = :city, status = :status
             WHERE id = :id'
        );
        $update->execute([
            'name' => $params['name'], 'company' => $params['company'], 'tax_id' => $params['tax_id'],
            'phone' => $params['phone'], 'address' => $params['address'], 'city' => $params['city'],
            'status' => 'active', 'id' => $existingId,
        ]);
        return $existingId;
    }

    $insert = db()->prepare(
        'INSERT INTO customers (name, company, tax_id, email, phone, address, city, status)
         VALUES (:name, :company, :tax_id, :email, :phone, :address, :city, :status)'
    );
    $params['status'] = 'active';
    $insert->execute($params);
    return (int) db()->lastInsertId();
}
