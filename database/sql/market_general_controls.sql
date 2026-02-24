CREATE TABLE market_general_controls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month INT NOT NULL,
    year INT NOT NULL,
    data JSON NULL,
    status_id INT NOT NULL DEFAULT 2,
    id_user INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_market_general_controls_status
        FOREIGN KEY (status_id) REFERENCES statuses_reports(id) ON DELETE RESTRICT
);
