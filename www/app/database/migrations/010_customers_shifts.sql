-- =====================================================
-- CUSTOMERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT DEFAULT NULL,
    address TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    credit_limit REAL NOT NULL DEFAULT 0,
    balance REAL NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX IF NOT EXISTS idx_customers_name ON customers(name);
CREATE INDEX IF NOT EXISTS idx_customers_phone ON customers(phone);

-- =====================================================
-- SHIFTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS shifts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    opened_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    closed_at TEXT DEFAULT NULL,
    opening_cash REAL NOT NULL DEFAULT 0,
    closing_cash REAL DEFAULT NULL,
    expected_cash REAL DEFAULT NULL,
    cash_difference REAL DEFAULT NULL,
    total_sales REAL DEFAULT 0,
    total_returns REAL DEFAULT 0,
    total_expenses REAL DEFAULT 0,
    total_cash_in REAL DEFAULT 0,
    total_cash_out REAL DEFAULT 0,
    sale_count INTEGER DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'open' CHECK(status IN ('open', 'closed')),
    notes TEXT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_shifts_user ON shifts(user_id);
CREATE INDEX IF NOT EXISTS idx_shifts_status ON shifts(status);
CREATE INDEX IF NOT EXISTS idx_shifts_opened ON shifts(opened_at);

-- =====================================================
-- CUSTOMER TRANSACTIONS (Credit Ledger)
-- =====================================================
CREATE TABLE IF NOT EXISTS customer_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('sale', 'payment', 'credit', 'adjustment')),
    amount REAL NOT NULL,
    balance_after REAL NOT NULL,
    reference_type TEXT DEFAULT NULL,
    reference_id INTEGER DEFAULT NULL,
    description TEXT DEFAULT NULL,
    date TEXT NOT NULL DEFAULT (date('now', 'localtime')),
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_cust_txn_customer ON customer_transactions(customer_id);
CREATE INDEX IF NOT EXISTS idx_cust_txn_type ON customer_transactions(type);
CREATE INDEX IF NOT EXISTS idx_cust_txn_date ON customer_transactions(date);
