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