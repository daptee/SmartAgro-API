-- =============================================================================
-- ALTA DE PRODUCTO - 2026-09-04
-- Falta "LIMANGUS PC." dentro de la clasificación "Toros" (id 105, hija de
-- Ganadería), para completar el mapeo de razas de toros scrapeadas desde
-- Entre Surcos y Corrales Ya (LivestockPriceController::ESC_MODULES['toros']).
-- Se sigue el mismo patrón que A.ANGUS PC. NEGRO / BRANGUS C. / BRAFORD C.
-- (id_classification=105, status_id=1, description = name).
-- =============================================================================

-- Verificar que no exista ya (por las dudas)
SELECT id, name, id_classification, status_id
FROM products WHERE name = 'LIMANGUS PC.';

INSERT INTO products (name, description, id_classification, status_id, created_at, updated_at)
VALUES ('LIMANGUS PC.', 'LIMANGUS PC.', 105, 1, NOW(), NOW());

-- Verificar resultado
SELECT id, name, description, id_classification, status_id, created_at
FROM products WHERE name = 'LIMANGUS PC.';
