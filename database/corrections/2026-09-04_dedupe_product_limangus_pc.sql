-- =============================================================================
-- LIMPIEZA DE DUPLICADO - 2026-09-04
-- El script 2026-09-04_add_product_limangus_pc.sql se corrió más de una vez
-- (no tenía una guarda real, sólo un SELECT informativo antes del INSERT) y
-- quedaron 2 filas "LIMANGUS PC." en products. Este script conserva la de
-- menor id, migra a esa cualquier cotización que ya haya quedado asociada al
-- duplicado (por la FK restrictOnDelete de livestock_prices) y borra el resto.
-- =============================================================================

-- 1) Ver los duplicados
SELECT id, name, id_classification, status_id, created_at
FROM products WHERE name = 'LIMANGUS PC.'
ORDER BY id;

-- 2) Migrar al id más viejo cualquier cotización que haya quedado contra el duplicado
UPDATE livestock_prices lp
JOIN products p ON p.id = lp.product_id AND p.name = 'LIMANGUS PC.'
SET lp.product_id = (SELECT id FROM (SELECT MIN(id) AS id FROM products WHERE name = 'LIMANGUS PC.') AS t)
WHERE p.id <> (SELECT id FROM (SELECT MIN(id) AS id FROM products WHERE name = 'LIMANGUS PC.') AS t);

-- 3) Borrar el/los duplicados, dejando sólo el de menor id
DELETE FROM products
WHERE name = 'LIMANGUS PC.'
  AND id <> (SELECT id FROM (SELECT MIN(id) AS id FROM products WHERE name = 'LIMANGUS PC.') AS t);

-- 4) Verificar que quedó uno solo
SELECT id, name, description, id_classification, status_id, created_at
FROM products WHERE name = 'LIMANGUS PC.';
