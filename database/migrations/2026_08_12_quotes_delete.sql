-- Go Creative Chile - Permiso para eliminar cotizaciones
-- Migración no destructiva y reejecutable.
-- Ejecutar después de 2026_08_11_comercial.sql.

SET NAMES utf8mb4;

INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Eliminar cotizaciones', 'quotes.delete', 'Eliminar propuestas, sus ítems y el historial de correos.', 'Cotizaciones'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'quotes.delete');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.slug = 'superadministrador'
  AND p.slug = 'quotes.delete';
