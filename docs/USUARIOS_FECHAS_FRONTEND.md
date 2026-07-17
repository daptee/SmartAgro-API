# Fechas de pago y baja por usuario — Guía para Frontend

## Endpoint

```
GET /admin/users
```

Estos campos vienen incluidos en cada objeto de `data` del listado de usuarios (junto con los campos básicos ya existentes: `id`, `name`, `email`, `id_plan`, etc.). No requieren ningún filtro adicional para aparecer.

## Campos

| Campo | Tipo | Descripción |
|---|---|---|
| `last_payment_date` | datetime \| `null` | Fecha del último pago real registrado (`payment_history.type` en `payment` o `approved`). `null` si el usuario nunca pagó. |
| `next_payment_date` | datetime \| `null` | Fecha estimada del próximo cobro: `last_payment_date` + 1 mes (suscripción mensual) o + 1 año (suscripción anual). `null` si el usuario nunca pagó. |
| `cancelled_at` | datetime \| `null` | Fecha de la última baja (downgrade a plan gratuito) registrada en el historial de planes del usuario. `null` si el usuario nunca fue dado de baja. |

## Notas

- `next_payment_date` es una **estimación** calculada a partir del último pago y el tipo de suscripción (`subscription_type`), no una fecha confirmada por Mercado Pago.
- `cancelled_at` refleja la baja **más reciente** del usuario, sin importar el motivo (cancelación desde la app, desde Mercado Pago, o automática por deudor — ver [METRICAS_USUARIOS_FRONTEND.md](./METRICAS_USUARIOS_FRONTEND.md) para filtrar por motivo). Si el usuario se dio de baja y luego volvió a suscribirse, `cancelled_at` sigue mostrando la fecha de esa baja aunque hoy esté activo (`id_plan = 2`).
- Si necesitás saber el **motivo** de la baja de un usuario puntual (no solo la fecha), no está disponible como campo en `data` — usá los filtros de [METRICAS_USUARIOS_FRONTEND.md](./METRICAS_USUARIOS_FRONTEND.md) (`cancelled_via_app`, `cancelled_via_mercadopago`, `auto_cancelled_debtor`) para acotar el listado por tipo de baja.

## Ejemplo

```json
{
    "id": 128,
    "name": "Juan",
    "last_name": "Pérez",
    "email": "juan@example.com",
    "id_plan": 1,
    "subscription_type": "monthly",
    "last_payment_date": "2026-05-10 14:32:00",
    "next_payment_date": "2026-06-10 14:32:00",
    "cancelled_at": "2026-05-15 09:12:00"
}
```
