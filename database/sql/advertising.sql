ALTER TABLE advertising_spaces
ADD status_id INT NOT NULL DEFAULT 1,
ADD FOREIGN KEY (status_id) REFERENCES status(id);

-- Tabla para almacenar registros detallados de impresiones y clics en publicidades
CREATE TABLE advertising_interactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    -- Referencia a la publicidad (soporta ambos tipos de publicidades)
    id_company_advertising INT NULL,
    id_company_plan_publicity INT NULL,

    -- Tipo de interacción
    interaction_type ENUM('impression', 'click') NOT NULL,

    -- Información del usuario (NULL si es usuario anónimo)
    user_id BIGINT UNSIGNED NULL,

    -- Datos de contexto adicionales (JSON)
    context_data JSON NULL COMMENT 'Puede incluir: user_agent, ip_address, referrer_url, device_type, etc.',

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Índices para optimizar consultas
    INDEX idx_company_advertising (id_company_advertising),
    INDEX idx_company_plan_publicity (id_company_plan_publicity),
    INDEX idx_user_id (user_id),
    INDEX idx_interaction_type (interaction_type),
    INDEX idx_created_at (created_at),
    INDEX idx_composite_advertising (id_company_advertising, interaction_type, created_at),
    INDEX idx_composite_plan_publicity (id_company_plan_publicity, interaction_type, created_at),

    -- Claves foráneas
    FOREIGN KEY (id_company_advertising) REFERENCES companies_advertisings(id) ON DELETE CASCADE,
    FOREIGN KEY (id_company_plan_publicity) REFERENCES company_plan_publicities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,

    -- Constraint para asegurar que se especifique al menos un tipo de publicidad
    CONSTRAINT chk_advertising_type CHECK (
        (id_company_advertising IS NOT NULL AND id_company_plan_publicity IS NULL) OR
        (id_company_advertising IS NULL AND id_company_plan_publicity IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registra cada impresión y clic en publicidades con información de usuario y timestamp';

-- Vista para facilitar consultas de reportes de publicidades de empresas
CREATE OR REPLACE VIEW v_company_advertising_stats AS
SELECT
    ca.id as advertising_id,
    ca.id_company,
    ca.id_advertising_space,
    c.company_name,
    as_space.name as space_name,
    ca.date_start,
    ca.date_end,
    COUNT(CASE WHEN ai.interaction_type = 'impression' THEN 1 END) as total_impressions,
    COUNT(CASE WHEN ai.interaction_type = 'click' THEN 1 END) as total_clicks,
    COUNT(DISTINCT ai.user_id) as unique_users,
    COUNT(DISTINCT CASE WHEN ai.interaction_type = 'click' THEN ai.user_id END) as unique_clickers,
    ROUND(
        CASE
            WHEN COUNT(CASE WHEN ai.interaction_type = 'impression' THEN 1 END) > 0
            THEN (COUNT(CASE WHEN ai.interaction_type = 'click' THEN 1 END) * 100.0) /
                 COUNT(CASE WHEN ai.interaction_type = 'impression' THEN 1 END)
            ELSE 0
        END,
        2
    ) as ctr_percentage
FROM companies_advertisings ca
LEFT JOIN advertising_interactions ai ON ca.id = ai.id_company_advertising
LEFT JOIN companies c ON ca.id_company = c.id
LEFT JOIN advertising_spaces as_space ON ca.id_advertising_space = as_space.id
GROUP BY ca.id, ca.id_company, ca.id_advertising_space, c.company_name, as_space.name, ca.date_start, ca.date_end;

-- Vista para facilitar consultas de reportes de publicidades de planes
CREATE OR REPLACE VIEW v_company_plan_publicity_stats AS
SELECT
    cpp.id as publicity_id,
    cpp.id_company_plan,
    cpp.id_advertising_space,
    cp.id_company,
    c.company_name,
    as_space.name as space_name,
    cp.date_start,
    cp.date_end,
    COUNT(CASE WHEN ai.interaction_type = 'impression' THEN 1 END) as total_impressions,
    COUNT(CASE WHEN ai.interaction_type = 'click' THEN 1 END) as total_clicks,
    COUNT(DISTINCT ai.user_id) as unique_users,
    COUNT(DISTINCT CASE WHEN ai.interaction_type = 'click' THEN ai.user_id END) as unique_clickers,
    ROUND(
        CASE
            WHEN COUNT(CASE WHEN ai.interaction_type = 'impression' THEN 1 END) > 0
            THEN (COUNT(CASE WHEN ai.interaction_type = 'click' THEN 1 END) * 100.0) /
                 COUNT(CASE WHEN ai.interaction_type = 'impression' THEN 1 END)
            ELSE 0
        END,
        2
    ) as ctr_percentage
FROM company_plan_publicities cpp
LEFT JOIN advertising_interactions ai ON cpp.id = ai.id_company_plan_publicity
LEFT JOIN company_plans cp ON cpp.id_company_plan = cp.id
LEFT JOIN companies c ON cp.id_company = c.id
LEFT JOIN advertising_spaces as_space ON cpp.id_advertising_space = as_space.id
GROUP BY cpp.id, cpp.id_company_plan, cpp.id_advertising_space, cp.id_company, c.company_name, as_space.name, cp.date_start, cp.date_end;