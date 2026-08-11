-- Go Creative Chile - Panel de control
-- Compatible con MySQL 5.7+ y MariaDB 10.4+ (XAMPP)
-- Fecha: 2026-08-11

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `gocreative`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `gocreative`;

DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` varchar(255) NOT NULL,
  `group_name` varchar(80) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_permissions` (
  `role_id` int unsigned NOT NULL,
  `permission_id` int unsigned NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  KEY `role_permissions_permission_index` (`permission_id`),
  CONSTRAINT `role_permissions_role_fk` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_permission_fk` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role_id`),
  KEY `users_status_index` (`status`),
  CONSTRAINT `users_role_fk` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(190) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `successful` tinyint(1) NOT NULL DEFAULT 0,
  `attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `login_attempts_email_time_index` (`email`, `attempted_at`),
  KEY `login_attempts_ip_time_index` (`ip_address`, `attempted_at`),
  KEY `login_attempts_success_time_index` (`successful`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(80) NOT NULL,
  `entity_id` int unsigned DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_index` (`user_id`),
  KEY `audit_logs_entity_index` (`entity_type`, `entity_id`),
  KEY `audit_logs_created_index` (`created_at`),
  CONSTRAINT `audit_logs_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`) VALUES
  (1, 'Superadministrador', 'superadministrador', 'Acceso maestro a todas las áreas, usuarios, roles y permisos.', 1),
  (2, 'Administrador', 'administrador', 'Gestiona usuarios y consulta la configuración de roles.', 0),
  (3, 'Editor', 'editor', 'Acceso básico al resumen del panel.', 0);

INSERT INTO `permissions` (`id`, `name`, `slug`, `description`, `group_name`) VALUES
  (1, 'Ver resumen', 'dashboard.view', 'Acceder a indicadores y actividad reciente.', 'Panel'),
  (2, 'Ver usuarios', 'users.view', 'Consultar el listado y detalle de usuarios.', 'Usuarios'),
  (3, 'Crear usuarios', 'users.create', 'Registrar nuevas cuentas de acceso.', 'Usuarios'),
  (4, 'Editar usuarios', 'users.edit', 'Modificar datos, roles, estado y contraseñas.', 'Usuarios'),
  (5, 'Eliminar usuarios', 'users.delete', 'Eliminar cuentas de forma permanente.', 'Usuarios'),
  (6, 'Ver roles', 'roles.view', 'Consultar roles y sus permisos.', 'Roles y permisos'),
  (7, 'Crear roles', 'roles.create', 'Crear nuevas combinaciones de permisos.', 'Roles y permisos'),
  (8, 'Editar roles', 'roles.edit', 'Modificar nombres, descripciones y permisos.', 'Roles y permisos'),
  (9, 'Eliminar roles', 'roles.delete', 'Eliminar roles sin usuarios asignados.', 'Roles y permisos');

-- Superadministrador: todos los permisos.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;

-- Administrador: panel y gestión operativa de usuarios.
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
  (2, 1), (2, 2), (2, 3), (2, 4), (2, 6);

-- Editor: acceso básico al panel.
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
  (3, 1);

-- La primera cuenta se crea de forma segura desde /admin/instalar.php.
-- No se publican correos ni contraseñas predeterminadas en el repositorio.

SET FOREIGN_KEY_CHECKS = 1;
