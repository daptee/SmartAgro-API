CREATE TABLE statuses_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status_name VARCHAR(50) NOT NULL
);

INSERT INTO statuses_reports (status_name)
VALUES
('Publicado'),
('Borrador');

ALTER TABLE news
ADD COLUMN status_id INT AFTER id_plan,
ADD COLUMN id_user INT NULL AFTER status_id,
ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at,
ADD CONSTRAINT fk_news_status
    FOREIGN KEY (status_id) REFERENCES statuses_reports(id);

SET SQL_SAFE_UPDATES = 0;

UPDATE news
SET status_id = 1;

SET SQL_SAFE_UPDATES = 1;

ALTER TABLE mag_lease_index
ADD COLUMN status_id INT AFTER id_plan,
ADD COLUMN id_user INT NULL AFTER status_id,
ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at,
ADD CONSTRAINT fk_mag_lease_index_status
    FOREIGN KEY (status_id) REFERENCES statuses_reports(id);

SET SQL_SAFE_UPDATES = 0;

UPDATE mag_lease_index
SET status_id = 1;

SET SQL_SAFE_UPDATES = 1;

ALTER TABLE mag_steer_index
ADD COLUMN status_id INT AFTER id_plan,
ADD COLUMN id_user INT NULL AFTER status_id,
ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at,
ADD CONSTRAINT fk_mag_steer_index_status
    FOREIGN KEY (status_id) REFERENCES statuses_reports(id);

SET SQL_SAFE_UPDATES = 0;

UPDATE mag_steer_index
SET status_id = 1;

SET SQL_SAFE_UPDATES = 1;

ALTER TABLE major_crops
ADD COLUMN month INT NULL AFTER data,
ADD COLUMN year INT NULL AFTER month,
ADD COLUMN status_id INT AFTER id_plan,
ADD COLUMN id_user INT NULL AFTER status_id,
ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER id_user,
ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at,
ADD CONSTRAINT fk_major_crops_status
    FOREIGN KEY (status_id) REFERENCES statuses_reports(id);

SET SQL_SAFE_UPDATES = 0;

UPDATE major_crops
SET status_id = 1;

SET SQL_SAFE_UPDATES = 1;

ALTER TABLE insights
ADD COLUMN status_id INT AFTER id_plan,
ADD COLUMN id_user INT NULL AFTER status_id,
ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at,
ADD CONSTRAINT fk_insights_status
    FOREIGN KEY (status_id) REFERENCES statuses_reports(id);

SET SQL_SAFE_UPDATES = 0;

UPDATE insights
SET status_id = 1;

SET SQL_SAFE_UPDATES = 1;

ALTER TABLE rainfall_record_provinces
ADD COLUMN month INT NULL AFTER data,
ADD COLUMN year INT NULL AFTER month,
ADD COLUMN status_id INT AFTER id_plan,
ADD COLUMN id_user INT NULL AFTER status_id,
ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at,
ADD CONSTRAINT fk_rainfall_record_provinces_status
    FOREIGN KEY (status_id) REFERENCES statuses_reports(id);

SET SQL_SAFE_UPDATES = 0;

UPDATE rainfall_record_provinces
SET status_id = 1;

-- Extraer month y year del campo date para rainfall_record_provinces
UPDATE rainfall_record_provinces
SET month = MONTH(date),
    year = YEAR(date)
WHERE id > 0 AND date IS NOT NULL;

SET SQL_SAFE_UPDATES = 1;

ALTER TABLE main_grain_prices
ADD COLUMN month INT NULL AFTER data,
ADD COLUMN year INT NULL AFTER month,
ADD COLUMN status_id INT AFTER id_plan,
ADD COLUMN id_user INT NULL AFTER status_id,
ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at,
ADD CONSTRAINT fk_main_grain_prices_status
    FOREIGN KEY (status_id) REFERENCES statuses_reports(id);

SET SQL_SAFE_UPDATES = 0;

UPDATE main_grain_prices
SET status_id = 1;

-- Extraer month y year del campo date para main_grain_prices
UPDATE main_grain_prices
SET month = MONTH(date),
    year = YEAR(date)
WHERE id > 0 AND date IS NOT NULL;

SET SQL_SAFE_UPDATES = 1;

ALTER TABLE prices_main_active_ingredients_producers
ADD COLUMN month INT NULL AFTER data,
ADD COLUMN year INT NULL AFTER month,
ADD COLUMN status_id INT AFTER id_plan,
ADD COLUMN id_user INT NULL AFTER status_id,
ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at,
ADD CONSTRAINT fk_prices_main_active_ingredients_producers_status
    FOREIGN KEY (status_id) REFERENCES statuses_reports(id);

SET SQL_SAFE_UPDATES = 0;

UPDATE prices_main_active_ingredients_producers
SET status_id = 1;

-- Extraer month y year del campo date para prices_main_active_ingredients_producers
UPDATE prices_main_active_ingredients_producers
SET month = MONTH(date),
    year = YEAR(date)
WHERE id > 0 AND date IS NOT NULL;

SET SQL_SAFE_UPDATES = 1;

ALTER TABLE producer_segment_prices
ADD COLUMN month INT NULL AFTER data,
ADD COLUMN year INT NULL AFTER month,
ADD COLUMN status_id INT AFTER id_plan,
ADD COLUMN id_user INT NULL AFTER status_id,
ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at,
ADD CONSTRAINT fk_producer_segment_prices_status
    FOREIGN KEY (status_id) REFERENCES statuses_reports(id);

SET SQL_SAFE_UPDATES = 0;

UPDATE producer_segment_prices
SET status_id = 1;

-- Extraer month y year del campo date para producer_segment_prices
UPDATE producer_segment_prices
SET month = MONTH(date),
    year = YEAR(date)
WHERE id > 0 AND date IS NOT NULL;

SET SQL_SAFE_UPDATES = 1;