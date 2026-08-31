-- =====================================================
-- Migration 013: Fix credit payment method CHECK constraint
-- The sales.payment_method CHECK only allowed 'cash','card','other'
-- but the POS code uses 'credit' for credit sales.
-- This migration recreates the table with 'credit' added.
-- =====================================================

PRAGMA foreign_keys = OFF;

-- 1. Rename existing table
ALTER TABLE sales RENAME TO sales_backup_013;

-- 2. Create new table with corrected CHECK constraint
CREATE TABLE IF NOT EXISTS sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sale_number INTEGER DEFAULT NULL,
    datetime TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    subtotal REAL NOT NULL DEFAULT 0,
    discount REAL NOT NULL DEFAULT 0,
    total REAL NOT NULL DEFAULT 0,
    payment_method TEXT NOT NULL DEFAULT 'cash' CHECK(payment_method IN ('cash', 'card', 'other', 'credit')),
    notes TEXT DEFAULT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    type TEXT DEFAULT 'sale',
    status TEXT DEFAULT 'completed',
    parent_sale_id INTEGER DEFAULT NULL,
    customer_id INTEGER DEFAULT NULL,
    shift_id INTEGER DEFAULT NULL,
    amount_paid REAL DEFAULT NULL,
    change_amount REAL DEFAULT NULL,
    FOREIGN KEY (parent_sale_id) REFERENCES sales(id) ON DELETE SET NULL
);

-- 3. Copy all data from backup
INSERT INTO sales (id, sale_number, datetime, subtotal, discount, total, payment_method, notes, created_at, type, status, parent_sale_id, customer_id, shift_id, amount_paid, change_amount)
SELECT id, sale_number, datetime, subtotal, discount, total, payment_method, notes, created_at, type, status, parent_sale_id, customer_id, shift_id, amount_paid, change_amount
FROM sales_backup_013;

-- 4. Drop backup table
DROP TABLE sales_backup_013;

-- 5. Recreate indexes
CREATE INDEX IF NOT EXISTS idx_sales_datetime ON sales(datetime);
CREATE INDEX IF NOT EXISTS idx_sales_customer ON sales(customer_id);
CREATE INDEX IF NOT EXISTS idx_sales_shift ON sales(shift_id);

PRAGMA foreign_keys = ON;
