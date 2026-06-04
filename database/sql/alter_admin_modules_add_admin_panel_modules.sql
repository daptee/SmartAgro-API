-- Agrega los módulos de administración del propio panel (roles, módulos, asignación de rol)
-- Permite controlar quién puede gestionar roles y asignarlos desde el panel
INSERT INTO admin_modules (slug, name) VALUES
('admin_roles',    'Administración > Roles'),
('admin_modulos',  'Administración > Módulos'),
('asignacion_rol', 'Administración > Asignación de rol');
