# Sistema de Perfiles / Roles de Administrador — Guía para Frontend

## Índice

1. [Conceptos generales](#1-conceptos-generales)
2. [Login de administrador](#2-login-de-administrador)
3. [Módulos disponibles](#3-módulos-disponibles)
4. [Gestión de roles](#4-gestión-de-roles)
5. [Asignar rol a un usuario](#5-asignar-rol-a-un-usuario)
6. [Control de acceso en el frontend](#6-control-de-acceso-en-el-frontend)
7. [Manejo de permisos cambiados](#7-manejo-de-permisos-cambiados)
8. [Referencia rápida de endpoints](#8-referencia-rápida-de-endpoints)

---

## 1. Conceptos generales

El sistema permite gestionar **roles de administración**, donde cada rol define a qué módulos del panel tiene acceso el usuario que lo tiene asignado.

### Entidades clave

| Entidad | Descripción |
|---|---|
| **Módulo** | Sección del panel (ej: Mercado, Usuarios). Catálogo fijo de 18 ítems. |
| **Rol** | Agrupa módulos. Ej: "Operador Mercado" puede ver Mercado e Indicadores. |
| **Usuario** | Tiene asignado **un solo rol admin** a la vez. |

### Rol superadmin (`admin`)

Existe un rol especial llamado `admin` que tiene acceso irrestricto a todo el panel. Este rol **no puede ser editado ni eliminado** desde la API. En el token, se identifica porque `allowed_modules` es `["*"]`.

---

## 2. Login de administrador

### `POST /admin/auth`

**Body:**
```json
{
    "email": "admin@smartagro.com",
    "password": "password"
}
```

**Respuesta exitosa `200`:**
```json
{
    "message": "Login exitoso.",
    "data": {
        "access_token": "eyJ...",
        "user": {
            "id": 32,
            "name": "nahuel",
            "last_name": "carrizo",
            "email": "admin@smartagro.com",
            "roles": [
                {
                    "id": 2,
                    "name": "operador_mercado",
                    "description": "Puede gestionar mercado e indicadores",
                    "is_admin_role": 1,
                    "modules": [
                        { "id": 6, "slug": "mercado",                "name": "Mercado" },
                        { "id": 7, "slug": "indicadores_comerciales", "name": "Indicadores comerciales" }
                    ]
                }
            ],
            "allowed_modules": ["mercado", "indicadores_comerciales"]
        },
        "company_plan": { ... }
    }
}
```

> `company_plan` solo aparece si el usuario tiene `id_plan = 3`.

### Qué guardar en el estado global

Al hacer login, guardar en el store/contexto:

```js
{
    token:           data.access_token,
    user:            data.user,
    allowedModules:  data.user.allowed_modules,   // ["*"] o ["mercado", ...]
    roles:           data.user.roles
}
```

### Errores posibles

| Código | Mensaje | Causa |
|---|---|---|
| `400` | `Usuario y/o clave no válidos.` | Email no existe o contraseña incorrecta |
| `400` | `La cuenta no está verificada.` | Email sin confirmar |
| `403` | `Usuario no autorizado para acceder.` | El usuario no tiene ningún rol admin |

---

## 3. Módulos disponibles

### `GET /admin/modules`

Devuelve el catálogo completo de módulos. Usar para poblar el selector al crear/editar un rol.

**Headers:** `Authorization: Bearer {token}`

**Respuesta `200`:**
```json
{
    "data": [
        { "id": 1,  "slug": "usuarios",               "name": "Usuarios" },
        { "id": 2,  "slug": "gestion_empresas",       "name": "Gestión de empresas" },
        { "id": 3,  "slug": "planes_empresa",         "name": "Planes empresa" },
        { "id": 4,  "slug": "gestion_publicidades",   "name": "Gestión de publicidades" },
        { "id": 5,  "slug": "espacios_publicitarios", "name": "Espacios publicitarios" },
        { "id": 6,  "slug": "mercado",                "name": "Mercado" },
        { "id": 7,  "slug": "indicadores_comerciales","name": "Indicadores comerciales" },
        { "id": 8,  "slug": "config_planes",          "name": "Configuración > Planes" },
        { "id": 9,  "slug": "config_faqs",            "name": "Configuración > FAQs" },
        { "id": 10, "slug": "config_regiones",        "name": "Configuración > Regiones" },
        { "id": 11, "slug": "config_perfiles",        "name": "Configuración > Perfiles" },
        { "id": 12, "slug": "config_iconos",          "name": "Configuración > Iconos" },
        { "id": 13, "slug": "config_imagenes",        "name": "Configuración > Imágenes" },
        { "id": 14, "slug": "config_clasificaciones", "name": "Configuración > Clasificaciones" },
        { "id": 15, "slug": "config_productos",       "name": "Configuración > Productos" },
        { "id": 16, "slug": "config_cultivos",        "name": "Configuración > Cultivos" },
        { "id": 17, "slug": "config_unidades",        "name": "Configuración > Unidades" },
        { "id": 18, "slug": "config_variables",       "name": "Configuración > Variables" }
    ]
}
```

**Agrupación sugerida para la UI:**

Los módulos con prefijo `config_` pertenecen al grupo Configuración. Se puede agrupar así:

```js
const grupos = {
    principal: modules.filter(m => !m.slug.startsWith('config_')),
    configuracion: modules.filter(m => m.slug.startsWith('config_'))
}
```

---

## 4. Gestión de roles

### Listar roles — `GET /admin/roles`

**Respuesta `200`:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "admin",
            "description": "Administrador del sistema",
            "is_admin_role": 1,
            "permissions_hash": null,
            "modules": []
        },
        {
            "id": 2,
            "name": "operador_mercado",
            "description": "Puede gestionar mercado e indicadores",
            "is_admin_role": 1,
            "permissions_hash": "a3f5c2...",
            "modules": [
                { "id": 6, "slug": "mercado",                "name": "Mercado" },
                { "id": 7, "slug": "indicadores_comerciales", "name": "Indicadores comerciales" }
            ]
        }
    ]
}
```

> El rol `admin` siempre aparece con `modules: []` porque tiene acceso total implícito, no por módulos asignados.

---

### Detalle de rol — `GET /admin/roles/{id}`

**Respuesta `200`:**
```json
{
    "data": {
        "id": 2,
        "name": "operador_mercado",
        "description": "Puede gestionar mercado e indicadores",
        "is_admin_role": 1,
        "permissions_hash": "a3f5c2...",
        "modules": [
            { "id": 6, "slug": "mercado",                "name": "Mercado" },
            { "id": 7, "slug": "indicadores_comerciales", "name": "Indicadores comerciales" }
        ]
    }
}
```

**Error `404`:**
```json
{ "message": "Rol no encontrado" }
```

---

### Crear rol — `POST /admin/roles`

**Body:**
```json
{
    "name": "operador_mercado",
    "description": "Puede gestionar mercado e indicadores comerciales",
    "module_ids": [6, 7]
}
```

| Campo | Tipo | Requerido | Validación |
|---|---|---|---|
| `name` | string | Sí | Único, máx 50 caracteres |
| `description` | string | No | Máx 255 caracteres |
| `module_ids` | array de enteros | Sí | Mínimo 1 elemento, IDs deben existir |

**Respuesta `201`:**
```json
{
    "message": "Rol creado con éxito",
    "role": {
        "id": 3,
        "name": "operador_mercado",
        "description": "Puede gestionar mercado e indicadores comerciales",
        "is_admin_role": 1,
        "permissions_hash": "d6acb9...",
        "modules": [
            { "id": 6, "slug": "mercado",                "name": "Mercado" },
            { "id": 7, "slug": "indicadores_comerciales", "name": "Indicadores comerciales" }
        ]
    }
}
```

**Error `422` — nombre duplicado:**
```json
{
    "message": "The name has already been taken.",
    "errors": {
        "name": ["The name has already been taken."]
    }
}
```

---

### Editar rol — `PUT /admin/roles/{id}`

Todos los campos son opcionales. Solo se actualizan los que se envían.

**Body (ejemplo parcial — solo cambia módulos):**
```json
{
    "module_ids": [1, 6, 7]
}
```

**Body (ejemplo completo):**
```json
{
    "name": "operador_mercado",
    "description": "Descripción actualizada",
    "module_ids": [1, 6, 7]
}
```

**Respuesta `200`:**
```json
{
    "message": "Rol actualizado con éxito",
    "role": {
        "id": 2,
        "name": "operador_mercado",
        "description": "Descripción actualizada",
        "is_admin_role": 1,
        "permissions_hash": "b7e3a1...",
        "modules": [
            { "id": 1, "slug": "usuarios",               "name": "Usuarios" },
            { "id": 6, "slug": "mercado",                "name": "Mercado" },
            { "id": 7, "slug": "indicadores_comerciales", "name": "Indicadores comerciales" }
        ]
    }
}
```

**Error `403` — intento de editar el rol superadmin:**
```json
{ "message": "El rol admin no puede ser modificado" }
```

> **Importante:** Al editar los módulos de un rol, todos los usuarios con ese rol recibirán un `401 PERMISSIONS_CHANGED` en su próximo request y serán forzados a re-loguearse. Ver sección 7.

---

## 5. Asignar rol a un usuario

### `POST /admin/users/{id}/role`

Reemplaza el rol admin actual del usuario por el nuevo. Un usuario solo puede tener **un rol admin a la vez**.

**Body:**
```json
{
    "id_role": 2
}
```

| Campo | Tipo | Requerido | Validación |
|---|---|---|---|
| `id_role` | entero | Sí | Debe existir y ser un rol con `is_admin_role = 1` |

**Respuesta `200`:**
```json
{
    "message": "Rol asignado con éxito",
    "data": {
        "id": 5,
        "name": "Juan Pérez",
        "email": "juan@example.com",
        "roles": [
            {
                "id": 2,
                "name": "operador_mercado",
                "is_admin_role": 1
            }
        ]
    }
}
```

**Error `422` — el rol no es de tipo admin:**
```json
{ "message": "El rol seleccionado no es un rol de administración válido" }
```

> El usuario afectado recibirá `401 PERMISSIONS_CHANGED` en su próximo request, lo que lo fuerza a re-loguearse con el nuevo rol.

---

## 6. Control de acceso en el frontend

### Usar `allowed_modules` del login

El campo `allowed_modules` del login es la fuente de verdad para mostrar/ocultar secciones:

```js
// Superadmin — acceso total
allowed_modules = ["*"]

// Rol con módulos específicos
allowed_modules = ["mercado", "indicadores_comerciales"]
```

### Función helper sugerida

```js
function canAccess(module) {
    if (allowedModules.includes('*')) return true
    return allowedModules.includes(module)
}

// Uso
if (canAccess('mercado')) {
    // mostrar sección Mercado
}

if (canAccess('config_planes')) {
    // mostrar ítem de menú Configuración > Planes
}
```

### Slugs por sección

| Sección en el panel | Slug a verificar |
|---|---|
| Usuarios | `usuarios` |
| Gestión de empresas | `gestion_empresas` |
| Planes empresa | `planes_empresa` |
| Gestión de publicidades | `gestion_publicidades` |
| Espacios publicitarios | `espacios_publicitarios` |
| Mercado | `mercado` |
| Indicadores comerciales | `indicadores_comerciales` |
| Config > Planes | `config_planes` |
| Config > FAQs | `config_faqs` |
| Config > Regiones | `config_regiones` |
| Config > Perfiles | `config_perfiles` |
| Config > Iconos | `config_iconos` |
| Config > Imágenes | `config_imagenes` |
| Config > Clasificaciones | `config_clasificaciones` |
| Config > Productos | `config_productos` |
| Config > Cultivos | `config_cultivos` |
| Config > Unidades | `config_unidades` |
| Config > Variables | `config_variables` |

> El backend también valida esto por cada request. El control en el frontend es solo para UX (ocultar menús). Un `403` del backend no debería ocurrir si el frontend oculta correctamente las secciones.

---

## 7. Manejo de permisos cambiados

Cuando un administrador modifica los módulos de un rol o reasigna el rol de un usuario, el backend detecta automáticamente que el token del usuario afectado quedó desactualizado.

### Respuesta que puede llegar en cualquier request al panel

**HTTP `401`:**
```json
{
    "message": "Tus permisos han cambiado. Por favor, volvé a iniciar sesión.",
    "error_code": "PERMISSIONS_CHANGED"
}
```

### Manejo requerido

El frontend **debe interceptar este caso** en el handler global de errores HTTP y forzar logout:

```js
// Ejemplo con axios interceptor
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            const errorCode = error.response.data?.error_code

            if (errorCode === 'PERMISSIONS_CHANGED') {
                // Limpiar sesión y redirigir al login
                store.dispatch('logout')
                router.push('/admin/login')
                showNotification('Tus permisos fueron actualizados. Por favor, volvé a iniciar sesión.')
                return
            }

            // 401 normal (token expirado)
            store.dispatch('logout')
            router.push('/admin/login')
        }

        return Promise.reject(error)
    }
)
```

### Flujo completo

```
Admin edita módulos del rol "operador_mercado"
        ↓
Backend recalcula permissions_hash del rol en BD
        ↓
Próximo request del usuario con ese rol:
  - Middleware compara hash del token vs hash en BD
  - Son distintos → 401 con error_code: PERMISSIONS_CHANGED
        ↓
Frontend detecta PERMISSIONS_CHANGED → fuerza logout
        ↓
Usuario re-loguea → nuevo token con módulos actualizados
```

---

## 8. Referencia rápida de endpoints

Todos los endpoints requieren `Authorization: Bearer {token}` y `Accept: application/json`.

| Método | Endpoint | Descripción | Módulo requerido |
|---|---|---|---|
| `POST` | `/admin/auth` | Login admin | — (público) |
| `GET` | `/admin/modules` | Listar módulos disponibles | Ninguno (solo ser admin) |
| `GET` | `/admin/roles` | Listar roles con sus módulos | Ninguno (solo ser admin) |
| `GET` | `/admin/roles/{id}` | Detalle de un rol | Ninguno (solo ser admin) |
| `POST` | `/admin/roles` | Crear nuevo rol | Ninguno (solo ser admin) |
| `PUT` | `/admin/roles/{id}` | Editar rol | Ninguno (solo ser admin) |
| `POST` | `/admin/users/{id}/role` | Asignar rol a usuario | Ninguno (solo ser admin) |

> Los endpoints de gestión de roles y módulos no requieren un módulo específico: son accesibles a cualquier usuario con `is_admin_role = 1`.
