-- =============================================================================
-- Script para poblar market_general_controls con los datos ya existentes
-- Ejecutar DESPUÉS de crear la tabla market_general_controls
-- =============================================================================

-- Paso 1: Recopilar todas las combinaciones únicas de mes/año que existen
--         en cualquier bloque de información (publicado o borrador)

INSERT INTO market_general_controls (month, year, data, status_id, id_user, created_at, updated_at)
SELECT
    t.month,
    t.year,
    JSON_OBJECT(
        'major_crops',
            IF(EXISTS(
                SELECT 1 FROM major_crops
                WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON)),
        'insights',
            IF(EXISTS(
                SELECT 1 FROM insights
                WHERE MONTH(date) = t.month AND YEAR(date) = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON)),
        'news',
            IF(EXISTS(
                SELECT 1 FROM news
                WHERE MONTH(date) = t.month AND YEAR(date) = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON)),
        'rainfall_records',
            IF(EXISTS(
                SELECT 1 FROM rainfall_record_provinces
                WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON)),
        'main_grain_prices',
            IF(EXISTS(
                SELECT 1 FROM main_grain_prices
                WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON)),
        'price_main_active_ingredients_producers',
            IF(EXISTS(
                SELECT 1 FROM prices_main_active_ingredients_producers
                WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON)),
        'producer_segment_prices',
            IF(EXISTS(
                SELECT 1 FROM producer_segment_prices
                WHERE month = t.month AND year = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON)),
        'mag_lease_index',
            IF(EXISTS(
                SELECT 1 FROM mag_lease_index
                WHERE MONTH(date) = t.month AND YEAR(date) = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON)),
        'mag_steer_index',
            IF(EXISTS(
                SELECT 1 FROM mag_steer_index
                WHERE MONTH(date) = t.month AND YEAR(date) = t.year AND status_id = 1 AND deleted_at IS NULL
            ), CAST(TRUE AS JSON), CAST(FALSE AS JSON))
    ) AS data,
    2 AS status_id,
    NULL AS id_user,
    NOW() AS created_at,
    NOW() AS updated_at
FROM (
    -- Unión de todas las combinaciones mes/año de todos los bloques
    SELECT DISTINCT month, year FROM major_crops WHERE deleted_at IS NULL
    UNION
    SELECT DISTINCT MONTH(date) AS month, YEAR(date) AS year FROM insights WHERE deleted_at IS NULL
    UNION
    SELECT DISTINCT MONTH(date) AS month, YEAR(date) AS year FROM news WHERE deleted_at IS NULL
    UNION
    SELECT DISTINCT month, year FROM rainfall_record_provinces WHERE deleted_at IS NULL
    UNION
    SELECT DISTINCT month, year FROM main_grain_prices WHERE deleted_at IS NULL
    UNION
    SELECT DISTINCT month, year FROM prices_main_active_ingredients_producers WHERE deleted_at IS NULL
    UNION
    SELECT DISTINCT month, year FROM producer_segment_prices WHERE deleted_at IS NULL
    UNION
    SELECT DISTINCT MONTH(date) AS month, YEAR(date) AS year FROM mag_lease_index WHERE deleted_at IS NULL
    UNION
    SELECT DISTINCT MONTH(date) AS month, YEAR(date) AS year FROM mag_steer_index WHERE deleted_at IS NULL
) t
ORDER BY t.year, t.month;
