-- Add customer_id and shift_id to sales table
ALTER TABLE sales ADD COLUMN customer_id INTEGER DEFAULT NULL;
ALTER TABLE sales ADD COLUMN shift_id INTEGER DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_sales_customer ON sales(customer_id);
CREATE INDEX IF NOT EXISTS idx_sales_shift ON sales(shift_id);
