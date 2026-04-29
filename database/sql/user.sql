

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL
);

INSERT INTO roles (name, description) VALUES
('admin', 'Administrador del sistema'),
('user', 'Usuario regular');

CREATE TABLE user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user BIGINT UNSIGNED NOT NULL,
    id_role INT NOT NULL, 
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_role) REFERENCES roles(id) ON DELETE CASCADE
);

ALTER TABLE users
ADD COLUMN is_debtor TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE users
ADD COLUMN grace_period_used TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Indica si el usuario ya usó su período de gracia (1 vez por usuario)';

ALTER TABLE users
ADD COLUMN event_id BIGINT NULL,
ADD CONSTRAINT fk_users_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL;

-- Fecha en que el usuario se pasó al plan siembra (plan 2)
ALTER TABLE users
ADD COLUMN plan_start_date DATETIME NULL COMMENT 'Fecha en que el usuario se suscribió al Plan Siembra';

-- Tipo de suscripción al plan siembra: monthly (mensual) o yearly (anual)
ALTER TABLE users
ADD COLUMN subscription_type ENUM('monthly', 'yearly') NULL COMMENT 'Tipo de suscripción al Plan Siembra: mensual o anual';

-- Si el usuario activó el beneficio del primer mes gratuito
ALTER TABLE users
ADD COLUMN free_trial_used TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Indica si el usuario utilizó el beneficio del primer mes gratuito';

-- Fecha y hora de la última actividad registrada del usuario
ALTER TABLE users
ADD COLUMN last_activity_at DATETIME NULL COMMENT 'Fecha y hora de la última actividad del usuario en la plataforma';

CREATE INDEX idx_users_plan_start_date ON users (plan_start_date);
CREATE INDEX idx_users_last_activity_at ON users (last_activity_at);
