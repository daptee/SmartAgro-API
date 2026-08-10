ALTER TABLE insights
    ADD COLUMN id_classification BIGINT UNSIGNED NULL AFTER id_plan,
    ADD CONSTRAINT fk_insights_classification
        FOREIGN KEY (id_classification)
        REFERENCES classifications (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
