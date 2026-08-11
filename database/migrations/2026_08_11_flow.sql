-- Go Creative Chile - Integracion de cobros Flow.cl
-- Migracion no destructiva para instalaciones existentes.
-- Compatible con MySQL 5.7+ y MariaDB 10.4+.

SET NAMES utf8mb4;
USE `gocreative`;

CREATE TABLE IF NOT EXISTS `payment_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_by` int unsigned DEFAULT NULL,
  `commerce_order` varchar(64) NOT NULL,
  `flow_order` bigint unsigned DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `checkout_url` varchar(700) DEFAULT NULL,
  `public_key` char(64) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(190) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `amount` int unsigned NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'CLP',
  `status` enum('created','pending','paid','rejected','cancelled','error') NOT NULL DEFAULT 'created',
  `flow_status` tinyint unsigned DEFAULT NULL,
  `payment_method` varchar(80) DEFAULT NULL,
  `flow_response_json` longtext DEFAULT NULL,
  `last_error` varchar(500) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `last_synced_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_orders_commerce_unique` (`commerce_order`),
  UNIQUE KEY `payment_orders_flow_unique` (`flow_order`),
  UNIQUE KEY `payment_orders_token_unique` (`token`),
  UNIQUE KEY `payment_orders_public_unique` (`public_key`),
  KEY `payment_orders_created_by_index` (`created_by`),
  KEY `payment_orders_status_created_index` (`status`, `created_at`),
  KEY `payment_orders_email_index` (`customer_email`),
  CONSTRAINT `payment_orders_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_order_id` bigint unsigned NOT NULL,
  `event_type` varchar(80) NOT NULL,
  `flow_status` tinyint unsigned DEFAULT NULL,
  `message` varchar(500) NOT NULL,
  `payload_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `payment_events_order_created_index` (`payment_order_id`, `created_at`),
  CONSTRAINT `payment_events_order_fk` FOREIGN KEY (`payment_order_id`) REFERENCES `payment_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Ver cobros', 'payments.view', 'Consultar ordenes, montos y estados de Flow.', 'Cobros Flow'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'payments.view');

INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Crear cobros', 'payments.create', 'Generar ordenes y enlaces de pago mediante Flow.', 'Cobros Flow'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'payments.create');

INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Sincronizar cobros', 'payments.sync', 'Consultar manualmente el estado informado por Flow.', 'Cobros Flow'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'payments.sync');

-- El superadministrador recibe todos los permisos nuevos.
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.slug = 'superadministrador'
  AND p.slug IN ('payments.view', 'payments.create', 'payments.sync');

-- El rol Administrador puede operar cobros por defecto.
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.slug = 'administrador'
  AND p.slug IN ('payments.view', 'payments.create', 'payments.sync');
