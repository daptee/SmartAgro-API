-- =============================================================
-- Sistema de roles y permisos por módulo para administradores
-- Ejecutar una sola vez contra la base de datos
-- =============================================================

-- 1. Columna en roles para identificar roles de tipo admin
ALTER TABLE roles ADD COLUMN is_admin_role TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Indica si este rol da acceso al panel de administración';
UPDATE roles SET is_admin_role = 1 WHERE name = 'admin';

-- 2. Tabla de módulos del panel admin (hardcoded, seed inicial)
CREATE TABLE admin_modules (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60)  NOT NULL UNIQUE COMMENT 'Clave usada en el middleware check_module',
    name VARCHAR(120) NOT NULL         COMMENT 'Nombre legible para mostrar en el frontend'
);

INSERT INTO admin_modules (slug, name) VALUES
('usuarios',               'Usuarios'),
('gestion_empresas',       'Gestión de empresas'),
('planes_empresa',         'Planes empresa'),
('gestion_publicidades',   'Gestión de publicidades'),
('espacios_publicitarios', 'Espacios publicitarios'),
('mercado',                'Mercado'),
('indicadores_comerciales','Indicadores comerciales'),
('config_planes',          'Configuración > Planes'),
('config_faqs',            'Configuración > FAQs'),
('config_regiones',        'Configuración > Regiones'),
('config_perfiles',        'Configuración > Perfiles'),
('config_iconos',          'Configuración > Iconos'),
('config_imagenes',        'Configuración > Imágenes'),
('config_clasificaciones', 'Configuración > Clasificaciones'),
('config_productos',       'Configuración > Productos'),
('config_cultivos',        'Configuración > Cultivos'),
('config_unidades',        'Configuración > Unidades'),
('config_variables',       'Configuración > Variables');

-- 3. Tabla pivot: rol <-> módulos habilitados
CREATE TABLE role_modules (
    id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_role   INT UNSIGNED NOT NULL,
    id_module INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_role_module (id_role, id_module),
    FOREIGN KEY (id_role)   REFERENCES roles(id)         ON DELETE CASCADE,
    FOREIGN KEY (id_module) REFERENCES admin_modules(id) ON DELETE CASCADE
);
