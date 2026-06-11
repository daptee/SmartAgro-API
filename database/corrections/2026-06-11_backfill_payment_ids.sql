-- =============================================================================
-- BACKFILL payment_id EN payment_history
-- Completa payment_id = NULL usando el campo $.id del JSON de cada registro.
-- Omite filas donde ese payment_id ya existe en otra fila (UNIQUE constraint).
-- =============================================================================

-- 1. Ver cuántos registros tienen payment_id NULL y tienen $.id en el JSON
SELECT type, COUNT(*) AS pendientes
FROM payment_history
WHERE payment_id IS NULL
  AND JSON_EXTRACT(data, '$.id') IS NOT NULL
  AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.id')) != 'null'
GROUP BY type
ORDER BY type;

-- 2. Ver duplicados que quedarán en NULL (mismo $.id en varias filas)
SELECT
    ph.id,
    ph.type,
    ph.id_user,
    CAST(JSON_UNQUOTE(JSON_EXTRACT(ph.data, '$.id')) AS UNSIGNED) AS mp_id,
    ph.created_at
FROM payment_history ph
WHERE ph.payment_id IS NULL
  AND JSON_EXTRACT(ph.data, '$.id') IS NOT NULL
  AND EXISTS (
      SELECT 1 FROM (
          SELECT JSON_UNQUOTE(JSON_EXTRACT(data, '$.id')) AS dup_id
          FROM payment_history
          WHERE JSON_EXTRACT(data, '$.id') IS NOT NULL
          GROUP BY dup_id
          HAVING COUNT(*) > 1
      ) AS dups
      WHERE dups.dup_id = JSON_UNQUOTE(JSON_EXTRACT(ph.data, '$.id'))
  )
ORDER BY CAST(JSON_UNQUOTE(JSON_EXTRACT(ph.data, '$.id')) AS UNSIGNED), ph.id;

-- 3. Backfill: asigna payment_id eligiendo solo el registro más viejo (MIN id)
--    por cada $.id, y saltea los que ya tienen ese payment_id en otra fila.
SET SQL_SAFE_UPDATES = 0;

UPDATE payment_history ph
INNER JOIN (
    -- Por cada $.id único entre filas con payment_id NULL, tomar solo la más vieja.
    -- Filtra por tipo de pago real y descarta UUIDs de preapproval que MySQL
    -- castea erróneamente a números pequeños (MP payment IDs son > 1.000.000).
    SELECT
        MIN(id)                                                       AS keep_id,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.id')) AS UNSIGNED)   AS mp_payment_id
    FROM payment_history
    WHERE payment_id IS NULL
      AND type IN ('approved', 'rejected', 'in_process', 'authorized', 'pending', 'payment')
      AND JSON_EXTRACT(data, '$.id') IS NOT NULL
      AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.id')) != 'null'
      AND CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.id')) AS UNSIGNED) > 1000000
    GROUP BY JSON_UNQUOTE(JSON_EXTRACT(data, '$.id'))
) AS candidates ON ph.id = candidates.keep_id
-- Saltear si ese payment_id ya existe como no-NULL en otra fila
LEFT JOIN payment_history ph_existing
    ON ph_existing.payment_id = candidates.mp_payment_id
SET ph.payment_id = candidates.mp_payment_id
WHERE ph_existing.id IS NULL;

SET SQL_SAFE_UPDATES = 1;

-- 4. Verificar resultado
SELECT
    type,
    COUNT(*)                 AS total,
    COUNT(payment_id)        AS con_payment_id,
    SUM(payment_id IS NULL)  AS sin_payment_id
FROM payment_history
GROUP BY type
ORDER BY type;

-- 5. Registros que quedaron en NULL (duplicados — revisar manualmente)
SELECT
    ph.id,
    ph.type,
    ph.id_user,
    ph.payment_id,
    CAST(JSON_UNQUOTE(JSON_EXTRACT(ph.data, '$.id')) AS UNSIGNED) AS mp_id_en_json,
    ph.created_at
FROM payment_history ph
WHERE ph.payment_id IS NULL
  AND JSON_EXTRACT(ph.data, '$.id') IS NOT NULL
  AND JSON_UNQUOTE(JSON_EXTRACT(ph.data, '$.id')) != 'null'
ORDER BY mp_id_en_json, ph.id;
