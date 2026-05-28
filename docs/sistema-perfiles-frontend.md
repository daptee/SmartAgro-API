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

El sistema permite gestionar **roles de administración**, donde cada rol define a qué módulos del panel tiene acceso el usuario, y qué acciones puede realizar en cada módulo.

### Entidades clave

| Entidad | Descripción |
|---|---|
| **Módulo** | Sección del panel. Catálogo fijo de 34 slugs. Mercado e Indicadores tienen un slug por bloque. |
| **Rol** | Agrupa módulos con sus acciones permitidas. Ej: "Operador Noticias" puede ver `mercado_news` con acciones `["store", "update"]`. |
| **Usuario** | Tiene asignado **un solo rol admin** a la vez. |

### Rol superadmin (`admin`)

Existe un rol especial llamado `admin` que tiene acceso irrestricto a todo el panel. Este rol **no puede ser editado ni eliminado** desde la API. En el token, se identifica porque `allowed_modules` es `["*"]`.

### Lógica de permisos

- **GET siempre pasa** si el usuario tiene el módulo asignado (sin necesidad de acciones explícitas).
- **POST / PUT / PATCH / DELETE** requieren que el nombre del método del controller (`store`, `update`, `destroy`, `changeStatus`, etc.) esté en el array `actions` del módulo asignado al rol.

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
                    "description": "Puede gestionar noticias y precios granos",
                    "is_admin_role": 1,
                    "modules": [
                        {
                            "id": 6,
                            "slug": "mercado_news",
                            "name": "Mercado > Noticias",
                            "pivot": { "actions": ["store", "update"] }
                        },
                        {
                            "id": 12,
                            "slug": "mercado_main_grain_prices",
                            "name": "Mercado > Precios granos",
                            "pivot": { "actions": ["store", "update", "destroy"] }
                        }
                    ]
                }
            ],
            "allowed_modules": ["mercado_news", "mercado_main_grain_prices"]
        },
        "company_plan": { }
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
    allowedModules:  data.user.allowed_modules,   // ["*"] o ["mercado_news", ...]
    roles:           data.user.roles
}
```

Y construir el mapa de acciones por módulo:

```js
const moduleActions = {}
for (const role of data.user.roles) {
    for (const mod of role.modules) {
        moduleActions[mod.slug] = mod.pivot.actions  // ["store", "update", ...]
    }
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
        { "id": 1,  "slug": "usuarios",                         "name": "Usuarios" },
        { "id": 2,  "slug": "gestion_empresas",                 "name": "Gestión de empresas" },
        { "id": 3,  "slug": "planes_empresa",                   "name": "Planes empresa" },
        { "id": 4,  "slug": "gestion_publicidades",             "name": "Gestión de publicidades" },
        { "id": 5,  "slug": "espacios_publicitarios",           "name": "Espacios publicitarios" },
        { "id": 6,  "slug": "mercado_news",                     "name": "Mercado > Noticias" },
        { "id": 7,  "slug": "mercado_mag_lease_index",          "name": "Mercado > Índice Arrendamiento" },
        { "id": 8,  "slug": "mercado_mag_steer_index",          "name": "Mercado > Índice Novillo" },
        { "id": 9,  "slug": "mercado_major_crops",              "name": "Mercado > Cultivos principales" },
        { "id": 10, "slug": "mercado_insights",                 "name": "Mercado > Insights" },
        { "id": 11, "slug": "mercado_rainfall_records",         "name": "Mercado > Lluvias por provincia" },
        { "id": 12, "slug": "mercado_main_grain_prices",        "name": "Mercado > Precios granos" },
        { "id": 13, "slug": "mercado_price_active_ingredients", "name": "Mercado > Precios insumos productor" },
        { "id": 14, "slug": "mercado_producer_segment_prices",  "name": "Mercado > Precios por segmento productor" },
        { "id": 15, "slug": "mercado_general_control",          "name": "Mercado > Control general y datos" },
        { "id": 16, "slug": "indicadores_pit",                  "name": "Indicadores > PIT" },
        { "id": 17, "slug": "indicadores_gross_margins",        "name": "Indicadores > Márgenes brutos" },
        { "id": 18, "slug": "indicadores_gross_margins_trend",  "name": "Indicadores > Tendencia márgenes" },
        { "id": 19, "slug": "indicadores_livestock",            "name": "Indicadores > Relación insumo-producto ganadera" },
        { "id": 20, "slug": "indicadores_agricultural",         "name": "Indicadores > Relación insumo-producto agrícola" },
        { "id": 21, "slug": "indicadores_product_prices",       "name": "Indicadores > Precios productos" },
        { "id": 22, "slug": "indicadores_harvest_prices",       "name": "Indicadores > Precios cosecha" },
        { "id": 23, "slug": "indicadores_traffic_light",        "name": "Indicadores > Semáforo compra/venta cultivos" },
        { "id": 24, "slug": "indicadores_business_controls",    "name": "Indicadores > Business indicator controls" },
        { "id": 25, "slug": "config_planes",                    "name": "Configuración > Planes" },
        { "id": 26, "slug": "config_faqs",                      "name": "Configuración > FAQs" },
        { "id": 27, "slug": "config_regiones",                  "name": "Configuración > Regiones" },
        { "id": 28, "slug": "config_perfiles",                  "name": "Configuración > Perfiles" },
        { "id": 29, "slug": "config_iconos",                    "name": "Configuración > Iconos" },
        { "id": 30, "slug": "config_imagenes",                  "name": "Configuración > Imágenes" },
        { "id": 31, "slug": "config_clasificaciones",           "name": "Configuración > Clasificaciones" },
        { "id": 32, "slug": "config_productos",                 "name": "Configuración > Productos" },
        { "id": 33, "slug": "config_cultivos",                  "name": "Configuración > Cultivos" },
        { "id": 34, "slug": "config_unidades",                  "name": "Configuración > Unidades" },
        { "id": 35, "slug": "config_variables",                 "name": "Configuración > Variables" }
    ]
}
```

**Agrupación sugerida para la UI:**

```js
const grupos = {
    principal:     modules.filter(m => !m.slug.startsWith('config_') && !m.slug.startsWith('mercado_') && !m.slug.startsWith('indicadores_')),
    mercado:       modules.filter(m => m.slug.startsWith('mercado_')),
    indicadores:   modules.filter(m => m.slug.startsWith('indicadores_')),
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
            "description": "Puede gestionar noticias y precios granos",
            "is_admin_role": 1,
            "permissions_hash": "a3f5c2...",
            "modules": [
                {
                    "id": 6,
                    "slug": "mercado_news",
                    "name": "Mercado > Noticias",
                    "pivot": { "actions": ["store", "update"] }
                },
                {
                    "id": 12,
                    "slug": "mercado_main_grain_prices",
                    "name": "Mercado > Precios granos",
                    "pivot": { "actions": ["store", "update", "destroy"] }
                }
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
        "description": "Puede gestionar noticias y precios granos",
        "is_admin_role": 1,
        "permissions_hash": "a3f5c2...",
        "modules": [
            {
                "id": 6,
                "slug": "mercado_news",
                "name": "Mercado > Noticias",
                "pivot": { "actions": ["store", "update"] }
            },
            {
                "id": 12,
                "slug": "mercado_main_grain_prices",
                "name": "Mercado > Precios granos",
                "pivot": { "actions": ["store", "update", "destroy"] }
            }
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
    "description": "Solo puede cargar noticias y gestionar precios granos",
    "modules": [
        { "id": 6,  "actions": ["store", "update"] },
        { "id": 12, "actions": ["store", "update", "destroy"] }
    ]
}
```

| Campo | Tipo | Requerido | Validación |
|---|---|---|---|
| `name` | string | Sí | Único, máx 50 caracteres |
| `description` | string | No | Máx 255 caracteres |
| `modules` | array de objetos | Sí | Mínimo 1 elemento |
| `modules[].id` | integer | Sí | Debe existir en `admin_modules` |
| `modules[].actions` | array de strings | Sí | Mínimo 1. Nombres de métodos del controller (ver tabla de acciones más abajo) |

**Respuesta `201`:**
```json
{
    "message": "Rol creado con éxito",
    "role": {
        "id": 3,
        "name": "operador_mercado",
        "description": "Solo puede cargar noticias y gestionar precios granos",
        "is_admin_role": 1,
        "permissions_hash": "d6acb9...",
        "modules": [
            {
                "id": 6,
                "slug": "mercado_news",
                "name": "Mercado > Noticias",
                "pivot": { "actions": ["store", "update"] }
            },
            {
                "id": 12,
                "slug": "mercado_main_grain_prices",
                "name": "Mercado > Precios granos",
                "pivot": { "actions": ["destroy", "store", "update"] }
            }
        ]
    }
}
```

> Las acciones en la respuesta están **ordenadas alfabéticamente** (así se almacenan en BD).

**Error `422` — nombre duplicado:**
```json
{
    "message": "The name has already been taken.",
    "errors": { "name": ["The name has already been taken."] }
}
```

---

### Editar rol — `PUT /admin/roles/{id}`

Todos los campos son opcionales. Si se envía `modules`, reemplaza completamente los módulos y acciones del rol.

**Body (solo cambia acciones de módulos):**
```json
{
    "modules": [
        { "id": 6,  "actions": ["store", "update", "destroy"] },
        { "id": 12, "actions": ["store"] }
    ]
}
```

**Body (completo):**
```json
{
    "name": "operador_mercado",
    "description": "Descripción actualizada",
    "modules": [
        { "id": 1,  "actions": ["store", "update", "destroy"] },
        { "id": 6,  "actions": ["store", "update"] },
        { "id": 12, "actions": ["store", "update", "destroy", "changeStatus"] }
    ]
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
            { "id": 1,  "slug": "usuarios",               "name": "Usuarios",              "pivot": { "actions": ["destroy", "store", "update"] } },
            { "id": 6,  "slug": "mercado_news",           "name": "Mercado > Noticias",    "pivot": { "actions": ["store", "update"] } },
            { "id": 12, "slug": "mercado_main_grain_prices", "name": "Mercado > Precios granos", "pivot": { "actions": ["changeStatus", "destroy", "store", "update"] } }
        ]
    }
}
```

**Error `403` — intento de editar el rol superadmin:**
```json
{ "message": "El rol admin no puede ser modificado" }
```

> **Importante:** Al editar módulos o acciones de un rol, todos los usuarios con ese rol recibirán un `401 PERMISSIONS_CHANGED` en su próximo request y serán forzados a re-loguearse. Ver sección 7.

---

### Acciones disponibles por módulo

Las acciones son los **nombres de los métodos del controller** que se ejecutan al llamar a un endpoint de escritura (POST/PUT/PATCH/DELETE). Los GET siempre pasan si el módulo está asignado.

| Acción | Descripción |
|---|---|
| `store` | Crear un registro (POST) |
| `update` | Editar un registro (PUT/PATCH) |
| `destroy` | Eliminar un registro (DELETE) |
| `changeStatus` / `updateStatus` | Cambiar estado activo/inactivo |
| `updateCompanyPlanStatus` | Cambiar estado del plan de empresa |
| `deleteDuplicates` | Eliminar duplicados |
| `deleteImage` | Eliminar imagen |
| `updateImage` / `update_logo` | Reemplazar imagen / logo |
| `replicateAdditionalInfo` | Replicar información adicional |
| `updateData` | Actualizar datos específicos |
| `export` | Exportar datos |
| `import` | Importar datos |
| `add_main_admin_company_plan` | Asignar plan principal a empresa |
| `assignRole` | Asignar rol a usuario |
| `profile_picture_admin` | Actualizar foto de perfil del admin |

> La validación en el backend acepta cualquier string (máx 60 caracteres), por lo que también se pueden enviar nombres de métodos personalizados si se agregan nuevos endpoints.

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
allowed_modules = ["mercado_news", "mercado_main_grain_prices"]
```

### Funciones helper sugeridas

```js
// Construir mapa de acciones al hacer login
const moduleActions = {}
for (const role of user.roles) {
    for (const mod of role.modules) {
        moduleActions[mod.slug] = mod.pivot.actions  // ["store", "update", ...]
    }
}

// Verificar acceso a un módulo (GET siempre pasa en backend si el módulo está asignado)
function canAccess(slug) {
    if (allowedModules.includes('*')) return true
    return allowedModules.includes(slug)
}

// Verificar si puede ejecutar una acción de escritura específica
// action = nombre del método del controller: "store", "update", "destroy", "changeStatus", etc.
function canDo(slug, action) {
    if (allowedModules.includes('*')) return true
    return moduleActions[slug]?.includes(action) ?? false
}

// Uso
if (canAccess('mercado_news')) {
    // mostrar sección Noticias (el GET siempre pasará en el backend)
}

if (canDo('mercado_news', 'store')) {
    // mostrar botón "Crear noticia"
}

if (canDo('mercado_news', 'update')) {
    // mostrar botón "Editar noticia"
}

if (canDo('mercado_main_grain_prices', 'destroy')) {
    // mostrar botón eliminar en precios granos
}

if (canDo('usuarios', 'changeStatus')) {
    // mostrar toggle de activar/desactivar usuario
}
```

### Respuesta del backend cuando la acción no está permitida

**HTTP `403`:**
```json
{
    "message": "No tienes permiso para realizar esta acción.",
    "required_action": "destroy",
    "allowed_actions": ["store", "update"]
}
```

El campo `required_action` indica el nombre exacto del método del controller que se intentó ejecutar.

### Slugs por sección

**Módulos principales**

| Sección en el panel | Slug |
|---|---|
| Usuarios | `usuarios` |
| Gestión de empresas | `gestion_empresas` |
| Planes empresa | `planes_empresa` |
| Gestión de publicidades | `gestion_publicidades` |
| Espacios publicitarios | `espacios_publicitarios` |

**Mercado** (un slug por bloque)

| Sección en el panel | Slug |
|---|---|
| Mercado > Noticias | `mercado_news` |
| Mercado > Índice Arrendamiento | `mercado_mag_lease_index` |
| Mercado > Índice Novillo | `mercado_mag_steer_index` |
| Mercado > Cultivos principales | `mercado_major_crops` |
| Mercado > Insights | `mercado_insights` |
| Mercado > Lluvias por provincia | `mercado_rainfall_records` |
| Mercado > Precios granos | `mercado_main_grain_prices` |
| Mercado > Precios insumos productor | `mercado_price_active_ingredients` |
| Mercado > Precios por segmento productor | `mercado_producer_segment_prices` |
| Mercado > Control general y datos (export/import) | `mercado_general_control` |

**Indicadores comerciales** (un slug por bloque)

| Sección en el panel | Slug |
|---|---|
| Indicadores > PIT | `indicadores_pit` |
| Indicadores > Márgenes brutos | `indicadores_gross_margins` |
| Indicadores > Tendencia márgenes | `indicadores_gross_margins_trend` |
| Indicadores > Relación insumo-producto ganadera | `indicadores_livestock` |
| Indicadores > Relación insumo-producto agrícola | `indicadores_agricultural` |
| Indicadores > Precios productos | `indicadores_product_prices` |
| Indicadores > Precios cosecha | `indicadores_harvest_prices` |
| Indicadores > Semáforo compra/venta cultivos | `indicadores_traffic_light` |
| Indicadores > Business indicator controls y datos (export/import) | `indicadores_business_controls` |

**Configuración**

| Sección en el panel | Slug |
|---|---|
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

> El backend también valida esto por cada request. El control en el frontend es solo para UX (ocultar botones/menús). Un `403` del backend no debería ocurrir si el frontend oculta correctamente las acciones.

---

## 7. Manejo de permisos cambiados

Cuando un administrador modifica los módulos o acciones de un rol, o reasigna el rol de un usuario, el backend detecta automáticamente que el token del usuario afectado quedó desactualizado.

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
Admin edita módulos o acciones del rol "operador_mercado"
        ↓
Backend recalcula permissions_hash del rol en BD
  (hash incluye: ids de módulos + acciones de cada módulo)
        ↓
Próximo request del usuario con ese rol:
  - Middleware compara hash del token vs hash en BD
  - Son distintos → 401 con error_code: PERMISSIONS_CHANGED
        ↓
Frontend detecta PERMISSIONS_CHANGED → fuerza logout
        ↓
Usuario re-loguea → nuevo token con módulos y acciones actualizados
```

---

## 8. Referencia rápida de endpoints

Todos los endpoints requieren `Authorization: Bearer {token}` y `Accept: application/json`.

| Método | Endpoint | Descripción | Módulo requerido |
|---|---|---|---|
| `POST` | `/admin/auth` | Login admin | — (público) |
| `GET` | `/admin/modules` | Listar módulos disponibles | Ninguno (solo ser admin) |
| `GET` | `/admin/roles` | Listar roles con sus módulos y acciones | Ninguno (solo ser admin) |
| `GET` | `/admin/roles/{id}` | Detalle de un rol | Ninguno (solo ser admin) |
| `POST` | `/admin/roles` | Crear nuevo rol | Ninguno (solo ser admin) |
| `PUT` | `/admin/roles/{id}` | Editar rol | Ninguno (solo ser admin) |
| `POST` | `/admin/users/{id}/role` | Asignar rol a usuario | Ninguno (solo ser admin) |

> Los endpoints de gestión de roles y módulos no requieren un módulo específico: son accesibles a cualquier usuario con `is_admin_role = 1`.
