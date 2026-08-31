-- =====================================================
-- Migration 014: Safe Transaction Reversals
-- Adds reversal tracking columns to safe_transactions
-- =====================================================

-- Add reversal tracking columns
ALTER TABLE safe_transactions ADD COLUMN related_transaction_id INTEGER DEFAULT NULL;
ALTER TABLE safe_transactions ADD COLUMN transaction_type TEXT NOT NULL DEFAULT 'normal';
ALTER TABLE safe_transactions ADD COLUMN reversal_reason TEXT DEFAULT NULL;
ALTER TABLE safe_transactions ADD COLUMN reversed_by TEXT DEFAULT NULL;
ALTER TABLE safe_transactions ADD COLUMN reversed_at TEXT DEFAULT NULL;
ALTER TABLE safe_transactions ADD COLUMN status TEXT NOT NULL DEFAULT 'active';

-- Add index for related transaction lookup
CREATE INDEX IF NOT EXISTS idx_safe_related_txn ON safe_transactions(related_transaction_id);
