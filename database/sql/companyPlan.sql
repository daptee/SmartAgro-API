CREATE TABLE company_plan_publicities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_company_plan INT NOT NULL,
    id_advertising_space INT NOT NULL,
    gif_path VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_company_plan) REFERENCES company_plans(id),
    FOREIGN KEY (id_advertising_space) REFERENCES advertising_spaces(id)
);

CREATE TABLE company_plan_publicity_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_company_plan INT NOT NULL UNIQUE,
    show_any_ads BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_company_plan) REFERENCES company_plans(id)
);

CREATE TABLE users_companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user BIGINT UNSIGNED NOT NULL,
    id_company_plan INT NOT NULL,
    id_user_company_rol INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id),
    FOREIGN KEY (id_company_plan) REFERENCES company_plans(id),
    FOREIGN KEY (id_user_company_rol) REFERENCES users_company_roles(id)
);

CREATE TABLE company_invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_company_plan INT NOT NULL,
    mail VARCHAR(255) NOT NULL,
    id_user_company_rol INT NOT NULL,
    invitation_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_company_plan) REFERENCES company_plans(id),
    FOREIGN KEY (id_user_company_rol) REFERENCES users_company_roles(id)
);

