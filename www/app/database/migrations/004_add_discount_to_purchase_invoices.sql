-- Add discount column to purchase_invoices
ALTER TABLE purchase_invoices ADD COLUMN discount REAL NOT NULL DEFAULT 0;
