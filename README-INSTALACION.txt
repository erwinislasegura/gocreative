GO CREATIVE — INSTALACIÓN EN CPANEL
===================================

Requisitos
----------
- PHP 8.0 o superior.
- Extensiones PDO MySQL, mbstring y cURL habilitadas.
- MySQL 5.7+ o MariaDB 10.4+.
- Apache o LiteSpeed.
- Función mail() habilitada para contacto, avisos de hosting y cotizaciones.

Instalación
-----------
1. Realiza un respaldo completo de la web actual y sus correos.
2. Abre el Administrador de archivos de cPanel.
3. Entra a public_html (o a la carpeta raíz configurada para gocreative.cl).
4. Sube el ZIP y extrae TODO su contenido directamente en esa carpeta.
5. Confirma que index.php quede en la raíz pública, no dentro de otra subcarpeta.
6. Selecciona PHP 8.0, 8.1, 8.2 o superior en MultiPHP Manager.
7. Crea una base MySQL e importa database/gocreative.sql desde phpMyAdmin.
8. Copia config/database/database.example.php como config/database/database.local.php y completa los datos MySQL, o configura las variables GC_DB_*.
9. Copia config/flow/flow.example.php como config/flow/flow.local.php y agrega
   tus credenciales. Comienza siempre con environment = sandbox.
10. Prueba la portada, todas las páginas, /admin/ y el formulario de contacto.

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
CSRF, auditoría, cobros mediante Flow, renovaciones de hosting y cotizaciones
con PDF. Revisa
database/README-INSTALACION-BD.txt antes de importar.

Pasarela Flow.cl
----------------
Configuración privada:
config/flow/flow.local.php

El archivo indica con comentarios dónde cambiar el ambiente, API Key, Secret
Key y URL pública. Nunca publiques esa copia. También puedes configurar:

GC_FLOW_ENVIRONMENT
GC_FLOW_API_KEY
GC_FLOW_SECRET_KEY
GC_FLOW_PUBLIC_URL
GC_FLOW_PAYMENT_METHOD
GC_FLOW_TIMEOUT

Flow necesita llegar por HTTPS a:
- /pagos/confirmacion.php
- /pagos/retorno.php

Por eso localhost no sirve como URL de confirmación. Para pruebas con XAMPP
usa un túnel HTTPS o publica una instalación de prueba y escribe esa dirección
en public_url. Antes de cobrar dinero real, completa una operación en sandbox,
comprueba que el panel cambie a "Pagada" y luego cambia a production con las
credenciales reales.

Actualización de una instalación existente
-------------------------------------------
Si la base ya contiene usuarios, NO vuelvas a importar gocreative.sql porque
ese archivo es para instalaciones nuevas y recrea tablas. Importa una sola vez
y en este orden:

1. database/migrations/2026_08_11_flow.sql
2. database/migrations/2026_08_11_comercial.sql

La segunda migración crea clientes, hosting, avisos, catálogo y cotizaciones.
No guarda datos de tarjetas. El botón de cada aviso lleva al checkout seguro
de Flow y, cuando Flow confirma el pago, la próxima fecha de hosting avanza
seis o doce meses según el ciclo contratado.

Correos del panel
-----------------
Los avisos y las cotizaciones usan mail() de PHP y SITE_EMAIL como remitente.
En XAMPP configura SMTP/sendmail antes de probar. En cPanel usa un correo del
dominio y verifica SPF, DKIM y DMARC para mejorar la entrega. Las cotizaciones
se adjuntan como PDF y también incluyen un enlace privado para aceptar,
rechazar o descargar nuevamente la propuesta.

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
7. Ejecuta un pago completo en sandbox y verifica confirmación y retorno.
8. Envía un aviso de prueba y una cotización a un correo controlado por ti.
9. Vacía caché de LiteSpeed/cPanel si todavía aparece la versión anterior.

Versión: 3.2.0 — agosto de 2026
