-- Migration: Add technical spec fields to products
-- Adds ficha técnica / specification fields for product detail page

ALTER TABLE products
    ADD COLUMN origin          VARCHAR(255) NULL AFTER description,
    ADD COLUMN variety         VARCHAR(255) NULL AFTER origin,
    ADD COLUMN fermentation    VARCHAR(255) NULL AFTER variety,
    ADD COLUMN humidity        VARCHAR(120)  NULL AFTER fermentation,
    ADD COLUMN altitude        VARCHAR(120)  NULL AFTER humidity,
    ADD COLUMN grain_count     VARCHAR(120)  NULL AFTER altitude,
    ADD COLUMN grain_index     VARCHAR(120)  NULL AFTER grain_count,
    ADD COLUMN certification   VARCHAR(255) NULL AFTER grain_index,
    ADD COLUMN presentation    VARCHAR(255) NULL AFTER certification;
