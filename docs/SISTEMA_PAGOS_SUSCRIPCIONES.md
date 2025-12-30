# Sistema de Pagos y Suscripciones - SmartAgro

**Versión:** 2.0 | **Última actualización:** Diciembre 2024

---

## Resumen General

El sistema maneja automáticamente las suscripciones de MercadoPago con:

- ✅ Registro completo de pagos (exitosos y fallidos)
- ✅ Período de gracia **único** (una vez por usuario)
- ✅ Sistema de deudores (`is_debtor`)
- ✅ Validación de firma de webhooks (seguridad)
- ✅ Prevención de race conditions
- ✅ Envío seguro de emails con manejo de errores
- ✅ Notificaciones automáticas

---

## Campos Clave en Tabla `users`

### `is_debtor` (boolean)
- **Propósito:** Indica si el usuario tiene pagos pendientes
- **Se marca `true`:** Cuando un pago falla
- **Se marca `false`:** Cuando paga exitosamente o cancela suscripción
- **Se resetea:** Sí, en cada pago

### `grace_period_used` (boolean)
- **Propósito:** Indica si ya usó su período de gracia (UNA VEZ en la vida)
- **Se marca `true`:** Primera vez que MP pausa la suscripción
- **Se resetea:** NO, permanece `true` para siempre
- **Uso:** Prevenir abuso del sistema

**SQL:**
```sql
ALTER TABLE users ADD COLUMN is_debtor TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN grace_period_used TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Indica si el usuario ya usó su período de gracia (1 vez por usuario)';
```

**Archivo:** `database/sql/user.sql`

---

## Flujo de Pagos Fallidos

### 1. Primer Pago Falla
- Usuario: `is_debtor = true`
- Plan: Mantiene Plan Siembra
- Email: Notificación de fallo (usuario + admin)

### 2. MercadoPago Reintenta (4 veces en ~20 días)
- Cada fallo se registra en `payment_history`
- Usuario recibe email por cada fallo
- Usuario mantiene Plan Siembra

### 3. Suscripción Pausada (después de 4 fallos)

**Primera vez (`grace_period_used = false`):**
```
✅ Otorgar período de gracia
- Mantener Plan Siembra
- Marcar grace_period_used = true
- NO enviar emails
```

**Segunda vez o más (`grace_period_used = true`):**
```
❌ Downgrade inmediato
- Bajar a Plan Gratuito
- Marcar is_debtor = false
- Enviar emails de expiración
```

---

## Webhooks de MercadoPago

**Endpoint:** `POST /webhooks/mercadopago`

### Tipo: `subscription_preapproval`

| Estado | Acción |
|--------|--------|
| `authorized` | Usuario sube a Plan Siembra, emails de bienvenida |
| `cancelled` | Usuario baja a Plan Gratuito, emails de cancelación |
| `failed` | Registro en historial, sin cambio de plan |
| `paused` | Verificar `grace_period_used` y aplicar lógica descrita arriba |

### Tipo: `payment`

| Estado | Acción |
|--------|--------|
| `approved` / `authorized` | `is_debtor = false`, email de pago exitoso |
| `rejected` / `failed` | `is_debtor = true`, emails de pago fallido |

**Importante:** `grace_period_used` NO se resetea cuando el usuario paga.

---

## Correcciones Críticas de Seguridad

### 1. Validación de Firma de Webhooks
- **Método:** `validateWebhookSignature()` (líneas 55-89)
- **Implementación:** HMAC SHA256
- **Configuración requerida:**
  ```env
  MERCADOPAGO_WEBHOOK_SECRET=tu_secret_key_aqui
  ```
  ```php
  // config/app.php
  'mercadopago_webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
  ```

### 2. Envío Seguro de Emails
- **Método:** `sendEmailSafely()` (líneas 32-50)
- Envuelve `Mail::send()` en try-catch
- No detiene el webhook si falla el email

### 3. Prevención de Race Conditions
- **Ubicación:** Líneas 399-416
- Verifica duplicados antes de crear en `payment_history`
- Ventana de 5 minutos

### 4. Validación de Usuarios
- Verifica que `$user` existe antes de operaciones
- Previene errores fatales

### 5. Corrección de Operador Lógico (Bug Crítico)
- **Antes:** `if (!$subscriptionData['status'] == "authorized")`  ❌
- **Ahora:** `if ($subscriptionData['status'] != "authorized")`  ✅

---

## Emails del Sistema

| Email | Cuándo | Destinatario |
|-------|--------|--------------|
| `WelcomePlan` | Suscripción autorizada | Usuario |
| `NotificationWelcomePlan` | Suscripción autorizada | Admin |
| `NewPayment` | Pago exitoso | Usuario |
| `FailedPayment` | Pago fallido | Usuario |
| `NotificationFailedPayment` | Pago fallido | Admin |
| `ExpiredSubscription` | Suscripción vence (sin gracia) | Usuario |
| `NotificationExpiredSubscription` | Suscripción vence (sin gracia) | Admin |
| `LowPlan` | Cancelación | Usuario |
| `NotificationLowPlan` | Cancelación | Admin |

**Ubicación:**
- Mailables: `app/Mail/`
- Vistas: `resources/views/emails/`

---

## Archivos Modificados

### `app/Http/Controllers/SubscriptionController.php`
- Líneas 32-50: `sendEmailSafely()`
- Líneas 55-89: `validateWebhookSignature()`
- Líneas 144: Corrección operador lógico
- Líneas 249-262: Validación firma webhook
- Líneas 342-402: Lógica `paused` con `grace_period_used`
- Líneas 399-416: Prevención race conditions
- Líneas 421-438: Gestión `is_debtor`

### `app/Models/User.php`
- `$fillable`: `'is_debtor'`, `'grace_period_used'`
- `casts()`: `'is_debtor' => 'boolean'`, `'grace_period_used' => 'boolean'`

### `database/sql/user.sql`
- Línea 21: Campo `is_debtor`
- Línea 24: Campo `grace_period_used`

---

## Ejemplos de Uso

### Consultar usuarios deudores
```sql
SELECT id, name, email, id_plan, is_debtor, grace_period_used
FROM users
WHERE is_debtor = 1;
```

### Usuarios que ya usaron su gracia
```sql
SELECT id, name, email
FROM users
WHERE grace_period_used = 1;
```

### Usuarios deudores con gracia disponible
```sql
SELECT id, name, email
FROM users
WHERE is_debtor = 1 AND grace_period_used = 0 AND id_plan = 2;
```

---

## Comparación: `is_debtor` vs `grace_period_used`

| Campo | Se resetea | Cuándo cambia | Propósito |
|-------|-----------|---------------|-----------|
| `is_debtor` | ✅ Sí | Cada pago exitoso/fallido | Deuda actual |
| `grace_period_used` | ❌ No | Solo una vez (primera pausa) | Prevenir abuso |

**Ejemplo:**
```php
// Usuario con pago fallido (primera vez)
is_debtor = true
grace_period_used = false

// MP pausa (primera vez)
is_debtor = true
grace_period_used = true  // ← Se marca permanentemente
Plan = Siembra (mantiene)

// Usuario paga exitosamente
is_debtor = false  // ← Se resetea
grace_period_used = true  // ← NO se resetea
Plan = Siembra

// Pagos fallan nuevamente, MP pausa
is_debtor = true
grace_period_used = true  // ← Ya era true
Plan = Gratuito (downgrade inmediato, SIN gracia)
```

---

## Checklist de Producción

Antes de desplegar:

- [ ] Aplicar migraciones SQL (`is_debtor`, `grace_period_used`)
- [ ] Configurar `MERCADOPAGO_WEBHOOK_SECRET` en `.env`
- [ ] Agregar config en `config/app.php`
- [ ] Obtener secret key desde dashboard de MercadoPago
- [ ] Probar webhook con firma válida/inválida
- [ ] Verificar logs tras recibir webhooks
- [ ] Confirmar envío de emails
- [ ] Probar webhooks simultáneos (no duplicados)

---

## Logs Importantes

```
[INFO] Webhook recibido de Mercado Pago: {...}
[WARNING] Webhook rechazado: firma inválida
[INFO] Suscripción pausada para usuario 123. Otorgando período de gracia (manteniendo Plan Siembra).
[WARNING] Suscripción pausada para usuario 456 que ya usó su período de gracia. Bajando a plan gratuito.
[INFO] PaymentHistory creado para usuario 123 con status approved
[INFO] PaymentHistory ya existe (evitando duplicado)
[INFO] Email enviado exitosamente a user@example.com. Contexto: Pago exitoso - usuario
[ERROR] Error al enviar email. Contexto: Pago fallido - admin
```

**Ubicación:** `storage/logs/laravel.log`

---

## Soporte

- **Documentación completa de correcciones:** [CORRECCIONES_CRITICAS_APLICADAS.md](./CORRECCIONES_CRITICAS_APLICADAS.md)
- **Email:** soporte@smartagro.io
- **MercadoPago Dashboard:** https://www.mercadopago.com.ar/

---

**Changelog v2.0:**
- ✅ Agregado campo `grace_period_used` (período de gracia único)
- ✅ Validación de firma de webhooks (HMAC SHA256)
- ✅ Prevención de race conditions en PaymentHistory
- ✅ Envío seguro de emails con manejo de errores
- ✅ Validación de usuarios antes de operaciones
- ✅ Corrección de bugs críticos de lógica
- ✅ Eliminación de código duplicado
