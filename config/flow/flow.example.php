<?php
declare(strict_types=1);

/**
 * CONFIGURACION DE FLOW.CL
 *
 * 1. Copia este archivo como flow.local.php dentro de esta misma carpeta.
 * 2. Cambia environment, api_key y secret_key con los datos de tu cuenta Flow.
 * 3. Si pruebas desde XAMPP, public_url debe ser una URL HTTPS publica que
 *    apunte a este proyecto. Flow no puede confirmar pagos a localhost.
 *
 * flow.local.php esta ignorado por Git. Nunca publiques tus credenciales.
 */
return [
    // Usa "sandbox" para pruebas y "production" solamente al pasar a cobros reales.
    'environment' => 'sandbox',

    // Flow > Mis datos > Integraciones.
    'api_key' => 'REEMPLAZA_CON_TU_API_KEY',
    'secret_key' => 'REEMPLAZA_CON_TU_SECRET_KEY',

    // Dominio publico donde Flow enviara confirmaciones y retornara al cliente.
    'public_url' => 'https://gocreative.cl',

    // 9 permite mostrar todos los medios de pago habilitados en tu cuenta Flow.
    'payment_method' => 9,

    // Vigencia de cada orden en segundos. 172800 equivale a 48 horas.
    'timeout' => 172800,
];
