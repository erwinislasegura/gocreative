# Go Creative Chile

Sitio multipágina y panel administrativo para una agencia de diseño web,
software y marketing digital.

## Instalación local con XAMPP

1. Copia el proyecto en `xampp/htdocs/gocreative`.
2. Inicia Apache y MySQL.
3. Copia `config/database/database.example.php` como `config/database/database.local.php` y completa tus propios datos MySQL.
4. Importa `database/gocreative.sql` desde phpMyAdmin.
5. Copia `config/flow/flow.example.php` como `config/flow/flow.local.php` y agrega tus credenciales de prueba Flow.
6. Abre `http://localhost/gocreative/`.
7. Abre `http://localhost/gocreative/admin/instalar.php` y crea tu cuenta principal.
8. Después accede normalmente en `http://localhost/gocreative/admin/`.

El proyecto no incluye correos ni contraseñas predeterminadas. El instalador
se bloquea automáticamente después de crear el primer superadministrador.

`config/database/database.local.php` está ignorado por Git para evitar publicar credenciales.
El nombre de base configurado es `gocreative`; los comentarios del archivo indican dónde cambiar nombre, usuario y contraseña.
En producción también puedes usar `GC_DB_HOST`, `GC_DB_PORT`, `GC_DB_NAME`,
`GC_DB_USER` y `GC_DB_PASSWORD`.

## Pasarela de pago Flow.cl

El panel incluye un módulo de cobros con enlaces públicos, checkout Flow,
confirmación automática, retorno del cliente, consulta manual de estado e
historial de eventos. Las claves viven exclusivamente en el archivo ignorado
`config/flow/flow.local.php` o en variables `GC_FLOW_*`.

Si ya instalaste una versión anterior de la base, no vuelvas a importar el
script completo. Importa, en este orden:

1. `database/migrations/2026_08_11_flow.sql`
2. `database/migrations/2026_08_11_comercial.sql`

Para probar callbacks desde XAMPP,
`public_url` debe ser una URL HTTPS accesible desde Internet; Flow no puede
conectarse a `localhost`.

## Gestión comercial

- **Hosting:** clientes, dominios, ciclo semestral o anual, vencimientos en
  rojo, tres niveles de aviso, historial de correos y renovación automática
  cuando Flow confirma el pago.
- **Cotizaciones:** catálogo editable, servicios y productos, descuento, IVA,
  vigencia, estados, aceptación o rechazo mediante enlace privado, correo HTML
  y PDF A4 profesional adjunto.

Los correos salen mediante la función `mail()` de PHP. En cPanel normalmente
basta con disponer de una cuenta de correo del dominio; en XAMPP debes
configurar un servidor SMTP/sendmail para probar envíos reales.

Consulta [README-INSTALACION.txt](README-INSTALACION.txt) y
[database/README-INSTALACION-BD.txt](database/README-INSTALACION-BD.txt) para
el detalle completo.
