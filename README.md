# Go Creative Chile

Sitio multipágina y panel administrativo para una agencia de diseño web,
software y marketing digital.

## Instalación local con XAMPP

1. Copia el proyecto en `xampp/htdocs/gocreative`.
2. Inicia Apache y MySQL.
3. Copia `config/database/database.example.php` como `config/database/database.local.php` y completa tus propios datos MySQL.
4. Crea y selecciona tu base en phpMyAdmin; después importa `database/gocreative.sql`.
5. Copia `config/flow/flow.example.php` como `config/flow/flow.local.php` y agrega tus credenciales de prueba Flow.
6. Abre `http://localhost/gocreative/`.
7. Abre `http://localhost/gocreative/admin/instalar.php` y crea tu cuenta principal.
8. Después accede normalmente en `http://localhost/gocreative/admin/`.
9. Configura Google Analytics, reCAPTCHA v2 y Correo SMTP desde el menú.

El proyecto no incluye correos ni contraseñas predeterminadas. El instalador
se bloquea automáticamente después de crear el primer superadministrador.

`config/database/database.local.php` está ignorado por Git para evitar publicar credenciales.
El nombre de base configurado es `gocreative`; los comentarios del archivo indican dónde cambiar nombre, usuario y contraseña.
En producción también puedes usar `GC_DB_HOST`, `GC_DB_PORT`, `GC_DB_NAME`,
`GC_DB_USER` y `GC_DB_PASSWORD`.

## Google Analytics y reCAPTCHA v2

El módulo **Google Analytics** del panel administra la etiqueta pública. El
valor inicial es `GT-TXZH8NNL`, comprobado
directamente en gocreative.cl. La captura de Analytics identifica la cuenta
`161497159` y la propiedad `490278227`. La configuración es central y no se
carga en el panel ni en propuestas privadas.

También admite `GC_ANALYTICS_ENABLED`, `GC_ANALYTICS_TAG_ID`,
`GC_ANALYTICS_ACCOUNT_ID` y `GC_ANALYTICS_PROPERTY_ID`. Estas variables del
servidor tienen prioridad sobre los valores del panel.

El módulo **reCAPTCHA v2** permite guardar las claves y activar por separado
el login o el contacto. La clave secreta nunca vuelve a mostrarse. Se guarda
en el archivo ignorado `config/recaptcha/recaptcha.local.php` o en las variables
`GC_RECAPTCHA_SITE_KEY` y `GC_RECAPTCHA_SECRET_KEY`. Registra `gocreative.cl`
y `localhost` como dominios permitidos si trabajarás también desde XAMPP.

## Correo SMTP

El módulo **Correo SMTP** del panel configura el transporte común para avisos
de Hosting, cotizaciones y el formulario de contacto. Incluye una prueba real
de entrega y muestra el error exacto de conexión o autenticación. En XAMPP se
recomienda SMTP porque `mail()` no tiene un servidor de salida configurado de
forma predeterminada.

La contraseña se guarda en el archivo ignorado `config/mail/mail.local.php`.
También puedes utilizar `GC_MAIL_TRANSPORT`, `GC_SMTP_HOST`, `GC_SMTP_PORT`,
`GC_SMTP_ENCRYPTION`, `GC_SMTP_USERNAME`, `GC_SMTP_PASSWORD`,
`GC_MAIL_FROM_EMAIL`, `GC_MAIL_FROM_NAME` y `GC_SMTP_TIMEOUT`.

## Checkout Flow.cl para Hosting

Flow no aparece como un módulo independiente del panel. Se utiliza únicamente
en las renovaciones de Hosting: al enviar un aviso, el botón del correo abre
directamente el checkout de Flow. La confirmación firmada actualiza el próximo
vencimiento de manera automática. Las claves viven exclusivamente en el
archivo ignorado `config/flow/flow.local.php` o en variables `GC_FLOW_*`.

Si ya instalaste una versión anterior de la base, no vuelvas a importar el
script completo. Selecciona primero tu base real en phpMyAdmin e importa, en
este orden:

1. `database/migrations/2026_08_11_flow.sql`
2. `database/migrations/2026_08_11_comercial.sql`
3. `database/migrations/2026_08_11_settings.sql`
4. `database/migrations/2026_08_11_hosting_delete.sql`

Las migraciones no contienen `USE gocreative`, por lo que aceptan directamente
el nombre con prefijo asignado por cPanel.

Para probar callbacks desde XAMPP,
`public_url` debe ser una URL HTTPS accesible desde Internet; Flow no puede
conectarse a `localhost`.

## Gestión comercial

- **Hosting:** clientes, dominios, ciclo semestral o anual, vencimientos en
  rojo, tres niveles de aviso, historial de correos, checkout Flow directo y
  renovación automática cuando Flow confirma el pago. El Superadministrador
  puede eliminar registros sin checkout vigente desde el listado o la ficha.
- **Cotizaciones:** catálogo editable, servicios y productos, descuento, IVA,
  vigencia, estados, aceptación o rechazo mediante enlace privado, correo HTML
  y PDF A4 profesional adjunto.

Los correos utilizan el transporte elegido en el módulo Correo SMTP. Configura
un buzón real del dominio y ejecuta la prueba antes de enviar avisos a clientes.

Consulta [README-INSTALACION.txt](README-INSTALACION.txt) y
[database/README-INSTALACION-BD.txt](database/README-INSTALACION-BD.txt) para
el detalle completo.
