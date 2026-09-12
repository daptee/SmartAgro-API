-- =============================================================================
-- ALTA DE PRODUCTO - 2026-09-04
-- Falta "Vaquillona (promedio)" dentro de la clasificación "Invernada" (id 21,
-- hija de Ganadería), junto a "Novillo (promedio)", para completar el mapeo de
-- Mercado Agroganadero (LivestockPriceController::MAG_PRODUCTS['VAQUILLONAS']).
-- Mismo patrón que Novillo (promedio): id_classification=21, status_id=1,
-- description = name.
--
-- A diferencia del script de LIMANGUS PC., acá el INSERT tiene una guarda real
-- (WHERE NOT EXISTS) para que correrlo dos veces no genere un duplicado.
-- =============================================================================

-- Ver estado actual
SELECT id, name, id_classification, status_id, created_at
FROM products WHERE name = 'Vaquillona (promedio)';

INSERT INTO products (name, description, id_classification, status_id, created_at, updated_at)
SELECT 'Vaquillona (promedio)', 'Vaquillona (promedio)', 21, 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM products WHERE name = 'Vaquillona (promedio)'
);

-- Verificar resultado (debe haber una sola fila)
SELECT id, name, description, id_classification, status_id, created_at
FROM products WHERE name = 'Vaquillona (promedio)';
