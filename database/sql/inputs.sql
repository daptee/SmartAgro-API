CREATE TABLE inputs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    status_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (status_id) REFERENCES status(id)
);

INSERT INTO inputs (name, status_id) VALUES
('Glifosato 54%', 1),
('Fosfato Diamónico', 1),
('Urea', 1),
('Gasoil', 1),
('Ternero', 1),
('Ternera', 1),
('Vaquillona c/ Gtia. Preñez', 1),
('Atrazina', 1),
('Soja', 1);

CREATE TABLE main_crops_buying_selling_traffic_light (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_plan INT NOT NULL,
    date DATE NOT NULL,
    input INT NOT NULL,
    variable VARCHAR(100) NOT NULL,
    data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_plan) REFERENCES plans(id),
    FOREIGN KEY (input) REFERENCES inputs(id)
);