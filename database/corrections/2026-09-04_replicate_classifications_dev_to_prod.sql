-- =============================================================================
-- REPLICAR CLASIFICACIONES DE DEV A PROD (mismos ids) - 2026-09-04
-- Prod no tenía la columna short_name (sí existe en dev). Se agrega y se
-- backfillea con el name de cada fila existente antes de insertar las nuevas.
-- =============================================================================

ALTER TABLE classifications ADD COLUMN short_name VARCHAR(255) NULL AFTER name;

UPDATE classifications SET short_name = name WHERE short_name IS NULL;

INSERT INTO classifications (id, name, short_name, description, id_parent_classification, id_icon, status_id, deleted_at, created_at, updated_at)
VALUES
(104, 'Invernada', 'Invernada', 'Invernada', 8, NULL, 1, '2026-08-11 09:36:14', '2026-08-11 09:35:44', '2026-08-11 09:36:14'),
(105, 'Toros', 'Toros', 'Toros', 8, NULL, 1, NULL, '2026-08-11 09:37:58', '2026-08-11 09:37:58'),
(106, 'Insumos', 'Insumos', 'Insumos', NULL, 3, 1, NULL, '2026-08-14 12:44:30', '2026-08-14 12:44:30'),
(107, 'Agricultura', 'Agricultura', 'Agricultura', NULL, 11, 1, NULL, '2026-08-14 12:48:37', '2026-08-14 12:48:37'),
(108, 'Maquinaria', 'Maquinaria', 'Maquinaria', NULL, 38, 1, NULL, '2026-08-14 12:50:04', '2026-08-14 12:50:04'),
(109, 'Clima', 'Clima', 'Clima', NULL, 56, 1, NULL, '2026-08-14 12:50:26', '2026-08-14 12:50:26'),
(110, 'Politica/economía', 'Politica/economía', 'Politica/economía', NULL, 54, 1, NULL, '2026-08-14 12:56:58', '2026-08-14 12:56:58');
