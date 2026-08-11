-- Go Creative Chile - Permiso para eliminar servicios de hosting
-- Migración no destructiva y reejecutable para instalaciones existentes.
-- Selecciona primero la base de destino en phpMyAdmin.

SET NAMES utf8mb4;

INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Eliminar hosting', 'hosting.delete', 'Eliminar servicios de hosting y su historial de avisos.', 'Hosting'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'hosting.delete');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.slug = 'superadministrador'
  AND p.slug = 'hosting.delete';
