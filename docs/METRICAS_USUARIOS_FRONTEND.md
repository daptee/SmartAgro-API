# Métricas y filtros de usuarios — Guía para Frontend

## Índice

1. [Endpoint](#1-endpoint)
2. [Filtros disponibles](#2-filtros-disponibles)
3. [Estructura de la respuesta](#3-estructura-de-la-respuesta)
4. [Métricas disponibles](#4-métricas-disponibles)
5. [Métrica → filtro (click en una métrica)](#5-métrica--filtro-click-en-una-métrica)
6. [Ejemplos](#6-ejemplos)
7. [Notas sobre confiabilidad de los datos de baja](#7-notas-sobre-confiabilidad-de-los-datos-de-baja)

---

## 1. Endpoint

```
GET /admin/users
```

Requiere sesión de administrador (`admin` + `check_permissions_hash`) y el módulo `usuarios` habilitado en el rol.

---

## 2. Filtros disponibles

Todos los filtros son opcionales y se combinan entre sí (AND). Se envían como query params.

| Filtro | Valores | Descripción |
|---|---|---|
| `search` | string | Busca por `name`, `last_name` o `email` |
| `id_plan` | `1` \| `2` | 1 = Semilla (gratis), 2 = Siembra (pago) |
| `id_user_profile` | int | Filtra por perfil de usuario |
| `id_country` | int | Filtra por país |
| `id_province` | int | Filtra por provincia |
| `id_locality` | int | Filtra por localidad |
| `id_status` | int | Filtra por estado del usuario |
| `referred_by` | int | Filtra por quién lo refirió |
| `event_id` | int | Filtra por evento |
| `plan_start_from` | date (`YYYY-MM-DD`) | Fecha de alta de plan desde |
| `plan_start_to` | date (`YYYY-MM-DD`) | Fecha de alta de plan hasta |
| `subscription_type` | `monthly` \| `yearly` | Tipo de suscripción |
| `free_trial_used` | `1` \| `0` | Usó el mes/período gratuito |
| `email_confirmation` | `1` \| `0` | Email confirmado o no |
| `subscription_manual` | `1` \| `0` | 1 = plan activado manualmente desde admin, 0 = activado por pago real |
| `paid_siembra` | `1` \| `0` | Fuerza `id_plan=2`. 1 = Siembra con al menos un pago real; 0 = Siembra habilitado manualmente (sin pagos) |
| `active_free_trial` | `1` \| `0` | 1 = Siembra actualmente en período de prueba gratuito (sin pagos reales aún); 0 = Siembra que ya superó el período gratuito (con al menos un pago real) |
| `paid_churned` | `1` \| `0` | 1 = pagaron al menos una vez y tuvieron **alguna** baja (por app, por Mercado Pago o automática por deudor), sin importar si hoy volvieron a suscribirse; 0 = el resto |
| `cancelled_via_app` | `1` \| `0` | 1 = cancelaron la suscripción con el botón de la app; 0 = el resto |
| `cancelled_via_mercadopago` | `1` \| `0` | 1 = cancelaron la suscripción directamente en Mercado Pago (no desde la app); 0 = el resto |
| `auto_cancelled_debtor` | `1` \| `0` | 1 = el sistema los dio de baja automáticamente por impago (deudor); 0 = el resto |
| `per_page` | int | Pagina el listado. Si no se envía, `data` trae **todos** los usuarios que matchean el filtro |

---

## 3. Estructura de la respuesta

```json
{
    "message": "Listado de usuarios",
    "data": [ /* usuarios paginados o completos, según per_page */ ],
    "meta": {
        "total": 120,
        "per_page": 20,
        "current_page": 1,
        "last_page": 6
    },
    "metrics": { /* ver sección 4 */ }
}
```

- `meta` es `null` si no se envía `per_page`.
- `metrics` se calcula sobre el total de usuarios que matchean los filtros **genéricos** aplicados (no sobre la página actual). Los filtros "selector de métrica" (ver sección 5) acotan `data`/`meta` pero **no** recalculan `metrics` — el panel de métricas debe mantenerse estable al clickear una tarjeta.

---

## 4. Métricas disponibles

```json
"metrics": {
    "plan_semilla": 340,
    "plan_siembra": 85,
    "plan_siembra_pagos": 60,
    "plan_siembra_manual": 25,
    "siembra_mensual": 40,
    "siembra_anual": 20,
    "siembra_periodo_gratis": 15,
    "siembra_free_trial_activo": 5,
    "pagaron_y_se_dieron_de_baja": 18,
    "baja_por_app": 10,
    "baja_por_mercadopago": 4,
    "baja_automatica_deudor": 6
}
```

| Métrica | Descripción |
|---|---|
| `plan_semilla` | Usuarios en plan gratuito |
| `plan_siembra` | Usuarios en plan de pago (actual) |
| `plan_siembra_pagos` | De los de plan de pago, los que tienen al menos un pago real registrado |
| `plan_siembra_manual` | De los de plan de pago, los habilitados manualmente desde el admin |
| `siembra_mensual` | Plan de pago con suscripción mensual |
| `siembra_anual` | Plan de pago con suscripción anual |
| `siembra_periodo_gratis` | Plan de pago que usó el período de prueba gratuito |
| `siembra_free_trial_activo` | Plan de pago actualmente en período de prueba gratuito, sin pagos reales aún |
| `pagaron_y_se_dieron_de_baja` | Pagaron al menos una vez y tuvieron **alguna** baja (unión de `baja_por_app`, `baja_por_mercadopago` y `baja_automatica_deudor`), aunque hoy hayan vuelto a suscribirse |
| `baja_por_app` | Cancelaron la suscripción desde el botón de la app |
| `baja_por_mercadopago` | Cancelaron la suscripción directamente en Mercado Pago (no desde la app) |
| `baja_automatica_deudor` | El sistema los bajó automáticamente a plan gratuito por impago |

---

## 5. Métrica → filtro (click en una métrica)

Cuando el usuario hace click en una tarjeta de métrica en el panel, hay que repetir el request a `/admin/users` agregando el filtro correspondiente (manteniendo los demás filtros ya aplicados, si los hubiera):

| Tarjeta / métrica | Filtro a agregar | ¿Recalcula `metrics`? |
|---|---|---|
| Plan Semilla | `id_plan=1` | Sí |
| Plan Siembra | `id_plan=2` | Sí |
| Siembra con pagos | `id_plan=2&paid_siembra=1` | `id_plan` sí, `paid_siembra` no |
| Siembra manual | `id_plan=2&subscription_manual=1` | `id_plan` sí, `subscription_manual` no |
| Siembra mensual | `subscription_type=monthly` | Sí |
| Siembra anual | `subscription_type=yearly` | Sí |
| Con período de prueba usado | `free_trial_used=1` | Sí |
| Free trial activo | `active_free_trial=1` | No |
| Pagaron y se dieron de baja | `paid_churned=1` | No |
| Baja por la app | `cancelled_via_app=1` | No |
| Baja por Mercado Pago | `cancelled_via_mercadopago=1` | No |
| Baja automática por deudor | `auto_cancelled_debtor=1` | No |

Con esto, `data` (y `meta`, si hay `per_page`) devuelve **solo** los usuarios de esa métrica.

`metrics` solo se recalcula cuando el filtro agregado es uno de los filtros genéricos (los listados en la sección 2 sin ser exclusivos de una tarjeta: `id_plan`, `subscription_type`, `free_trial_used`, y el resto de filtros de la sección 2). Los filtros exclusivos de una tarjeta de métrica (`paid_siembra`, `subscription_manual`, `active_free_trial`, `paid_churned`, `cancelled_via_app`, `cancelled_via_mercadopago`, `auto_cancelled_debtor`, `unsubscribed`) **no** alteran los valores de `metrics` — el panel se mantiene estable y solo cambia el listado (`data`).

---

## 6. Ejemplos

**Usuarios que pagaron y hoy están de baja, paginado de 20:**
```
GET /admin/users?paid_churned=1&per_page=20
```

**Usuarios dados de baja automáticamente por deudor, dentro de un rango de fechas de alta de plan:**
```
GET /admin/users?auto_cancelled_debtor=1&plan_start_from=2026-01-01&plan_start_to=2026-06-30
```

**Usuarios que cancelaron desde la app, filtrando también por país:**
```
GET /admin/users?cancelled_via_app=1&id_country=1
```

---

## 7. Notas sobre confiabilidad de los datos de baja

`baja_por_app`, `baja_por_mercadopago`, `baja_automatica_deudor` y `paid_churned` (y sus filtros equivalentes) se calculan a partir del historial de cambios de plan (`users_plans`), que no tiene una columna dedicada al origen de la baja — se infiere de un texto libre guardado en ese historial. Es información confiable para uso analítico, pero no es un campo estructurado del modelo `User`, por lo que **no aparece como columna en `data`** de cada usuario; solo existe como filtro y como conteo agregado en `metrics`.
