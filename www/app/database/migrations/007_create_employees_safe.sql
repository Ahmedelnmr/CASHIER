-- =====================================================
-- EMPLOYEES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS employees (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT DEFAULT NULL,
    base_salary REAL NOT NULL DEFAULT 0,
    settings TEXT DEFAULT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX IF NOT EXISTS idx_employees_name ON employees(name);

-- =====================================================
-- SAFE TRANSACTIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS safe_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL,
    amount REAL NOT NULL,
    date DATE NOT NULL DEFAULT (date('now', 'localtime')),
    description TEXT DEFAULT NULL,
    employee_id INTEGER DEFAULT NULL,
    category_id INTEGER DEFAULT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_safe_transactions_type ON safe_transactions(type);
CREATE INDEX IF NOT EXISTS idx_safe_transactions_date ON safe_transactions(date);
CREATE INDEX IF NOT EXISTS idx_safe_transactions_employee ON safe_transactions(employee_id);
