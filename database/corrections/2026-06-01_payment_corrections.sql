-- =============================================================================
-- CORRECCIONES MANUALES DE PAGOS - 2026-06-01
-- Registros ya insertados (id=145 y id=146). Este script actualiza la data
-- con el JSON completo reconstruido a partir de los pagos históricos y logs de MP.
-- =============================================================================


-- =============================================================================
-- 1. FLORENCIA PIPUIG (id=436) — Renovación anual mayo 2026
--    payment_history id=145, payment_id=160508824582
--    Confirmado via authorized_payment 7028333474: approved/accredited, $66.270 ARS
--    Payer/card tomados del pago original 2025 (misma suscripción, misma tarjeta).
--    fee/tax aproximados; net_received_amount null (sin acceso API prod para este pago).
-- =============================================================================

-- Verificar registro existente
SELECT id, type, preapproval_id, payment_id, created_at
FROM payment_history WHERE id = 145;

UPDATE payment_history
SET data = CAST('{
    "id": 160508824582,
    "card": {
        "id": "9425836613",
        "bin": "51647800",
        "tags": ["credit"],
        "country": "ARG",
        "cardholder": {
            "name": "CARELLI DELIA M",
            "identification": {"type": "DNI", "number": "18195457"}
        },
        "date_created": "2026-05-22T09:12:41.000-04:00",
        "expiration_year": 2027,
        "expiration_month": 3,
        "first_six_digits": "516478",
        "last_four_digits": "7423",
        "date_last_updated": "2026-05-22T09:12:41.000-04:00"
    },
    "tags": null,
    "order": [],
    "payer": {
        "id": "145676425",
        "type": null,
        "email": "florenciapipuig@hotmail.com",
        "phone": {"number": null, "area_code": null, "extension": null},
        "last_name": null,
        "first_name": null,
        "entity_type": null,
        "operator_id": null,
        "identification": {"type": "CUIL", "number": "27389159781"}
    },
    "pos_id": null,
    "status": "approved",
    "refunds": [],
    "brand_id": null,
    "captured": true,
    "metadata": {
        "preapproval_id": "0f5f215e32e64ebb8255b0031e8c45e3"
    },
    "store_id": null,
    "issuer_id": "1049",
    "live_mode": true,
    "sponsor_id": null,
    "binary_mode": false,
    "currency_id": "ARS",
    "description": "Cobro anual SmartAgro - Plan Siembra",
    "fee_details": [
        {
            "type": "mercadopago_fee",
            "amount": 2717.07,
            "fee_payer": "collector"
        }
    ],
    "platform_id": null,
    "collector_id": 1532822464,
    "date_created": "2026-05-22T09:14:15.915-04:00",
    "installments": 1,
    "release_info": null,
    "taxes_amount": 0,
    "accounts_info": null,
    "coupon_amount": 0,
    "date_approved": "2026-05-22T09:14:16.000-04:00",
    "integrator_id": null,
    "status_detail": "accredited",
    "corporation_id": null,
    "operation_type": "regular_payment",
    "payment_method": {
        "id": "master",
        "data": {"routing_data": {"merchant_account_id": "56"}},
        "type": "credit_card",
        "issuer_id": "1049"
    },
    "additional_info": {
        "items": [
            {
                "title": "Cobro anual SmartAgro - Plan Siembra",
                "quantity": "1",
                "unit_price": "66270"
            }
        ],
        "tracking_id": "platform:v1-blacklabel,so:ALL,type:N/A,security:none"
    },
    "charges_details": [
        {
            "id": "160508824582-001",
            "name": "mercadopago_fee",
            "rate": 4.1,
            "type": "fee",
            "amounts": {"original": 2717.07, "refunded": 0},
            "accounts": {"to": "mp", "from": "collector"},
            "metadata": {"reason": "", "source": "rule-engine"},
            "client_id": 0,
            "reserve_id": null,
            "base_amount": 66270.00,
            "date_created": "2026-05-22T09:14:15.000-04:00",
            "last_updated": "2026-05-22T09:14:15.000-04:00",
            "refund_charges": []
        },
        {
            "id": "160508824582-002",
            "name": "tax_withholding_collector-debitos_creditos",
            "rate": 0.6,
            "type": "tax",
            "amounts": {"original": 397.62, "refunded": 0},
            "accounts": {"to": "mp", "from": "collector"},
            "metadata": {
                "source": "taxes",
                "user_id": 1532822464,
                "mov_type": "expense",
                "mov_detail": "tax_withholding_collector",
                "tax_status": "applied",
                "mov_financial_entity": "debitos_creditos"
            },
            "client_id": 0,
            "reserve_id": null,
            "base_amount": 66270.00,
            "date_created": "2026-05-22T09:14:15.000-04:00",
            "last_updated": "2026-05-22T09:14:15.000-04:00",
            "refund_charges": []
        }
    ],
    "financing_group": null,
    "merchant_number": null,
    "payment_type_id": "credit_card",
    "processing_mode": "aggregator",
    "shipping_amount": 0,
    "counter_currency": null,
    "deduction_schema": null,
    "notification_url": null,
    "date_last_updated": "2026-05-23T05:49:27.725-04:00",
    "marketplace_owner": null,
    "payment_method_id": "master",
    "authorization_code": null,
    "date_of_expiration": null,
    "external_reference": "436",
    "money_release_date": "2026-06-09T09:14:16.000-04:00",
    "transaction_amount": 66270.00,
    "merchant_account_id": null,
    "transaction_details": {
        "overpaid_amount": 0,
        "total_paid_amount": 66270.00,
        "acquirer_reference": null,
        "installment_amount": 66270.00,
        "net_received_amount": null,
        "external_resource_url": null,
        "financial_institution": null,
        "payable_deferral_period": null,
        "payment_method_reference_id": null
    },
    "money_release_schema": null,
    "money_release_status": "released",
    "point_of_interaction": {
        "type": "SUBSCRIPTIONS",
        "location": {"source": "collector", "state_id": "null-null"},
        "business_info": {"unit": "online_payments", "branch": "Merchant Services", "sub_unit": "recurring"},
        "application_data": {"name": "recurring", "version": null, "operating_system": null},
        "transaction_data": {
            "plan_id": null,
            "processor": null,
            "billing_date": "2026-05-22",
            "user_present": null,
            "first_time_use": false,
            "invoice_period": {"type": "monthly", "period": 12},
            "subscription_id": "0f5f215e32e64ebb8255b0031e8c45e3",
            "payment_reference": null,
            "subscription_sequence": {"total": null, "number": 2}
        }
    },
    "statement_descriptor": "MERPAGO*SMARTKETING",
    "call_for_authorize_id": null,
    "authorized_payment_id": 7028333474,
    "acquirer_reconciliation": [],
    "differential_pricing_id": null,
    "transaction_amount_refunded": 0,
    "_reconstructed": true,
    "_note": "Registro insertado manualmente 2026-06-01 — renovacion anual 22/05/2026 confirmada en MercadoPago"
}' AS JSON)
WHERE id = 145;

-- Verificar resultado
SELECT id, type, payment_id, created_at,
       JSON_UNQUOTE(JSON_EXTRACT(data, '$.status')) as status,
       JSON_UNQUOTE(JSON_EXTRACT(data, '$.transaction_amount')) as monto
FROM payment_history WHERE id = 145;


-- =============================================================================
-- 2. EZEQUIEL DÍAZ VALDEZ (id=611) — Pago mayo 2026
--    payment_history id=146, payment_id=157879402724
--    Confirmado via authorized_payment 7027841954: approved/accredited, $6.933,50 ARS
--    Payer/card tomados del historial existente (misma suscripción, misma tarjeta).
--    subscription_sequence=7 (seq 6 fue marzo, abril sin pago en MP).
--    fee/tax calculados con tasa histórica 4.1% + 0.6%; net aproximado.
-- =============================================================================

-- Verificar registro existente
SELECT id, type, preapproval_id, payment_id, created_at
FROM payment_history WHERE id = 146;

UPDATE payment_history
SET
    payment_id = 157879402724,
    data = CAST('{
    "id": 157879402724,
    "card": {
        "id": "9706654740",
        "bin": "55056850",
        "tags": ["credit"],
        "country": "ARG",
        "cardholder": {
            "name": "DIAZ VALDEZ EZEQUIEL",
            "identification": {"type": "DNI", "number": "31722216"}
        },
        "date_created": "2026-05-05T17:25:09.000-04:00",
        "expiration_year": 2026,
        "expiration_month": 12,
        "first_six_digits": "550568",
        "last_four_digits": "8117",
        "date_last_updated": "2026-05-05T17:25:09.000-04:00"
    },
    "tags": null,
    "order": [],
    "payer": {
        "id": "47992734",
        "type": null,
        "email": "equi_diaz@hotmail.com",
        "phone": {"number": null, "area_code": null, "extension": null},
        "last_name": null,
        "first_name": null,
        "entity_type": null,
        "operator_id": null,
        "identification": {"type": "CUIT", "number": "20317222166"}
    },
    "pos_id": null,
    "status": "approved",
    "refunds": [],
    "brand_id": null,
    "captured": true,
    "metadata": {
        "user_type": "registered",
        "preapproval_id": "281bc7f55e9549e69296987a079c1f12",
        "available_tries": 3
    },
    "store_id": null,
    "issuer_id": "279",
    "live_mode": true,
    "sponsor_id": null,
    "binary_mode": false,
    "currency_id": "ARS",
    "description": "Cobro mensual SmartAgro - Plan Siembra",
    "fee_details": [
        {
            "type": "mercadopago_fee",
            "amount": 284.27,
            "fee_payer": "collector"
        }
    ],
    "platform_id": null,
    "collector_id": 1532822464,
    "date_created": "2026-05-05T17:26:46.000-04:00",
    "installments": 1,
    "release_info": null,
    "taxes_amount": 0,
    "accounts_info": null,
    "coupon_amount": 0,
    "date_approved": "2026-05-05T17:34:41.000-04:00",
    "integrator_id": null,
    "status_detail": "accredited",
    "corporation_id": null,
    "operation_type": "recurring_payment",
    "payment_method": {
        "id": "master",
        "data": {"routing_data": {"merchant_account_id": "56"}},
        "type": "credit_card",
        "issuer_id": "279"
    },
    "additional_info": {
        "tracking_id": "platform:v1-blacklabel,so:ALL,type:N/A,security:none"
    },
    "charges_details": [
        {
            "id": "157879402724-001",
            "name": "mercadopago_fee",
            "rate": 4.1,
            "type": "fee",
            "amounts": {"original": 284.27, "refunded": 0},
            "accounts": {"to": "mp", "from": "collector"},
            "metadata": {"reason": "", "source": "proc-svc-charges", "source_detail": "processing_fee_charge"},
            "client_id": 0,
            "reserve_id": null,
            "base_amount": 6933.5,
            "date_created": "2026-05-05T17:26:46.000-04:00",
            "last_updated": "2026-05-05T17:26:46.000-04:00",
            "refund_charges": [],
            "update_charges": []
        },
        {
            "id": "157879402724-002",
            "name": "tax_withholding_collector-debitos_creditos",
            "rate": 0.6,
            "type": "tax",
            "amounts": {"original": 41.60, "refunded": 0},
            "accounts": {"to": "mp", "from": "collector"},
            "metadata": {
                "source": "taxes",
                "user_id": 1532822464,
                "mov_type": "expense",
                "mov_detail": "tax_withholding_collector",
                "tax_status": "applied",
                "mov_financial_entity": "debitos_creditos"
            },
            "client_id": 0,
            "reserve_id": null,
            "base_amount": 6933.5,
            "date_created": "2026-05-05T17:26:46.000-04:00",
            "last_updated": "2026-05-05T17:26:46.000-04:00",
            "refund_charges": [],
            "update_charges": []
        }
    ],
    "financing_group": null,
    "merchant_number": null,
    "payment_type_id": "credit_card",
    "processing_mode": "aggregator",
    "shipping_amount": 0,
    "counter_currency": null,
    "deduction_schema": null,
    "notification_url": null,
    "date_last_updated": "2026-05-05T17:34:41.000-04:00",
    "marketplace_owner": null,
    "payment_method_id": "master",
    "authorization_code": null,
    "date_of_expiration": null,
    "external_reference": "611",
    "money_release_date": "2026-05-23T17:34:41.000-04:00",
    "transaction_amount": 6933.5,
    "merchant_account_id": null,
    "transaction_details": {
        "overpaid_amount": 0,
        "total_paid_amount": 6933.5,
        "acquirer_reference": null,
        "installment_amount": 6933.5,
        "net_received_amount": 6607.63,
        "external_resource_url": null,
        "financial_institution": null,
        "payable_deferral_period": null,
        "payment_method_reference_id": null
    },
    "money_release_schema": null,
    "money_release_status": "released",
    "point_of_interaction": {
        "type": "SUBSCRIPTIONS",
        "location": {"source": "payer", "state_id": "AR-C"},
        "business_info": {"unit": "online_payments", "branch": "Merchant Services", "sub_unit": "recurring"},
        "application_data": {"name": null, "version": null, "operating_system": null},
        "transaction_data": {
            "plan_id": null,
            "processor": null,
            "billing_date": "2026-05-05",
            "user_present": false,
            "first_time_use": false,
            "invoice_period": {"type": "monthly", "period": 1},
            "subscription_id": "281bc7f55e9549e69296987a079c1f12",
            "payment_reference": {"id": "128190209637", "acquirer": null},
            "subscription_sequence": {"total": null, "number": 7}
        }
    },
    "statement_descriptor": "MERPAGO*SMARTKETING",
    "call_for_authorize_id": null,
    "authorized_payment_id": 7027841954,
    "acquirer_reconciliation": [],
    "differential_pricing_id": null,
    "transaction_amount_refunded": 0,
    "_reconstructed": true,
    "_note": "Registro insertado manualmente 2026-06-02 — pago mayo 2026 confirmado en logs de MP (authorized_payment id 7027841954)"
}' AS JSON)
WHERE id = 146;

-- Verificar resultado
SELECT id, type, payment_id, created_at,
       JSON_UNQUOTE(JSON_EXTRACT(data, '$.status')) as status,
       JSON_UNQUOTE(JSON_EXTRACT(data, '$.transaction_amount')) as monto
FROM payment_history WHERE id = 146;


-- =============================================================================
-- 3. NICOLÁS GERARDO (id=716) — Restaurar Plan 2 hasta diciembre 2026
--
-- El usuario canceló su suscripción el 15/05/2026 pero había pagado
-- anualmente en diciembre 2025 (cobertura hasta 05/12/2026).
-- Se restaura manualmente el acceso al plan 2 por el período ya pagado.
--
-- ⚠️  La suscripción en MercadoPago (151b4071b7624300b59af1c777df5b2e)
-- está CANCELADA. No habrá renovación automática en diciembre.
-- Contactar al usuario para que cree una nueva suscripción si desea continuar.
-- =============================================================================

-- Verificar estado antes de modificar
SELECT id, name, id_plan, is_debtor, grace_period_used, subscription_type, plan_start_date
FROM users WHERE id = 716;

SELECT id, id_plan, preapproval_id, next_payment_date, created_at
FROM users_plans WHERE id_user = 716 ORDER BY id DESC LIMIT 5;

-- Restaurar plan 2
UPDATE users
SET id_plan = 2, grace_period_used = 0
WHERE id = 716;

-- Registrar la restauración en users_plans
INSERT INTO users_plans (id_user, id_plan, preapproval_id, next_payment_date, data, created_at, updated_at)
VALUES (
    716,
    2,
    '151b4071b7624300b59af1c777df5b2e',
    '2026-12-05 15:49:05',
    JSON_OBJECT(
        'reason', 'Restaurado manualmente por admin — suscripcion anual pagada hasta 05/12/2026. El usuario cancelo la suscripcion en MP pero el periodo ya estaba pago.',
        'is_system', 'true',
        'restored_by', 'admin',
        'restored_at', '2026-06-02'
    ),
    NOW(),
    NOW()
);

-- Verificar resultado final
SELECT id, name, id_plan, is_debtor, subscription_type FROM users WHERE id = 716;
SELECT id, id_plan, preapproval_id, next_payment_date, created_at
FROM users_plans WHERE id_user = 716 ORDER BY id DESC LIMIT 3;
