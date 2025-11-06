ALTER TABLE advertising_spaces
ADD status_id INT NOT NULL DEFAULT 1,
ADD FOREIGN KEY (status_id) REFERENCES status(id);