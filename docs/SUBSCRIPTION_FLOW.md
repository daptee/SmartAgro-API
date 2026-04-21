# Flujo de Suscripciones - SmartAgro

Sistema de suscripciones integrado con MercadoPago.
Archivo de referencia: `app/Http/Controllers/SubscriptionController.php`

---

## Campos relevantes en `users`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_plan` | int | Plan actual: `1` = Semilla (gratuito), `2` = Siembra (pago) |
| `is_debtor` | bool | `true` si tiene un pago fallido pendiente |
| `grace_period_used` | bool | `true` si ya consumió el período de gracia (no se resetea nunca) |

---

## Flujo completo

### 1. Alta de suscripción
**Webhook:** `subscription_preapproval` → `status = authorized`

- Si `id_plan != 2` → `id_plan = 2`
- Crea registro en `UserPlan` con `next_payment_date`
- Si tiene `free_trial` → registra en `PaymentHistory` como `free_trial`
- **Email usuario:** Bienvenido al Plan Siembra
- **Email admin:** Notificación nueva suscripción

---

### 2. Cobro mensual exitoso
**Webhook:** `payment` → `status = approved / authorized`

- `is_debtor = false`, `id_plan = 2`
- `grace_period_used` **nunca se modifica** (si ya se usó, queda `true` para siempre)

| Condición previa | Email usuario |
|-----------------|---------------|
| `!wasDebtor && !hadGracePeriod` | **"Nuevo Pago"** |
| `wasDebtor = true` ó `hadGracePeriod = true` | **"Servicio Regularizado"** |

---

### 3. Cobro falla — reintentos (primeros 20 días)
**Webhook:** `payment` → `status = rejected / failed`

#### Caso A: `grace_period_used = false` (nunca usó la gracia)
- **Primer fallo** (`is_debtor = false`): `is_debtor = true` → **Email: "Pago Rechazado"**
- **Reintentos siguientes** (`is_debtor = true`): ignorado, sin email

#### Caso B: `grace_period_used = true`, `id_plan = 2` (ya usó la gracia, falla de nuevo)
- Cancela suscripción en MP (`status: cancelled`)
- `id_plan = 1`, `is_debtor = false`
- Guarda historial en `UserPlan`
- **Email usuario:** "Baja definitiva por impago"
- **Email admin:** Notificación baja definitiva

#### Caso C: `grace_period_used = true`, `id_plan != 2`
- Ya fue dado de baja en webhook previo → ignorado

---

### 4. MP agota 20 días sin cobrar → pausa la suscripción
**Webhook:** `subscription_preapproval` → `status = paused`

#### Caso A: `grace_period_used = false` (primera vez, gracia disponible)
- `grace_period_used = true`, `is_debtor = true`
- Reactiva la suscripción en MP (`status: authorized`) para que cobre el mes siguiente
- Registra en `PaymentHistory`
- **Email usuario:** "Beneficio de Gracia Activado"
- **Email admin:** Notificación período de gracia

#### Caso B: `grace_period_used = true`, `id_plan = 2` (ya usó la gracia, segundo ciclo fallido)
- `id_plan = 1`, `is_debtor = false`
- Guarda historial en `UserPlan` y `PaymentHistory`
- **Email usuario:** "Suspensión de Servicio"
- **Email admin:** Notificación suspensión

#### Caso C: `grace_period_used = true`, `id_plan != 2`
- Ya fue dado de baja antes (por el webhook `payment` del Caso B) → ignorado

---

### 5. Cancelación
**Webhook:** `subscription_preapproval` → `status = cancelled`

> Puede ser cancelación manual por el usuario o cancelación automática generada por el sistema (baja definitiva por impago).

- `id_plan = 1`, `is_debtor = false`
- Guarda historial en `UserPlan`
- **Email usuario:** "Bajaste de plan"
- **Email admin:** Notificación cancelación

> Si el sistema ya bajó al usuario vía webhook `payment` (Caso B), el `cancelled` llegará igualmente pero `id_plan` ya es `1` — no hay acción duplicada.

---

## Resumen de estados del usuario

```
grace_period_used = false, is_debtor = false, id_plan = 2  →  Al día
grace_period_used = false, is_debtor = true,  id_plan = 2  →  Pago fallido, MP reintentando
grace_period_used = true,  is_debtor = true,  id_plan = 2  →  En período de gracia
grace_period_used = true,  is_debtor = false, id_plan = 1  →  Dado de baja definitiva
```

---

## CRON (`GET /cron-payment`)

Se llama periódicamente para actualizar los montos al tipo de cambio del día.

- Busca `UserPlan` con `id_plan = 2` y `next_payment_date` entre hoy y mañana
  - También incluye hasta 7 días vencidos si el usuario tiene `is_debtor = true`
- Para cada suscripción consulta el estado actual en MP
- Si `status = authorized`: hace PUT actualizando el monto (pesos al tipo de cambio vigente)
- Si `status = paused`: solo loguea — el webhook maneja la lógica
- Si `status = cancelled`: no hace nada

---

## Emails del sistema

| Clase | Destinatario | Cuándo se envía |
|-------|-------------|-----------------|
| `WelcomePlan` | Usuario | Alta de suscripción |
| `NewPayment` | Usuario | Pago exitoso sin historial de deuda |
| `ServiceRegularized` | Usuario | Pago exitoso luego de deuda o gracia |
| `FailedPayment` | Usuario | Primer fallo de pago (una sola vez) |
| `GracePeriodGranted` | Usuario | Se otorga período de gracia |
| `ExpiredSubscription` | Usuario | Baja definitiva por impago (segundo ciclo fallido) |
| `LowPlan` | Usuario | Cancelación manual |
| `Notification*` | Admin | Espejo de cada evento anterior |
