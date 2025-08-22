CREATE TABLE IF NOT EXISTS horarios_disponibilidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contratista_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    disponible BOOLEAN DEFAULT 1,
    motivo_no_disponible VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (contratista_id) REFERENCES usuarios(id),
    UNIQUE KEY unique_contratista_fecha_hora (contratista_id, fecha, hora_inicio)
);

-- Tabla para rate limiting
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_created (ip_address, created_at),
    INDEX idx_created (created_at)
);

-- Tabla para logs de actividad
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id),
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
);

-- Tabla para configuraciones del sistema
CREATE TABLE IF NOT EXISTS configuraciones_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    descripcion TEXT NULL,
    tipo ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar configuraciones por defecto
INSERT IGNORE INTO configuraciones_sistema (clave, valor, descripcion, tipo) VALUES
('rate_limit_requests', '100', 'Número máximo de requests por ventana de tiempo', 'integer'),
('rate_limit_window', '15', 'Ventana de tiempo en minutos para rate limiting', 'integer'),
('cache_ttl_default', '3600', 'TTL por defecto para cache en segundos', 'integer'),
('notifications_enabled', 'true', 'Habilitar notificaciones WhatsApp', 'boolean'),
('maintenance_mode', 'false', 'Modo mantenimiento activado', 'boolean');
