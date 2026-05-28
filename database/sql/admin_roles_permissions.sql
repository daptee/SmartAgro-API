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
-- Módulos principales
('usuarios',                          'Usuarios'),
('gestion_empresas',                  'Gestión de empresas'),
('planes_empresa',                    'Planes empresa'),
('gestion_publicidades',              'Gestión de publicidades'),
('espacios_publicitarios',            'Espacios publicitarios'),
-- Mercado (un módulo por bloque)
('mercado_news',                      'Mercado > Noticias'),
('mercado_mag_lease_index',           'Mercado > Índice Arrendamiento'),
('mercado_mag_steer_index',           'Mercado > Índice Novillo'),
('mercado_major_crops',               'Mercado > Cultivos principales'),
('mercado_insights',                  'Mercado > Insights'),
('mercado_rainfall_records',          'Mercado > Lluvias por provincia'),
('mercado_main_grain_prices',         'Mercado > Precios granos'),
('mercado_price_active_ingredients',  'Mercado > Precios insumos productor'),
('mercado_producer_segment_prices',   'Mercado > Precios por segmento productor'),
('mercado_general_control',           'Mercado > Control general y datos'),
-- Indicadores comerciales (un módulo por bloque)
('indicadores_pit',                   'Indicadores > PIT'),
('indicadores_gross_margins',         'Indicadores > Márgenes brutos'),
('indicadores_gross_margins_trend',   'Indicadores > Tendencia márgenes'),
('indicadores_livestock',             'Indicadores > Relación insumo-producto ganadera'),
('indicadores_agricultural',          'Indicadores > Relación insumo-producto agrícola'),
('indicadores_product_prices',        'Indicadores > Precios productos'),
('indicadores_harvest_prices',        'Indicadores > Precios cosecha'),
('indicadores_traffic_light',         'Indicadores > Semáforo compra/venta cultivos'),
('indicadores_business_controls',     'Indicadores > Business indicator controls'),
-- Configuración
('config_planes',                     'Configuración > Planes'),
('config_faqs',                       'Configuración > FAQs'),
('config_regiones',                   'Configuración > Regiones'),
('config_perfiles',                   'Configuración > Perfiles'),
('config_iconos',                     'Configuración > Iconos'),
('config_imagenes',                   'Configuración > Imágenes'),
('config_clasificaciones',            'Configuración > Clasificaciones'),
('config_productos',                  'Configuración > Productos'),
('config_cultivos',                   'Configuración > Cultivos'),
('config_unidades',                   'Configuración > Unidades'),
('config_variables',                  'Configuración > Variables');

-- 3. Tabla pivot: rol <-> módulos habilitados con acciones permitidas
-- Nota: id_role es INT (sin UNSIGNED) para coincidir con roles.id INT
--       id_module es INT UNSIGNED para coincidir con admin_modules.id INT UNSIGNED
-- actions: JSON array con los nombres de métodos del controller permitidos para POST/PUT/DELETE.
--          Los GET siempre están permitidos si el módulo está asignado (el middleware los bypasea).
--          Ejemplos de valores: "store", "update", "destroy", "changeStatus",
--          "deleteDuplicates", "updateImage", "deleteImage", "replicateAdditionalInfo",
--          "updateData", "export", "import", etc.
CREATE TABLE role_modules (
    id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_role   INT          NOT NULL,
    id_module INT UNSIGNED NOT NULL,
    actions   JSON         NOT NULL
                           COMMENT 'Métodos del controller permitidos para escritura (POST/PUT/DELETE). GET siempre pasa.',
    UNIQUE KEY uq_role_module (id_role, id_module),
    FOREIGN KEY (id_role)   REFERENCES roles(id)         ON DELETE CASCADE,
    FOREIGN KEY (id_module) REFERENCES admin_modules(id) ON DELETE CASCADE
);
