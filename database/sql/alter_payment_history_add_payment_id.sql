ALTER TABLE payment_history
ADD COLUMN payment_id BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'ID único del pago en MercadoPago (usado para deduplicar webhooks repetidos)',
ADD UNIQUE INDEX uq_payment_history_payment_id (payment_id);
