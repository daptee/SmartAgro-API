ALTER TABLE users ADD referral_code VARCHAR(20) UNIQUE AFTER email;
ALTER TABLE users ADD referred_by BIGINT UNSIGNED NULL AFTER referral_code;

ALTER TABLE users 
    ADD CONSTRAINT fk_users_referred_by 
    FOREIGN KEY (referred_by) REFERENCES users(id)
    ON DELETE SET NULL;