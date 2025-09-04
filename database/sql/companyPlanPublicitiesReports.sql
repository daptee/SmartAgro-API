CREATE TABLE company_plan_publicities_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_company_plan_publicity INT NOT NULL,
    cant_impressions INT NOT NULL,
    cant_clicks INT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
	FOREIGN KEY (id_company_plan_publicity) REFERENCES company_plan_publicities(id) ON DELETE CASCADE
);