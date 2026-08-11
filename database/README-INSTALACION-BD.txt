GO CREATIVE — BASE DE DATOS DEL PANEL
======================================

Archivo para importar
---------------------
database/gocreative.sql

Instalación rápida en XAMPP
---------------------------
1. Inicia Apache y MySQL desde el panel de XAMPP.
2. Abre http://localhost/phpmyadmin/.
3. Copia config/database/database.example.php como config/database/database.local.php.
4. Completa en ese archivo tus propios datos de conexión MySQL.
5. Entra en la pestaña "Importar" de phpMyAdmin.
6. Selecciona database/gocreative.sql y ejecuta la importación.
7. Abre http://localhost/gocreative/admin/instalar.php.
8. Crea el primer superadministrador con tu propio correo y contraseña.
9. Ingresa en http://localhost/gocreative/admin/.

Actualizar una instalación existente
-------------------------------------
Si ya tienes usuarios creados, NO vuelvas a importar gocreative.sql. Para
agregar los módulos sin borrar información importa una sola vez y en orden:

1. database/migrations/2026_08_11_flow.sql
2. database/migrations/2026_08_11_comercial.sql
3. database/migrations/2026_08_11_settings.sql

La primera migración crea la infraestructura interna del checkout Flow, sin
menú ni permisos propios. La segunda agrega clientes, hosting, avisos, catálogo
y cotizaciones, y asigna sus permisos a Superadministrador y Administrador.
La tercera incorpora el permiso de configuración para los módulos Google
Analytics y reCAPTCHA v2; por seguridad se asigna solo al Superadministrador.
La migración comercial es reejecutable: si un intento anterior se interrumpió
por una columna o tabla existente, descarga la versión actual y vuelve a
importarla completa. No elimina registros ni duplica el catálogo inicial.

Primer acceso seguro
--------------------
El proyecto no contiene un correo o contraseña predeterminados. El instalador
se habilita solamente mientras la tabla users está vacía y queda bloqueado
automáticamente después de crear la primera cuenta.

Configuración de conexión
-------------------------
El archivo config/database/database.local.php está excluido por .gitignore y
protegido contra acceso web. Nunca publiques ese archivo. Como alternativa,
define estas variables en el hosting:

GC_DB_HOST
GC_DB_PORT
GC_DB_NAME
GC_DB_USER
GC_DB_PASSWORD

Seguridad incluida
------------------
- Contraseñas con password_hash/password_verify.
- Requisitos de contraseña robusta para todas las cuentas.
- Sesiones regeneradas y cookies HttpOnly/SameSite.
- Protección CSRF en formularios.
- Consultas PDO preparadas.
- Límite de intentos de acceso.
- Auditoría de acciones administrativas.
- Panel excluido de buscadores.
- El checkout interno guarda estados y referencias, nunca datos de tarjetas.
- Confirmación Flow verificada consultando la API firmada.
- Enlaces públicos de cotización con claves aleatorias de 256 bits.
- Respuesta de cotizaciones protegida con sesión y token CSRF.

Importante
----------
El script elimina y vuelve a crear las tablas del panel. Úsalo para una
instalación inicial o después de respaldar los datos existentes.
