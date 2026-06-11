-- =============================================================================
-- AGREGAR COLUMNA amount A payment_history
-- Guarda el importe cobrado al usuario (transaction_amount de MP).
-- NULL para free_trial, paused, cancelled, failed — solo se llena en pagos reales.
-- =============================================================================

-- 1. Agregar columna (ya ejecutado si no da error)
ALTER TABLE payment_history
    ADD COLUMN amount DECIMAL(12, 2) NULL DEFAULT NULL AFTER payment_id;

-- =============================================================================
-- DIAGNÓSTICO: ver estructura de registros type='payment' antes de backfill
-- Ejecutar esto primero para confirmar qué campo tiene el monto
-- =============================================================================
SELECT
    id,
    type,
    JSON_EXTRACT(data, '$.transaction_amount')       AS campo_transaction_amount,
    JSON_EXTRACT(data, '$.amount')                   AS campo_amount,
    JSON_EXTRACT(data, '$.net_received_amount')      AS campo_net,
    LEFT(JSON_UNQUOTE(data), 120)                    AS data_preview
FROM payment_history
WHERE type = 'payment'
LIMIT 5;

-- =============================================================================
-- 2. Backfill para approved / rejected / in_process / authorized / pending
-- =============================================================================
SET SQL_SAFE_UPDATES = 0;

UPDATE payment_history
SET amount = CAST(
    JSON_UNQUOTE(JSON_EXTRACT(data, '$.transaction_amount'))
    AS DECIMAL(12, 2)
)
WHERE type IN ('approved', 'rejected', 'in_process', 'authorized', 'pending')
  AND JSON_EXTRACT(data, '$.transaction_amount') IS NOT NULL
  AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.transaction_amount')) != 'null';

-- =============================================================================
-- 3. Backfill para type='payment'
-- Ajustar el campo ($.transaction_amount, $.amount, etc.) según el resultado
-- del SELECT diagnóstico de arriba
-- =============================================================================
UPDATE payment_history
SET amount = CAST(
    JSON_UNQUOTE(JSON_EXTRACT(data, '$.transaction_amount'))
    AS DECIMAL(12, 2)
)
WHERE type = 'payment'
  AND JSON_EXTRACT(data, '$.transaction_amount') IS NOT NULL
  AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.transaction_amount')) != 'null';

-- =============================================================================
-- 4a. Diagnóstico: ver duplicados antes del backfill de payment_id
-- Registros con el mismo $.id en JSON — se actualizará solo el más viejo (menor id),
-- los demás quedan con payment_id NULL para revisión manual.
-- =============================================================================
SELECT
    ph.id,
    ph.type,
    ph.id_user,
    ph.payment_id                                          AS payment_id_actual,
    CAST(JSON_UNQUOTE(JSON_EXTRACT(ph.data, '$.id')) AS UNSIGNED) AS mp_id_en_json,
    ph.created_at
FROM payment_history ph
WHERE ph.payment_id IS NULL
  AND ph.type IN ('approved', 'rejected', 'in_process', 'authorized', 'pending', 'payment')
  AND JSON_EXTRACT(ph.data, '$.id') IS NOT NULL
  AND EXISTS (
      SELECT 1 FROM (
          SELECT JSON_UNQUOTE(JSON_EXTRACT(data, '$.id')) AS dup_id
          FROM payment_history
          WHERE type IN ('approved', 'rejected', 'in_process', 'authorized', 'pending', 'payment')
          GROUP BY dup_id
          HAVING COUNT(*) > 1
      ) AS dups
      WHERE dups.dup_id = JSON_UNQUOTE(JSON_EXTRACT(ph.data, '$.id'))
  )
ORDER BY ph.id;

-- =============================================================================
-- 4b. Backfill payment_id: solo actualiza cuando NO existe ya ese payment_id
-- en otro registro (evita el error de UNIQUE). Los duplicados quedan en NULL.
-- =============================================================================
UPDATE payment_history ph
LEFT JOIN payment_history ph2
    ON ph2.payment_id = CAST(JSON_UNQUOTE(JSON_EXTRACT(ph.data, '$.id')) AS UNSIGNED)
    AND ph2.id != ph.id
SET ph.payment_id = CAST(
    JSON_UNQUOTE(JSON_EXTRACT(ph.data, '$.id'))
    AS UNSIGNED
)
WHERE ph.payment_id IS NULL
  AND ph.type IN ('approved', 'rejected', 'in_process', 'authorized', 'pending', 'payment')
  AND JSON_EXTRACT(ph.data, '$.id') IS NOT NULL
  AND JSON_UNQUOTE(JSON_EXTRACT(ph.data, '$.id')) != 'null'
  AND ph2.id IS NULL;  -- solo si no existe ya ese payment_id en otra fila

SET SQL_SAFE_UPDATES = 1;

-- 5. Verificar resultado final
SELECT type, COUNT(*) AS total,
       COUNT(amount)     AS con_amount,    SUM(amount IS NULL)     AS sin_amount,
       COUNT(payment_id) AS con_payment_id, SUM(payment_id IS NULL) AS sin_payment_id
FROM payment_history
GROUP BY type
ORDER BY type;
