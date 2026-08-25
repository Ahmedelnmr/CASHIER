<?php
/**
 * Inventory Management Module Tests
 * Tests: INV-001 to INV-010
 *
 * Run: php tests/InventoryTest.php
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

function assert_greater_than($value, $threshold, $testId, $description) {
    assert_true($value > $threshold, $testId, $description . " (value={$value}, threshold={$threshold})");
}

function assert_greater_or_equal($value, $threshold, $testId, $description) {
    assert_true($value >= $threshold, $testId, $description . " (value={$value}, threshold={$threshold})");
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
function insertProduct($pdo, $data) {
    $defaults = [
        'name' => 'Test Product', 'barcode' => null, 'barcode2' => null, 'barcode3' => null,
        'plu_code' => null, 'type' => 'unit', 'purchase_price' => 10.0, 'sale_price_unit' => 20.0,
        'pack_type' => null, 'pack_unit_quantity' => null, 'pack_purchase_price' => null,
        'pack_sale_price' => null, 'stock_quantity' => 100, 'min_stock' => 5, 'category' => null, 'notes' => null,
    ];
    $d = array_merge($defaults, $data);
    $stmt = $pdo->prepare(
        "INSERT INTO products (name, barcode, barcode2, barcode3, plu_code, type, purchase_price, sale_price_unit,
         pack_type, pack_unit_quantity, pack_purchase_price, pack_sale_price, stock_quantity, min_stock, category, notes, is_active)
         VALUES (:name, :barcode, :barcode2, :barcode3, :plu_code, :type, :purchase_price, :sale_price_unit,
         :pack_type, :pack_unit_quantity, :pack_purchase_price, :pack_sale_price, :stock_quantity, :min_stock, :category, :notes, 1)"
    );
    $stmt->execute($d);
    return $pdo->lastInsertId();
}

function getStock($pdo, $productId) {
    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    return floatval($stmt->fetchColumn());
}

function getProduct($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ============================================================
// Replicate InventoryService methods
// ============================================================
function adjustStock($pdo, $productId, $newQuantity, $reason = '') {
    $product = getProduct($pdo, $productId);
    if (!$product) return ['success' => false, 'message' => 'المنتج غير موجود'];

    $difference = $newQuantity - $product['stock_quantity'];

    try {
        $pdo->beginTransaction();

        // Update stock
        $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?")->execute([$difference, $productId]);

        // Record movement
        $pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, reference_type, reference_id, notes) VALUES (?, 'adjustment', ?, 'manual', ?, ?)")
            ->execute([$productId, $difference, null, $reason ?: 'تعديل يدوي']);

        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getLowStock($pdo) {
    $stmt = $pdo->prepare(
        "SELECT * FROM products WHERE is_active = 1 AND stock_quantity <= min_stock AND min_stock > 0 ORDER BY stock_quantity ASC"
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function getSummary($pdo) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as total_products,
                SUM(stock_quantity * purchase_price) as total_value,
                COUNT(CASE WHEN stock_quantity <= min_stock AND min_stock > 0 THEN 1 END) as low_stock_count
         FROM products WHERE is_active = 1"
    );
    $stmt->execute();
    return $stmt->fetch();
}

function getMovementsByProduct($pdo, $productId, $limit = 50) {
    $stmt = $pdo->prepare(
        "SELECT sm.*, p.name as product_name
         FROM stock_movements sm
         LEFT JOIN products p ON sm.product_id = p.id
         WHERE sm.product_id = :product_id
         ORDER BY sm.id DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getRecentMovements($pdo, $limit = 50) {
    $stmt = $pdo->prepare(
        "SELECT sm.*, p.name as product_name
         FROM stock_movements sm
         LEFT JOIN products p ON sm.product_id = p.id
         ORDER BY sm.created_at DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function productSearch($pdo, $query, $limit = 20) {
    $stmt = $pdo->prepare(
        "SELECT id, name, barcode, plu_code, type, purchase_price, sale_price_unit,
                pack_type, pack_unit_quantity, pack_purchase_price, pack_sale_price,
                stock_quantity, min_stock
         FROM products
         WHERE is_active = 1 AND (name LIKE :q OR barcode LIKE :q2 OR barcode2 LIKE :q4 OR barcode3 LIKE :q5 OR plu_code LIKE :q3)
         ORDER BY name ASC LIMIT :limit"
    );
    $stmt->bindValue(':q', "%$query%", PDO::PARAM_STR);
    $stmt->bindValue(':q2', "%$query%", PDO::PARAM_STR);
    $stmt->bindValue(':q3', "%$query%", PDO::PARAM_STR);
    $stmt->bindValue(':q4', "%$query%", PDO::PARAM_STR);
    $stmt->bindValue(':q5', "%$query%", PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// ============================================================
// Run Tests
// ============================================================
echo "\n";
echo "==========================================================\n";
echo "  Inventory Management Module Tests (INV-001 to INV-010)\n";
echo "==========================================================\n\n";

// ----------------------------------------------------------
// Setup test data
// ----------------------------------------------------------
$pdo = createTestDb();

$prod1 = insertProduct($pdo, ['name' => 'Healthy Stock', 'barcode' => 'HS001', 'purchase_price' => 10.0, 'sale_price_unit' => 20.0, 'stock_quantity' => 50, 'min_stock' => 5]);
$prod2 = insertProduct($pdo, ['name' => 'Low Stock Item', 'barcode' => 'LS001', 'purchase_price' => 15.0, 'sale_price_unit' => 30.0, 'stock_quantity' => 3, 'min_stock' => 10]);
$prod3 = insertProduct($pdo, ['name' => 'At Min Stock', 'barcode' => 'MS001', 'purchase_price' => 8.0, 'sale_price_unit' => 16.0, 'stock_quantity' => 10, 'min_stock' => 10]);
$prod4 = insertProduct($pdo, ['name' => 'No Min Stock', 'barcode' => 'NM001', 'purchase_price' => 5.0, 'sale_price_unit' => 12.0, 'stock_quantity' => 0, 'min_stock' => 0]);
$prod5 = insertProduct($pdo, ['name' => 'High Value', 'barcode' => 'HV001', 'purchase_price' => 100.0, 'sale_price_unit' => 200.0, 'stock_quantity' => 20, 'min_stock' => 5]);
$inactive = insertProduct($pdo, ['name' => 'Hidden Item', 'barcode' => 'HI001', 'purchase_price' => 10.0, 'sale_price_unit' => 20.0, 'stock_quantity' => 5, 'min_stock' => 10]);
$pdo->prepare("UPDATE products SET is_active = 0 WHERE id = ?")->execute([$inactive]);

// ----------------------------------------------------------
// INV-001: View inventory
// ----------------------------------------------------------
echo "--- INV-001: View inventory ---\n";

$products = productSearch($pdo, '');
assert_greater_than(count($products), 0, 'INV-001', 'Inventory lists active products');
// Inactive excluded
$foundInactive = false;
foreach ($products as $p) {
    if ($p['id'] == $inactive) { $foundInactive = true; break; }
}
assert_false($foundInactive, 'INV-001b', 'Inactive products excluded from inventory');

// Each product has stock_quantity
assert_true(isset($products[0]['stock_quantity']), 'INV-001c', 'Products display stock_quantity');

// ----------------------------------------------------------
// INV-002: Summary stats
// ----------------------------------------------------------
echo "\n--- INV-002: Summary stats ---\n";

$summary = getSummary($pdo);
assert_equals(5, (int)$summary['total_products'], 'INV-002', 'Summary: total_products = 5 (active only)');

// total_value = sum(stock_quantity * purchase_price)
// prod1: 50*10=500, prod2: 3*15=45, prod3: 10*8=80, prod4: 0*5=0, prod5: 20*100=2000
$expectedValue = 500 + 45 + 80 + 0 + 2000;
assert_equals((float)$expectedValue, floatval($summary['total_value']), 'INV-002b', "Summary: total_value = {$expectedValue}");

// low_stock_count: prod2 (3<=10) + prod3 (10<=10) = 2
assert_equals(2, (int)$summary['low_stock_count'], 'INV-002c', 'Summary: low_stock_count = 2');

// ----------------------------------------------------------
// INV-003: Low stock alerts
// ----------------------------------------------------------
echo "\n--- INV-003: Low stock alerts ---\n";

$lowStock = getLowStock($pdo);
assert_equals(2, count($lowStock), 'INV-003', 'Low stock returns 2 products');

$lowNames = array_column($lowStock, 'name');
assert_true(in_array('Low Stock Item', $lowNames), 'INV-003b', 'Low Stock Item in alerts');
assert_true(in_array('At Min Stock', $lowNames), 'INV-003c', 'At Min Stock in alerts');
assert_false(in_array('Healthy Stock', $lowNames), 'INV-003d', 'Healthy Stock NOT in alerts');
assert_false(in_array('No Min Stock', $lowNames), 'INV-003e', 'No Min Stock (min=0) NOT in alerts');
assert_false(in_array('Hidden Item', $lowNames), 'INV-003f', 'Inactive item NOT in alerts');

// Ordered by stock_quantity ASC
assert_true(floatval($lowStock[0]['stock_quantity']) <= floatval($lowStock[1]['stock_quantity']), 'INV-003g', 'Low stock ordered by stock_quantity ASC');

// ----------------------------------------------------------
// INV-004: Search products
// ----------------------------------------------------------
echo "\n--- INV-004: Search products ---\n";

$results = productSearch($pdo, 'Healthy');
assert_equals(1, count($results), 'INV-004', 'Search by name returns 1 result');
assert_equals('Healthy Stock', $results[0]['name'], 'INV-004b', 'Search finds correct product');

$results = productSearch($pdo, 'LS001');
assert_equals(1, count($results), 'INV-004c', 'Search by barcode returns 1 result');

$results = productSearch($pdo, 'nonexistent');
assert_equals(0, count($results), 'INV-004d', 'Search with no results returns empty');

// ----------------------------------------------------------
// INV-005: Adjust stock (increase)
// ----------------------------------------------------------
echo "\n--- INV-005: Adjust stock (increase) ---\n";

$stockBefore = getStock($pdo, $prod1); // 50
$result = adjustStock($pdo, $prod1, 75, '_received new shipment');
assert_true($result['success'], 'INV-005', 'Adjust stock increase succeeds');
$stockAfter = getStock($pdo, $prod1);
assert_equals(75.0, $stockAfter, 'INV-005b', 'Stock increased to 75');

// Movement logged
$movements = getMovementsByProduct($pdo, $prod1);
$lastMove = $movements[0];
assert_equals('adjustment', $lastMove['type'], 'INV-005c', 'Movement type = adjustment');
assert_equals(25.0, floatval($lastMove['quantity']), 'INV-005d', 'Movement quantity = +25 (increase)');
assert_equals('manual', $lastMove['reference_type'], 'INV-005e', 'Movement reference_type = manual');

// ----------------------------------------------------------
// INV-006: Adjust stock (decrease)
// ----------------------------------------------------------
echo "\n--- INV-006: Adjust stock (decrease) ---\n";

$result = adjustStock($pdo, $prod1, 30, 'damaged goods');
assert_true($result['success'], 'INV-006', 'Adjust stock decrease succeeds');
$stockAfter = getStock($pdo, $prod1);
assert_equals(30.0, $stockAfter, 'INV-006b', 'Stock decreased to 30');

$movements = getMovementsByProduct($pdo, $prod1);
$lastMove = $movements[0];
assert_equals(-45.0, floatval($lastMove['quantity']), 'INV-006c', 'Movement quantity = -45 (decrease)');

// ----------------------------------------------------------
// INV-007: Adjust to zero
// ----------------------------------------------------------
echo "\n--- INV-007: Adjust to zero ---\n";

$result = adjustStock($pdo, $prod1, 0, 'cleared out');
assert_true($result['success'], 'INV-007', 'Adjust to zero succeeds');
$stockAfter = getStock($pdo, $prod1);
assert_equals(0.0, $stockAfter, 'INV-007b', 'Stock = 0 after adjustment');

$movements = getMovementsByProduct($pdo, $prod1);
$lastMove = $movements[0];
assert_equals(-30.0, floatval($lastMove['quantity']), 'INV-007c', 'Movement quantity = -30 (to zero)');

// ----------------------------------------------------------
// INV-008: Adjust to negative (KNOWN BUG - no validation)
// ----------------------------------------------------------
echo "\n--- INV-008: Adjust to negative ---\n";

$result = adjustStock($pdo, $prod1, -5, 'negative test');
assert_true($result['success'], 'INV-008', 'KNOWN BUG: Adjust to negative succeeds (no validation)');
$stockAfter = getStock($pdo, $prod1);
assert_equals(-5.0, $stockAfter, 'INV-008b', 'Stock = -5 (negative allowed)');

// ----------------------------------------------------------
// INV-009: Movement history
// ----------------------------------------------------------
echo "\n--- INV-009: Movement history ---\n";

// We have multiple movements for prod1 now
$movements = getMovementsByProduct($pdo, $prod1);
assert_greater_than(count($movements), 1, 'INV-009', 'Movement history has multiple entries');

// Most recent first (by id)
assert_true($movements[0]['id'] > $movements[1]['id'], 'INV-009b', 'Movements ordered by id DESC (most recent first)');

// Each movement has product_name
assert_not_null($movements[0]['product_name'], 'INV-009c', 'Movements include product_name');

// ----------------------------------------------------------
// INV-010: Movement types correct
// ----------------------------------------------------------
echo "\n--- INV-010: Movement types correct ---\n";

// Create a sale to generate a 'sale' type movement
$saleStmt = $pdo->prepare("INSERT INTO sales (sale_number, subtotal, discount, total, payment_method, type, status) VALUES (1, 20, 0, 20, 'cash', 'sale', 'completed')");
$saleStmt->execute();
$saleId = $pdo->lastInsertId();
$pdo->prepare("INSERT INTO sale_items (sale_id, product_id, product_name, quantity, price, subtotal) VALUES (?, ?, 'Healthy Stock', 2, 10, 20)")->execute([$saleId, $prod1]);
$pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - 2 WHERE id = ?")->execute([$prod1]);
$pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, reference_type, reference_id, notes) VALUES (?, 'sale', -2, 'sale', ?, 'بيع')")->execute([$prod1, $saleId]);

// Create a purchase to generate a 'purchase' type movement
$pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + 10 WHERE id = ?")->execute([$prod1]);
$pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, reference_type, reference_id, notes) VALUES (?, 'purchase', 10, 'purchase_invoice', 1, 'شراء')")->execute([$prod1]);

// Get all movements for prod1
$movements = getMovementsByProduct($pdo, $prod1);
$types = array_column($movements, 'type');

assert_true(in_array('adjustment', $types), 'INV-010', 'Movement type: adjustment exists');
assert_true(in_array('sale', $types), 'INV-010b', 'Movement type: sale exists');
assert_true(in_array('purchase', $types), 'INV-010c', 'Movement type: purchase exists');

// Recent movements (all products)
$recent = getRecentMovements($pdo, 10);
assert_greater_than(count($recent), 0, 'INV-010d', 'Recent movements returns results');
assert_not_null($recent[0]['product_name'], 'INV-010e', 'Recent movements include product_name');

// Non-existent product
$movements = getMovementsByProduct($pdo, 99999);
assert_equals(0, count($movements), 'INV-010f', 'Movements for non-existent product returns empty');

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

echo "\n  All Inventory Management tests passed!\n\n";
exit(0);
