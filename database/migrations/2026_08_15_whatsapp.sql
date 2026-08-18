-- Go Creative Chile - Automatización WhatsApp Cloud API
-- Migración no destructiva y reejecutable. Selecciona primero la base en phpMyAdmin.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `whatsapp_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `wa_id` varchar(32) NOT NULL,
  `profile_name` varchar(120) DEFAULT NULL,
  `opt_out` tinyint(1) NOT NULL DEFAULT 0,
  `last_seen_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `whatsapp_contacts_wa_unique` (`wa_id`), KEY `whatsapp_contacts_seen_index` (`last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_conversations` (
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
  PRIMARY KEY (`id`), KEY `whatsapp_conversations_contact_index` (`contact_id`, `status`, `id`), KEY `whatsapp_conversations_assigned_index` (`assigned_to`), KEY `whatsapp_conversations_last_index` (`last_message_at`),
  CONSTRAINT `whatsapp_conversations_contact_fk` FOREIGN KEY (`contact_id`) REFERENCES `whatsapp_contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_conversations_user_fk` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_messages` (
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
  PRIMARY KEY (`id`), UNIQUE KEY `whatsapp_messages_meta_unique` (`meta_message_id`), KEY `whatsapp_messages_conversation_index` (`conversation_id`, `created_at`),
  CONSTRAINT `whatsapp_messages_conversation_fk` FOREIGN KEY (`conversation_id`) REFERENCES `whatsapp_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_leads` (
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
  PRIMARY KEY (`id`), UNIQUE KEY `whatsapp_leads_conversation_unique` (`conversation_id`), KEY `whatsapp_leads_contact_index` (`contact_id`), KEY `whatsapp_leads_status_index` (`status`, `created_at`),
  CONSTRAINT `whatsapp_leads_contact_fk` FOREIGN KEY (`contact_id`) REFERENCES `whatsapp_contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_leads_conversation_fk` FOREIGN KEY (`conversation_id`) REFERENCES `whatsapp_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_knowledge` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(160) NOT NULL,
  `keywords` varchar(1000) NOT NULL,
  `answer` text NOT NULL,
  `priority` smallint unsigned NOT NULL DEFAULT 100,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `whatsapp_knowledge_status_priority_index` (`status`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Ver WhatsApp', 'whatsapp.view', 'Consultar conversaciones y oportunidades recibidas por WhatsApp.', 'WhatsApp'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'whatsapp.view');

INSERT INTO `permissions` (`name`, `slug`, `description`, `group_name`)
SELECT 'Gestionar WhatsApp', 'whatsapp.manage', 'Configurar el bot, responder, derivar conversaciones y administrar conocimiento.', 'WhatsApp'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `slug` = 'whatsapp.manage');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r INNER JOIN `permissions` p ON p.slug IN ('whatsapp.view', 'whatsapp.manage') WHERE r.slug = 'superadministrador';

INSERT INTO `whatsapp_knowledge` (`title`, `keywords`, `answer`, `priority`)
SELECT 'Precio de una página web', 'precio web,valor web,cuanto cuesta pagina,costo sitio', 'Las páginas web profesionales parten desde $55.000. El valor final depende de las secciones, funciones y contenido. Puedo hacerte unas preguntas para preparar la cotización.', 200
WHERE NOT EXISTS (SELECT 1 FROM `whatsapp_knowledge` WHERE `title` = 'Precio de una página web');

INSERT INTO `whatsapp_knowledge` (`title`, `keywords`, `answer`, `priority`)
SELECT 'Tiempo de entrega', 'tiempo entrega,cuanto demora,plazo web,cuando estaria', 'El plazo depende del alcance y de la entrega de contenidos. Una web informativa sencilla normalmente puede planificarse desde 5 días hábiles; un asesor confirmará el plazo exacto.', 150
WHERE NOT EXISTS (SELECT 1 FROM `whatsapp_knowledge` WHERE `title` = 'Tiempo de entrega');

INSERT INTO `whatsapp_knowledge` (`title`, `keywords`, `answer`, `priority`)
SELECT 'Formas de pago', 'forma de pago,medios de pago,como pago,cuotas', 'Las condiciones y medios de pago se confirman en la cotización. Cuéntame qué proyecto necesitas y un asesor te entregará las alternativas disponibles.', 120
WHERE NOT EXISTS (SELECT 1 FROM `whatsapp_knowledge` WHERE `title` = 'Formas de pago');
