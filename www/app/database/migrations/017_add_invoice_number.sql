-- Migration 017: Add invoice_number column with INV-MMDDYY-SSS format
-- Adds a professional business invoice number separate from the internal ID

-- 1. Add the new column (nullable initially for backfill)
ALTER TABLE sales ADD COLUMN invoice_number TEXT DEFAULT NULL;

-- 2. Backfill existing invoices with the new format
-- Uses the existing sale_number and datetime to generate INV-MMDDYY-SSS
UPDATE sales
SET invoice_number = 'INV-'
    || substr('0' || CAST(strftime('%m', datetime) AS TEXT), -2)
    || substr('0' || CAST(strftime('%d', datetime) AS TEXT), -2)
    || substr(CAST(strftime('%Y', datetime) AS TEXT), 3, 2)
    || '-'
    || substr('000' || CAST(sale_number AS TEXT), -3)
WHERE invoice_number IS NULL
  AND sale_number IS NOT NULL;

-- 3. For any remaining rows with NULL sale_number, generate from row id
UPDATE sales
SET invoice_number = 'INV-'
    || substr('0' || CAST(strftime('%m', datetime) AS TEXT), -2)
    || substr('0' || CAST(strftime('%d', datetime) AS TEXT), -2)
    || substr(CAST(strftime('%Y', datetime) AS TEXT), 3, 2)
    || '-'
    || substr('000' || CAST(id AS TEXT), -3)
WHERE invoice_number IS NULL;

-- 4. Create UNIQUE index on invoice_number
CREATE UNIQUE INDEX IF NOT EXISTS idx_sales_invoice_number ON sales(invoice_number);
