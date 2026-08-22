-- Add multiple barcodes support
ALTER TABLE products ADD COLUMN barcode2 TEXT DEFAULT NULL;
ALTER TABLE products ADD COLUMN barcode3 TEXT DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_products_barcode2 ON products(barcode2);
CREATE INDEX IF NOT EXISTS idx_products_barcode3 ON products(barcode3);
