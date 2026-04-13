-- =============================================================================
-- Script para poblar business_indicator_controls con los datos ya existentes
-- Ejecutar DESPUÉS de crear la tabla business_indicator_controls
-- y DESPUÉS de haber corrido business_indicators.sql (para que month/year
-- estén poblados en todas las tablas de bloques)
-- =============================================================================

INSERT INTO business_indicator_controls (month, year, data, status_id, id_user, created_at, updated_at)
SELECT
    t.month,
    t.year,
    JSON_OBJECT(
        'pit_indicators',
            IF(EXISTS(
                SELECT 1 FROM pit_indicators
                WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON)),
        'gross_margin',
            IF(EXISTS(
                SELECT 1 FROM gross_margin
                WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON)),
        'gross_margins_trend',
            IF(EXISTS(
                SELECT 1 FROM gross_margins_trend
                WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON)),
        'livestock_input_output_ratio',
            IF(EXISTS(
                SELECT 1 FROM livestock_input_output_ratio
                WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON)),
        'agricultural_input_output_relationship',
            IF(EXISTS(
                SELECT 1 FROM agricultural_input_output_relationship
                WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON)),
        'products_prices',
            IF(EXISTS(
                SELECT 1 FROM products_prices
                WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON)),
        'harvest_prices',
            IF(EXISTS(
                SELECT 1 FROM harvest_prices
                WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON)),
        'main_crops_buying_selling_traffic_light',
            IF(EXISTS(
                SELECT 1 FROM main_crops_buying_selling_traffic_light
                WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON))
    ) AS data,
    IF(
        EXISTS(SELECT 1 FROM pit_indicators                         WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL) OR
        EXISTS(SELECT 1 FROM gross_margin                           WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL) OR
        EXISTS(SELECT 1 FROM gross_margins_trend                    WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL) OR
        EXISTS(SELECT 1 FROM livestock_input_output_ratio           WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL) OR
        EXISTS(SELECT 1 FROM agricultural_input_output_relationship  WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL) OR
        EXISTS(SELECT 1 FROM products_prices                        WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL) OR
        EXISTS(SELECT 1 FROM harvest_prices                         WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL) OR
        EXISTS(SELECT 1 FROM main_crops_buying_selling_traffic_light WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL),
        1, 2
    ) AS status_id,
    NULL AS id_user,
    NOW() AS created_at,
    NOW() AS updated_at
FROM (
    -- Unión de todas las combinaciones mes/año de todos los bloques
    SELECT DISTINCT month, year FROM pit_indicators WHERE deleted_at IS NULL AND month IS NOT NULL AND year IS NOT NULL
    UNION
    SELECT DISTINCT month, year FROM gross_margin WHERE deleted_at IS NULL AND month IS NOT NULL AND year IS NOT NULL
    UNION
    SELECT DISTINCT month, year FROM gross_margins_trend WHERE deleted_at IS NULL AND month IS NOT NULL AND year IS NOT NULL
    UNION
    SELECT DISTINCT month, year FROM livestock_input_output_ratio WHERE deleted_at IS NULL AND month IS NOT NULL AND year IS NOT NULL
    UNION
    SELECT DISTINCT month, year FROM agricultural_input_output_relationship WHERE deleted_at IS NULL AND month IS NOT NULL AND year IS NOT NULL
    UNION
    SELECT DISTINCT month, year FROM products_prices WHERE deleted_at IS NULL AND month IS NOT NULL AND year IS NOT NULL
    UNION
    SELECT DISTINCT month, year FROM harvest_prices WHERE deleted_at IS NULL AND month IS NOT NULL AND year IS NOT NULL
    UNION
    SELECT DISTINCT month, year FROM main_crops_buying_selling_traffic_light WHERE deleted_at IS NULL AND month IS NOT NULL AND year IS NOT NULL
) t
ORDER BY t.year, t.month
ON DUPLICATE KEY UPDATE
    data       = VALUES(data),
    status_id  = VALUES(status_id),
    updated_at = NOW();
