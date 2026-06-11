-- =============================================================================
-- CORRECCIONES MANUALES DE PAGOS - 2026-06-11
-- Usuario 739: 1 registro faltante
--   - payment 160790644706 (aprobado, may 24 2026, ARS 6811)
--     El pago aprobado no se guardó porque el webhook llegó antes de que se
--     desplegara el fallback que maneja el 401 de /v1/payments/{id}.
--     Pager MP user ID: 165. Pago vía account_money (sin datos de tarjeta).
--
-- NOTA: Los 4 pagos rechazados (7027505485, 7028246685, 7028336589, 7028395003)
--   YA EXISTEN en payment_history (ids 116, 131, 135, 137 respectivamente).
--   Están duplicados por el bug de dedup de 5 min — limpiarlos con
--   2026-06-11_cleanup_user739.sql ANTES de ejecutar este script.
-- =============================================================================

-- Verificar que no existe ya el registro
SELECT id, type, payment_id, created_at
FROM payment_history
WHERE id_user = 739
   OR payment_id = 160790644706;

-- ============================================================
-- PAGO APROBADO: payment 160790644706
-- Cobro exitoso may 24 2026 - account_money ARS 6811
-- preapproval: 44be670ed7af43b596b1468f638c0829
-- authorized_payment.id: 7028377801
-- payer MP user ID: 165
-- ============================================================
INSERT INTO payment_history (id_user, type, payment_id, preapproval_id, data, error_message, created_at, updated_at)
VALUES (
    739,
    'approved',
    160790644706,
    '44be670ed7af43b596b1468f638c0829',
    CAST('{
    "id": 160790644706,
    "card": null,
    "tags": null,
    "order": [],
    "payer": {
        "id": "165",
        "type": "registered",
        "email": null,
        "phone": {"number": null, "area_code": null, "extension": null},
        "last_name": null,
        "first_name": null,
        "entity_type": null,
        "operator_id": null,
        "identification": {"type": null, "number": null}
    },
    "pos_id": null,
    "status": "approved",
    "refunds": [],
    "brand_id": null,
    "captured": true,
    "metadata": {
        "preapproval_id": "44be670ed7af43b596b1468f638c0829"
    },
    "store_id": null,
    "issuer_id": null,
    "live_mode": true,
    "sponsor_id": null,
    "binary_mode": false,
    "currency_id": "ARS",
    "description": "Cobro mensual SmartAgro - Plan Siembra",
    "fee_details": [
        {
            "type": "mercadopago_fee",
            "amount": 279.25,
            "fee_payer": "collector"
        }
    ],
    "platform_id": null,
    "collector_id": 1532822464,
    "date_created": "2026-05-24T07:22:13.371-04:00",
    "installments": 1,
    "release_info": null,
    "taxes_amount": 0,
    "accounts_info": null,
    "coupon_amount": 0,
    "date_approved": "2026-05-24T07:28:22.462-04:00",
    "integrator_id": null,
    "status_detail": "accredited",
    "corporation_id": null,
    "operation_type": "recurring_payment",
    "payment_method": {
        "id": "account_money",
        "type": "account_money",
        "issuer_id": null
    },
    "additional_info": {
        "tracking_id": "platform:v1-blacklabel,so:ALL,type:N/A,security:none"
    },
    "charges_details": [
        {
            "id": "160790644706-001",
            "name": "mercadopago_fee",
            "rate": 4.1,
            "type": "fee",
            "amounts": {"original": 279.25, "refunded": 0},
            "accounts": {"to": "mp", "from": "collector"},
            "metadata": {"reason": "", "source": "rule-engine"},
            "client_id": 0,
            "reserve_id": null,
            "base_amount": 6811.0,
            "date_created": "2026-05-24T07:22:13.000-04:00",
            "last_updated": "2026-05-24T07:22:13.000-04:00",
            "refund_charges": [],
            "update_charges": []
        },
        {
            "id": "160790644706-002",
            "name": "tax_withholding_collector-debitos_creditos",
            "rate": 0.6,
            "type": "tax",
            "amounts": {"original": 40.87, "refunded": 0},
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
            "base_amount": 6811.0,
            "date_created": "2026-05-24T07:22:13.000-04:00",
            "last_updated": "2026-05-24T07:22:13.000-04:00",
            "refund_charges": [],
            "update_charges": []
        }
    ],
    "financing_group": null,
    "merchant_number": null,
    "payment_type_id": "account_money",
    "processing_mode": "aggregator",
    "shipping_amount": 0,
    "counter_currency": null,
    "deduction_schema": null,
    "notification_url": null,
    "date_last_updated": "2026-05-24T07:28:22.462-04:00",
    "marketplace_owner": null,
    "payment_method_id": "account_money",
    "authorization_code": null,
    "date_of_expiration": null,
    "external_reference": "739",
    "money_release_date": null,
    "transaction_amount": 6811.0,
    "merchant_account_id": null,
    "transaction_details": {
        "overpaid_amount": 0,
        "total_paid_amount": 6811.0,
        "acquirer_reference": null,
        "installment_amount": 6811.0,
        "net_received_amount": 6490.88,
        "external_resource_url": null,
        "financial_institution": null,
        "payable_deferral_period": null,
        "payment_method_reference_id": null
    },
    "money_release_schema": null,
    "money_release_status": "released",
    "point_of_interaction": {
        "type": "SUBSCRIPTIONS",
        "location": {"source": "payer", "state_id": null},
        "business_info": {
            "unit": "online_payments",
            "branch": "Merchant Services",
            "sub_unit": "recurring"
        },
        "application_data": {"name": null, "version": null, "operating_system": null},
        "transaction_data": {
            "plan_id": null,
            "processor": null,
            "billing_date": "2026-05-24",
            "user_present": false,
            "first_time_use": false,
            "invoice_period": {"type": "monthly", "period": 1},
            "subscription_id": "44be670ed7af43b596b1468f638c0829",
            "payment_reference": null,
            "subscription_sequence": {"total": null, "number": null}
        }
    },
    "statement_descriptor": "MERPAGO*SMARTKETING",
    "call_for_authorize_id": null,
    "authorized_payment_id": 7028377801,
    "acquirer_reconciliation": [],
    "differential_pricing_id": null,
    "transaction_amount_refunded": 0,
    "_authorized_payment_id": 7028377801,
    "_reconstructed": true,
    "_note": "Insertado manualmente 2026-06-11. Pago account_money — sin datos de tarjeta por diseño. Payer MP ID: 165. Reconstruido desde authorized_payment 7028377801."
}' AS JSON),
    NULL,
    '2026-05-24 07:22:13',
    '2026-05-24 07:28:22'
);

-- ============================================================
-- UPDATE para el registro ya insertado (si ya ejecutaste el INSERT anterior)
-- Reemplaza el data esquelético con la versión completa
-- ============================================================
UPDATE payment_history
SET data = CAST('{
    "id": 160790644706,
    "card": null,
    "tags": null,
    "order": [],
    "payer": {
        "id": "165",
        "type": "registered",
        "email": null,
        "phone": {"number": null, "area_code": null, "extension": null},
        "last_name": null,
        "first_name": null,
        "entity_type": null,
        "operator_id": null,
        "identification": {"type": null, "number": null}
    },
    "pos_id": null,
    "status": "approved",
    "refunds": [],
    "brand_id": null,
    "captured": true,
    "metadata": {
        "preapproval_id": "44be670ed7af43b596b1468f638c0829"
    },
    "store_id": null,
    "issuer_id": null,
    "live_mode": true,
    "sponsor_id": null,
    "binary_mode": false,
    "currency_id": "ARS",
    "description": "Cobro mensual SmartAgro - Plan Siembra",
    "fee_details": [
        {
            "type": "mercadopago_fee",
            "amount": 279.25,
            "fee_payer": "collector"
        }
    ],
    "platform_id": null,
    "collector_id": 1532822464,
    "date_created": "2026-05-24T07:22:13.371-04:00",
    "installments": 1,
    "release_info": null,
    "taxes_amount": 0,
    "accounts_info": null,
    "coupon_amount": 0,
    "date_approved": "2026-05-24T07:28:22.462-04:00",
    "integrator_id": null,
    "status_detail": "accredited",
    "corporation_id": null,
    "operation_type": "recurring_payment",
    "payment_method": {
        "id": "account_money",
        "type": "account_money",
        "issuer_id": null
    },
    "additional_info": {
        "tracking_id": "platform:v1-blacklabel,so:ALL,type:N/A,security:none"
    },
    "charges_details": [
        {
            "id": "160790644706-001",
            "name": "mercadopago_fee",
            "rate": 4.1,
            "type": "fee",
            "amounts": {"original": 279.25, "refunded": 0},
            "accounts": {"to": "mp", "from": "collector"},
            "metadata": {"reason": "", "source": "rule-engine"},
            "client_id": 0,
            "reserve_id": null,
            "base_amount": 6811.0,
            "date_created": "2026-05-24T07:22:13.000-04:00",
            "last_updated": "2026-05-24T07:22:13.000-04:00",
            "refund_charges": [],
            "update_charges": []
        },
        {
            "id": "160790644706-002",
            "name": "tax_withholding_collector-debitos_creditos",
            "rate": 0.6,
            "type": "tax",
            "amounts": {"original": 40.87, "refunded": 0},
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
            "base_amount": 6811.0,
            "date_created": "2026-05-24T07:22:13.000-04:00",
            "last_updated": "2026-05-24T07:22:13.000-04:00",
            "refund_charges": [],
            "update_charges": []
        }
    ],
    "financing_group": null,
    "merchant_number": null,
    "payment_type_id": "account_money",
    "processing_mode": "aggregator",
    "shipping_amount": 0,
    "counter_currency": null,
    "deduction_schema": null,
    "notification_url": null,
    "date_last_updated": "2026-05-24T07:28:22.462-04:00",
    "marketplace_owner": null,
    "payment_method_id": "account_money",
    "authorization_code": null,
    "date_of_expiration": null,
    "external_reference": "739",
    "money_release_date": null,
    "transaction_amount": 6811.0,
    "merchant_account_id": null,
    "transaction_details": {
        "overpaid_amount": 0,
        "total_paid_amount": 6811.0,
        "acquirer_reference": null,
        "installment_amount": 6811.0,
        "net_received_amount": 6490.88,
        "external_resource_url": null,
        "financial_institution": null,
        "payable_deferral_period": null,
        "payment_method_reference_id": null
    },
    "money_release_schema": null,
    "money_release_status": "released",
    "point_of_interaction": {
        "type": "SUBSCRIPTIONS",
        "location": {"source": "payer", "state_id": null},
        "business_info": {
            "unit": "online_payments",
            "branch": "Merchant Services",
            "sub_unit": "recurring"
        },
        "application_data": {"name": null, "version": null, "operating_system": null},
        "transaction_data": {
            "plan_id": null,
            "processor": null,
            "billing_date": "2026-05-24",
            "user_present": false,
            "first_time_use": false,
            "invoice_period": {"type": "monthly", "period": 1},
            "subscription_id": "44be670ed7af43b596b1468f638c0829",
            "payment_reference": null,
            "subscription_sequence": {"total": null, "number": null}
        }
    },
    "statement_descriptor": "MERPAGO*SMARTKETING",
    "call_for_authorize_id": null,
    "authorized_payment_id": 7028377801,
    "acquirer_reconciliation": [],
    "differential_pricing_id": null,
    "transaction_amount_refunded": 0,
    "_authorized_payment_id": 7028377801,
    "_reconstructed": true,
    "_note": "Insertado manualmente 2026-06-11. Pago account_money — sin datos de tarjeta por diseño. Payer MP ID: 165. Reconstruido desde authorized_payment 7028377801."
}' AS JSON)
WHERE id_user = 739
  AND payment_id = 160790644706;

-- Verificar resultado final
SELECT id, type, payment_id,
       JSON_UNQUOTE(JSON_EXTRACT(data, '$.payer.id'))       AS payer_mp_id,
       JSON_UNQUOTE(JSON_EXTRACT(data, '$.status'))         AS status,
       JSON_UNQUOTE(JSON_EXTRACT(data, '$.transaction_amount')) AS monto,
       created_at
FROM payment_history
WHERE id_user = 739
ORDER BY created_at;
