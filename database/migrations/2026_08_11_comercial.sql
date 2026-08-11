-- Go Creative Chile - Hosting, avisos de cobro y cotizaciones
-- Migracion no destructiva. Ejecutar despues de 2026_08_11_flow.sql.
-- Compatible con MySQL 5.7+ y MariaDB 10.4+.

SET NAMES utf8mb4;
USE `gocreative`;

ALTER TABLE `payment_orders`
  ADD COLUMN `reference_type` varchar(30) NOT NULL DEFAULT 'manual' AFTER `currency`,
  ADD COLUMN `reference_id` bigint unsigned DEFAULT NULL AFTER `reference_type`,
  ADD COLUMN `reference_processed_at` datetime DEFAULT NULL AFTER `reference_id`,
  ADD KEY `payment_orders_reference_index` (`reference_type`, `reference_id`);

CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `company` varchar(150) DEFAULT NULL,
  `tax_id` varchar(30) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `notes` varchar(1500) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_email_unique` (`email`),
  KEY `customers_status_name_index` (`status`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `hosting_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `current_payment_order_id` bigint unsigned DEFAULT NULL,
  `service_name` varchar(150) NOT NULL,
  `domain` varchar(190) DEFAULT NULL,
  `plan_name` varchar(120) NOT NULL,
  `billing_cycle` enum('semiannual','annual') NOT NULL DEFAULT 'annual',
  `start_date` date NOT NULL,
  `due_date` date NOT NULL,
  `amount` int unsigned NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'CLP',
  `status` enum('active','suspended','cancelled') NOT NULL DEFAULT 'active',
  `last_notice_level` tinyint unsigned NOT NULL DEFAULT 0,
  `last_notice_at` datetime DEFAULT NULL,
  `last_paid_at` datetime DEFAULT NULL,
  `notes` varchar(1500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `hosting_customer_index` (`customer_id`),
  KEY `hosting_created_by_index` (`created_by`),
  KEY `hosting_payment_order_index` (`current_payment_order_id`),
  KEY `hosting_due_status_index` (`due_date`, `status`),
  CONSTRAINT `hosting_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `hosting_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hosting_payment_order_fk` FOREIGN KEY (`current_payment_order_id`) REFERENCES `payment_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `hosting_notices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hosting_service_id` bigint unsigned NOT NULL,
  `payment_order_id` bigint unsigned DEFAULT NULL,
  `notice_level` tinyint unsigned NOT NULL,
  `recipient` varchar(190) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `error_message` varchar(500) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `hosting_notices_service_index` (`hosting_service_id`, `created_at`),
  KEY `hosting_notices_payment_index` (`payment_order_id`),
  CONSTRAINT `hosting_notices_service_fk` FOREIGN KEY (`hosting_service_id`) REFERENCES `hosting_services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hosting_notices_payment_fk` FOREIGN KEY (`payment_order_id`) REFERENCES `payment_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `catalog_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `item_type` enum('service','product') NOT NULL DEFAULT 'service',
  `name` varchar(180) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `unit_price` int unsigned NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` smallint unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `catalog_status_order_index` (`status`, `sort_order`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quotes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `quote_number` varchar(40) NOT NULL,
  `public_key` char(64) NOT NULL,
  `title` varchar(180) NOT NULL,
  `introduction` varchar(1500) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `valid_until` date NOT NULL,
  `status` enum('draft','sent','accepted','rejected','expired') NOT NULL DEFAULT 'draft',
  `currency` char(3) NOT NULL DEFAULT 'CLP',
  `subtotal` int unsigned NOT NULL DEFAULT 0,
  `discount_amount` int unsigned NOT NULL DEFAULT 0,
  `tax_percent` tinyint unsigned NOT NULL DEFAULT 19,
  `tax_amount` int unsigned NOT NULL DEFAULT 0,
  `total` int unsigned NOT NULL DEFAULT 0,
  `terms` varchar(2000) DEFAULT NULL,
  `notes` varchar(1500) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotes_number_unique` (`quote_number`),
  UNIQUE KEY `quotes_public_key_unique` (`public_key`),
  KEY `quotes_customer_index` (`customer_id`),
  KEY `quotes_created_by_index` (`created_by`),
  KEY `quotes_status_date_index` (`status`, `created_at`),
  CONSTRAINT `quotes_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `quotes_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quote_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quote_id` bigint unsigned NOT NULL,
  `item_type` enum('service','product') NOT NULL DEFAULT 'service',
  `name` varchar(180) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` int unsigned NOT NULL DEFAULT 0,
  `line_total` int unsigned NOT NULL DEFAULT 0,
  `sort_order` smallint unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `quote_items_quote_order_index` (`quote_id`, `sort_order`),
  CONSTRAINT `quote_items_quote_fk` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quote_emails` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quote_id` bigint unsigned NOT NULL,
  `recipient` varchar(190) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `error_message` varchar(500) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `quote_emails_quote_index` (`quote_id`, `created_at`),
  CONSTRAINT `quote_emails_quote_fk` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `catalog_items` (`item_type`, `name`, `description`, `unit_price`, `sort_order`) VALUES
  ('service', 'Diseño y desarrollo web', 'Sitio corporativo responsive, autoadministrable y optimizado para buscadores.', 450000, 10),
  ('service', 'Tienda online', 'Ecommerce con catálogo, carrito, pagos, envíos y capacitación de administración.', 750000, 20),
  ('service', 'Software a medida', 'Análisis, diseño y desarrollo de una plataforma adaptada a procesos empresariales.', 0, 30),
  ('service', 'Automatización de procesos', 'Integración de formularios, alertas, tareas, documentos y datos.', 0, 40),
  ('service', 'Gestión Meta Ads', 'Configuración, gestión y optimización mensual de campañas en Meta.', 180000, 50),
  ('service', 'Diseño creativo digital', 'Sistema visual y piezas digitales coherentes con la identidad de marca.', 250000, 60),
  ('service', 'Soporte técnico', 'Bloque de soporte, diagnóstico y mantenimiento web.', 45000, 70),
  ('service', 'Hosting administrado anual', 'Alojamiento web, SSL, respaldos y soporte básico por 12 meses.', 120000, 80);

INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Ver hosting', 'hosting.view', 'Consultar servicios, vencimientos y avisos.', 'Hosting'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'hosting.view');
INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Crear hosting', 'hosting.create', 'Registrar nuevos servicios de alojamiento.', 'Hosting'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'hosting.create');
INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Editar hosting', 'hosting.edit', 'Modificar fechas, ciclos, montos y estados.', 'Hosting'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'hosting.edit');
INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Enviar avisos hosting', 'hosting.send', 'Enviar primer, segundo y ultimo aviso con pago Flow.', 'Hosting'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'hosting.send');
INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Ver cotizaciones', 'quotes.view', 'Consultar propuestas, estados y PDFs.', 'Cotizaciones'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'quotes.view');
INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Crear cotizaciones', 'quotes.create', 'Generar propuestas con servicios y productos.', 'Cotizaciones'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'quotes.create');
INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Editar cotizaciones', 'quotes.edit', 'Modificar el alcance y condiciones de propuestas.', 'Cotizaciones'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'quotes.edit');
INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Enviar cotizaciones', 'quotes.send', 'Enviar correo HTML con la propuesta PDF.', 'Cotizaciones'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'quotes.send');
INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Gestionar catalogo', 'catalog.manage', 'Administrar servicios y productos para cotizar rapidamente.', 'Cotizaciones'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'catalog.manage');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r CROSS JOIN `permissions` p
WHERE r.slug IN ('superadministrador', 'administrador')
  AND p.slug IN ('hosting.view','hosting.create','hosting.edit','hosting.send','quotes.view','quotes.create','quotes.edit','quotes.send','catalog.manage');
