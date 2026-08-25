-- =====================================================
-- USERS TABLE (Authentication & Roles)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    name TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'cashier' CHECK(role IN ('admin', 'manager', 'cashier')),
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

-- Default admin user (password: admin123)
INSERT OR IGNORE INTO users (username, password_hash, name, role) VALUES
('admin', '$2y$10$oXU/cfyY1Fwrg4AW6gFuDuWE.6PWCh37hBD6Cdt8Q2izau1E4zEia', 'مدير النظام', 'admin');
