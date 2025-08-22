-- Tabla de pagos (ejecutar si no existe)
CREATE TABLE IF NOT EXISTS `pagos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cita_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `contratista_id` int(11) NOT NULL,
  `monto_consulta` decimal(10,2) NOT NULL,
  `monto_servicio` decimal(10,2) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `estado_consulta` enum('pendiente','retenido','capturado','rechazado','reembolsado') DEFAULT 'pendiente',
  `estado_servicio` enum('pendiente','pagado','reembolsado') DEFAULT 'pendiente',
  `mp_payment_id` varchar(100) DEFAULT NULL,
  `mp_preference_id` varchar(100) DEFAULT NULL,
  `mp_status` varchar(50) DEFAULT NULL,
  `external_reference` varchar(100) DEFAULT NULL,
  `reembolso_solicitado` tinyint(1) DEFAULT 0,
  `reembolso_aprobado` tinyint(1) DEFAULT 0,
  `motivo_reembolso` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `consulta_pagada_at` timestamp NULL DEFAULT NULL,
  `servicio_pagado_at` timestamp NULL DEFAULT NULL,
  `reembolsada_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cita` (`cita_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_contratista` (`contratista_id`),
  KEY `idx_mp_payment` (`mp_payment_id`),
  KEY `idx_external_ref` (`external_reference`),
  FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`),
  FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`),
  FOREIGN KEY (`contratista_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de notificaciones (ejecutar si no existe)
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `leida_at` timestamp NULL DEFAULT NULL,
  `enviada_whatsapp` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_leida` (`leida`),
  KEY `idx_tipo` (`tipo`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de evaluaciones (ejecutar si no existe)
CREATE TABLE IF NOT EXISTS `evaluaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cita_id` int(11) NOT NULL,
  `evaluador_id` int(11) NOT NULL,
  `evaluado_id` int(11) NOT NULL,
  `tipo_evaluador` enum('cliente','contratista') NOT NULL,
  `calificacion` int(1) NOT NULL CHECK (`calificacion` >= 1 AND `calificacion` <= 5),
  `comentario` text DEFAULT NULL,
  `puntualidad` int(1) DEFAULT NULL CHECK (`puntualidad` >= 1 AND `puntualidad` <= 5),
  `calidad_trabajo` int(1) DEFAULT NULL CHECK (`calidad_trabajo` >= 1 AND `calidad_trabajo` <= 5),
  `comunicacion` int(1) DEFAULT NULL CHECK (`comunicacion` >= 1 AND `comunicacion` <= 5),
  `limpieza` int(1) DEFAULT NULL CHECK (`limpieza` >= 1 AND `limpieza` <= 5),
  `visible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cita` (`cita_id`),
  KEY `idx_evaluador` (`evaluador_id`),
  KEY `idx_evaluado` (`evaluado_id`),
  KEY `idx_tipo_evaluador` (`tipo_evaluador`),
  KEY `idx_calificacion` (`calificacion`),
  FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`),
  FOREIGN KEY (`evaluador_id`) REFERENCES `usuarios` (`id`),
  FOREIGN KEY (`evaluado_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar campo password a usuarios si no existe
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS `password` varchar(255) DEFAULT NULL AFTER `email`;

-- Agregar campo whatsapp a usuarios si no existe
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS `whatsapp` varchar(20) DEFAULT NULL AFTER `telefono`;