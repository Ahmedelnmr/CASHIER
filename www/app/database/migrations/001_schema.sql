-- =====================================================
-- POS & Inventory Management System - Database Schema
-- Technology: SQLite
-- Version: 1.0
-- =====================================================

-- Enable Foreign Keys
PRAGMA foreign_keys = ON;

-- =====================================================
-- PRODUCTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    barcode TEXT DEFAULT NULL,
    plu_code TEXT DEFAULT NULL,
    type TEXT NOT NULL DEFAULT 'unit' CHECK(type IN ('unit', 'pack', 'weight')),
    purchase_price REAL NOT NULL DEFAULT 0,
    sale_price_unit REAL NOT NULL DEFAULT 0,
    pack_type TEXT DEFAULT 'كرتونة',
    pack_unit_quantity REAL DEFAULT NULL,
    pack_purchase_price REAL DEFAULT NULL,
    pack_sale_price REAL DEFAULT NULL,
    stock_quantity REAL NOT NULL DEFAULT 0,
    min_stock REAL NOT NULL DEFAULT 0,
    category TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX IF NOT EXISTS idx_products_barcode ON products(barcode);
CREATE INDEX IF NOT EXISTS idx_products_plu_code ON products(plu_code);
CREATE INDEX IF NOT EXISTS idx_products_name ON products(name);
CREATE INDEX IF NOT EXISTS idx_products_category ON products(category);
CREATE INDEX IF NOT EXISTS idx_products_type ON products(type);

-- =====================================================
-- SUPPLIERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT DEFAULT NULL,
    address TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX IF NOT EXISTS idx_suppliers_name ON suppliers(name);

-- =====================================================
-- PURCHASE INVOICES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS purchase_invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_id INTEGER NOT NULL,
    invoice_number TEXT DEFAULT NULL,
    date TEXT NOT NULL DEFAULT (date('now', 'localtime')),
    total REAL NOT NULL DEFAULT 0,
    notes TEXT DEFAULT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_purchase_invoices_supplier ON purchase_invoices(supplier_id);
CREATE INDEX IF NOT EXISTS idx_purchase_invoices_date ON purchase_invoices(date);

-- =====================================================
-- PURCHASE ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS purchase_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    quantity REAL NOT NULL,
    unit_type TEXT DEFAULT 'قطعة',
    purchase_price REAL NOT NULL,
    sale_price REAL DEFAULT NULL,
    subtotal REAL NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES purchase_invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_purchase_items_invoice ON purchase_items(invoice_id);
CREATE INDEX IF NOT EXISTS idx_purchase_items_product ON purchase_items(product_id);

-- =====================================================
-- SALES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sale_number INTEGER DEFAULT NULL,
    datetime TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    subtotal REAL NOT NULL DEFAULT 0,
    discount REAL NOT NULL DEFAULT 0,
    total REAL NOT NULL DEFAULT 0,
    payment_method TEXT NOT NULL DEFAULT 'cash' CHECK(payment_method IN ('cash', 'card', 'other')),
    notes TEXT DEFAULT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX IF NOT EXISTS idx_sales_datetime ON sales(datetime);

-- =====================================================
-- SALE ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS sale_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sale_id INTEGER NOT NULL,
    product_id INTEGER DEFAULT NULL,
    product_name TEXT NOT NULL,
    quantity REAL NOT NULL,
    unit_type TEXT DEFAULT 'قطعة',
    price REAL NOT NULL,
    sale_mode TEXT NOT NULL DEFAULT 'unit' CHECK(sale_mode IN ('unit', 'pack', 'weight', 'custom')),
    subtotal REAL NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_sale_items_sale ON sale_items(sale_id);
CREATE INDEX IF NOT EXISTS idx_sale_items_product ON sale_items(product_id);

-- =====================================================
-- STOCK MOVEMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS stock_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('purchase', 'sale', 'adjustment')),
    quantity REAL NOT NULL,
    reference_type TEXT DEFAULT NULL,
    reference_id INTEGER DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_stock_movements_product ON stock_movements(product_id);
CREATE INDEX IF NOT EXISTS idx_stock_movements_type ON stock_movements(type);
CREATE INDEX IF NOT EXISTS idx_stock_movements_created ON stock_movements(created_at);

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================



-- Default supplier
INSERT OR IGNORE INTO suppliers (id, name, phone) VALUES
(1, 'مورد افتراضي', '0000000000');
