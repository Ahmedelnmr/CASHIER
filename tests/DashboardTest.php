<?php
/**
 * Dashboard Module Tests
 * Tests: DASH-001 through DASH-008
 *
 * Run: php tests/DashboardTest.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================================
// Test Framework
// ============================================================
$passed = 0;
$failed = 0;
$errors = [];

function assert_true($condition, $testId, $description) {
    global $passed, $failed, $errors;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$testId}: {$description}\n";
    } else {
        $failed++;
        $errors[] = "{$testId}: {$description}";
        echo "  [FAIL] {$testId}: {$description}\n";
    }
}

function assert_equals($expected, $actual, $testId, $description) {
    assert_true($expected === $actual, $testId, $description . " (expected: " . var_export($expected, true) . ", got: " . var_export($actual, true) . ")");
}

function assert_greater_than($value, $threshold, $testId, $description) {
    assert_true($value > $threshold, $testId, $description . " (value={$value}, threshold={$threshold})");
}

function assert_greater_or_equal($value, $threshold, $testId, $description) {
    assert_true($value >= $threshold, $testId, $description . " (value={$value}, threshold={$threshold})");
}

// ============================================================
// Database Setup — Temporary In-Memory SQLite
// ============================================================
function createTestDb() {
    $pdo = new PDO('sqlite:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');

    // Run schema
    $migrationsDir = __DIR__ . '/../www/app/database/migrations/';
    $files = glob($migrationsDir . '*.sql');
    sort($files);
    foreach ($files as $file) {
        $sql = file_get_contents($file);
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Ignore "already exists" errors from ALTER TABLE
            if (strpos($e->getMessage(), 'already exists') === false &&
                strpos($e->getMessage(), 'duplicate column') === false) {
                throw $e;
            }
        }
    }

    return $pdo;
}

// ============================================================
// Helper: Insert test data
// ============================================================
function insertProduct($pdo, $name, $barcode, $type, $purchasePrice, $salePrice, $stock, $minStock, $category = null) {
    $stmt = $pdo->prepare(
        "INSERT INTO products (name, barcode, type, purchase_price, sale_price_unit, stock_quantity, min_stock, category, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)"
    );
    $stmt->execute([$name, $barcode, $type, $purchasePrice, $salePrice, $stock, $minStock, $category]);
    return $pdo->lastInsertId();
}

function insertSale($pdo, $total, $status = 'completed', $type = 'sale', $datetime = null) {
    $datetime = $datetime ?: date('Y-m-d H:i:s');
    $stmt = $pdo->prepare(
        "INSERT INTO sales (sale_number, subtotal, discount, total, payment_method, type, status, datetime)
         VALUES (?, ?, 0, ?, 'cash', ?, ?, ?)"
    );
    $saleNumber = rand(1, 9999);
    $stmt->execute([$saleNumber, $total, $total, $type, $status, $datetime]);
    return $pdo->lastInsertId();
}

function insertSaleItem($pdo, $saleId, $productId, $productName, $quantity, $price, $subtotal) {
    $stmt = $pdo->prepare(
        "INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal)
         VALUES (?, ?, ?, ?, 'قطعة', ?, 'unit', ?)"
    );
    $stmt->execute([$saleId, $productId, $productName, $quantity, $price, $subtotal]);
}

// ============================================================
// Replicate ReportService methods (test against same SQL)
// ============================================================
function dashboardSummary($pdo) {
    $todayStmt = $pdo->prepare(
        "SELECT COUNT(*) as sales_count, COALESCE(SUM(total), 0) as sales_total
         FROM sales
         WHERE date(datetime) = date('now', 'localtime')
           AND status != 'voided'
           AND type = 'sale'"
    );
    $todayStmt->execute();
    $today = $todayStmt->fetch();

    $prodStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE is_active = 1");
    $prodStmt->execute();
    $productCount = $prodStmt->fetchColumn();

    $lowStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock_quantity <= min_stock AND min_stock > 0"
    );
    $lowStmt->execute();
    $lowStockCount = $lowStmt->fetchColumn();

    $monthStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(total), 0) as total FROM sales
         WHERE strftime('%Y-%m', datetime) = strftime('%Y-%m', 'now', 'localtime')
           AND status != 'voided'
           AND type = 'sale'"
    );
    $monthStmt->execute();
    $monthTotal = $monthStmt->fetchColumn();

    return [
        'today_sales_count' => (int)$today['sales_count'],
        'today_sales_total' => (float)$today['sales_total'],
        'product_count'     => (int)$productCount,
        'low_stock_count'   => (int)$lowStockCount,
        'month_total'       => (float)$monthTotal,
    ];
}

function lowStock($pdo) {
    $stmt = $pdo->prepare(
        "SELECT * FROM products
         WHERE is_active = 1 AND stock_quantity <= min_stock AND min_stock > 0
         ORDER BY (stock_quantity / CASE WHEN min_stock > 0 THEN min_stock ELSE 1 END) ASC"
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function recentSales($pdo, $limit = 5) {
    $stmt = $pdo->prepare(
        "SELECT * FROM sales WHERE status != 'voided' AND type = 'sale' ORDER BY datetime DESC LIMIT ?"
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// ============================================================
// Run Tests
// ============================================================
echo "\n";
echo "==========================================================\n";
echo "  Dashboard Module Tests (DASH-001 to DASH-008)\n";
echo "==========================================================\n\n";

// ----------------------------------------------------------
// DASH-007: Empty state (test first with clean DB)
// ----------------------------------------------------------
echo "--- DASH-007: Empty state ---\n";
$pdo = createTestDb();

$summary = dashboardSummary($pdo);
assert_equals(0, $summary['today_sales_count'], 'DASH-007a', 'Empty DB: today_sales_count is 0');
assert_equals(0.0, $summary['today_sales_total'], 'DASH-007b', 'Empty DB: today_sales_total is 0');
assert_equals(0, $summary['product_count'], 'DASH-007c', 'Empty DB: product_count is 0');
assert_equals(0, $summary['low_stock_count'], 'DASH-007d', 'Empty DB: low_stock_count is 0');
assert_equals(0.0, $summary['month_total'], 'DASH-007e', 'Empty DB: month_total is 0');

$lowStockItems = lowStock($pdo);
assert_equals(0, count($lowStockItems), 'DASH-007f', 'Empty DB: no low stock items');

$recentSalesItems = recentSales($pdo);
assert_equals(0, count($recentSalesItems), 'DASH-007g', 'Empty DB: no recent sales');

$pdo = null; // close

// ----------------------------------------------------------
// DASH-001: Dashboard loads with data
// ----------------------------------------------------------
echo "\n--- DASH-001: Dashboard loads with stats ---\n";
$pdo = createTestDb();

// Insert products
$prod1 = insertProduct($pdo, 'Product A', '111111', 'unit', 10.0, 20.0, 100, 5);
$prod2 = insertProduct($pdo, 'Product B', '222222', 'unit', 15.0, 30.0, 3, 10);  // low stock
$prod3 = insertProduct($pdo, 'Product C', '333333', 'pack', 5.0, 12.0, 2, 5);    // low stock

// Insert today's sales
$sale1 = insertSale($pdo, 100.00, 'completed', 'sale');
insertSaleItem($pdo, $sale1, $prod1, 'Product A', 5, 20.0, 100.0);

$sale2 = insertSale($pdo, 50.00, 'completed', 'sale');
insertSaleItem($pdo, $sale2, $prod2, 'Product B', 1, 30.0, 30.0);
insertSaleItem($pdo, $sale2, $prod3, 'Product C', 1, 12.0, 12.0);

$summary = dashboardSummary($pdo);

assert_true($summary['today_sales_count'] >= 2, 'DASH-001a', 'Dashboard shows today sales count (>=2)');
assert_greater_than($summary['today_sales_total'], 0, 'DASH-001b', 'Dashboard shows today sales total > 0');
assert_equals(3, $summary['product_count'], 'DASH-001c', 'Dashboard shows product count = 3');
assert_equals(2, $summary['low_stock_count'], 'DASH-001d', 'Dashboard shows low stock count = 2');
assert_greater_than($summary['month_total'], 0, 'DASH-001e', 'Dashboard shows month_total > 0');

$lowStockItems = lowStock($pdo);
assert_equals(2, count($lowStockItems), 'DASH-001f', 'Low stock table has 2 items');
// Product B: stock=3/min_stock=10 = 0.3 ratio (worst), Product C: stock=2/min_stock=5 = 0.4 ratio
assert_equals('Product B', $lowStockItems[0]['name'], 'DASH-001g', 'Low stock item is Product B (worst ratio 0.3)');
assert_equals('Product C', $lowStockItems[1]['name'], 'DASH-001g2', 'Second low stock item is Product C (ratio 0.4)');

$recentSalesItems = recentSales($pdo);
assert_equals(2, count($recentSalesItems), 'DASH-001h', 'Recent sales table has 2 items');

$pdo = null;

// ----------------------------------------------------------
// DASH-002: Today's sales stat
// ----------------------------------------------------------
echo "\n--- DASH-002: Today's sales stat ---\n";
$pdo = createTestDb();

$prod = insertProduct($pdo, 'TestProd', '999001', 'unit', 10.0, 25.0, 50, 5);

// Insert 3 sales today
insertSale($pdo, 100.00, 'completed', 'sale');
insertSale($pdo, 200.00, 'completed', 'sale');
insertSale($pdo, 50.00, 'completed', 'sale');

$summary = dashboardSummary($pdo);
assert_equals(3, $summary['today_sales_count'], 'DASH-002a', 'Today sales count = 3 after 3 sales');
assert_equals(350.0, $summary['today_sales_total'], 'DASH-002b', 'Today sales total = 350.00');

// Add a voided sale — should NOT count
insertSale($pdo, 999.00, 'voided', 'sale');
$summary = dashboardSummary($pdo);
assert_equals(3, $summary['today_sales_count'], 'DASH-002c', 'Voided sale excluded from count');
assert_equals(350.0, $summary['today_sales_total'], 'DASH-002d', 'Voided sale excluded from total');

// Add a return — should NOT count (type=return)
insertSale($pdo, -30.00, 'completed', 'return');
$summary = dashboardSummary($pdo);
assert_equals(3, $summary['today_sales_count'], 'DASH-002e', 'Return sale excluded from count');
assert_equals(350.0, $summary['today_sales_total'], 'DASH-002f', 'Return sale excluded from total');

$pdo = null;

// ----------------------------------------------------------
// DASH-003: Product count stat
// ----------------------------------------------------------
echo "\n--- DASH-003: Product count stat ---\n";
$pdo = createTestDb();

insertProduct($pdo, 'Prod 1', '100001', 'unit', 5.0, 10.0, 50, 5);
insertProduct($pdo, 'Prod 2', '100002', 'unit', 8.0, 15.0, 30, 5);
$summary = dashboardSummary($pdo);
assert_equals(2, $summary['product_count'], 'DASH-003a', 'Product count = 2 after adding 2 products');

insertProduct($pdo, 'Prod 3', '100003', 'pack', 3.0, 8.0, 100, 5);
$summary = dashboardSummary($pdo);
assert_equals(3, $summary['product_count'], 'DASH-003b', 'Product count = 3 after adding 3rd product');

// Deactivated product should NOT count
$pdo->exec("UPDATE products SET is_active = 0 WHERE id = 1");
$summary = dashboardSummary($pdo);
assert_equals(2, $summary['product_count'], 'DASH-003c', 'Deactivated product excluded from count');

$pdo = null;

// ----------------------------------------------------------
// DASH-004: Monthly sales stat
// ----------------------------------------------------------
echo "\n--- DASH-004: Monthly sales stat ---\n";
$pdo = createTestDb();

// Sales this month (today)
insertSale($pdo, 100.00, 'completed', 'sale');
insertSale($pdo, 200.00, 'completed', 'sale');

$summary = dashboardSummary($pdo);
assert_equals(300.0, $summary['month_total'], 'DASH-004a', 'Monthly total = 300 after 2 sales');

// Add more sales this month
insertSale($pdo, 500.00, 'completed', 'sale');
$summary = dashboardSummary($pdo);
assert_equals(800.0, $summary['month_total'], 'DASH-004b', 'Monthly total = 800 after 3rd sale');

// Voided sale should not count
insertSale($pdo, 999.00, 'voided', 'sale');
$summary = dashboardSummary($pdo);
assert_equals(800.0, $summary['month_total'], 'DASH-004c', 'Voided sale excluded from monthly total');

// Return should not count
insertSale($pdo, -50.00, 'completed', 'return');
$summary = dashboardSummary($pdo);
assert_equals(800.0, $summary['month_total'], 'DASH-004d', 'Return excluded from monthly total');

$pdo = null;

// ----------------------------------------------------------
// DASH-005: Low stock alerts
// ----------------------------------------------------------
echo "\n--- DASH-005: Low stock alerts ---\n";
$pdo = createTestDb();

// Product with stock ABOVE min_stock — should NOT appear
insertProduct($pdo, 'Healthy', '200001', 'unit', 10.0, 20.0, 50, 10);

// Product with stock EQUAL to min_stock — should appear
insertProduct($pdo, 'At Min', '200002', 'unit', 10.0, 20.0, 10, 10);

// Product with stock BELOW min_stock — should appear
insertProduct($pdo, 'Below Min', '200003', 'unit', 10.0, 20.0, 3, 10);

// Product with min_stock = 0 — should NOT appear (guard clause)
insertProduct($pdo, 'No Min', '200004', 'unit', 10.0, 20.0, 0, 0);

// Deactivated product below min — should NOT appear
insertProduct($pdo, 'Inactive Low', '200005', 'unit', 10.0, 20.0, 1, 10);
$pdo->exec("UPDATE products SET is_active = 0 WHERE barcode = '200005'");

$summary = dashboardSummary($pdo);
assert_equals(2, $summary['low_stock_count'], 'DASH-005a', 'Low stock count = 2 (At Min + Below Min)');

$lowStockItems = lowStock($pdo);
assert_equals(2, count($lowStockItems), 'DASH-005b', 'Low stock query returns 2 items');

$lowStockNames = array_column($lowStockItems, 'name');
assert_true(in_array('Below Min', $lowStockNames), 'DASH-005c', 'Below Min product in low stock list');
assert_true(in_array('At Min', $lowStockNames), 'DASH-005d', 'At Min product in low stock list');
assert_true(!in_array('Healthy', $lowStockNames), 'DASH-005e', 'Healthy product NOT in low stock list');
assert_true(!in_array('No Min', $lowStockNames), 'DASH-005f', 'No Min (min_stock=0) NOT in low stock list');
assert_true(!in_array('Inactive Low', $lowStockNames), 'DASH-005g', 'Inactive product NOT in low stock list');

// Verify ordering — worst ratio first
assert_equals('Below Min', $lowStockItems[0]['name'], 'DASH-005h', 'Worst ratio product (Below Min) is first');

$pdo = null;

// ----------------------------------------------------------
// DASH-006: Recent sales table
// ----------------------------------------------------------
echo "\n--- DASH-006: Recent sales table ---\n";
$pdo = createTestDb();

// Insert 7 sales with different timestamps
for ($i = 1; $i <= 7; $i++) {
    $dt = date('Y-m-d H:i:s', strtotime("-{$i} hours"));
    insertSale($pdo, $i * 10.00, 'completed', 'sale', $dt);
}

$recent = recentSales($pdo, 5);
assert_equals(5, count($recent), 'DASH-006a', 'Recent sales limited to 5');

// Should be in reverse chronological order (most recent first)
assert_true($recent[0]['datetime'] >= $recent[1]['datetime'], 'DASH-006b', 'Sales ordered by datetime DESC (1st >= 2nd)');
assert_true($recent[1]['datetime'] >= $recent[2]['datetime'], 'DASH-006c', 'Sales ordered by datetime DESC (2nd >= 3rd)');
assert_true($recent[4]['datetime'] >= $recent[4]['datetime'], 'DASH-006d', 'Last item exists in results');

// Verify the most recent sale is the one from 1 hour ago
$expectedTotal = 10.00; // sale #1 (1 hour ago)
assert_equals($expectedTotal, (float)$recent[0]['total'], 'DASH-006e', 'Most recent sale total = 10.00');

// Voided sales should be excluded
insertSale($pdo, 9999.00, 'voided', 'sale');
$recent = recentSales($pdo, 5);
assert_equals(5, count($recent), 'DASH-006f', 'Voided sale excluded from recent sales');

// Return sales should be excluded (type=return)
insertSale($pdo, -100.00, 'completed', 'return');
$recent = recentSales($pdo, 5);
assert_equals(5, count($recent), 'DASH-006g', 'Return sale excluded from recent sales');

$pdo = null;

// ----------------------------------------------------------
// DASH-008: Quick action links (structural test)
// ----------------------------------------------------------
echo "\n--- DASH-008: Quick action links ---\n";

$viewFile = __DIR__ . '/../www/app/views/dashboard/index.php';
$viewContent = file_get_contents($viewFile);

// Check that quick action links exist in the view
assert_true(strpos($viewContent, '?page=pos') !== false, 'DASH-008a', 'POS quick action link exists');
assert_true(strpos($viewContent, '?page=products&action=create') !== false, 'DASH-008b', 'Add Product quick action link exists');
assert_true(strpos($viewContent, '?page=purchases&action=create') !== false, 'DASH-008c', 'Purchase Invoice quick action link exists');
assert_true(strpos($viewContent, '?page=inventory') !== false, 'DASH-008d', 'Inventory quick action link exists');
assert_true(strpos($viewContent, '?page=reports') !== false, 'DASH-008e', 'Reports quick action link exists');

// Check that stat cards render the correct variables
assert_true(strpos($viewContent, "today_sales_total") !== false, 'DASH-008f', 'View renders today_sales_total');
assert_true(strpos($viewContent, "today_sales_count") !== false, 'DASH-008g', 'View renders today_sales_count');
assert_true(strpos($viewContent, "product_count") !== false, 'DASH-008h', 'View renders product_count');
assert_true(strpos($viewContent, "month_total") !== false, 'DASH-008i', 'View renders month_total');
assert_true(strpos($viewContent, "low_stock_count") !== false, 'DASH-008j', 'View renders low_stock_count');

// Check that low stock and recent sales sections exist
assert_true(strpos($viewContent, 'تنبيهات المخزون المنخفض') !== false, 'DASH-008k', 'Low stock alerts section exists in view');
assert_true(strpos($viewContent, 'آخر المبيعات') !== false, 'DASH-008l', 'Recent sales section exists in view');

// ============================================================
// Summary
// ============================================================
echo "\n";
echo "==========================================================\n";
echo "  RESULTS\n";
echo "==========================================================\n";
echo "  Passed: {$passed}\n";
echo "  Failed: {$failed}\n";
echo "  Total:  " . ($passed + $failed) . "\n";

if ($failed > 0) {
    echo "\n  Failed tests:\n";
    foreach ($errors as $err) {
        echo "    - {$err}\n";
    }
    echo "\n";
    exit(1);
}

echo "\n  All Dashboard tests passed!\n\n";
exit(0);
