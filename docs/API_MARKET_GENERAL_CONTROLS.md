# API Documentation - Market General Controls Module

## Descripcion
Modulo para el control general de carga de informacion de mercado del sistema SmartAgro. Permite gestionar el estado de publicacion mensual de todos los bloques de informacion de mercado (noticias, insights, cultivos principales, lluvias, precios, indices, etc.).

Esta tabla actua como "tabla madre" que controla que bloques de informacion estan cargados para cada mes/anio y si el conjunto esta publicado o en borrador. Cuando un registro esta publicado (status_id=1), los endpoints publicos de la plataforma web retornan los datos de ese mes/anio.

---

## Endpoints

### 1. GET ALL - Listar Controles Generales de Mercado
**Endpoint:** `GET /admin/market-general-controls`

**Descripcion:** Retorna todos los controles generales de mercado con paginado y filtros.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
| Parametro | Tipo | Requerido | Descripcion |
|-----------|------|-----------|-------------|
| `per_page` | integer | No | Numero de registros por pagina. Si no se especifica, retorna todos |
| `page` | integer | No | Numero de pagina (default: 1) |
| `month` | integer | No | Filtrar por mes (1-12) |
| `year` | integer | No | Filtrar por anio |
| `status_id` | integer | No | Filtrar por estado (1=Publicado, 2=Borrador) |

**Ejemplo Request:**
```
GET /admin/market-general-controls?per_page=10&page=1&year=2026&month=1&status_id=1
```

**Ejemplo Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "month": 1,
      "year": 2026,
      "data": {
        "major_crops": true,
        "insights": true,
        "news": false,
        "rainfall_records": true,
        "main_grain_prices": false,
        "price_main_active_ingredients_producers": false,
        "producer_segment_prices": false,
        "mag_lease_index": true,
        "mag_steer_index": true
      },
      "status_id": 2,
      "id_user": 5,
      "created_at": "2026-01-05T10:00:00.000000Z",
      "updated_at": "2026-01-05T10:00:00.000000Z",
      "deleted_at": null,
      "status": {
        "id": 2,
        "status_name": "Borrador"
      },
      "user": {
        "id": 5,
        "name": "Admin User"
      }
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 10,
    "total": 5,
    "last_page": 1
  }
}
```

---

### 2. GET BY ID - Detalle de Control General de Mercado
**Endpoint:** `GET /admin/market-general-controls/{id}`

**Descripcion:** Retorna un control general de mercado por su ID, con todas sus relaciones.

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameters:**
| Parametro | Tipo | Descripcion |
|-----------|------|-------------|
| `id` | integer | ID del control general de mercado |

**Ejemplo Request:**
```
GET /admin/market-general-controls/1
```

**Ejemplo Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "month": 1,
    "year": 2026,
    "data": {
      "major_crops": true,
      "insights": true,
      "news": false,
      "rainfall_records": true,
      "main_grain_prices": false,
      "price_main_active_ingredients_producers": false,
      "producer_segment_prices": false,
      "mag_lease_index": true,
      "mag_steer_index": true
    },
    "status_id": 2,
    "id_user": 5,
    "created_at": "2026-01-05T10:00:00.000000Z",
    "updated_at": "2026-01-05T10:00:00.000000Z",
    "deleted_at": null,
    "status": { "..." },
    "user": { "..." }
  }
}
```

---

### 3. POST - Crear Control General de Mercado
**Endpoint:** `POST /admin/market-general-controls`

**Descripcion:** Crea un nuevo control general de mercado. Solo recibe mes y anio. El JSON `data` se inicializa automaticamente con todos los bloques en `false`. El estado siempre inicia como Borrador (status_id=2).

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body Parameters:**
| Parametro | Tipo | Requerido | Descripcion |
|-----------|------|-----------|-------------|
| `month` | integer | Si | Mes (1-12) |
| `year` | integer | Si | Anio (2000-2100) |

**Ejemplo Request:**
```json
{
  "month": 2,
  "year": 2026
}
```

**Ejemplo Response (201 Created):**
```json
{
  "data": {
    "id": 2,
    "month": 2,
    "year": 2026,
    "data": {
      "major_crops": false,
      "insights": false,
      "news": false,
      "rainfall_records": false,
      "main_grain_prices": false,
      "price_main_active_ingredients_producers": false,
      "producer_segment_prices": false,
      "mag_lease_index": false,
      "mag_steer_index": false
    },
    "status_id": 2,
    "id_user": 5,
    "created_at": "2026-02-01T14:30:00.000000Z",
    "updated_at": "2026-02-01T14:30:00.000000Z",
    "status": { "..." },
    "user": { "..." }
  }
}
```

**Errores Posibles:**
- `400 Bad Request`: Ya existe un registro para ese mes/anio
- `422 Unprocessable Entity`: Validacion fallida
- `500 Internal Server Error`: Error del servidor

---

### 4. PUT - Editar Mes y Anio
**Endpoint:** `PUT /admin/market-general-controls/{id}`

**Descripcion:** Edita el mes y anio de un control general de mercado existente.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
| Parametro | Tipo | Descripcion |
|-----------|------|-------------|
| `id` | integer | ID del control general de mercado |

**Body Parameters:**
| Parametro | Tipo | Requerido | Descripcion |
|-----------|------|-----------|-------------|
| `month` | integer | Si | Mes (1-12) |
| `year` | integer | Si | Anio (2000-2100) |

**Ejemplo Request:**
```
PUT /admin/market-general-controls/2
```
```json
{
  "month": 3,
  "year": 2026
}
```

**Ejemplo Response (200 OK):**
```json
{
  "data": {
    "id": 2,
    "month": 3,
    "year": 2026,
    "data": { "..." },
    "status_id": 2,
    "...": "..."
  }
}
```

---

### 5. PUT - Actualizar Data (Bloques Cargados)
**Endpoint:** `PUT /admin/market-general-controls/{id}/data`

**Descripcion:** Actualiza un campo especifico dentro del JSON `data`, indicando si un bloque de informacion fue cargado o eliminado.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
| Parametro | Tipo | Descripcion |
|-----------|------|-------------|
| `id` | integer | ID del control general de mercado |

**Body Parameters:**
| Parametro | Tipo | Requerido | Descripcion |
|-----------|------|-----------|-------------|
| `block` | string | Si | Nombre del bloque a actualizar |
| `loaded` | boolean | Si | `true` si fue cargado, `false` si fue eliminado |

**Valores validos para `block`:**
- `major_crops`
- `insights`
- `news`
- `rainfall_records`
- `main_grain_prices`
- `price_main_active_ingredients_producers`
- `producer_segment_prices`
- `mag_lease_index`
- `mag_steer_index`

**Ejemplo Request:**
```
PUT /admin/market-general-controls/1/data
```
```json
{
  "block": "news",
  "loaded": true
}
```

**Ejemplo Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "month": 1,
    "year": 2026,
    "data": {
      "major_crops": true,
      "insights": true,
      "news": true,
      "rainfall_records": true,
      "main_grain_prices": false,
      "price_main_active_ingredients_producers": false,
      "producer_segment_prices": false,
      "mag_lease_index": true,
      "mag_steer_index": true
    },
    "status_id": 2,
    "...": "..."
  }
}
```

---

### 6. PUT - Cambiar Estado
**Endpoint:** `PUT /admin/market-general-controls/{id}/status`

**Descripcion:** Cambia el estado del control general de mercado entre Publicado y Borrador. Cuando se publica, los endpoints publicos de la plataforma web retornan los datos de ese mes/anio.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
| Parametro | Tipo | Descripcion |
|-----------|------|-------------|
| `id` | integer | ID del control general de mercado |

**Body Parameters:**
| Parametro | Tipo | Requerido | Descripcion |
|-----------|------|-----------|-------------|
| `status_id` | integer | Si | Nuevo estado (1=Publicado, 2=Borrador) |

**Ejemplo Request:**
```
PUT /admin/market-general-controls/1/status
```
```json
{
  "status_id": 1
}
```

**Ejemplo Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "status_id": 1,
    "...": "..."
  }
}
```

---

### 7. DELETE - Eliminar Control General de Mercado
**Endpoint:** `DELETE /admin/market-general-controls/{id}`

**Descripcion:** Elimina un control general de mercado mediante soft delete.

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameters:**
| Parametro | Tipo | Descripcion |
|-----------|------|-------------|
| `id` | integer | ID del control general de mercado a eliminar |

**Ejemplo Request:**
```
DELETE /admin/market-general-controls/1
```

**Ejemplo Response (200 OK):**
```json
{
  "message": "Control general de mercado eliminado correctamente"
}
```

---

## Sincronizacion Automatica

Cuando se cambia el estado (publicado/borrador) de cualquier bloque de informacion individual (noticias, insights, major crops, etc.), el campo `data` del control general de mercado correspondiente al mismo mes/anio se actualiza automaticamente.

Por ejemplo:
- Si se publica una noticia del mes 1/2026, el campo `data.news` del control general de mercado de enero 2026 se actualiza a `true`.
- Si se pasa a borrador un insight del mes 1/2026, el campo `data.insights` del control general de mercado de enero 2026 se actualiza a `false`.

## Filtrado en Plataforma Web

Los endpoints publicos de la plataforma web (bajo `/api/companies/...`) solo retornan registros cuyos meses/anios esten publicados en la tabla `market_general_controls` (status_id=1).

---

## Notas Importantes

### Estados
- **1 - Publicado**: Los datos de ese mes/anio son visibles en la plataforma web
- **2 - Borrador**: Los datos de ese mes/anio NO son visibles en la plataforma web

### Bloques de Informacion
El campo `data` es un JSON que indica el estado de carga de cada bloque:
- `true`: El bloque tiene datos cargados y publicados para ese mes/anio
- `false`: El bloque no tiene datos cargados o estan en borrador

### Soft Delete
Los registros eliminados:
- Tienen el campo `deleted_at` con timestamp
- NO aparecen en listados ni consultas
- Permanecen en la base de datos para auditoria

---

## Ejemplos de Uso

### Crear un nuevo mes de carga
```bash
curl -X POST https://api.smartagro.com/admin/market-general-controls \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "month": 2,
    "year": 2026
  }'
```

### Indicar que un bloque fue cargado
```bash
curl -X PUT https://api.smartagro.com/admin/market-general-controls/1/data \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "block": "major_crops",
    "loaded": true
  }'
```

### Publicar un mes completo
```bash
curl -X PUT https://api.smartagro.com/admin/market-general-controls/1/status \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status_id": 1
  }'
```

---

## Changelog

### v1.0.0 - 2026-02-13
- Creacion inicial del modulo de Control General de Mercado
- GET ALL con filtros y paginado
- GET BY ID
- POST para crear nuevo control (solo mes y anio)
- PUT para editar mes y anio
- PUT para actualizar bloques del JSON data
- PUT para cambiar estado (publicado/borrador)
- DELETE con soft delete
- Sincronizacion automatica con bloques individuales
- Filtrado en endpoints publicos por estado de control general
