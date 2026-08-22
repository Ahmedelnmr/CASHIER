-- Migration 004: Create Employee Accruals Table

CREATE TABLE IF NOT EXISTS employee_accruals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    month TEXT NOT NULL, -- Format: YYYY-MM
    amount REAL NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    UNIQUE(employee_id, month)
);

-- Index for faster lookup
CREATE INDEX IF NOT EXISTS idx_accruals_employee_month ON employee_accruals(employee_id, month);
