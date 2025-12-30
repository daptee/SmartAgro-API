

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
