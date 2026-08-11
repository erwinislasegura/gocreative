<?php
declare(strict_types=1);

/**
 * CONFIGURACIÓN DE LA BASE DE DATOS
 * ---------------------------------
 * 1. Duplica este archivo.
 * 2. Renombra la copia como: database.local.php
 * 3. Cambia en la copia únicamente los valores indicados abajo.
 *
 * IMPORTANTE: database.local.php contiene datos privados y no se sube a Git.
 */
return [
    'host' => '127.0.0.1', // Servidor MySQL. Normalmente no es necesario cambiarlo en XAMPP.
    'port' => '3306', // Puerto de MySQL. Normalmente no es necesario cambiarlo.

    // ↓ NOMBRE DE LA BASE DE DATOS. La base mostrada en phpMyAdmin es gocreative.
    'name' => 'gocreative',

    // ↓ CAMBIA AQUÍ EL USUARIO DE LA BASE DE DATOS.
    'user' => 'CAMBIA_AQUI_TU_USUARIO_MYSQL',

    // ↓ CAMBIA AQUÍ LA CONTRASEÑA DEL USUARIO DE LA BASE DE DATOS.
    'password' => 'CAMBIA_AQUI_TU_CONTRASENA_MYSQL',
];
