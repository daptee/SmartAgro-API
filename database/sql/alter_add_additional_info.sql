-- Indicadores comerciales
ALTER TABLE gross_margin
    ADD COLUMN additional_info JSON NULL AFTER data;

ALTER TABLE gross_margins_trend
    ADD COLUMN additional_info JSON NULL AFTER data;

ALTER TABLE pit_indicators
    ADD COLUMN additional_info JSON NULL AFTER data;

ALTER TABLE livestock_input_output_ratio
    ADD COLUMN additional_info JSON NULL AFTER data;

ALTER TABLE agricultural_input_output_relationship
    ADD COLUMN additional_info JSON NULL AFTER data;

ALTER TABLE products_prices
    ADD COLUMN additional_info JSON NULL AFTER data;

ALTER TABLE harvest_prices
    ADD COLUMN additional_info JSON NULL AFTER data;

ALTER TABLE main_crops_buying_selling_traffic_light
    ADD COLUMN additional_info JSON NULL AFTER data;

-- Mercado general
ALTER TABLE major_crops
    ADD COLUMN additional_info JSON NULL;

ALTER TABLE insights
    ADD COLUMN additional_info JSON NULL;

ALTER TABLE news
    ADD COLUMN additional_info JSON NULL;

ALTER TABLE rainfall_record_provinces
    ADD COLUMN additional_info JSON NULL;

ALTER TABLE main_grain_prices
    ADD COLUMN additional_info JSON NULL;

ALTER TABLE prices_main_active_ingredients_producers
    ADD COLUMN additional_info JSON NULL;

ALTER TABLE producer_segment_prices
    ADD COLUMN additional_info JSON NULL;

ALTER TABLE mag_lease_index
    ADD COLUMN additional_info JSON NULL;

ALTER TABLE mag_steer_index
    ADD COLUMN additional_info JSON NULL;

-- Controles generales de mercado e indicadores comerciales
ALTER TABLE market_general_controls
    ADD COLUMN additional_info JSON NULL AFTER data;

ALTER TABLE business_indicator_controls
    ADD COLUMN additional_info JSON NULL AFTER data;
