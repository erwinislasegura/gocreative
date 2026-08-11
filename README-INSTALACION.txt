GO CREATIVE — INSTALACIÓN EN CPANEL
===================================

Requisitos
----------
- PHP 8.0 o superior.
- Extensiones PDO MySQL y mbstring habilitadas.
- MySQL 5.7+ o MariaDB 10.4+.
- Apache o LiteSpeed.
- Función mail() habilitada para el formulario de contacto.

Instalación
-----------
1. Realiza un respaldo completo de la web actual y sus correos.
2. Abre el Administrador de archivos de cPanel.
3. Entra a public_html (o a la carpeta raíz configurada para gocreative.cl).
4. Sube el ZIP y extrae TODO su contenido directamente en esa carpeta.
5. Confirma que index.php quede en la raíz pública, no dentro de otra subcarpeta.
6. Selecciona PHP 8.0, 8.1, 8.2 o superior en MultiPHP Manager.
7. Crea una base MySQL e importa database/gocreative.sql desde phpMyAdmin.
8. Copia includes/database.example.php como includes/database.local.php y completa los datos MySQL, o configura las variables GC_DB_*.
9. Prueba la portada, todas las páginas, /admin/ y el formulario de contacto.

Configuración central
---------------------
Los datos de marca y contacto se encuentran en:
includes/config.php

Allí puedes actualizar:
- dominio principal;
- correo de recepción;
- teléfono y WhatsApp;
- ubicación;
- proyectos del portafolio.

Formulario de contacto
----------------------
El formulario envía los mensajes a contacto@gocreative.cl mediante mail() de PHP.
Si el servidor no entrega correos usando mail(), configura un buzón del mismo dominio en cPanel y solicita al hosting habilitar la entrega PHP, o reemplaza el envío por SMTP.

Panel de control
----------------
Ruta: /admin/

Después de importar la base, abre /admin/instalar.php y crea el primer
superadministrador con tus propios datos. No existen credenciales
predeterminadas en el código. El instalador queda bloqueado automáticamente.

El panel incluye usuarios, roles, permisos, control de intentos, protección
CSRF y auditoría. Revisa database/README-INSTALACION-BD.txt antes de importar.

SEO incluido
------------
- Títulos y descripciones únicos por página.
- Etiquetas Open Graph.
- Imagen social 1200 x 630 para Facebook, WhatsApp, LinkedIn y X.
- Favicon SVG/PNG, Apple Touch Icon y webmanifest.
- Datos estructurados Schema.org.
- Jerarquía semántica H1/H2/H3.
- sitemap.xml y robots.txt.
- URLs por carpetas sin depender de redirecciones complejas.
- Imágenes WebP optimizadas y carga diferida.
- Canonical configurado para https://gocreative.cl.

Después de publicar
-------------------
1. Revisa que el certificado SSL esté activo.
2. Envía https://gocreative.cl/sitemap.xml a Google Search Console.
3. Revisa la portada con Rich Results Test y la URL con el depurador de Facebook.
4. Crea el superadministrador desde /admin/instalar.php.
5. Configura Google Analytics o Tag Manager si se utilizará medición.
6. Prueba el envío desde /contacto/ y confirma que llegue al correo.
7. Vacía caché de LiteSpeed/cPanel si todavía aparece la versión anterior.

Versión: 3.0.0 — agosto de 2026
