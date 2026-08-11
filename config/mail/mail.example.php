<?php
declare(strict_types=1);

return [
    // Usa smtp en XAMPP y en producción para una entrega autenticada.
    'transport' => 'smtp',
    'host' => 'mail.gocreative.cl',
    'port' => 587,
    'encryption' => 'tls', // tls, ssl o none

    // Normalmente el usuario SMTP es la dirección completa del buzón.
    'username' => 'contacto@gocreative.cl',
    'password' => '', // Nunca publiques esta contraseña.

    'from_email' => 'contacto@gocreative.cl',
    'from_name' => 'Go Creative',
    'timeout' => 12,
];
