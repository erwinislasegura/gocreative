# Go Creative Chile

Sitio multipágina y panel administrativo para una agencia de diseño web,
software y marketing digital.

## Instalación local con XAMPP

1. Copia el proyecto en `xampp/htdocs/gocreative`.
2. Inicia Apache y MySQL.
3. Copia `config/database/database.example.php` como `config/database/database.local.php` y completa tus propios datos MySQL.
4. Importa `database/gocreative.sql` desde phpMyAdmin.
5. Abre `http://localhost/gocreative/`.
6. Abre `http://localhost/gocreative/admin/instalar.php` y crea tu cuenta principal.
7. Después accede normalmente en `http://localhost/gocreative/admin/`.

El proyecto no incluye correos ni contraseñas predeterminadas. El instalador
se bloquea automáticamente después de crear el primer superadministrador.

`config/database/database.local.php` está ignorado por Git para evitar publicar credenciales.
El nombre de base configurado es `gocreative`; los comentarios del archivo indican dónde cambiar nombre, usuario y contraseña.
En producción también puedes usar `GC_DB_HOST`, `GC_DB_PORT`, `GC_DB_NAME`,
`GC_DB_USER` y `GC_DB_PASSWORD`.

Consulta [README-INSTALACION.txt](README-INSTALACION.txt) y
[database/README-INSTALACION-BD.txt](database/README-INSTALACION-BD.txt) para
el detalle completo.
