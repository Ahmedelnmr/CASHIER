-- =====================================================
-- Migration 012: POS Enhancements
-- Adds payment tracking columns and performance indexes
-- =====================================================

-- Payment tracking on sales
ALTER TABLE sales ADD COLUMN amount_paid REAL DEFAULT NULL;
ALTER TABLE sales ADD COLUMN change_amount REAL DEFAULT NULL;

-- Performance indexes for POS product listing
CREATE INDEX IF NOT EXISTS idx_products_active_category ON products(is_active, category);
CREATE INDEX IF NOT EXISTS idx_products_active_name ON products(is_active, name);
CREATE INDEX IF NOT EXISTS idx_products_active_stock ON products(is_active, stock_quantity);
