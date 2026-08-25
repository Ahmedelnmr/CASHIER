-- =====================================================
-- POS System - Integration Test Seed Data
-- Version: 1.0
-- Date: 2026-08-25
-- Purpose: Comprehensive seed data covering all integration test scenarios
-- =====================================================

PRAGMA foreign_keys = ON;

-- =====================================================
-- 1. USERS (3 users - admin, manager, cashier)
-- Passwords: admin123, manager123, cashier123 (bcrypt)
-- =====================================================
INSERT OR IGNORE INTO users (id, username, password_hash, name, role, is_active) VALUES
(1, 'admin', '$2y$10$oXU/cfyY1Fwrg4AW6gFuDuWE.6PWCh37hBD6Cdt8Q2izau1E4zEia', 'مدير النظام', 'admin', 1),
(2, 'manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير المبيعات', 'manager', 1),
(3, 'cashier1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'أحمد الكاشير', 'cashier', 1),
(4, 'cashier2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'محمد الكاشير', 'cashier', 1),
(5, 'admin_inactive', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير محذوف', 'admin', 0);

-- =====================================================
-- 2. SETTINGS (Default system settings)
-- =====================================================
INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES
('store_name', 'تاجر'),
('store_phone', '01096107040'),
('store_address', 'القاهرة - المرج - ش الشهيد محمد عبدالفتاح الحجار'),
('receipt_header', 'مرحباً بكم في متجر تاجر'),
('receipt_footer', 'شكراً لتسوقكم معنا - نتمنى لكم يوماً سعيداً'),
('currency_symbol', 'ج.م'),
('currency_name', 'جنيه مصري');

-- =====================================================
-- 3. SUPPLIERS (3 suppliers)
-- =====================================================
INSERT OR IGNORE INTO suppliers (id, name, phone, address, notes, is_active) VALUES
(1, 'شركة النيل للتوزيع', '01234567890', 'القاهرة - مدينة نصر', 'مورد رئيسي للمواد الغذائية', 1),
(2, 'مورد القاهرة', '01987654321', 'القاهرة - المعادي', 'مورد للمشروبات', 1),
(3, 'مورد الإسكندرية', '01112223334', 'الإسكندرية - سيدي جابر', 'مورد للمنظفات', 1),
(4, 'مورد محذوف', '01000000000', 'عنوان محذوف', 'هذا المورد محذوف', 0);

-- =====================================================
-- 4. PRODUCTS (15 products - various types)
-- =====================================================

-- Unit Products (8)
INSERT OR IGNORE INTO products (id, name, barcode, barcode2, barcode3, plu_code, type, purchase_price, sale_price_unit, stock_quantity, min_stock, category, is_active) VALUES
(1, 'أرز مصري 1 كجم', '6221234567890', '6221234567891', NULL, '1001', 'unit', 15.00, 22.00, 100, 10, 'أرز ومخلفات', 1),
(2, 'سكر 1 كجم', '6221234567892', NULL, NULL, '1002', 'unit', 18.00, 25.00, 200, 20, 'أساسيات', 1),
(3, 'زيت عباد الشمس 1 لتر', '6221234567893', NULL, NULL, '1003', 'unit', 30.00, 42.00, 80, 10, 'زيوت', 1),
(4, 'شاي ليبتون 100 جرام', '6221234567894', '6221234567895', '6221234567896', '1004', 'unit', 12.00, 18.00, 150, 15, 'مشروبات', 1),
(5, 'مكرونة 500 جرام', '6221234567897', NULL, NULL, '1005', 'unit', 5.00, 8.00, 300, 25, 'أرز ومخلفات', 1),
(6, 'ملح 1 كجم', '6221234567898', NULL, NULL, '1006', 'unit', 3.00, 5.00, 500, 50, 'أساسيات', 1),
(7, 'فول مدمس 400 جرام', '6221234567899', NULL, NULL, '1007', 'unit', 8.00, 12.00, 3, 10, 'معلبات', 1),  -- LOW STOCK
(8, 'تونا 170 جرام', '6221234567900', NULL, NULL, '1008', 'unit', 15.00, 22.00, 5, 10, 'معلبات', 1);  -- LOW STOCK

-- Pack Products (4)
INSERT OR IGNORE INTO products (id, name, barcode, plu_code, type, purchase_price, sale_price_unit, pack_type, pack_unit_quantity, pack_purchase_price, pack_sale_price, stock_quantity, min_stock, category, is_active) VALUES
(9, 'مياه معدنية', '6221234567901', '2001', 'pack', 3.00, 5.00, 'كرتونة', 12, 30.00, 50.00, 60, 5, 'مشروبات', 1),
(10, 'بيبسي 330 مل', '6221234567902', '2002', 'pack', 5.00, 8.00, 'كرتونة', 24, 100.00, 160.00, 48, 10, 'مشروبات', 1),
(11, 'عصير برتقال 1 لتر', '6221234567903', '2003', 'pack', 8.00, 12.00, 'كرتونة', 6, 40.00, 60.00, 30, 5, 'مشروبات', 1),
(12, 'بسكويت عائلي', '6221234567904', '2004', 'pack', 10.00, 15.00, 'كرتونة', 12, 100.00, 150.00, 0, 5, 'حلويات', 1);  -- ZERO STOCK

-- Weight Products (2)
INSERT OR IGNORE INTO products (id, name, barcode, plu_code, type, purchase_price, sale_price_unit, stock_quantity, min_stock, category, is_active) VALUES
(13, 'جبنة رومي', '6221234567905', '3001', 'weight', 80.00, 120.00, 25.5, 5, 'ألبان', 1),
(14, 'لحمة بقري', '6221234567906', '3002', 'weight', 150.00, 220.00, 10.0, 3, 'لحوم', 1);

-- Inactive Product (1)
INSERT OR IGNORE INTO products (id, name, barcode, plu_code, type, purchase_price, sale_price_unit, stock_quantity, min_stock, category, is_active) VALUES
(15, 'منتج محذوف', '6221234567907', '9999', 'unit', 10.00, 15.00, 50, 0, 'أخرى', 0);

-- Product with special characters in name
INSERT OR IGNORE INTO products (id, name, barcode, plu_code, type, purchase_price, sale_price_unit, stock_quantity, min_stock, category, is_active) VALUES
(16, 'مُسَلَّم - اختبار', '6221234567908', '4001', 'unit', 20.00, 30.00, 100, 0, NULL, 1);  -- NULL category

-- Product for EAN-13/UPC-A fuzzy matching test
INSERT OR IGNORE INTO products (id, name, barcode, plu_code, type, purchase_price, sale_price_unit, stock_quantity, min_stock, category, is_active) VALUES
(17, 'منتج UPC-A', '012345678905', '5001', 'unit', 25.00, 35.00, 75, 0, 'اختبار', 1);  -- 12-digit UPC-A

-- =====================================================
-- 5. CUSTOMERS (5 customers)
-- =====================================================
INSERT OR IGNORE INTO customers (id, name, phone, address, credit_limit, balance, is_active) VALUES
(1, 'أحمد محمد', '01012345678', 'القاهرة - مصر الجديدة', 5000.00, 0.00, 1),
(2, 'محمد علي', '01098765432', 'القاهرة - النزهة', 0.00, 0.00, 1),  -- Unlimited credit
(3, 'فاطمة حسن', '01055556666', 'القاهرة - مدينة نصر', 2000.00, 0.00, 1),
(4, 'عميل محذوف', '01077778888', 'عنوان محذوف', 1000.00, 0.00, 0),  -- Soft deleted
(5, 'عميل مدين', '01033334444', 'الجيزة - الدقي', 10000.00, 750.00, 1);  -- Has debt

-- =====================================================
-- 6. EMPLOYEES (3 employees)
-- =====================================================
INSERT OR IGNORE INTO employees (id, name, phone, base_salary, is_active) VALUES
(1, 'كريم أحمد', '01011112222', 5000.00, 1),
(2, 'سارة محمود', '01033334444', 4500.00, 1),
(3, 'موظف محذوف', '01055556666', 3000.00, 0);  -- Inactive

-- =====================================================
-- 7. EMPLOYEE ACCRUALS (For current and previous month)
-- =====================================================
INSERT OR IGNORE INTO employee_accruals (id, employee_id, month, amount) VALUES
(1, 1, '2026-07', 5000.00),  -- July accrual
(2, 1, '2026-08', 5000.00),  -- August accrual
(3, 2, '2026-07', 4500.00),  -- July accrual
(4, 2, '2026-08', 4500.00);  -- August accrual

-- =====================================================
-- 8. PURCHASE INVOICES (5 invoices)
-- =====================================================

-- Invoice 1: Normal purchase from supplier 1
INSERT OR IGNORE INTO purchase_invoices (id, supplier_id, invoice_number, date, total, discount, type, status, notes) VALUES
(1, 1, 'INV-2026-001', '2026-08-01', 5000.00, 0.00, 'purchase', 'completed', 'فاتورة شراء عادية');

-- Invoice 2: Purchase with discount from supplier 2
INSERT OR IGNORE INTO purchase_invoices (id, supplier_id, invoice_number, date, total, discount, type, status, notes) VALUES
(2, 2, 'INV-2026-002', '2026-08-05', 2400.00, 200.00, 'purchase', 'completed', 'فاتورة مع خصم');

-- Invoice 3: Purchase with bonus items from supplier 1
INSERT OR IGNORE INTO purchase_invoices (id, supplier_id, invoice_number, date, total, discount, type, status, notes) VALUES
(3, 1, 'INV-2026-003', '2026-08-10', 1500.00, 0.00, 'purchase', 'completed', 'فاتورة مع هدايا');

-- Invoice 4: Voided purchase from supplier 3
INSERT OR IGNORE INTO purchase_invoices (id, supplier_id, invoice_number, date, total, discount, type, status, notes) VALUES
(4, 3, 'INV-2026-004', '2026-08-12', 800.00, 0.00, 'purchase', 'voided', 'فاتورة ملغاة');

-- Invoice 5: Purchase return
INSERT OR IGNORE INTO purchase_invoices (id, supplier_id, invoice_number, date, total, discount, type, status, notes) VALUES
(5, 1, 'RET-INV-2026-001', '2026-08-15', -300.00, 0.00, 'return', 'completed', 'مرتجع شراء');

-- =====================================================
-- 9. PURCHASE ITEMS
-- =====================================================

-- Invoice 1 items (normal purchase)
INSERT OR IGNORE INTO purchase_items (id, invoice_id, product_id, quantity, unit_type, purchase_price, sale_price, subtotal) VALUES
(1, 1, 1, 50, 'قطعة', 15.00, 22.00, 750.00),   -- أرز
(2, 1, 2, 100, 'قطعة', 18.00, 25.00, 1800.00),  -- سكر
(3, 1, 3, 30, 'قطعة', 30.00, 42.00, 900.00),     -- زيت
(4, 1, 5, 200, 'قطعة', 5.00, 8.00, 1000.00);     -- مكرونة

-- Invoice 2 items (with discount)
INSERT OR IGNORE INTO purchase_items (id, invoice_id, product_id, quantity, unit_type, purchase_price, sale_price, subtotal) VALUES
(5, 2, 9, 10, 'كرتونة', 30.00, 50.00, 300.00),   -- مياه (pack)
(6, 2, 10, 5, 'كرتونة', 100.00, 160.00, 500.00), -- بيبسي (pack)
(7, 2, 11, 20, 'كرتونة', 40.00, 60.00, 800.00),  -- عصير (pack)
(8, 2, 13, 10, 'كجم', 80.00, 120.00, 800.00);    -- جبنة (weight)

-- Invoice 3 items (with bonus)
INSERT OR IGNORE INTO purchase_items (id, invoice_id, product_id, quantity, unit_type, purchase_price, sale_price, subtotal) VALUES
(9, 3, 4, 100, 'قطعة', 12.00, 18.00, 1200.00),   -- شاي
(10, 3, 6, 200, 'قطعة', 3.00, 5.00, 600.00),     -- ملح
(11, 3, 1, 10, 'قطعة', 0.00, 0.00, 0.00);         -- أرز (BONUS - zero cost)

-- Invoice 4 items (voided purchase)
INSERT OR IGNORE INTO purchase_items (id, invoice_id, product_id, quantity, unit_type, purchase_price, sale_price, subtotal) VALUES
(12, 4, 7, 50, 'قطعة', 8.00, 12.00, 400.00),     -- فول
(13, 4, 8, 20, 'قطعة', 15.00, 22.00, 300.00);     -- تونا

-- Invoice 5 items (purchase return)
INSERT OR IGNORE INTO purchase_items (id, invoice_id, product_id, quantity, unit_type, purchase_price, sale_price, subtotal) VALUES
(14, 5, 1, -10, 'قطعة', 15.00, 22.00, -150.00),  -- أرز return
(15, 5, 2, -10, 'قطعة', 18.00, 25.00, -180.00);   -- سكر return

-- =====================================================
-- 10. SHIFTS (3 shifts)
-- =====================================================

-- Shift 1: Closed shift (yesterday)
INSERT OR IGNORE INTO shifts (id, user_id, opened_at, closed_at, opening_cash, closing_cash, expected_cash, cash_difference, total_sales, total_returns, total_expenses, total_cash_in, total_cash_out, sale_count, status, notes) VALUES
(1, 1, '2026-08-24 08:00:00', '2026-08-24 20:00:00', 500.00, 3200.00, 3150.00, 50.00, 2800.00, 200.00, 150.00, 3300.00, 350.00, 15, 'closed', 'وردية أمس - فائض 50 جنيه');

-- Shift 2: Open shift (today)
INSERT OR IGNORE INTO shifts (id, user_id, opened_at, closed_at, opening_cash, closing_cash, expected_cash, cash_difference, total_sales, total_returns, total_expenses, total_cash_in, total_cash_out, sale_count, status, notes) VALUES
(2, 3, '2026-08-25 08:00:00', NULL, 1000.00, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 'open', 'وردية اليوم - أحمد الكاشير');

-- Shift 3: Old closed shift (last week)
INSERT OR IGNORE INTO shifts (id, user_id, opened_at, closed_at, opening_cash, closing_cash, expected_cash, cash_difference, total_sales, total_returns, total_expenses, total_cash_in, total_cash_out, sale_count, status, notes) VALUES
(3, 4, '2026-08-18 08:00:00', '2026-08-18 20:00:00', 300.00, 1800.00, 1800.00, 0.00, 1500.00, 0.00, 100.00, 1800.00, 100.00, 10, 'closed', 'وردية الأسبوع الماضي - متوازنة');

-- =====================================================
-- 11. SALES (10 sales - various types)
-- =====================================================

-- Sale 1: Normal cash sale (today, shift 2)
INSERT OR IGNORE INTO sales (id, sale_number, datetime, subtotal, discount, total, payment_method, notes, type, status, customer_id, shift_id) VALUES
(1, 1, '2026-08-25 09:00:00', 64.00, 0.00, 64.00, 'cash', 'بيع نقدي عادي', 'sale', 'completed', NULL, 2);

-- Sale 2: Card payment sale (today, shift 2)
INSERT OR IGNORE INTO sales (id, sale_number, datetime, subtotal, discount, total, payment_method, notes, type, status, customer_id, shift_id) VALUES
(2, 2, '2026-08-25 09:30:00', 110.00, 0.00, 110.00, 'card', 'بيع بالبطاقة', 'sale', 'completed', NULL, 2);

-- Sale 3: Sale with discount (today, shift 2)
INSERT OR IGNORE INTO sales (id, sale_number, datetime, subtotal, discount, total, payment_method, notes, type, status, customer_id, shift_id) VALUES
(3, 3, '2026-08-25 10:00:00', 100.00, 10.00, 90.00, 'cash', 'بيع مع خصم', 'sale', 'completed', NULL, 2);

-- Sale 4: Credit sale linked to customer 1 (today, shift 2)
INSERT OR IGNORE INTO sales (id, sale_number, datetime, subtotal, discount, total, payment_method, notes, type, status, customer_id, shift_id) VALUES
(4, 4, '2026-08-25 10:30:00', 220.00, 0.00, 220.00, 'cash', 'بيع آجل - أحمد محمد', 'sale', 'completed', 1, 2);

-- Sale 5: Multi-item sale (today, shift 2)
INSERT OR IGNORE INTO sales (id, sale_number, datetime, subtotal, discount, total, payment_method, notes, type, status, customer_id, shift_id) VALUES
(5, 5, '2026-08-25 11:00:00', 175.00, 5.00, 170.00, 'cash', 'بيع متعدد الأصناف', 'sale', 'completed', NULL, 2);

-- Sale 6: Voided sale (today, shift 2)
INSERT OR IGNORE INTO sales (id, sale_number, datetime, subtotal, discount, total, payment_method, notes, type, status, customer_id, shift_id) VALUES
(6, 6, '2026-08-25 11:30:00', 50.00, 0.00, 50.00, 'cash', 'بيع ملغى', 'sale', 'voided', NULL, 2);

-- Sale 7: Return sale (today, shift 2)
INSERT OR IGNORE INTO sales (id, sale_number, datetime, subtotal, discount, total, payment_method, notes, type, status, customer_id, shift_id) VALUES
(7, 7, '2026-08-25 12:00:00', -44.00, 0.00, -44.00, 'cash', 'مرتجع مبيعات', 'return', 'completed', NULL, 2);

-- Sale 8: Sale with pack items (today, shift 2)
INSERT OR IGNORE INTO sales (id, sale_number, datetime, subtotal, discount, total, payment_method, notes, type, status, customer_id, shift_id) VALUES
(8, 8, '2026-08-25 12:30:00', 50.00, 0.00, 50.00, 'cash', 'بيع كراتين', 'sale', 'completed', NULL, 2);

-- Sale 9: Sale with weight items (today, shift 2)
INSERT OR IGNORE INTO sales (id, sale_number, datetime, subtotal, discount, total, payment_method, notes, type, status, customer_id, shift_id) VALUES
(9, 9, '2026-08-25 13:00:00', 120.00, 0.00, 120.00, 'cash', 'بيع بالوزن', 'sale', 'completed', NULL, 2);

-- Sale 10: Sale from yesterday's shift
INSERT OR IGNORE INTO sales (id, sale_number, datetime, subtotal, discount, total, payment_method, notes, type, status, customer_id, shift_id) VALUES
(10, 1, '2026-08-24 10:00:00', 150.00, 0.00, 150.00, 'cash', 'بيع أمس', 'sale', 'completed', NULL, 1);

-- Sale 11: Sale linked to customer 2 (unlimited credit)
INSERT OR IGNORE INTO sales (id, sale_number, datetime, subtotal, discount, total, payment_method, notes, type, status, customer_id, shift_id) VALUES
(11, 2, '2026-08-24 14:00:00', 300.00, 0.00, 300.00, 'cash', 'بيع آجل - محمد علي', 'sale', 'completed', 2, 1);

-- Sale 12: Sale from last week's shift
INSERT OR IGNORE INTO sales (id, sale_number, datetime, subtotal, discount, total, payment_method, notes, type, status, customer_id, shift_id) VALUES
(12, 1, '2026-08-18 10:00:00', 88.00, 0.00, 88.00, 'cash', 'بيع الأسبوع الماضي', 'sale', 'completed', NULL, 3);

-- =====================================================
-- 12. SALE ITEMS
-- =====================================================

-- Sale 1 items (normal cash sale)
INSERT OR IGNORE INTO sale_items (id, sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal) VALUES
(1, 1, 1, 'أرز مصري 1 كجم', 2, 'قطعة', 22.00, 'unit', 44.00),
(2, 1, 2, 'سكر 1 كجم', 1, 'قطعة', 25.00, 'unit', 25.00);

-- Sale 2 items (card payment)
INSERT OR IGNORE INTO sale_items (id, sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal) VALUES
(3, 2, 3, 'زيت عباد الشمس 1 لتر', 1, 'قطعة', 42.00, 'unit', 42.00),
(4, 2, 4, 'شاي ليبتون 100 جرام', 2, 'قطعة', 18.00, 'unit', 36.00),
(5, 2, 5, 'مكرونة 500 جرام', 4, 'قطعة', 8.00, 'unit', 32.00);

-- Sale 3 items (with discount)
INSERT OR IGNORE INTO sale_items (id, sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal) VALUES
(6, 3, 1, 'أرز مصري 1 كجم', 3, 'قطعة', 22.00, 'unit', 66.00),
(7, 3, 6, 'ملح 1 كجم', 2, 'قطعة', 5.00, 'unit', 10.00);

-- Sale 4 items (credit sale - customer 1)
INSERT OR IGNORE INTO sale_items (id, sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal) VALUES
(8, 4, 13, 'جبنة رومي', 1, 'كجم', 120.00, 'weight', 120.00),
(9, 4, 14, 'لحمة بقري', 0.5, 'كجم', 220.00, 'weight', 110.00);

-- Sale 5 items (multi-item)
INSERT OR IGNORE INTO sale_items (id, sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal) VALUES
(10, 5, 1, 'أرز مصري 1 كجم', 2, 'قطعة', 22.00, 'unit', 44.00),
(11, 5, 2, 'سكر 1 كجم', 2, 'قطعة', 25.00, 'unit', 50.00),
(12, 5, 3, 'زيت عباد الشمس 1 لتر', 1, 'قطعة', 42.00, 'unit', 42.00),
(13, 5, 5, 'مكرونة 500 جرام', 5, 'قطعة', 8.00, 'unit', 40.00);

-- Sale 6 items (voided sale)
INSERT OR IGNORE INTO sale_items (id, sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal) VALUES
(14, 6, 4, 'شاي ليبتون 100 جرام', 2, 'قطعة', 18.00, 'unit', 36.00),
(15, 6, 6, 'ملح 1 كجم', 2, 'قطعة', 5.00, 'unit', 10.00);

-- Sale 7 items (return sale - negative quantities)
INSERT OR IGNORE INTO sale_items (id, sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal) VALUES
(16, 7, 1, 'أرز مصري 1 كجم', -2, 'قطعة', 22.00, 'unit', -44.00);

-- Sale 8 items (pack sale)
INSERT OR IGNORE INTO sale_items (id, sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal) VALUES
(17, 8, 9, 'مياه معدنية', 1, 'كرتونة', 50.00, 'pack', 50.00);

-- Sale 9 items (weight sale)
INSERT OR IGNORE INTO sale_items (id, sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal) VALUES
(18, 9, 13, 'جبنة رومي', 1, 'كجم', 120.00, 'weight', 120.00);

-- Sale 10 items (yesterday's sale)
INSERT OR IGNORE INTO sale_items (id, sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal) VALUES
(19, 10, 1, 'أرز مصري 1 كجم', 5, 'قطعة', 22.00, 'unit', 110.00),
(20, 10, 2, 'سكر 1 كجم', 2, 'قطعة', 25.00, 'unit', 50.00);

-- Sale 11 items (customer 2 - unlimited credit)
INSERT OR IGNORE INTO sale_items (id, sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal) VALUES
(21, 11, 10, 'بيبسي 330 مل', 2, 'كرتونة', 160.00, 'pack', 320.00);

-- Sale 12 items (last week)
INSERT OR IGNORE INTO sale_items (id, sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal) VALUES
(22, 12, 4, 'شاي ليبتون 100 جرام', 3, 'قطعة', 18.00, 'unit', 54.00),
(23, 12, 5, 'مكرونة 500 جرام', 4, 'قطعة', 8.00, 'unit', 32.00);

-- =====================================================
-- 13. STOCK MOVEMENTS (All types)
-- =====================================================

-- Purchase movements (from invoice 1)
INSERT OR IGNORE INTO stock_movements (id, product_id, type, quantity, reference_type, reference_id, notes, created_at) VALUES
(1, 1, 'purchase', 50, 'purchase_invoice', 1, 'شراء أرز', '2026-08-01 10:00:00'),
(2, 2, 'purchase', 100, 'purchase_invoice', 1, 'شراء سكر', '2026-08-01 10:00:00'),
(3, 3, 'purchase', 30, 'purchase_invoice', 1, 'شراء زيت', '2026-08-01 10:00:00'),
(4, 5, 'purchase', 200, 'purchase_invoice', 1, 'شراء مكرونة', '2026-08-01 10:00:00');

-- Purchase movements (from invoice 2 - pack conversion)
INSERT OR IGNORE INTO stock_movements (id, product_id, type, quantity, reference_type, reference_id, notes, created_at) VALUES
(5, 9, 'purchase', 120, 'purchase_invoice', 2, 'شراء مياه (10 كراتين × 12)', '2026-08-05 10:00:00'),
(6, 10, 'purchase', 120, 'purchase_invoice', 2, 'شراء بيبسي (5 كراتين × 24)', '2026-08-05 10:00:00'),
(7, 11, 'purchase', 120, 'purchase_invoice', 2, 'شراء عصير (20 كرتونة × 6)', '2026-08-05 10:00:00'),
(8, 13, 'purchase', 10, 'purchase_invoice', 2, 'شراء جبنة (وزن)', '2026-08-05 10:00:00');

-- Purchase movements (from invoice 3 - with bonus)
INSERT OR IGNORE INTO stock_movements (id, product_id, type, quantity, reference_type, reference_id, notes, created_at) VALUES
(9, 4, 'purchase', 100, 'purchase_invoice', 3, 'شراء شاي', '2026-08-10 10:00:00'),
(10, 6, 'purchase', 200, 'purchase_invoice', 3, 'شراء ملح', '2026-08-10 10:00:00'),
(11, 1, 'purchase', 10, 'purchase_invoice', 3, 'شراء أرز (هدية)', '2026-08-10 10:00:00');

-- Voided purchase movements (invoice 4 - reversed)
INSERT OR IGNORE INTO stock_movements (id, product_id, type, quantity, reference_type, reference_id, notes, created_at) VALUES
(12, 7, 'purchase', 50, 'purchase_invoice', 4, 'شراء فول', '2026-08-12 10:00:00'),
(13, 8, 'purchase', 20, 'purchase_invoice', 4, 'شراء تونا', '2026-08-12 10:00:00'),
(14, 7, 'adjustment', -50, 'purchase_void', 4, 'إلغاء فاتورة شراء', '2026-08-13 10:00:00'),
(15, 8, 'adjustment', -20, 'purchase_void', 4, 'إلغاء فاتورة شراء', '2026-08-13 10:00:00');

-- Purchase return movements (invoice 5)
INSERT OR IGNORE INTO stock_movements (id, product_id, type, quantity, reference_type, reference_id, notes, created_at) VALUES
(16, 1, 'purchase', -10, 'purchase_return', 5, 'مرتجع أرز', '2026-08-15 10:00:00'),
(17, 2, 'purchase', -10, 'purchase_return', 5, 'مرتجع سكر', '2026-08-15 10:00:00');

-- Sale movements
INSERT OR IGNORE INTO stock_movements (id, product_id, type, quantity, reference_type, reference_id, notes, created_at) VALUES
(18, 1, 'sale', -2, 'sale', 1, 'بيع أرز', '2026-08-25 09:00:00'),
(19, 2, 'sale', -1, 'sale', 1, 'بيع سكر', '2026-08-25 09:00:00'),
(20, 3, 'sale', -1, 'sale', 2, 'بيع زيت', '2026-08-25 09:30:00'),
(21, 4, 'sale', -2, 'sale', 2, 'بيع شاي', '2026-08-25 09:30:00'),
(22, 5, 'sale', -4, 'sale', 2, 'بيع مكرونة', '2026-08-25 09:30:00');

-- Voided sale movements (sale 6 - reversed)
INSERT OR IGNORE INTO stock_movements (id, product_id, type, quantity, reference_type, reference_id, notes, created_at) VALUES
(23, 4, 'sale', -2, 'sale', 6, 'بيع شاي (ملغى)', '2026-08-25 11:30:00'),
(24, 6, 'sale', -2, 'sale', 6, 'بيع ملح (ملغى)', '2026-08-25 11:30:00'),
(25, 4, 'adjustment', 2, 'sale_void', 6, 'إلغاء بيع', '2026-08-25 12:00:00'),
(26, 6, 'adjustment', 2, 'sale_void', 6, 'إلغاء بيع', '2026-08-25 12:00:00');

-- Return sale movements (sale 7)
INSERT OR IGNORE INTO stock_movements (id, product_id, type, quantity, reference_type, reference_id, notes, created_at) VALUES
(27, 1, 'sale', 2, 'sale_return', 7, 'مرتجع أرز', '2026-08-25 12:00:00');

-- Manual adjustment
INSERT OR IGNORE INTO stock_movements (id, product_id, type, quantity, reference_type, reference_id, notes, created_at) VALUES
(28, 6, 'adjustment', 50, 'manual', NULL, 'تعديل يدوي - جرد', '2026-08-20 10:00:00');

-- =====================================================
-- 14. SAFE TRANSACTIONS (All types)
-- =====================================================

-- Opening cash (shift 1)
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(1, 'opening', 500.00, '2026-08-24', 'رصيد افتتاحي الوردية', NULL, '2026-08-24 08:00:00');

-- Sales income (from shift 1)
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(2, 'sale', 150.00, '2026-08-24', 'بيع نقدي - فاتورة 1', NULL, '2026-08-24 10:00:00'),
(3, 'sale', 300.00, '2026-08-24', 'بيع آجل - فاتورة 2', NULL, '2026-08-24 14:00:00');

-- Expense (from shift 1)
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(4, 'expense', 100.00, '2026-08-24', 'مصاريف نقل', NULL, '2026-08-24 12:00:00'),
(5, 'expense', 50.00, '2026-08-24', 'مصاريف مكتبية', NULL, '2026-08-24 15:00:00');

-- Sale void (from shift 1)
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(6, 'sale_void', 75.00, '2026-08-24', 'إلغاء بيع', NULL, '2026-08-24 16:00:00');

-- Sale return (from shift 1)
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(7, 'sale_return', 44.00, '2026-08-24', 'مرتجع مبيعات', NULL, '2026-08-24 17:00:00');

-- Opening cash (shift 2 - today)
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(8, 'opening', 1000.00, '2026-08-25', 'رصيد افتتاحي الوردية', NULL, '2026-08-25 08:00:00');

-- Sales income (from shift 2 - today)
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(9, 'sale', 64.00, '2026-08-25', 'بيع نقدي - فاتورة 1', NULL, '2026-08-25 09:00:00'),
(10, 'sale', 110.00, '2026-08-25', 'بيع بالبطاقة - فاتورة 2', NULL, '2026-08-25 09:30:00'),
(11, 'sale', 90.00, '2026-08-25', 'بيع مع خصم - فاتورة 3', NULL, '2026-08-25 10:00:00'),
(12, 'sale', 220.00, '2026-08-25', 'بيع آجل - فاتورة 4', NULL, '2026-08-25 10:30:00'),
(13, 'sale', 170.00, '2026-08-25', 'بيع متعدد - فاتورة 5', NULL, '2026-08-25 11:00:00');

-- Sale void (from shift 2)
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(14, 'sale_void', 50.00, '2026-08-25', 'إلغاء بيع - فاتورة 6', NULL, '2026-08-25 12:00:00');

-- Sale return (from shift 2)
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(15, 'sale_return', 44.00, '2026-08-25', 'مرتجع مبيعات - فاتورة 7', NULL, '2026-08-25 12:00:00');

-- Purchase transactions
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(16, 'purchase', 5000.00, '2026-08-01', 'فاتورة شراء INV-2026-001', NULL, '2026-08-01 10:00:00'),
(17, 'purchase', 2400.00, '2026-08-05', 'فاتورة شراء INV-2026-002', NULL, '2026-08-05 10:00:00'),
(18, 'purchase', 1500.00, '2026-08-10', 'فاتورة شراء INV-2026-003', NULL, '2026-08-10 10:00:00');

-- Purchase void (refund)
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(19, 'deposit', 800.00, '2026-08-13', 'إلغاء فاتورة شراء INV-2026-004', NULL, '2026-08-13 10:00:00');

-- Purchase return (refund)
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(20, 'deposit', 300.00, '2026-08-15', 'مرتجع شراء RET-INV-2026-001', NULL, '2026-08-15 10:00:00');

-- Customer payment (deposit)
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(21, 'deposit', 500.00, '2026-08-20', 'دفعة من العميل أحمد محمد', NULL, '2026-08-20 10:00:00');

-- Salary payment
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(22, 'salary', 5000.00, '2026-08-01', 'راتب كريم أحمد - يوليو', 1, '2026-08-01 10:00:00'),
(23, 'salary_payment', 4500.00, '2026-08-01', 'راتب سارة محمود - يوليو', 2, '2026-08-01 10:00:00');

-- Advance
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(24, 'advance', 1000.00, '2026-08-10', 'سلفة لسارة محمود', 2, '2026-08-10 10:00:00');

-- Withdrawal (owner draw)
INSERT OR IGNORE INTO safe_transactions (id, type, amount, date, description, employee_id, created_at) VALUES
(25, 'withdrawal', 2000.00, '2026-08-15', 'سحب مالك', NULL, '2026-08-15 10:00:00');

-- =====================================================
-- 15. CUSTOMER TRANSACTIONS
-- =====================================================

-- Customer 1 (أحمد محمد) - credit sale and payment
INSERT OR IGNORE INTO customer_transactions (id, customer_id, type, amount, balance_after, reference_type, reference_id, description, date) VALUES
(1, 1, 'sale', 220.00, 220.00, 'sale', 4, 'بيع آجل', '2026-08-25'),
(2, 1, 'payment', 500.00, -280.00, 'safe', 21, 'دفعة نقدية', '2026-08-20');

-- Customer 2 (محمد علي) - credit sale
INSERT OR IGNORE INTO customer_transactions (id, customer_id, type, amount, balance_after, reference_type, reference_id, description, date) VALUES
(3, 2, 'sale', 300.00, 300.00, 'sale', 11, 'بيع آجل', '2026-08-24');

-- Customer 5 (عميل مدين) - has existing debt
INSERT OR IGNORE INTO customer_transactions (id, customer_id, type, amount, balance_after, reference_type, reference_id, description, date) VALUES
(4, 5, 'sale', 750.00, 750.00, 'sale', NULL, 'رصيد سابق', '2026-08-01');

-- =====================================================
-- 16. BARCODE LABELS (for barcode testing)
-- =====================================================
-- Note: barcode_labels table may not exist in schema, skip if needed

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================
-- Run these to verify seed data integrity:

-- SELECT 'Users' as tbl, COUNT(*) as cnt FROM users WHERE is_active = 1
-- UNION ALL SELECT 'Suppliers', COUNT(*) FROM suppliers WHERE is_active = 1
-- UNION ALL SELECT 'Products', COUNT(*) FROM products WHERE is_active = 1
-- UNION ALL SELECT 'Customers', COUNT(*) FROM customers WHERE is_active = 1
-- UNION ALL SELECT 'Employees', COUNT(*) FROM employees WHERE is_active = 1
-- UNION ALL SELECT 'Shifts', COUNT(*) FROM shifts
-- UNION ALL SELECT 'Sales', COUNT(*) FROM sales
-- UNION ALL SELECT 'Purchase Invoices', COUNT(*) FROM purchase_invoices
-- UNION ALL SELECT 'Safe Transactions', COUNT(*) FROM safe_transactions
-- UNION ALL SELECT 'Stock Movements', COUNT(*) FROM stock_movements
-- UNION ALL SELECT 'Customer Transactions', COUNT(*) FROM customer_transactions;
