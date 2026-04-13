ALTER TABLE classifications
    ADD COLUMN id_icon BIGINT UNSIGNED NULL AFTER id_parent_classification,
    ADD CONSTRAINT fk_classifications_icon
        FOREIGN KEY (id_icon)
        REFERENCES icons (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
