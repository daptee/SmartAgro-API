-- =============================================================================
-- LIMPIEZA USER 739
-- 1. Eliminar free_trial duplicados (válido solo el primero: id=90, ene-2026)
-- 2. Eliminar rejected duplicados por el bug de dedup de 5 minutos
-- 3. Actualizar payment_id en los registros que quedan (para dedup futuro)
-- 4. Marcar free_trial_used = true en users
-- =============================================================================

-- ── Verificar antes de ejecutar ──────────────────────────────────────────────
SELECT id, type, LEFT(JSON_UNQUOTE(JSON_EXTRACT(data, '$.id')), 20) AS ap_id,
       payment_id, created_at
FROM payment_history
WHERE id_user = 739
ORDER BY created_at;

-- ── 1. Eliminar free_trial duplicados (mantener id=90, el primero) ───────────
DELETE FROM payment_history
WHERE id_user = 739
  AND type = 'free_trial'
  AND id <> 90;

-- ── 2. Eliminar rejected duplicados (mantener el más viejo de cada ap_id) ────
-- authorized_payment 7027442720 → mantener id=114
DELETE FROM payment_history WHERE id IN (117, 121, 124, 126);

-- authorized_payment 7027359519 → mantener id=115
DELETE FROM payment_history WHERE id IN (118, 122);

-- authorized_payment 7027505485 → mantener id=116
DELETE FROM payment_history WHERE id IN (120, 123, 125, 127);

-- authorized_payment 7028246685 → mantener id=131
DELETE FROM payment_history WHERE id IN (132, 136, 139, 140);

-- authorized_payment 7028336589 → mantener id=135
DELETE FROM payment_history WHERE id IN (138, 142, 144);

-- authorized_payment 7028395003 → mantener id=137
DELETE FROM payment_history WHERE id IN (141, 143, 147);

-- ── 3. Asignar payment_id para dedup futuro (UNIQUE index) ──────────────────
-- Permite que el nuevo código reconozca estos registros y no los duplique
UPDATE payment_history SET payment_id = 7027442720 WHERE id = 114;
UPDATE payment_history SET payment_id = 7027359519 WHERE id = 115;
UPDATE payment_history SET payment_id = 7027505485 WHERE id = 116;
UPDATE payment_history SET payment_id = 7028246685 WHERE id = 131;
UPDATE payment_history SET payment_id = 7028336589 WHERE id = 135;
UPDATE payment_history SET payment_id = 7028395003 WHERE id = 137;

-- ── 4. Marcar free_trial_used en users ───────────────────────────────────────
UPDATE users SET free_trial_used = 1 WHERE id = 739;

-- ── Verificar resultado final ────────────────────────────────────────────────
SELECT id, type, LEFT(JSON_UNQUOTE(JSON_EXTRACT(data, '$.id')), 20) AS ap_id,
       payment_id, created_at
FROM payment_history
WHERE id_user = 739
ORDER BY created_at;

SELECT id, free_trial_used, id_plan, is_debtor FROM users WHERE id = 739;
