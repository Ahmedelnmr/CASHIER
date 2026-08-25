-- =====================================================
-- SETTINGS TABLE (System Configuration)
-- =====================================================
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX IF NOT EXISTS idx_settings_key ON settings(setting_key);

-- Default settings
INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES
('store_name', 'تاجر'),
('store_phone', '01096107040'),
('store_address', 'القاهرة - المرج - ش الشهيد محمد عبدالفتاح الحجار المتفرع من ش مؤسسة الزكاة'),
('receipt_header', ''),
('receipt_footer', 'شكراً لتسوقكم معنا - نتمنى لكم يوماً سعيداً'),
('currency_symbol', 'ج.م'),
('currency_name', 'جنيه مصري');
