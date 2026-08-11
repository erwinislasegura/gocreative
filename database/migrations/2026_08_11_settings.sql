-- Go Creative Chile - Configuración de Analytics y reCAPTCHA
-- Migración no destructiva y reejecutable para instalaciones existentes.

SET NAMES utf8mb4;
USE `gocreative`;

INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Gestionar integraciones', 'settings.manage', 'Configurar Google Analytics y reCAPTCHA v2.', 'Configuración'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'settings.manage');

-- La configuración contiene credenciales sensibles: se entrega inicialmente
-- solo al rol maestro. Después puede asignarse a otro rol desde el panel.
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
INNER JOIN `permissions` p ON p.slug = 'settings.manage'
WHERE r.slug = 'superadministrador';
