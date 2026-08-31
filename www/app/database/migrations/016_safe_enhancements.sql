-- =====================================================
-- Migration 016: Safe Enhancements
-- Adds reconciliation table for cash counting
-- =====================================================

CREATE TABLE IF NOT EXISTS safe_reconciliations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    expected_balance REAL NOT NULL,
    actual_count REAL NOT NULL,
    difference REAL NOT NULL,
    result TEXT NOT NULL CHECK(result IN ('balanced', 'shortage', 'over')),
    notes TEXT DEFAULT NULL,
    reconciled_by TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX IF NOT EXISTS idx_safe_reconciliations_date ON safe_reconciliations(created_at);
