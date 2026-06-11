-- =============================================================================
-- AGREGAR COLUMNA subscription_type A payment_history
-- Valores: 'monthly' | 'yearly' | NULL (para free_trial/paused/cancelled/failed
-- que no tienen datos de período, o registros sin info suficiente).
-- =============================================================================

-- 1. Agregar columna
ALTER TABLE payment_history
    ADD COLUMN subscription_type VARCHAR(10) NULL DEFAULT NULL AFTER amount;

-- 2. Backfill desde payment data (approved, rejected, payment, etc.)
--    Fuente: $.point_of_interaction.transaction_data.invoice_period.period
SET SQL_SAFE_UPDATES = 0;

UPDATE payment_history
SET subscription_type = CASE
    WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.point_of_interaction.transaction_data.invoice_period.period')) AS UNSIGNED) = 12
        THEN 'yearly'
    WHEN JSON_EXTRACT(data, '$.point_of_interaction.transaction_data.invoice_period.period') IS NOT NULL
        THEN 'monthly'
    ELSE NULL
END
WHERE type IN ('approved', 'rejected', 'in_process', 'authorized', 'pending', 'payment')
  AND subscription_type IS NULL;

-- 3. Backfill desde preapproval data (free_trial, paused, cancelled, failed)
--    Fuente: $.auto_recurring.frequency + $.auto_recurring.frequency_type
UPDATE payment_history
SET subscription_type = CASE
    WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.auto_recurring.frequency')) AS UNSIGNED) = 12
     AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.auto_recurring.frequency_type')) = 'months'
        THEN 'yearly'
    WHEN JSON_EXTRACT(data, '$.auto_recurring.frequency') IS NOT NULL
        THEN 'monthly'
    ELSE NULL
END
WHERE type IN ('free_trial', 'paused', 'cancelled', 'failed')
  AND subscription_type IS NULL;

-- 4. Propagar subscription_type desde otro registro del mismo preapproval_id
--    Cubre rejected/paused viejos en formato authorized_payment que no tienen
--    point_of_interaction ni auto_recurring (los pasos 2 y 3 los dejan en NULL).
UPDATE payment_history ph
INNER JOIN (
    SELECT preapproval_id,
           MAX(subscription_type) AS sub_type   -- MAX ignora NULLs; si hay un valor lo toma
    FROM payment_history
    WHERE subscription_type IS NOT NULL
      AND preapproval_id IS NOT NULL
    GROUP BY preapproval_id
) AS ref ON ph.preapproval_id = ref.preapproval_id
SET ph.subscription_type = ref.sub_type
WHERE ph.subscription_type IS NULL
  AND ph.preapproval_id IS NOT NULL;

-- 5. Fallback: los que siguen en NULL sin preapproval_id o sin par conocido → 'monthly'
--    (todas las suscripciones de este sistema son monthly excepto las anuales,
--    que se distinguen por amount >> 10000 ARS o por period=12, ya cubiertos arriba)
UPDATE payment_history
SET subscription_type = 'monthly'
WHERE subscription_type IS NULL;

SET SQL_SAFE_UPDATES = 1;

-- 7. Verificar resultado
SELECT type,
       subscription_type,
       COUNT(*) AS total
FROM payment_history
GROUP BY type, subscription_type
ORDER BY type, subscription_type;
