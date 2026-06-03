-- Agrega columna para identificar si el plan Siembra fue activado manualmente desde el admin
ALTER TABLE users
    ADD COLUMN subscription_manual TINYINT(1) NOT NULL DEFAULT 0 AFTER free_trial_used;

-- Usuarios con suscripción real de MercadoPago: tienen subscription_type definido (monthly/yearly)
UPDATE users
SET subscription_manual = 0
WHERE id_plan = 2
  AND subscription_type IS NOT NULL;

-- Usuarios activados manualmente: no tienen subscription_type (nunca pasaron por el flujo de MP)
UPDATE users
SET subscription_manual = 1
WHERE id_plan = 2
  AND subscription_type IS NULL;
