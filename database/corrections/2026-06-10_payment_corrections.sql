-- =============================================================================
-- CORRECCIONES MANUALES DE PAGOS - 2026-06-10
-- Usuario 1058: pago rechazado (fondos insuficientes) no guardado por TypeError
-- en buildPaymentDataFromAuthorizedPayment al pasar null como int.
-- authorized_payment.id: 7028910317
-- preapproval_id: 854a0c0c14174aa987f9486b2e3c27a5
-- =============================================================================

-- -------------------------------------------------------------------------
-- USUARIO 1058 - Pago rechazado junio 2026
-- -------------------------------------------------------------------------

-- Verificar que no existe ya el registro (doble check antes de insertar)
SELECT id, type, payment_id, created_at, error_message
FROM payment_history
WHERE id_user = 1058
   OR payment_id = 7028910317;

-- Insertar el pago rechazado
INSERT INTO payment_history (id_user, type, payment_id, preapproval_id, data, error_message, created_at, updated_at)
VALUES (
    1058,
    'rejected',
    7028910317,
    '854a0c0c14174aa987f9486b2e3c27a5',
    '{"id":7028910317,"card":[],"tags":null,"order":[],"payer":[],"pos_id":null,"status":"rejected","refunds":[],"brand_id":null,"captured":true,"metadata":{"preapproval_id":"854a0c0c14174aa987f9486b2e3c27a5"},"store_id":null,"live_mode":true,"sponsor_id":null,"binary_mode":false,"currency_id":"ARS","description":"Cobro mensual SmartAgro - Plan Siembra","fee_details":[{"type":"mercadopago_fee","amount":293.31,"fee_payer":"collector"}],"platform_id":null,"collector_id":1532822464,"date_created":"2026-06-10T13:27:52.533-04:00","installments":1,"release_info":null,"taxes_amount":0,"accounts_info":null,"coupon_amount":0,"date_approved":"2026-06-10T13:46:34.794-04:00","integrator_id":null,"status_detail":"insufficient_amount","corporation_id":null,"operation_type":"recurring_payment","payment_method":{"id":"account_money","type":"account_money"},"additional_info":{"tracking_id":"platform:v1-blacklabel,so:ALL,type:N/A,security:none"},"charges_details":[{"id":"7028910317-001","name":"mercadopago_fee","rate":4.1,"type":"fee","amounts":{"original":293.31,"refunded":0},"base_amount":7154,"refund_charges":[],"update_charges":[]},{"id":"7028910317-002","name":"tax_withholding_collector-debitos_creditos","rate":0.6,"type":"tax","amounts":{"original":42.92,"refunded":0},"base_amount":7154,"refund_charges":[],"update_charges":[]}],"financing_group":null,"merchant_number":null,"payment_type_id":"account_money","processing_mode":"aggregator","shipping_amount":0,"counter_currency":null,"deduction_schema":null,"notification_url":null,"date_last_updated":"2026-06-10T13:46:34.794-04:00","marketplace_owner":null,"payment_method_id":"account_money","authorization_code":null,"date_of_expiration":null,"external_reference":"1058","money_release_date":null,"transaction_amount":7154,"merchant_account_id":null,"transaction_details":{"overpaid_amount":0,"total_paid_amount":7154,"acquirer_reference":null,"installment_amount":7154,"net_received_amount":6817.77},"money_release_schema":null,"money_release_status":"released","point_of_interaction":{"type":"SUBSCRIPTIONS","transaction_data":{"billing_date":"2026-06-10","user_present":false,"first_time_use":false,"invoice_period":{"type":"monthly","period":1},"subscription_id":"854a0c0c14174aa987f9486b2e3c27a5","subscription_sequence":{"total":null,"number":null}}},"statement_descriptor":"MERPAGO*SMARTKETING","call_for_authorize_id":null,"acquirer_reconciliation":[],"differential_pricing_id":null,"transaction_amount_refunded":0,"_authorized_payment_id":7028910317,"_reconstructed":true,"_note":"Guardado via fallback desde authorized_payment (sin acceso a /v1/payments/{id}). card/payer no disponibles."}',
    'insufficient_amount',
    '2026-06-10 13:46:35',
    '2026-06-10 13:46:35'
);

-- Marcar usuario 1058 como deudor
UPDATE users
SET is_debtor = 1
WHERE id = 1058;

-- Verificar resultado
SELECT id, type, payment_id, created_at, error_message FROM payment_history WHERE id_user = 1058 ORDER BY created_at DESC;
SELECT id, is_debtor, id_plan FROM users WHERE id = 1058;
