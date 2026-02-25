-- ============================================================
-- BUSINESS INDICATORS - Ajuste de estructura
-- ============================================================
-- Se agregan columnas "month", "year", "status_id" e "id_user"
-- a la tabla gross_margin.
-- Las columnas "date" y "region" se mantienen por ahora.
-- ============================================================

-- gross_margin
-- ============================================================

ALTER TABLE gross_margin
ADD COLUMN month INT NULL AFTER data,
ADD COLUMN year INT NULL AFTER month,
ADD COLUMN status_id INT NULL AFTER id_plan,
ADD COLUMN id_user INT NULL AFTER status_id,
ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at,
ADD CONSTRAINT fk_gross_margin_status
    FOREIGN KEY (status_id) REFERENCES statuses_reports(id);

SET SQL_SAFE_UPDATES = 0;

-- Poblar month y year desde date
UPDATE gross_margin
SET month = MONTH(date),
    year  = YEAR(date)
WHERE id > 0 AND date IS NOT NULL;

-- Todos los registros existentes quedan como publicados
UPDATE gross_margin
SET status_id = 1
WHERE id > 0;

SET SQL_SAFE_UPDATES = 1;

-- ============================================================
-- gross_margins_trend
-- La columna "month" actual almacena "YYYY-MM" (mes y año juntos).
-- Se renombra a "month_label" para conservar el dato histórico
-- y se agregan las nuevas columnas "month" (INT) y "year" (INT).
-- Las columnas "date" y "region" se mantienen por ahora.
-- ============================================================

ALTER TABLE gross_margins_trend
ADD COLUMN year INT NULL AFTER month,
ADD COLUMN status_id INT NULL AFTER id_plan,
ADD COLUMN id_user INT NULL AFTER status_id,
ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at,
ADD CONSTRAINT fk_gross_margins_trend_status
    FOREIGN KEY (status_id) REFERENCES statuses_reports(id);

SET SQL_SAFE_UPDATES = 0;

-- Poblar month y year desde date
UPDATE gross_margins_trend
SET month = MONTH(date),
    year  = YEAR(date)
WHERE id > 0 AND date IS NOT NULL;

-- Todos los registros existentes quedan como publicados
UPDATE gross_margins_trend
SET status_id = 1
WHERE id > 0;

SET SQL_SAFE_UPDATES = 1;
