CREATE TABLE business_indicator_controls (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    month      INT NOT NULL,
    year       INT NOT NULL,
    data       JSON NULL,
    status_id  INT NOT NULL DEFAULT 2,
    id_user    INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_bic_month_year (month, year),
    CONSTRAINT fk_business_indicator_controls_status
        FOREIGN KEY (status_id) REFERENCES statuses_reports(id) ON DELETE RESTRICT
);
