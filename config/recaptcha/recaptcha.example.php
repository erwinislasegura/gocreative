<?php
declare(strict_types=1);

return [
    // Puedes activar cada formulario por separado desde el panel.
    'protect_login' => true,
    'protect_contact' => true,

    // CLAVE PÚBLICA: se muestra en el navegador dentro del widget.
    'site_key' => '',

    // CLAVE SECRETA: nunca la publiques ni la subas a GitHub.
    'secret_key' => '',

    // En Google reCAPTCHA registra gocreative.cl y localhost para probar XAMPP.
    'allowed_hosts' => [
        'gocreative.cl',
        'www.gocreative.cl',
        'localhost',
        '127.0.0.1',
    ],

    // Tiempo máximo de espera al verificar cada respuesta con Google.
    'timeout' => 8,
];
