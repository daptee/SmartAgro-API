-- =============================================================================
-- ALTA DE PRODUCTOS FALTANTES EN PROD - 2026-09-04
-- Los scripts anteriores (add_product_vaquillona_promedio.sql,
-- add_product_limangus_pc.sql, seed_ganaderia_catalog.sql) fallaban en prod
-- porque asumían ids de clasificación de dev que no coinciden en prod.
--
-- Confirmado en prod (vía diagnóstico manual):
--   Invernada = id 104 (hija de Ganadería, id 8)
--   Toros     = id 105 (hija de Ganadería, id 8)
-- Ambas ya existen en prod, así que sólo faltan los productos.
-- Cada INSERT tiene guarda real (WHERE NOT EXISTS): correrlo de más no duplica.
-- =============================================================================

-- Vaquillona (promedio) -> Invernada (104)
INSERT INTO products (name, description, id_classification, status_id, created_at, updated_at)
SELECT 'Vaquillona (promedio)', 'Vaquillona (promedio)', 104, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Vaquillona (promedio)');

-- LIMANGUS PC. -> Toros (105)
INSERT INTO products (name, description, id_classification, status_id, created_at, updated_at)
SELECT 'LIMANGUS PC.', 'LIMANGUS PC.', 105, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'LIMANGUS PC.');

-- Verificar resultado (debe haber una sola fila por cada uno)
SELECT id, name, id_classification, status_id
FROM products WHERE name IN ('Vaquillona (promedio)', 'LIMANGUS PC.');
