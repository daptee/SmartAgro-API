-- =============================================================
-- Agrega la columna actions a role_modules
-- Permite definir qué acciones (read/create/update/delete)
-- puede realizar cada rol sobre cada módulo.
-- Ejecutar una sola vez contra la base de datos.
-- =============================================================

ALTER TABLE role_modules
    ADD COLUMN actions JSON
        COMMENT 'Métodos del controller permitidos para escritura (POST/PUT/DELETE). GET siempre pasa.';

-- Los registros existentes reciben los métodos de escritura más comunes por defecto
UPDATE role_modules SET actions = JSON_ARRAY('store','update','destroy');

-- Aplicar NOT NULL una vez que todos los registros tienen valor
ALTER TABLE role_modules MODIFY COLUMN actions JSON NOT NULL
    COMMENT 'Métodos del controller permitidos para escritura (POST/PUT/DELETE). GET siempre pasa.';
