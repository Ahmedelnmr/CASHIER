-- =====================================================
-- Migration: Add Multi-Unit Product Support
-- Version: 002
-- Date: 2026-02-11
-- =====================================================

-- Add pack_type column to products
ALTER TABLE products ADD COLUMN pack_type TEXT DEFAULT 'كرتونة';

-- Rename pack_quantity to pack_unit_quantity (SQLite doesn't support RENAME COLUMN directly)
-- We'll handle this by creating new column and copying data
ALTER TABLE products ADD COLUMN pack_unit_quantity INTEGER DEFAULT NULL;
UPDATE products SET pack_unit_quantity = pack_quantity WHERE pack_quantity IS NOT NULL;

-- Add pack_purchase_price column
ALTER TABLE products ADD COLUMN pack_purchase_price REAL DEFAULT NULL;

-- Rename sale_price_pack to pack_sale_price
ALTER TABLE products ADD COLUMN pack_sale_price REAL DEFAULT NULL;
UPDATE products SET pack_sale_price = sale_price_pack WHERE sale_price_pack IS NOT NULL;

-- Add unit_type to purchase_items
ALTER TABLE purchase_items ADD COLUMN unit_type TEXT DEFAULT 'قطعة';

-- Add sale_price to purchase_items (for reference)
ALTER TABLE purchase_items ADD COLUMN sale_price REAL DEFAULT NULL;

-- Add unit_type to sale_items
ALTER TABLE sale_items ADD COLUMN unit_type TEXT DEFAULT 'قطعة';

-- Note: Old columns (pack_quantity, sale_price_pack) can be dropped after verification
-- For safety, we keep them for now. To drop later, uncomment:
-- CREATE TABLE products_new AS SELECT 
--   id, name, barcode, plu_code, type, purchase_price, sale_price_unit,
--   pack_type, pack_unit_quantity, pack_purchase_price, pack_sale_price,
--   stock_quantity, min_stock, category, notes, is_active, created_at, updated_at
-- FROM products;
-- DROP TABLE products;
-- ALTER TABLE products_new RENAME TO products;
