-- =====================================================
-- Migration 015: Create Categories Table
-- Creates proper category entity and migrates data
-- =====================================================

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

-- Migrate existing categories from products table
INSERT OR IGNORE INTO categories (name)
SELECT DISTINCT category FROM products 
WHERE category IS NOT NULL AND category != '';

-- Add category_id column to products
ALTER TABLE products ADD COLUMN category_id INTEGER DEFAULT NULL;

-- Update products to reference categories by ID
UPDATE products SET category_id = (
    SELECT id FROM categories WHERE name = products.category
)
WHERE category IS NOT NULL AND category != '';

-- Create index for category_id
CREATE INDEX IF NOT EXISTS idx_products_category_id ON products(category_id);
