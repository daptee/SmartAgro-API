# API Documentation - Insights Module

## Descripción
Módulo para la gestión de insights del sistema SmartAgro. Permite crear, editar, listar, cambiar estado y eliminar insights desde el panel de administración.

---

## Endpoints

### 1. GET ALL - Listar Insights
**Endpoint:** `GET /admin/insights`

**Descripción:** Retorna todos los insights sin importar el estado. Incluye filtros por rango de fechas, estado, plan y búsqueda por texto.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `per_page` | integer | No | Número de registros por página. Si no se especifica, retorna todos |
| `page` | integer | No | Número de página (default: 1) |
| `date_from` | date | No | Fecha de inicio (formato: YYYY-MM-DD) |
| `date_to` | date | No | Fecha de fin (formato: YYYY-MM-DD) |
| `status_id` | integer | No | ID del estado (1=Publicado, 2=Borrador) |
| `id_plan` | integer | No | ID del plan |
| `search` | string | No | Búsqueda por coincidencia en título o descripción |

**Ejemplo Request:**
```
GET /admin/insights?per_page=10&page=1&status_id=1&search=mercado
```

**Ejemplo Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Análisis del mercado agrícola",
      "description": "El mercado presenta tendencias positivas...",
      "icon": "icon-path.svg",
      "date": "2025-12-01",
      "id_plan": 1,
      "status_id": 1,
      "id_user": 5,
      "created_at": "2025-12-01T10:00:00.000000Z",
      "updated_at": "2025-12-01T10:00:00.000000Z",
      "deleted_at": null,
      "plan": {
        "id": 1,
        "plan": "Plan Semilla",
        "...": "..."
      },
      "status": {
        "id": 1,
        "status_name": "Publicado"
      },
      "user": {
        "id": 5,
        "name": "Admin User",
        "...": "..."
      }
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 10,
    "total": 25,
    "last_page": 3
  }
}
```

---

### 2. POST - Crear Insight
**Endpoint:** `POST /admin/insights`

**Descripción:** Crea un nuevo insight. Las validaciones dependen del estado:
- **Borrador (status_id=2)**: Solo requiere `title` y `date`
- **Publicado (status_id=1)**: Requiere todos los campos obligatorios

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body Parameters:**
| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `title` | string | Sí | Título del insight (máx. 255 caracteres) |
| `date` | date | Sí | Fecha del insight (formato: YYYY-MM-DD) |
| `status_id` | integer | Sí | Estado (1=Publicado, 2=Borrador) |
| `description` | string | Condicional* | Descripción del insight |
| `icon` | string | No | Ruta o nombre del ícono |
| `id_plan` | integer | Condicional* | ID del plan asociado |

*Condicional: Requerido si `status_id=1` (Publicado)

**Ejemplo Request:**
```json
{
  "title": "Tendencias del mercado agrícola 2026",
  "description": "El sector agrícola muestra perspectivas positivas para el próximo año...",
  "icon": "market-icon.svg",
  "date": "2026-01-05",
  "id_plan": 1,
  "status_id": 1
}
```

**Ejemplo Response (201 Created):**
```json
{
  "data": {
    "id": 15,
    "title": "Tendencias del mercado agrícola 2026",
    "description": "El sector agrícola muestra perspectivas positivas para el próximo año...",
    "icon": "market-icon.svg",
    "date": "2026-01-05",
    "id_plan": 1,
    "status_id": 1,
    "id_user": 5,
    "created_at": "2026-01-05T14:30:00.000000Z",
    "updated_at": "2026-01-05T14:30:00.000000Z",
    "plan": { "..." },
    "status": { "..." },
    "user": { "..." }
  }
}
```

**Errores Posibles:**
- `400 Bad Request`: Intentar publicar sin completar todos los campos
- `422 Unprocessable Entity`: Validación fallida
- `500 Internal Server Error`: Error del servidor

---

### 3. PUT - Editar Insight
**Endpoint:** `PUT /admin/insights/{id}`

**Descripción:** Actualiza un insight existente. Sigue los mismos criterios de validación que la creación.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | integer | ID del insight a editar |

**Body Parameters:** (Mismos que POST)

**Ejemplo Request:**
```
PUT /admin/insights/15
```
```json
{
  "title": "Tendencias del mercado agrícola 2026 (Actualizado)",
  "description": "Actualización: El sector agrícola muestra perspectivas muy positivas...",
  "icon": "market-icon-v2.svg",
  "date": "2026-01-05",
  "id_plan": 1,
  "status_id": 1
}
```

**Ejemplo Response (200 OK):**
```json
{
  "data": {
    "id": 15,
    "title": "Tendencias del mercado agrícola 2026 (Actualizado)",
    "description": "Actualización: El sector agrícola muestra perspectivas muy positivas...",
    "...": "..."
  }
}
```

**Errores Posibles:**
- `400 Bad Request`: Intentar publicar sin completar todos los campos
- `404 Not Found`: Insight no encontrado
- `422 Unprocessable Entity`: Validación fallida
- `500 Internal Server Error`: Error del servidor

---

### 4. PUT - Cambiar Estado
**Endpoint:** `PUT /admin/insights/{id}/status`

**Descripción:** Cambia el estado de un insight. Valida que todos los campos estén completos si se cambia a "Publicado".

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | integer | ID del insight |

**Body Parameters:**
| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `status_id` | integer | Sí | Nuevo estado (1=Publicado, 2=Borrador) |

**Ejemplo Request:**
```
PUT /admin/insights/15/status
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
    "id": 15,
    "status_id": 1,
    "...": "..."
  }
}
```

**Errores Posibles:**
- `400 Bad Request`: No se puede publicar porque faltan campos obligatorios
```json
{
  "message": "No se puede publicar el insight. Todos los campos deben estar completos (título, descripción, fecha y plan)."
}
```
- `404 Not Found`: Insight no encontrado
- `422 Unprocessable Entity`: Validación fallida
- `500 Internal Server Error`: Error del servidor

---

### 5. DELETE - Eliminar Insight
**Endpoint:** `DELETE /admin/insights/{id}`

**Descripción:** Elimina un insight mediante soft delete. El registro NO será devuelto en ninguna petición posterior.

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameters:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | integer | ID del insight a eliminar |

**Ejemplo Request:**
```
DELETE /admin/insights/15
```

**Ejemplo Response (200 OK):**
```json
{
  "message": "Insight eliminado correctamente"
}
```

**Errores Posibles:**
- `404 Not Found`: Insight no encontrado
- `500 Internal Server Error`: Error del servidor

---

## Notas Importantes

### Estados
- **1 - Publicado**: El insight es visible para los usuarios finales
- **2 - Borrador**: El insight está en proceso de creación/edición

### Validaciones por Estado

#### Borrador (status_id=2)
✅ Campos obligatorios:
- `title`
- `date`
- `status_id`

⚠️ Campos opcionales:
- `description`
- `icon`
- `id_plan`

#### Publicado (status_id=1)
✅ Campos obligatorios:
- `title`
- `date`
- `status_id`
- `description`
- `id_plan`

⚠️ Campos opcionales:
- `icon`

### Soft Delete
Los insights eliminados:
- ✅ Tienen el campo `deleted_at` con timestamp
- ✅ NO aparecen en listados ni consultas
- ✅ Permanecen en la base de datos para auditoría
- ❌ NO pueden ser recuperados desde la API (requiere acceso directo a BD)

---

## Ejemplos de Uso

### Crear un Borrador
```bash
curl -X POST https://api.smartagro.com/admin/insights \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Nuevo análisis",
    "date": "2026-01-10",
    "status_id": 2
  }'
```

### Completar y Publicar
```bash
curl -X PUT https://api.smartagro.com/admin/insights/20 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Nuevo análisis",
    "description": "Descripción completa del análisis...",
    "date": "2026-01-10",
    "id_plan": 1,
    "status_id": 1
  }'
```

### Buscar Insights Publicados
```bash
curl -X GET "https://api.smartagro.com/admin/insights?status_id=1&search=mercado&per_page=20" \
  -H "Authorization: Bearer {token}"
```

---

## Changelog

### v1.0.0 - 2026-01-05
- ✨ Creación inicial del módulo de Insights
- ✨ GET ALL con filtros avanzados
- ✨ POST para crear insights
- ✨ PUT para editar insights
- ✨ PUT para cambiar estado
- ✨ DELETE con soft delete
- ✨ Validaciones condicionales por estado
- ✨ Relaciones con Plan, Status y User
