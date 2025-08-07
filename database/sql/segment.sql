CREATE TABLE segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    status_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (status_id) REFERENCES status(id)
);

INSERT INTO segments (name, status_id) VALUES
('Herbicidas', 1),
('Fungicidas foliares', 1),
('Insecticidas foliares', 1),
('Tratamientos de semilla', 1),
('Adyuvantes', 1),
('Otros', 1);

ALTER TABLE products_prices
ADD COLUMN segment_id INT,
ADD FOREIGN KEY (segment_id) REFERENCES segments(id);

ALTER TABLE prices_main_active_ingredients_producers
ADD COLUMN segment_id INT NULL,
ADD FOREIGN KEY (segment_id) REFERENCES segments(id);