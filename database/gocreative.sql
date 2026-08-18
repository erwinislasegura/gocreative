-- Go Creative Chile - Panel de control
-- Compatible con MySQL 5.7+ y MariaDB 10.4+ (XAMPP)
-- Fecha: 2026-08-15
-- IMPORTANTE: selecciona primero la base de destino en phpMyAdmin.
-- El script no crea ni cambia de base para ser compatible con prefijos cPanel.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `whatsapp_leads`;
DROP TABLE IF EXISTS `whatsapp_messages`;
DROP TABLE IF EXISTS `whatsapp_conversations`;
DROP TABLE IF EXISTS `whatsapp_contacts`;
DROP TABLE IF EXISTS `whatsapp_knowledge`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `quote_emails`;
DROP TABLE IF EXISTS `quote_items`;
DROP TABLE IF EXISTS `quotes`;
DROP TABLE IF EXISTS `catalog_items`;
DROP TABLE IF EXISTS `hosting_notices`;
DROP TABLE IF EXISTS `hosting_services`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `payment_events`;
DROP TABLE IF EXISTS `payment_orders`;
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

CREATE TABLE `payment_orders` (
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
  `reference_type` varchar(30) NOT NULL DEFAULT 'hosting',
  `reference_id` bigint unsigned DEFAULT NULL,
  `reference_processed_at` datetime DEFAULT NULL,
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
  KEY `payment_orders_reference_index` (`reference_type`, `reference_id`),
  CONSTRAINT `payment_orders_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `payment_events` (
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

CREATE TABLE `whatsapp_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `wa_id` varchar(32) NOT NULL,
  `profile_name` varchar(120) DEFAULT NULL,
  `opt_out` tinyint(1) NOT NULL DEFAULT 0,
  `last_seen_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_contacts_wa_unique` (`wa_id`),
  KEY `whatsapp_contacts_seen_index` (`last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `whatsapp_conversations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contact_id` bigint unsigned NOT NULL,
  `assigned_to` int unsigned DEFAULT NULL,
  `mode` enum('bot','human') NOT NULL DEFAULT 'bot',
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `flow_step` varchar(40) NOT NULL DEFAULT '',
  `flow_data_json` longtext DEFAULT NULL,
  `unread_count` int unsigned NOT NULL DEFAULT 0,
  `last_message_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `whatsapp_conversations_contact_index` (`contact_id`, `status`, `id`),
  KEY `whatsapp_conversations_assigned_index` (`assigned_to`),
  KEY `whatsapp_conversations_last_index` (`last_message_at`),
  CONSTRAINT `whatsapp_conversations_contact_fk` FOREIGN KEY (`contact_id`) REFERENCES `whatsapp_contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_conversations_user_fk` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `whatsapp_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `meta_message_id` varchar(190) DEFAULT NULL,
  `direction` enum('incoming','outgoing') NOT NULL,
  `message_type` varchar(30) NOT NULL DEFAULT 'text',
  `body` text,
  `status` enum('received','sent','delivered','read','failed') NOT NULL,
  `error_message` varchar(500) DEFAULT NULL,
  `payload_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_messages_meta_unique` (`meta_message_id`),
  KEY `whatsapp_messages_conversation_index` (`conversation_id`, `created_at`),
  CONSTRAINT `whatsapp_messages_conversation_fk` FOREIGN KEY (`conversation_id`) REFERENCES `whatsapp_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `whatsapp_leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contact_id` bigint unsigned NOT NULL,
  `conversation_id` bigint unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `service` varchar(120) NOT NULL,
  `budget` varchar(80) DEFAULT NULL,
  `timeframe` varchar(80) DEFAULT NULL,
  `status` enum('new','contacted','qualified','won','lost') NOT NULL DEFAULT 'new',
  `notes` varchar(1500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_leads_conversation_unique` (`conversation_id`),
  KEY `whatsapp_leads_contact_index` (`contact_id`),
  KEY `whatsapp_leads_status_index` (`status`, `created_at`),
  CONSTRAINT `whatsapp_leads_contact_fk` FOREIGN KEY (`contact_id`) REFERENCES `whatsapp_contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_leads_conversation_fk` FOREIGN KEY (`conversation_id`) REFERENCES `whatsapp_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `whatsapp_knowledge` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(160) NOT NULL,
  `keywords` varchar(1000) NOT NULL,
  `answer` text NOT NULL,
  `priority` smallint unsigned NOT NULL DEFAULT 100,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `whatsapp_knowledge_status_priority_index` (`status`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `whatsapp_knowledge` (`title`, `keywords`, `answer`, `priority`) VALUES
  ('Precio de una página web', 'precio web,valor web,cuanto cuesta pagina,costo sitio', 'Las páginas web profesionales parten desde $55.000. El valor final depende de las secciones, funciones y contenido. Puedo hacerte unas preguntas para preparar la cotización.', 200),
  ('Tiempo de entrega', 'tiempo entrega,cuanto demora,plazo web,cuando estaria', 'El plazo depende del alcance y de la entrega de contenidos. Una web informativa sencilla normalmente puede planificarse desde 5 días hábiles; un asesor confirmará el plazo exacto.', 150),
  ('Formas de pago', 'forma de pago,medios de pago,como pago,cuotas', 'Las condiciones y medios de pago se confirman en la cotización. Cuéntame qué proyecto necesitas y un asesor te entregará las alternativas disponibles.', 120);

INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`) VALUES
  (1, 'Superadministrador', 'superadministrador', 'Acceso maestro a todas las áreas, usuarios, roles y permisos.', 1),
  (2, 'Administrador', 'administrador', 'Gestiona usuarios y consulta la configuración de roles.', 0),
  (3, 'Editor', 'editor', 'Acceso básico al resumen del panel.', 0);

INSERT INTO `catalog_items` (`item_type`, `name`, `description`, `unit_price`, `sort_order`) VALUES
  ('service', 'Diseño y desarrollo web', 'Sitio corporativo responsive, autoadministrable y optimizado para buscadores.', 450000, 10),
  ('service', 'Tienda online', 'Ecommerce con catálogo, carrito, pagos, envíos y capacitación de administración.', 750000, 20),
  ('service', 'Software a medida', 'Análisis, diseño y desarrollo de una plataforma adaptada a procesos empresariales.', 0, 30),
  ('service', 'Automatización de procesos', 'Integración de formularios, alertas, tareas, documentos y datos.', 0, 40),
  ('service', 'Gestión Meta Ads', 'Configuración, gestión y optimización mensual de campañas en Meta.', 180000, 50),
  ('service', 'Diseño creativo digital', 'Sistema visual y piezas digitales coherentes con la identidad de marca.', 250000, 60),
  ('service', 'Soporte técnico', 'Bloque de soporte, diagnóstico y mantenimiento web.', 45000, 70),
  ('service', 'Hosting administrado anual', 'Alojamiento web, SSL, respaldos y soporte básico por 12 meses.', 120000, 80);

INSERT INTO `permissions` (`id`, `name`, `slug`, `description`, `group_name`) VALUES
  (1, 'Ver resumen', 'dashboard.view', 'Acceder a indicadores y actividad reciente.', 'Panel'),
  (2, 'Ver usuarios', 'users.view', 'Consultar el listado y detalle de usuarios.', 'Usuarios'),
  (3, 'Crear usuarios', 'users.create', 'Registrar nuevas cuentas de acceso.', 'Usuarios'),
  (4, 'Editar usuarios', 'users.edit', 'Modificar datos, roles, estado y contraseñas.', 'Usuarios'),
  (5, 'Eliminar usuarios', 'users.delete', 'Eliminar cuentas de forma permanente.', 'Usuarios'),
  (6, 'Ver roles', 'roles.view', 'Consultar roles y sus permisos.', 'Roles y permisos'),
  (7, 'Crear roles', 'roles.create', 'Crear nuevas combinaciones de permisos.', 'Roles y permisos'),
  (8, 'Editar roles', 'roles.edit', 'Modificar nombres, descripciones y permisos.', 'Roles y permisos'),
  (9, 'Eliminar roles', 'roles.delete', 'Eliminar roles sin usuarios asignados.', 'Roles y permisos'),
  (10, 'Ver hosting', 'hosting.view', 'Consultar servicios, vencimientos y avisos.', 'Hosting'),
  (11, 'Crear hosting', 'hosting.create', 'Registrar nuevos servicios de alojamiento.', 'Hosting'),
  (12, 'Editar hosting', 'hosting.edit', 'Modificar fechas, ciclos, montos y estados.', 'Hosting'),
  (13, 'Enviar avisos hosting', 'hosting.send', 'Enviar primer, segundo y ultimo aviso con checkout Flow.', 'Hosting'),
  (14, 'Ver cotizaciones', 'quotes.view', 'Consultar propuestas, estados y PDFs.', 'Cotizaciones'),
  (15, 'Crear cotizaciones', 'quotes.create', 'Generar propuestas con servicios y productos.', 'Cotizaciones'),
  (16, 'Editar cotizaciones', 'quotes.edit', 'Modificar el alcance y condiciones de propuestas.', 'Cotizaciones'),
  (17, 'Enviar cotizaciones', 'quotes.send', 'Enviar correo HTML con la propuesta PDF.', 'Cotizaciones'),
  (18, 'Gestionar catalogo', 'catalog.manage', 'Administrar servicios y productos para cotizar rapidamente.', 'Cotizaciones'),
  (19, 'Gestionar integraciones', 'settings.manage', 'Configurar Analytics, reCAPTCHA y correo SMTP.', 'Configuración'),
  (20, 'Eliminar hosting', 'hosting.delete', 'Eliminar servicios de hosting y su historial de avisos.', 'Hosting'),
  (21, 'Ver WhatsApp', 'whatsapp.view', 'Consultar conversaciones y oportunidades recibidas por WhatsApp.', 'WhatsApp'),
  (22, 'Gestionar WhatsApp', 'whatsapp.manage', 'Configurar el bot, responder, derivar conversaciones y administrar conocimiento.', 'WhatsApp');

-- Superadministrador: todos los permisos.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;

-- Administrador: panel y gestión operativa de usuarios.
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
  (2, 1), (2, 2), (2, 3), (2, 4), (2, 6),
  (2, 10), (2, 11), (2, 12), (2, 13), (2, 14), (2, 15), (2, 16), (2, 17), (2, 18);

-- Editor: acceso básico al panel.
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
  (3, 1);

-- La primera cuenta se crea de forma segura desde /admin/instalar.php.
-- No se publican correos ni contraseñas predeterminadas en el repositorio.

SET FOREIGN_KEY_CHECKS = 1;
