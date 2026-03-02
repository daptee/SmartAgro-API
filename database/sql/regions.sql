ALTER TABLE regions
ADD COLUMN status_id INT AFTER region,
ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at,
ADD CONSTRAINT fk_regions_status
    FOREIGN KEY (status_id) REFERENCES status(id);

SET SQL_SAFE_UPDATES = 0;

UPDATE regions
SET status_id = 1;

SET SQL_SAFE_UPDATES = 1;