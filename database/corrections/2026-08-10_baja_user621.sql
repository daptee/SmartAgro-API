-- =============================================================================
-- CORRECCIÓN MANUAL DE BAJA - 2026-08-10
-- Usuario 621 (Juan Cruz Nogues) — activó el free trial de Siembra el 09/09/2025
-- 22:43 (plan_start_date), nunca llegó a pagar y canceló la suscripción en
-- Mercado Pago un mes después. La baja nunca quedó registrada en users_plans,
-- por lo que no aparecía en ninguna métrica de baja ("nunca se suscribieron" ni
-- "se dieron de baja").
-- =============================================================================

-- Verificar estado actual (sin registros de baja para este usuario)
SELECT id, name, last_name, id_plan, free_trial_used, plan_start_date
FROM users WHERE id = 621;

SELECT id, id_plan, preapproval_id, next_payment_date, data, created_at
FROM users_plans WHERE id_user = 621 ORDER BY created_at;

-- Registrar la baja: cancelación en Mercado Pago un mes después del alta
-- (09/09/2025 22:43 + 1 mes = 09/10/2025 22:43), sin ningún pago real de por medio.
INSERT INTO users_plans (id_user, id_plan, preapproval_id, next_payment_date, data, created_at, updated_at)
VALUES (
    621,
    1,
    'c0440b6678944a059c903361f50999ed',
    NULL,
    JSON_OBJECT(
        'reason', 'Cancelación de suscripción',
        'is_system', 'false'
    ),
    '2025-10-09 22:43:43',
    '2025-10-09 22:43:43'
);

-- Verificar resultado
SELECT id, id_plan, preapproval_id, next_payment_date,
       JSON_UNQUOTE(JSON_EXTRACT(data, '$.reason')) AS reason,
       JSON_UNQUOTE(JSON_EXTRACT(data, '$.is_system')) AS is_system,
       created_at
FROM users_plans WHERE id_user = 621 ORDER BY created_at;
