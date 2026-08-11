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
10. Copia config/recaptcha/recaptcha.example.php como
    config/recaptcha/recaptcha.local.php y agrega las claves reCAPTCHA v2.
11. Prueba la portada, todas las páginas, /admin/ y el formulario de contacto.

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

Google Analytics
----------------
La etiqueta pública verificada directamente en gocreative.cl es GT-TXZH8NNL.
La captura de Google Analytics identifica la cuenta 161497159 y la propiedad
490278227. La etiqueta se configura centralmente en includes/config.php y se
carga en todas las páginas públicas que usan includes/header.php. El panel y
las cotizaciones privadas no se miden.

reCAPTCHA v2
------------
Configuración privada:
config/recaptcha/recaptcha.local.php

Copia el archivo de ejemplo y escribe donde está comentado:
- site_key: clave pública del widget;
- secret_key: clave secreta para la verificación del servidor.

Nunca subas recaptcha.local.php a GitHub. También puedes configurar:
GC_RECAPTCHA_SITE_KEY
GC_RECAPTCHA_SECRET_KEY

Al registrar las claves en Google agrega gocreative.cl. Para probar la misma
integración en XAMPP agrega también localhost. Sin ambas claves, el contacto y
el login se mantienen deshabilitados para no dejar los formularios sin
protección.

Panel de control
----------------
Ruta: /admin/

Después de importar la base, abre /admin/instalar.php y crea el primer
superadministrador con tus propios datos. No existen credenciales
predeterminadas en el código. El instalador queda bloqueado automáticamente.

El panel incluye usuarios, roles, permisos, control de intentos, protección
CSRF, auditoría, renovaciones de hosting con checkout Flow y cotizaciones con
PDF. Flow no agrega un módulo separado al menú. Revisa
database/README-INSTALACION-BD.txt antes de importar.

Checkout Flow.cl para Hosting
-----------------------------
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

Flow se utiliza exclusivamente al enviar avisos de renovación desde Hosting.
El botón del correo abre directamente su checkout. Para confirmar el resultado,
Flow necesita llegar por HTTPS a:
- /pagos/confirmacion.php
- /pagos/retorno.php

Por eso localhost no sirve como URL de confirmación. Para pruebas con XAMPP
usa un túnel HTTPS o publica una instalación de prueba y escribe esa dirección
en public_url. Antes de cobrar dinero real, completa una renovación en sandbox,
comprueba que la fecha de Hosting avance seis o doce meses y luego cambia a
production con las credenciales reales.

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

Si phpMyAdmin informa que una columna o tabla ya existe, usa la versión más
reciente de 2026_08_11_comercial.sql y vuelve a importarla completa. El archivo
detecta instalaciones parciales y continúa sin borrar información.

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
5. Comprueba en Google Analytics que GT-TXZH8NNL reciba una visita en tiempo real.
6. Prueba el envío desde /contacto/ y confirma que llegue al correo.
7. Ejecuta una renovación de Hosting en sandbox y verifica el checkout, la
   confirmación y la nueva fecha de vencimiento.
8. Envía un aviso de prueba y una cotización a un correo controlado por ti.
9. Vacía caché de LiteSpeed/cPanel si todavía aparece la versión anterior.

Versión: 3.4.0 — agosto de 2026
