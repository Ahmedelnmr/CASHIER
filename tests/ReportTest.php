<?php
/**
 * Reports Module Tests
 * Tests: RPT-001 to RPT-020
 *
 * Run: php tests/ReportTest.php
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

function assert_false($value, $testId, $description) {
    assert_true(!$value, $testId, $description);
}

function assert_not_null($value, $testId, $description) {
    assert_true($value !== null && $value !== false, $testId, $description);
}

function assert_null($value, $testId, $description) {
    assert_true($value === null || $value === false, $testId, $description);
}

function assert_greater_than($value, $threshold, $testId, $description) {
    assert_true($value > $threshold, $testId, $description . " (value={$value}, threshold={$threshold})");
}

function assert_less_than($value, $threshold, $testId, $description) {
    assert_true($value < $threshold, $testId, $description . " (value={$value}, threshold={$threshold})");
}

// ============================================================
// Database Setup
// ============================================================
function createTestDb() {
    $pdo = new PDO('sqlite:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');

    $migrationsDir = __DIR__ . '/../www/app/database/migrations/';
    $files = glob($migrationsDir . '*.sql');
    sort($files);
    foreach ($files as $file) {
        $sql = file_get_contents($file);
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false &&
                strpos($e->getMessage(), 'duplicate column') === false) {
                throw $e;
            }
        }
    }
    return $pdo;
}

// ============================================================
// Helper Functions
// ============================================================
function createProduct($pdo, $name, $barcode, $type = 'unit', $purchasePrice = 10, $salePrice = 20, $stock = 100, $minStock = 10, $category = null) {
    $stmt = $pdo->prepare(
        "INSERT INTO products (name, barcode, type, purchase_price, sale_price_unit, stock_quantity, min_stock, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$name, $barcode, $type, $purchasePrice, $salePrice, $stock, $minStock, $category]);
    return $pdo->lastInsertId();
}

function createSaleRecord($pdo, $total, $discount = 0, $type = 'sale', $status = 'completed', $datetime = null, $shiftId = null) {
    if (!$datetime) $datetime = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare(
        "INSERT INTO sales (total, discount, type, status, datetime, shift_id) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$total, $discount, $type, $status, $datetime, $shiftId]);
    return $pdo->lastInsertId();
}

function createSaleItem($pdo, $saleId, $productId, $productName, $quantity, $price, $subtotal) {
    $stmt = $pdo->prepare(
        "INSERT INTO sale_items (sale_id, product_id, product_name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$saleId, $productId, $productName, $quantity, $price, $subtotal]);
    return $pdo->lastInsertId();
}

function createSafeTransaction($pdo, $type, $amount, $date = null, $description = '') {
    if (!$date) $date = date('Y-m-d');
    $stmt = $pdo->prepare(
        "INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$type, $amount, $date, $description]);
    return $pdo->lastInsertId();
}

function createSupplier($pdo, $name) {
    $stmt = $pdo->prepare("INSERT INTO suppliers (name) VALUES (?)");
    $stmt->execute([$name]);
    return $pdo->lastInsertId();
}

function createPurchaseInvoice($pdo, $supplierId, $total, $date = null, $status = 'completed', $type = 'purchase') {
    if (!$date) $date = date('Y-m-d');
    $stmt = $pdo->prepare(
        "INSERT INTO purchase_invoices (supplier_id, total, date, status, type) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$supplierId, $total, $date, $status, $type]);
    return $pdo->lastInsertId();
}

function createCustomer($pdo, $name, $balance = 0) {
    $stmt = $pdo->prepare("INSERT INTO customers (name, balance) VALUES (?, ?)");
    $stmt->execute([$name, $balance]);
    return $pdo->lastInsertId();
}

function createUser($pdo, $username, $name, $role = 'cashier') {
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, name, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, password_hash('test123', PASSWORD_DEFAULT), $name, $role]);
    return $pdo->lastInsertId();
}

function createShift($pdo, $userId, $openingCash = 0) {
    $stmt = $pdo->prepare("INSERT INTO shifts (user_id, opening_cash) VALUES (?, ?)");
    $stmt->execute([$userId, $openingCash]);
    return $pdo->lastInsertId();
}

// ============================================================
// Replicate ReportService methods (using half-open intervals)
// ============================================================
function dateBoundaries($dateFrom, $dateTo) {
    $start = $dateFrom . ' 00:00:00';
    $endExclusive = date('Y-m-d', strtotime($dateTo . ' +1 day')) . ' 00:00:00';
    return [$start, $endExclusive];
}

function dailySales($pdo, $date = null) {
    $date = $date ?: date('Y-m-d');
    [$start, $endExclusive] = dateBoundaries($date, $date);
    $stmt = $pdo->prepare(
        "SELECT s.id, s.sale_number, s.datetime, s.total, s.payment_method,
                COUNT(si.id) as item_count
         FROM sales s
         LEFT JOIN sale_items si ON s.id = si.sale_id
         WHERE s.datetime >= :start AND s.datetime < :end
           AND s.status != 'voided'
           AND s.type = 'sale'
         GROUP BY s.id
         ORDER BY s.datetime DESC"
    );
    $stmt->execute([':start' => $start, ':end' => $endExclusive]);
    $sales = $stmt->fetchAll();

    $summaryStmt = $pdo->prepare(
        "SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total,
                COALESCE(SUM(discount), 0) as discount
         FROM sales
         WHERE datetime >= :start AND datetime < :end
           AND status != 'voided'
           AND type = 'sale'"
    );
    $summaryStmt->execute([':start' => $start, ':end' => $endExclusive]);
    $summary = $summaryStmt->fetch();

    return ['sales' => $sales, 'summary' => $summary, 'date' => $date];
}

function salesByRange($pdo, $dateFrom, $dateTo) {
    [$start, $endExclusive] = dateBoundaries($dateFrom, $dateTo);
    $stmt = $pdo->prepare(
        "SELECT date(datetime) as sale_date, COUNT(*) as count, SUM(total) as total
         FROM sales
         WHERE datetime >= :start AND datetime < :end
           AND status != 'voided'
           AND type = 'sale'
         GROUP BY date(datetime)
         ORDER BY sale_date DESC"
    );
    $stmt->execute([':start' => $start, ':end' => $endExclusive]);
    $daily = $stmt->fetchAll();

    $totalStmt = $pdo->prepare(
        "SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total
         FROM sales
         WHERE datetime >= :start AND datetime < :end
           AND status != 'voided'
           AND type = 'sale'"
    );
    $totalStmt->execute([':start' => $start, ':end' => $endExclusive]);
    $totals = $totalStmt->fetch();

    return ['daily' => $daily, 'totals' => $totals];
}

function topProducts($pdo, $limit = 20, $dateFrom = null, $dateTo = null) {
    $sql = "SELECT si.product_id, si.product_name,
                   SUM(si.quantity) as total_qty, SUM(si.subtotal) as total_revenue,
                   COUNT(DISTINCT si.sale_id) as sale_count
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            WHERE s.status != 'voided' AND s.type = 'sale'";
    $params = [];

    if ($dateFrom) {
        [$start, $endExclusive] = dateBoundaries($dateFrom, $dateTo ?: $dateFrom);
        $sql .= " AND s.datetime >= :start AND s.datetime < :end";
        $params[':start'] = $start;
        $params[':end'] = $endExclusive;
    }

    $sql .= " GROUP BY si.product_id, si.product_name
               ORDER BY total_qty DESC LIMIT :limit";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function leastProducts($pdo, $limit = 20) {
    $stmt = $pdo->prepare(
        "SELECT p.id, p.name, p.stock_quantity,
                COALESCE(SUM(si.quantity), 0) as total_sold
         FROM products p
         LEFT JOIN sale_items si ON p.id = si.product_id
         LEFT JOIN sales s ON si.sale_id = s.id AND s.status != 'voided' AND s.type = 'sale'
         WHERE p.is_active = 1
         GROUP BY p.id
         ORDER BY total_sold ASC
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
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

function purchasesBySupplier($pdo, $dateFrom = null, $dateTo = null) {
    $sql = "SELECT s.id, s.name, COUNT(pi.id) as invoice_count, COALESCE(SUM(pi.total), 0) as total
            FROM suppliers s
            LEFT JOIN purchase_invoices pi ON s.id = pi.supplier_id AND pi.status != 'voided' AND pi.type = 'purchase'
            WHERE s.is_active = 1";
    $params = [];

    if ($dateFrom) {
        $endExclusive = date('Y-m-d', strtotime(($dateTo ?: $dateFrom) . ' +1 day'));
        $sql .= " AND pi.date >= :start AND pi.date < :end";
        $params[':start'] = $dateFrom;
        $params[':end'] = $endExclusive;
    }

    $sql .= " GROUP BY s.id ORDER BY total DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function stockMovements($pdo, $productId = null, $type = '', $dateFrom = null, $dateTo = null, $limit = 100) {
    $sql = "SELECT sm.*, p.name as product_name 
            FROM stock_movements sm 
            LEFT JOIN products p ON sm.product_id = p.id 
            WHERE 1=1";
    $params = [];

    if ($productId) {
        $sql .= " AND sm.product_id = :product_id";
        $params[':product_id'] = $productId;
    }
    if ($type) {
        $sql .= " AND sm.type = :type";
        $params[':type'] = $type;
    }
    if ($dateFrom) {
        $endExclusive = date('Y-m-d', strtotime(($dateTo ?: $dateFrom) . ' +1 day'));
        $sql .= " AND sm.created_at >= :start AND sm.created_at < :end";
        $params[':start'] = $dateFrom;
        $params[':end'] = $endExclusive;
    }

    $sql .= " ORDER BY sm.created_at DESC LIMIT :limit";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function financialReport($pdo, $dateFrom, $dateTo) {
    [$start, $endExclusive] = dateBoundaries($dateFrom, $dateTo);
    $safeEndExclusive = date('Y-m-d', strtotime($dateTo . ' +1 day'));

    // Sales
    $salesStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(total), 0) as total, COALESCE(SUM(discount), 0) as discounts
         FROM sales
         WHERE datetime >= :start AND datetime < :end
           AND status != 'voided'
           AND type = 'sale'"
    );
    $salesStmt->execute([':start' => $start, ':end' => $endExclusive]);
    $salesRow = $salesStmt->fetch();
    $totalSales = floatval($salesRow['total']);

    // Returns
    $returnsStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(ABS(total)), 0) as total
         FROM sales
         WHERE datetime >= :start AND datetime < :end
           AND type = 'return'
           AND status != 'voided'"
    );
    $returnsStmt->execute([':start' => $start, ':end' => $endExclusive]);
    $totalReturns = floatval($returnsStmt->fetchColumn());

    // Deposits
    $depositStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(amount), 0) as total
         FROM safe_transactions
         WHERE date >= :start AND date < :end AND type IN ('deposit', 'opening')"
    );
    $depositStmt->execute([':start' => $dateFrom, ':end' => $safeEndExclusive]);
    $totalDeposit = floatval($depositStmt->fetchColumn());

    $netIncome = $totalSales - $totalReturns + $totalDeposit;

    // Purchases
    $purchaseStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(total), 0) as total
         FROM purchase_invoices
         WHERE date >= :start AND date < :end
           AND status != 'voided'
           AND type = 'purchase'"
    );
    $purchaseStmt->execute([':start' => $dateFrom, ':end' => $safeEndExclusive]);
    $totalPurchases = floatval($purchaseStmt->fetchColumn());

    // Expenses
    $expenseStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(amount), 0) as total
         FROM safe_transactions
         WHERE date >= :start AND date < :end AND type = 'expense'"
    );
    $expenseStmt->execute([':start' => $dateFrom, ':end' => $safeEndExclusive]);
    $totalExpenses = floatval($expenseStmt->fetchColumn());

    $netProfit = $netIncome - $totalPurchases - $totalExpenses;

    return [
        'totals' => [
            'sales' => $totalSales,
            'returns' => $totalReturns,
            'deposits' => $totalDeposit,
            'income' => $netIncome,
            'purchases' => $totalPurchases,
            'expenses' => $totalExpenses,
            'profit' => $netProfit,
        ]
    ];
}

function salesByCategory($pdo, $dateFrom = null, $dateTo = null) {
    $sql = "SELECT 
                COALESCE(p.category, 'بدون قسم') as category,
                COUNT(DISTINCT si.sale_id) as sale_count,
                SUM(si.quantity) as total_qty,
                SUM(si.subtotal) as total_revenue
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            LEFT JOIN products p ON si.product_id = p.id
            WHERE s.status != 'voided' AND s.type = 'sale'";
    $params = [];
    if ($dateFrom) {
        [$start, $endExclusive] = dateBoundaries($dateFrom, $dateTo ?: $dateFrom);
        $sql .= " AND s.datetime >= :start AND s.datetime < :end";
        $params[':start'] = $start;
        $params[':end'] = $endExclusive;
    }
    $sql .= " GROUP BY COALESCE(p.category, 'بدون قسم') ORDER BY total_revenue DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function productProfitMargin($pdo, $dateFrom = null, $dateTo = null, $limit = 30) {
    $sql = "SELECT 
                si.product_id,
                si.product_name,
                SUM(si.quantity) as total_qty,
                SUM(si.subtotal) as total_revenue,
                COALESCE(p.purchase_price, 0) as avg_cost,
                SUM(si.quantity) * COALESCE(p.purchase_price, 0) as total_cost,
                SUM(si.subtotal) - (SUM(si.quantity) * COALESCE(p.purchase_price, 0)) as profit,
                CASE WHEN SUM(si.subtotal) > 0 
                    THEN ((SUM(si.subtotal) - (SUM(si.quantity) * COALESCE(p.purchase_price, 0))) / SUM(si.subtotal)) * 100 
                    ELSE 0 END as margin_pct
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            LEFT JOIN products p ON si.product_id = p.id
            WHERE s.status != 'voided' AND s.type = 'sale'";
    $params = [];
    if ($dateFrom) {
        [$start, $endExclusive] = dateBoundaries($dateFrom, $dateTo ?: $dateFrom);
        $sql .= " AND s.datetime >= :start AND s.datetime < :end";
        $params[':start'] = $start;
        $params[':end'] = $endExclusive;
    }
    $sql .= " GROUP BY si.product_id, si.product_name ORDER BY profit DESC LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function deadStock($pdo, $days = 30) {
    $stmt = $pdo->prepare(
        "SELECT p.id, p.name, p.barcode, p.stock_quantity, p.purchase_price, p.sale_price_unit,
                p.category, p.type,
                COALESCE(MAX(s.datetime), 'لم يُبَع') as last_sale_date,
                COALESCE(SUM(si.quantity), 0) as total_sold,
                (p.stock_quantity * p.purchase_price) as stock_value
         FROM products p
         LEFT JOIN sale_items si ON p.id = si.product_id
         LEFT JOIN sales s ON si.sale_id = s.id AND s.status != 'voided' AND s.type = 'sale'
         WHERE p.is_active = 1 AND p.stock_quantity > 0
         GROUP BY p.id
         HAVING last_sale_date = 'لم يُبَع' OR MAX(s.datetime) < date('now', '-' || ? || ' days')
         ORDER BY stock_value DESC"
    );
    $stmt->execute([$days]);
    return $stmt->fetchAll();
}

function cashierPerformance($pdo, $dateFrom = null, $dateTo = null) {
    $sql = "SELECT 
                u.id as user_id,
                u.name as cashier_name,
                COUNT(s.id) as sale_count,
                COALESCE(SUM(s.total), 0) as total_sales,
                COALESCE(SUM(s.discount), 0) as total_discounts,
                COALESCE(AVG(s.total), 0) as avg_sale
            FROM users u
            LEFT JOIN sales s ON s.shift_id IN (SELECT id FROM shifts WHERE user_id = u.id)
                AND s.status != 'voided' AND s.type = 'sale'";
    $params = [];
    if ($dateFrom) {
        [$start, $endExclusive] = dateBoundaries($dateFrom, $dateTo ?: $dateFrom);
        $sql .= " AND s.datetime >= :start AND s.datetime < :end";
        $params[':start'] = $start;
        $params[':end'] = $endExclusive;
    }
    $sql .= " GROUP BY u.id, u.name HAVING sale_count > 0 ORDER BY total_sales DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function customerDebtors($pdo) {
    $stmt = $pdo->prepare(
        "SELECT id, name, phone, balance, credit_limit, created_at
         FROM customers WHERE balance > 0 AND is_active = 1
         ORDER BY balance DESC"
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

// ============================================================
// Run Tests
// ============================================================
echo "\n";
echo "==========================================================\n";
echo "  Reports Module Tests (RPT-001 to RPT-020)\n";
echo "==========================================================\n\n";

$pdo = createTestDb();

// Setup test data
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$lastWeek = date('Y-m-d', strtotime('-7 days'));

// Create products
$prod1 = createProduct($pdo, 'Product A', '111', 'unit', 10, 20, 100, 10, 'Category1');
$prod2 = createProduct($pdo, 'Product B', '222', 'unit', 15, 30, 5, 10, 'Category1');  // Low stock
$prod3 = createProduct($pdo, 'Product C', '333', 'unit', 5, 15, 200, 10, 'Category2');
$prod4 = createProduct($pdo, 'Product D', '444', 'unit', 8, 25, 0, 10, 'Category2');   // Zero stock

// Create users and shifts
$userId = createUser($pdo, 'cashier1', 'Cashier One', 'cashier');
$shiftId = createShift($pdo, $userId, 500);

// Create sales for today
$sale1 = createSaleRecord($pdo, 200, 10, 'sale', 'completed', $today . ' 10:00:00', $shiftId);
createSaleItem($pdo, $sale1, $prod1, 'Product A', 5, 20, 100);
createSaleItem($pdo, $sale1, $prod2, 'Product B', 3, 30, 90);

$sale2 = createSaleRecord($pdo, 150, 0, 'sale', 'completed', $today . ' 14:00:00', $shiftId);
createSaleItem($pdo, $sale2, $prod3, 'Product C', 10, 15, 150);

// Create a return for today
$return1 = createSaleRecord($pdo, -30, 0, 'return', 'completed', $today . ' 16:00:00', $shiftId);
createSaleItem($pdo, $return1, $prod1, 'Product A', -1, 20, -20);

// Create a voided sale (should be excluded)
$voided = createSaleRecord($pdo, 100, 0, 'sale', 'voided', $today . ' 18:00:00', $shiftId);

// Create sales for yesterday
$sale3 = createSaleRecord($pdo, 300, 20, 'sale', 'completed', $yesterday . ' 11:00:00', $shiftId);
createSaleItem($pdo, $sale3, $prod1, 'Product A', 10, 20, 200);
createSaleItem($pdo, $sale3, $prod2, 'Product B', 4, 30, 120);

// Create safe transactions
createSafeTransaction($pdo, 'expense', 50, $today, 'Office supplies');
createSafeTransaction($pdo, 'deposit', 100, $today, 'Manual deposit');
createSafeTransaction($pdo, 'expense', 30, $yesterday, 'Transport');

// Create supplier and purchase
$supplierId = createSupplier($pdo, 'Supplier A');
$purchase1 = createPurchaseInvoice($pdo, $supplierId, 500, $today);

// Create stock movements
$stmt = $pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, created_at) VALUES (?, 'sale', -5, ?)");
$stmt->execute([$prod1, $today . ' 10:00:00']);
$stmt->execute([$prod2, $today . ' 10:00:00']);

// Create customers with balances
$cust1 = createCustomer($pdo, 'Customer A', 500);
$cust2 = createCustomer($pdo, 'Customer B', 200);
$cust3 = createCustomer($pdo, 'Customer C', 0);  // No debt

// ----------------------------------------------------------
// RPT-001: Daily report
// ----------------------------------------------------------
echo "--- RPT-001: Daily report ---\n";

$result = dailySales($pdo, $today);
assert_not_null($result, 'RPT-001', 'Daily report generated');
assert_equals($today, $result['date'], 'RPT-001b', 'Report date is today');
assert_not_null($result['sales'], 'RPT-001c', 'Report has sales array');
assert_not_null($result['summary'], 'RPT-001d', 'Report has summary');

// Should have 2 completed sales (not voided, not returns)
assert_equals(2, count($result['sales']), 'RPT-001e', 'Daily report shows 2 completed sales');

// Summary
$summary = $result['summary'];
assert_equals(2, intval($summary['count']), 'RPT-001f', 'Summary count is 2');
assert_equals(350.0, floatval($summary['total']), 'RPT-001g', 'Summary total is 350 (200+150)');

// ----------------------------------------------------------
// RPT-002: Daily for specific date
// ----------------------------------------------------------
echo "\n--- RPT-002: Daily for specific date ---\n";

$result = dailySales($pdo, $yesterday);
assert_equals($yesterday, $result['date'], 'RPT-002', 'Report date is yesterday');
assert_equals(1, count($result['sales']), 'RPT-002b', 'Yesterday has 1 sale');
assert_equals(300.0, floatval($result['summary']['total']), 'RPT-002c', 'Yesterday total is 300');

// ----------------------------------------------------------
// RPT-003: Daily with no sales
// ----------------------------------------------------------
echo "\n--- RPT-003: Daily with no sales ---\n";

$noSalesDate = '2020-01-01';
$result = dailySales($pdo, $noSalesDate);
assert_equals(0, count($result['sales']), 'RPT-003', 'No sales for empty date');
assert_equals(0, intval($result['summary']['count']), 'RPT-003b', 'Summary count is 0');
assert_equals(0.0, floatval($result['summary']['total']), 'RPT-003c', 'Summary total is 0');

// ----------------------------------------------------------
// RPT-004: Weekly report
// ----------------------------------------------------------
echo "\n--- RPT-004: Weekly report ---\n";

$result = salesByRange($pdo, $lastWeek, $today);
assert_not_null($result, 'RPT-004', 'Weekly report generated');
assert_not_null($result['daily'], 'RPT-004b', 'Report has daily breakdown');
assert_not_null($result['totals'], 'RPT-004c', 'Report has totals');

// Should include sales from today and yesterday
assert_greater_than(count($result['daily']), 0, 'RPT-004d', 'Weekly report has daily entries');
assert_greater_than(floatval($result['totals']['total']), 0, 'RPT-004e', 'Weekly total is positive');

// ----------------------------------------------------------
// RPT-005: Monthly report
// ----------------------------------------------------------
echo "\n--- RPT-005: Monthly report ---\n";

$monthStart = date('Y-m-01');
$result = salesByRange($pdo, $monthStart, $today);
assert_not_null($result, 'RPT-005', 'Monthly report generated');
assert_greater_than(floatval($result['totals']['total']), 0, 'RPT-005b', 'Monthly total is positive');

// ----------------------------------------------------------
// RPT-006: Custom date range
// ----------------------------------------------------------
echo "\n--- RPT-006: Custom date range ---\n";

$result = salesByRange($pdo, $yesterday, $today);
assert_not_null($result, 'RPT-006', 'Custom range report generated');
// Should include sales from both days
$allDates = array_column($result['daily'], 'sale_date');
assert_true(in_array($today, $allDates), 'RPT-006b', 'Range includes today');
assert_true(in_array($yesterday, $allDates), 'RPT-006c', 'Range includes yesterday');

// ----------------------------------------------------------
// RPT-007: Single day range
// ----------------------------------------------------------
echo "\n--- RPT-007: Single day range ---\n";

$result = salesByRange($pdo, $today, $today);
assert_equals(1, count($result['daily']), 'RPT-007', 'Single day range returns 1 day');
assert_equals($today, $result['daily'][0]['sale_date'], 'RPT-007b', 'Correct date returned');

// ----------------------------------------------------------
// RPT-008: Top products
// ----------------------------------------------------------
echo "\n--- RPT-008: Top products ---\n";

$result = topProducts($pdo, 20);
assert_greater_than(count($result), 0, 'RPT-008', 'Top products returned');

// Product A should be top (sold 15 units: 5+10-1 returns, but returns might not count)
$topProduct = $result[0];
assert_not_null($topProduct['product_id'], 'RPT-008b', 'Top product has product_id');
assert_not_null($topProduct['product_name'], 'RPT-008c', 'Top product has product_name');
assert_not_null($topProduct['total_qty'], 'RPT-008d', 'Top product has total_qty');
assert_not_null($topProduct['total_revenue'], 'RPT-008e', 'Top product has total_revenue');

// ----------------------------------------------------------
// RPT-009: Least products
// ----------------------------------------------------------
echo "\n--- RPT-009: Least products ---\n";

$result = leastProducts($pdo, 20);
assert_greater_than(count($result), 0, 'RPT-009', 'Least products returned');

// Product D (0 stock, no sales) should be at top
$leastProduct = $result[0];
assert_equals($prod4, $leastProduct['id'], 'RPT-009b', 'Least product is Product D (no sales)');

// ----------------------------------------------------------
// RPT-010: Product profit margins
// ----------------------------------------------------------
echo "\n--- RPT-010: Product profit margins ---\n";

$result = productProfitMargin($pdo, null, null, 30);
assert_greater_than(count($result), 0, 'RPT-010', 'Profit margins calculated');

// Check profit fields exist
$first = $result[0];
assert_true(isset($first['profit']), 'RPT-010b', 'Result has profit field');
assert_true(isset($first['margin_pct']), 'RPT-010c', 'Result has margin_pct field');
assert_true(isset($first['total_revenue']), 'RPT-010d', 'Result has total_revenue field');
assert_true(isset($first['total_cost']), 'RPT-010e', 'Result has total_cost field');

// ----------------------------------------------------------
// RPT-011: Dead stock
// ----------------------------------------------------------
echo "\n--- RPT-011: Dead stock ---\n";

$result = deadStock($pdo, 30);
assert_not_null($result, 'RPT-011', 'Dead stock report generated');

// Product D has stock=0, so it's excluded (dead stock requires stock > 0)
// Product C has stock=200 and was sold today, so it's not dead stock
// Products with no sales and stock > 0 would appear here
$deadNames = array_column($result, 'name');
assert_false(in_array('Product D', $deadNames), 'RPT-011b', 'Product D not in dead stock (stock=0)');

// ----------------------------------------------------------
// RPT-012: Low stock report
// ----------------------------------------------------------
echo "\n--- RPT-012: Low stock report ---\n";

$result = lowStock($pdo);
assert_not_null($result, 'RPT-012', 'Low stock report generated');

// Product B has stock=5, min_stock=10, should be low
$lowNames = array_column($result, 'name');
assert_true(in_array('Product B', $lowNames), 'RPT-012b', 'Product B in low stock (5 < 10)');

// Product A has stock=100, min_stock=10, should NOT be low
assert_false(in_array('Product A', $lowNames), 'RPT-012c', 'Product A not in low stock (100 > 10)');

// ----------------------------------------------------------
// RPT-013: Supplier purchases
// ----------------------------------------------------------
echo "\n--- RPT-013: Supplier purchases ---\n";

$result = purchasesBySupplier($pdo);
assert_greater_than(count($result), 0, 'RPT-013', 'Supplier purchases returned');

$supplier = $result[0];
assert_equals('Supplier A', $supplier['name'], 'RPT-013b', 'Supplier name correct');
assert_equals(1, intval($supplier['invoice_count']), 'RPT-013c', 'Invoice count is 1');
assert_equals(500.0, floatval($supplier['total']), 'RPT-013d', 'Supplier total is 500');

// ----------------------------------------------------------
// RPT-014: Stock movements
// ----------------------------------------------------------
echo "\n--- RPT-014: Stock movements ---\n";

$result = stockMovements($pdo);
assert_greater_than(count($result), 0, 'RPT-014', 'Stock movements returned');

// Check movement fields
$first = $result[0];
assert_true(isset($first['product_id']), 'RPT-014b', 'Movement has product_id');
assert_true(isset($first['type']), 'RPT-014c', 'Movement has type');
assert_true(isset($first['quantity']), 'RPT-014d', 'Movement has quantity');
assert_true(isset($first['product_name']), 'RPT-014e', 'Movement has product_name');

// Filter by product
$result = stockMovements($pdo, $prod1);
foreach ($result as $mov) {
    assert_equals($prod1, $mov['product_id'], 'RPT-014f', 'Filtered movements are for correct product');
}

// ----------------------------------------------------------
// RPT-015: Financial P&L
// ----------------------------------------------------------
echo "\n--- RPT-015: Financial P&L ---\n";

$result = financialReport($pdo, $lastWeek, $today);
assert_not_null($result, 'RPT-015', 'Financial report generated');
assert_not_null($result['totals'], 'RPT-015b', 'Report has totals');

$totals = $result['totals'];
assert_true(isset($totals['sales']), 'RPT-015c', 'Totals has sales');
assert_true(isset($totals['returns']), 'RPT-015d', 'Totals has returns');
assert_true(isset($totals['income']), 'RPT-015e', 'Totals has income');
assert_true(isset($totals['purchases']), 'RPT-015f', 'Totals has purchases');
assert_true(isset($totals['expenses']), 'RPT-015g', 'Totals has expenses');
assert_true(isset($totals['profit']), 'RPT-015h', 'Totals has profit');

// Verify calculations
$expectedIncome = $totals['sales'] - $totals['returns'] + $totals['deposits'];
assert_equals($expectedIncome, $totals['income'], 'RPT-015i', 'Income = sales - returns + deposits');

$expectedProfit = $totals['income'] - $totals['purchases'] - $totals['expenses'];
assert_equals($expectedProfit, $totals['profit'], 'RPT-015j', 'Profit = income - purchases - expenses');

// ----------------------------------------------------------
// RPT-016: Financial date range
// ----------------------------------------------------------
echo "\n--- RPT-016: Financial date range ---\n";

// Today only
$resultToday = financialReport($pdo, $today, $today);
assert_not_null($resultToday, 'RPT-016', 'Today financial report generated');

// Yesterday only
$resultYesterday = financialReport($pdo, $yesterday, $yesterday);
assert_not_null($resultYesterday, 'RPT-016b', 'Yesterday financial report generated');

// Totals should be different
assert_true(
    $resultToday['totals']['sales'] !== $resultYesterday['totals']['sales'],
    'RPT-016c',
    'Different dates have different sales'
);

// ----------------------------------------------------------
// RPT-017: Financial accuracy
// ----------------------------------------------------------
echo "\n--- RPT-017: Financial accuracy ---\n";

// Manually calculate expected values
$manualSales = 200 + 150;  // Today's completed sales
$manualReturns = 30;        // Today's return
$manualDeposits = 100;      // Today's deposit
$manualIncome = $manualSales - $manualReturns + $manualDeposits;

$result = financialReport($pdo, $today, $today);
assert_equals(floatval($manualSales), $result['totals']['sales'], 'RPT-017', 'Sales match manual calculation');
assert_equals(floatval($manualReturns), $result['totals']['returns'], 'RPT-017b', 'Returns match manual calculation');
assert_equals(floatval($manualDeposits), $result['totals']['deposits'], 'RPT-017c', 'Deposits match manual calculation');
assert_equals(floatval($manualIncome), $result['totals']['income'], 'RPT-017d', 'Income matches manual calculation');

// ----------------------------------------------------------
// RPT-018: Sales by category
// ----------------------------------------------------------
echo "\n--- RPT-018: Sales by category ---\n";

$result = salesByCategory($pdo);
assert_greater_than(count($result), 0, 'RPT-018', 'Category breakdown returned');

// Check category fields
$first = $result[0];
assert_true(isset($first['category']), 'RPT-018b', 'Result has category');
assert_true(isset($first['sale_count']), 'RPT-018c', 'Result has sale_count');
assert_true(isset($first['total_qty']), 'RPT-018d', 'Result has total_qty');
assert_true(isset($first['total_revenue']), 'RPT-018e', 'Result has total_revenue');

// ----------------------------------------------------------
// RPT-019: Cashier performance
// ----------------------------------------------------------
echo "\n--- RPT-019: Cashier performance ---\n";

$result = cashierPerformance($pdo);
assert_greater_than(count($result), 0, 'RPT-019', 'Cashier performance returned');

$cashier = $result[0];
assert_true(isset($cashier['user_id']), 'RPT-019b', 'Result has user_id');
assert_true(isset($cashier['cashier_name']), 'RPT-019c', 'Result has cashier_name');
assert_true(isset($cashier['sale_count']), 'RPT-019d', 'Result has sale_count');
assert_true(isset($cashier['total_sales']), 'RPT-019e', 'Result has total_sales');

// ----------------------------------------------------------
// RPT-020: Customer debtors
// ----------------------------------------------------------
echo "\n--- RPT-020: Customer debtors ---\n";

$result = customerDebtors($pdo);
assert_greater_than(count($result), 0, 'RPT-020', 'Debtors list returned');

// Customer A (500) and B (200) should be in list
$debtorNames = array_column($result, 'name');
assert_true(in_array('Customer A', $debtorNames), 'RPT-020b', 'Customer A in debtors');
assert_true(in_array('Customer B', $debtorNames), 'RPT-020c', 'Customer B in debtors');

// Customer C (0 balance) should NOT be in list
assert_false(in_array('Customer C', $debtorNames), 'RPT-020d', 'Customer C not in debtors (no balance)');

// Check debtor fields
$first = $result[0];
assert_true(isset($first['id']), 'RPT-020e', 'Debtor has id');
assert_true(isset($first['name']), 'RPT-020f', 'Debtor has name');
assert_true(isset($first['balance']), 'RPT-020g', 'Debtor has balance');
assert_true(isset($first['credit_limit']), 'RPT-020h', 'Debtor has credit_limit');

// Verify ordering (highest balance first)
assert_equals(500.0, floatval($result[0]['balance']), 'RPT-020i', 'Debtors ordered by balance DESC');

// ----------------------------------------------------------
// RPT-021: Date boundary — Same-day sales
// ----------------------------------------------------------
echo "\n--- RPT-021: Date boundary — Same-day sales ---\n";

$boundaryDate = '2026-08-31';
createSaleRecord($pdo, 100, 0, 'sale', 'completed', '2026-08-31 10:30:00', $shiftId);
createSaleRecord($pdo, 200, 0, 'sale', 'completed', '2026-08-31 14:00:00', $shiftId);

// Earlier tests created 2 sales on today (2026-08-31): 200 + 150 = 350
// Now we added 2 more: 100 + 200 = 300
$result = dailySales($pdo, $boundaryDate);
assert_equals(4, count($result['sales']), 'RPT-021a', 'Same-day: 4 completed sales (2 earlier + 2 new)');
assert_equals(650.0, floatval($result['summary']['total']), 'RPT-021b', 'Same-day: total is 650 (350+300)');

// ----------------------------------------------------------
// RPT-022: Date boundary — Midnight sale
// ----------------------------------------------------------
echo "\n--- RPT-022: Date boundary — Midnight sale ---\n";

createSaleRecord($pdo, 50, 0, 'sale', 'completed', '2026-08-31 00:00:00', $shiftId);

$result = dailySales($pdo, $boundaryDate);
assert_equals(5, count($result['sales']), 'RPT-022a', 'Midnight sale at 00:00:00 is included');

// ----------------------------------------------------------
// RPT-023: Date boundary — End of day sale
// ----------------------------------------------------------
echo "\n--- RPT-023: Date boundary — End of day sale ---\n";

createSaleRecord($pdo, 75, 0, 'sale', 'completed', '2026-08-31 23:59:59', $shiftId);

$result = dailySales($pdo, $boundaryDate);
assert_equals(6, count($result['sales']), 'RPT-023a', 'End-of-day sale at 23:59:59 is included');

// ----------------------------------------------------------
// RPT-024: Date boundary — Previous day excluded
// ----------------------------------------------------------
echo "\n--- RPT-024: Date boundary — Previous day excluded ---\n";

createSaleRecord($pdo, 999, 0, 'sale', 'completed', '2026-08-30 23:59:59', $shiftId);

$result = dailySales($pdo, $boundaryDate);
assert_equals(6, count($result['sales']), 'RPT-024a', 'Previous day 23:59:59 excluded from Aug 31');

// ----------------------------------------------------------
// RPT-025: Date boundary — Next day excluded
// ----------------------------------------------------------
echo "\n--- RPT-025: Date boundary — Next day excluded ---\n";

createSaleRecord($pdo, 888, 0, 'sale', 'completed', '2026-09-01 00:00:00', $shiftId);

$result = dailySales($pdo, $boundaryDate);
assert_equals(6, count($result['sales']), 'RPT-025a', 'Next day 00:00:00 excluded from Aug 31');

// ----------------------------------------------------------
// RPT-026: Multi-day range
// ----------------------------------------------------------
echo "\n--- RPT-026: Multi-day range ---\n";

$result = salesByRange($pdo, '2026-08-30', '2026-08-31');
$allDates = array_column($result['daily'], 'sale_date');
assert_true(in_array('2026-08-30', $allDates), 'RPT-026a', 'Multi-day range includes Aug 30');
assert_true(in_array('2026-08-31', $allDates), 'RPT-026b', 'Multi-day range includes Aug 31');

// Aug 30 has 2 sales (300+999=1299), Aug 31 has 6 sales (200+150+100+200+50+75=775)
$totalInRange = floatval($result['totals']['total']);
assert_equals(2074.0, $totalInRange, 'RPT-026c', 'Multi-day range total = 1299 + 775 = 2074');

// ----------------------------------------------------------
// RPT-027: Empty range
// ----------------------------------------------------------
echo "\n--- RPT-027: Empty range ---\n";

$result = dailySales($pdo, '2020-01-01');
assert_equals(0, count($result['sales']), 'RPT-027a', 'Empty date returns 0 sales');
assert_equals(0, intval($result['summary']['count']), 'RPT-027b', 'Empty date summary count is 0');
assert_equals(0.0, floatval($result['summary']['total']), 'RPT-027c', 'Empty date summary total is 0');

$result = salesByRange($pdo, '2020-01-01', '2020-01-07');
assert_equals(0, count($result['daily']), 'RPT-027d', 'Empty range returns 0 daily entries');
assert_equals(0, intval($result['totals']['count']), 'RPT-027e', 'Empty range totals count is 0');

// ----------------------------------------------------------
// RPT-028: Range boundary — sale at start of range
// ----------------------------------------------------------
echo "\n--- RPT-028: Range boundary — sale at start of range ---\n";

$result = salesByRange($pdo, '2026-08-31', '2026-08-31');
$dates = array_column($result['daily'], 'sale_date');
assert_true(in_array('2026-08-31', $dates), 'RPT-028a', 'Single-day range includes sales from that day');
assert_equals(6, intval($result['totals']['count']), 'RPT-028b', 'Single-day range count matches daily sales');

// ----------------------------------------------------------
// RPT-029: Voided sales excluded from boundary tests
// ----------------------------------------------------------
echo "\n--- RPT-029: Voided sales excluded from boundary tests ---\n";

createSaleRecord($pdo, 500, 0, 'sale', 'voided', '2026-08-31 12:00:00', $shiftId);

$result = dailySales($pdo, $boundaryDate);
assert_equals(6, count($result['sales']), 'RPT-029a', 'Voided sale excluded from boundary date');

// ----------------------------------------------------------
// RPT-030: Returns excluded from daily sales count
// ----------------------------------------------------------
echo "\n--- RPT-030: Returns excluded from daily sales count ---\n";

createSaleRecord($pdo, -25, 0, 'return', 'completed', '2026-08-31 15:00:00', $shiftId);

$result = dailySales($pdo, $boundaryDate);
assert_equals(6, count($result['sales']), 'RPT-030a', 'Return excluded from daily sales list');
// Summary should still only count completed sales
assert_equals(6, intval($result['summary']['count']), 'RPT-030b', 'Summary excludes returns');

$pdo = null;

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

echo "\n  All Reports tests passed!\n\n";
exit(0);
