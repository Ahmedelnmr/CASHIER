-- Add Sales Returns & Voids Support
ALTER TABLE sales ADD COLUMN type TEXT DEFAULT 'sale'; -- 'sale', 'return'
ALTER TABLE sales ADD COLUMN status TEXT DEFAULT 'completed'; -- 'completed', 'voided'
ALTER TABLE sales ADD COLUMN parent_sale_id INTEGER DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_sales_type ON sales(type);
CREATE INDEX IF NOT EXISTS idx_sales_status ON sales(status);
CREATE INDEX IF NOT EXISTS idx_sales_parent ON sales(parent_sale_id);

-- Add Purchase Returns & Voids Support
ALTER TABLE purchase_invoices ADD COLUMN type TEXT DEFAULT 'purchase'; -- 'purchase', 'return'
ALTER TABLE purchase_invoices ADD COLUMN status TEXT DEFAULT 'completed'; -- 'completed', 'voided'
ALTER TABLE purchase_invoices ADD COLUMN parent_invoice_id INTEGER DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_purchases_type ON purchase_invoices(type);
CREATE INDEX IF NOT EXISTS idx_purchases_status ON purchase_invoices(status);
CREATE INDEX IF NOT EXISTS idx_purchases_parent ON purchase_invoices(parent_invoice_id);
