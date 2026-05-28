-- =============================================================
-- Agrega permissions_hash a la tabla roles
-- Se usa para detectar cambios de permisos y forzar re-login
-- Ejecutar una sola vez contra la base de datos
-- =============================================================

-- 1. Columna que guarda un hash de los módulos asignados al rol
ALTER TABLE roles
    ADD COLUMN permissions_hash VARCHAR(64) NULL
        COMMENT 'SHA-256 de los IDs de módulos asignados, ordenados. Cambia cuando se modifican los permisos del rol.';

-- 2. Inicializar el hash para todos los roles que ya tienen módulos asignados
--    (calcula el hash a partir de los ids de módulos concatenados y ordenados)
UPDATE roles r
SET r.permissions_hash = (
    SELECT SHA2(
        GROUP_CONCAT(rm.id_module ORDER BY rm.id_module ASC SEPARATOR ','),
        256
    )
    FROM role_modules rm
    WHERE rm.id_role = r.id
)
WHERE EXISTS (
    SELECT 1 FROM role_modules rm WHERE rm.id_role = r.id
);

-- 3. Para el rol 'admin' (superadmin) usamos un hash fijo conocido
--    ya que su acceso es total y no pasa por role_modules
UPDATE roles
SET permissions_hash = SHA2('*', 256)
WHERE name = 'admin';
