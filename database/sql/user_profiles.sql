ALTER TABLE users_profiles
ADD COLUMN status_id INT AFTER name,
ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at,
ADD CONSTRAINT fk_users_profiles
    FOREIGN KEY (status_id) REFERENCES status(id);

SET SQL_SAFE_UPDATES = 0;

UPDATE users_profiles
SET status_id = 1;

SET SQL_SAFE_UPDATES = 1;