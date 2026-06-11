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

SET SQL_SAFE_UPDATES = 1;

-- 4. Verificar resultado final
SELECT type, COUNT(*) AS total, COUNT(amount) AS con_amount, SUM(amount IS NULL) AS sin_amount
FROM payment_history
GROUP BY type
ORDER BY type;
