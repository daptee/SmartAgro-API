-- =============================================================================
-- BACKFILL: plan_start_date, subscription_type, free_trial_used
-- para usuarios que ya tienen o tuvieron el Plan Siembra (id_plan = 2)
-- =============================================================================
-- Lógica:
--   plan_start_date  → fecha del primer registro en users_plans con id_plan = 2
--   subscription_type → derivado del campo 'data' JSON en users_plans:
--                        frequency_type='months' y frequency=12 → 'yearly'
--                        cualquier otro caso                     → 'monthly'
--   free_trial_used  → existe algún registro en payment_history con type = 'free_trial'
--                       para ese usuario
-- =============================================================================

SET SQL_SAFE_UPDATES = 0;

-- -----------------------------------------------------------------------------
-- 1. plan_start_date: fecha más antigua en que el usuario tuvo id_plan = 2
-- -----------------------------------------------------------------------------
UPDATE users u
JOIN (
    SELECT id_user, MIN(created_at) AS first_plan_date
    FROM users_plans
    WHERE id_plan = 2
    GROUP BY id_user
) up ON up.id_user = u.id
SET u.plan_start_date = up.first_plan_date
WHERE u.plan_start_date IS NULL
  AND u.id_plan IN (1, 2);  -- aplica tanto a activos como a ex-suscriptores

-- -----------------------------------------------------------------------------
-- 2. subscription_type: derivado del JSON 'data' del primer registro con id_plan=2
--    MercadoPago usa: frequency_type='months', frequency=1  → mensual
--                     frequency_type='months', frequency=12 → anual
-- -----------------------------------------------------------------------------
UPDATE users u
JOIN (
    SELECT
        up.id_user,
        CASE
            WHEN JSON_UNQUOTE(JSON_EXTRACT(up.data, '$.auto_recurring.frequency_type')) = 'months'
             AND CAST(JSON_UNQUOTE(JSON_EXTRACT(up.data, '$.auto_recurring.frequency')) AS UNSIGNED) = 12
            THEN 'yearly'
            ELSE 'monthly'
        END AS sub_type
    FROM users_plans up
    INNER JOIN (
        SELECT id_user, MIN(id) AS min_id
        FROM users_plans
        WHERE id_plan = 2
          AND data IS NOT NULL
          AND data != 'null'
        GROUP BY id_user
    ) first_record ON first_record.id_user = up.id_user
                   AND first_record.min_id = up.id
) derived ON derived.id_user = u.id
SET u.subscription_type = derived.sub_type
WHERE u.subscription_type IS NULL
  AND u.id_plan IN (1, 2);

-- -----------------------------------------------------------------------------
-- 3. free_trial_used: si existe un registro en payment_history con type = 'free_trial'
-- -----------------------------------------------------------------------------
UPDATE users u
JOIN (
    SELECT DISTINCT id_user
    FROM payment_history
    WHERE type = 'free_trial'
) ph ON ph.id_user = u.id
SET u.free_trial_used = 1
WHERE u.free_trial_used = 0;

SET SQL_SAFE_UPDATES = 1;

-- -----------------------------------------------------------------------------
-- Verificación (ejecutar por separado para revisar resultados)
-- -----------------------------------------------------------------------------
-- SELECT id, name, email, id_plan, plan_start_date, subscription_type, free_trial_used
-- FROM users
-- WHERE plan_start_date IS NOT NULL
-- ORDER BY plan_start_date;
