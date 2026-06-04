-- Agrega columna para controlar el acceso al panel de administración por rol
-- Separa la clasificación del rol (is_admin_role) del permiso de entrada al panel (admin_access)
ALTER TABLE roles
    ADD COLUMN admin_access TINYINT(1) NOT NULL DEFAULT 0 AFTER is_admin_role;

-- Los roles que actualmente son is_admin_role=1 obtienen admin_access=1 por defecto
UPDATE roles SET admin_access = 1 WHERE id > 0 AND is_admin_role = 1;
