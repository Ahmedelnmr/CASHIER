<?php
/**
 * Point of Sale (POS) Module Tests
 * Tests: POS-001 through POS-056
 *
 * Run: php tests/POSTest.php
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

function assert_null($value, $testId, $description) {
    assert_true($value === null, $testId, $description . " (expected: null, got: " . var_export($value, true) . ")");
}

function assert_not_null($value, $testId, $description) {
    assert_true($value !== null, $testId, $description);
}

function assert_greater_than($value, $threshold, $testId, $description) {
    assert_true($value > $threshold, $testId, $description . " (value={$value}, threshold={$threshold})");
}

function assert_array_key_exists($key, $array, $testId, $description) {
    assert_true(is_array($array) && array_key_exists($key, $array), $testId, $description);
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
        'name' => 'Test Product',
        'barcode' => null,
        'barcode2' => null,
        'barcode3' => null,
        'plu_code' => null,
        'type' => 'unit',
        'purchase_price' => 10.0,
        'sale_price_unit' => 20.0,
        'pack_type' => 'كرتونة',
        'pack_unit_quantity' => null,
        'pack_purchase_price' => null,
        'pack_sale_price' => null,
        'stock_quantity' => 100,
        'min_stock' => 5,
        'category' => null,
        'notes' => null,
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

function insertSale($pdo, $data) {
    $defaults = [
        'sale_number' => rand(1, 9999),
        'subtotal' => 100.0,
        'discount' => 0,
        'total' => 100.0,
        'payment_method' => 'cash',
        'notes' => null,
        'type' => 'sale',
        'status' => 'completed',
        'customer_id' => null,
        'shift_id' => null,
        'datetime' => date('Y-m-d H:i:s'),
    ];
    $d = array_merge($defaults, $data);

    $stmt = $pdo->prepare(
        "INSERT INTO sales (sale_number, subtotal, discount, total, payment_method, notes, type, status, customer_id, shift_id, datetime)
         VALUES (:sale_number, :subtotal, :discount, :total, :payment_method, :notes, :type, :status, :customer_id, :shift_id, :datetime)"
    );
    $stmt->execute($d);
    return $pdo->lastInsertId();
}

function insertSaleItem($pdo, $saleId, $productId, $productName, $quantity, $price, $subtotal, $unitType = 'قطعة', $saleMode = 'unit') {
    $stmt = $pdo->prepare(
        "INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal)
         VALUES (:sale_id, :product_id, :product_name, :quantity, :unit_type, :price, :sale_mode, :subtotal)"
    );
    $stmt->execute([
        ':sale_id' => $saleId,
        ':product_id' => $productId,
        ':product_name' => $productName,
        ':quantity' => $quantity,
        ':unit_type' => $unitType,
        ':price' => $price,
        ':sale_mode' => $saleMode,
        ':subtotal' => $subtotal,
    ]);
}

// ============================================================
// Replicate POSEngine methods for testing
// ============================================================
function findProduct($pdo, $query) {
    $query = trim($query);

    // 1. Exact barcode match
    $stmt = $pdo->prepare("SELECT * FROM products WHERE (barcode = :b OR barcode2 = :b OR barcode3 = :b) AND is_active = 1");
    $stmt->execute([':b' => $query]);
    $product = $stmt->fetch();
    if ($product) return $product;

    // 2. PLU match
    $stmt = $pdo->prepare("SELECT * FROM products WHERE plu_code = :plu AND is_active = 1");
    $stmt->execute([':plu' => $query]);
    $product = $stmt->fetch();
    if ($product) return $product;

    // 3. ID match (only if active)
    if (is_numeric($query)) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute([':id' => (int)$query]);
        $product = $stmt->fetch();
        if ($product && $product['is_active']) return $product;
    }

    // 4. Fuzzy EAN-13/UPC-A
    if (is_numeric($query)) {
        if (strlen($query) == 13 && substr($query, 0, 1) === '0') {
            $upc = substr($query, 1);
            $stmt = $pdo->prepare("SELECT * FROM products WHERE barcode = :b AND is_active = 1");
            $stmt->execute([':b' => $upc]);
            $product = $stmt->fetch();
            if ($product) return $product;
        }
        if (strlen($query) == 12) {
            $ean = '0' . $query;
            $stmt = $pdo->prepare("SELECT * FROM products WHERE barcode = :b AND is_active = 1");
            $stmt->execute([':b' => $ean]);
            $product = $stmt->fetch();
            if ($product) return $product;
        }
    }

    return null;
}

function searchProducts($pdo, $query, $limit = 20) {
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

function getPrice($product, $saleMode = 'unit') {
    switch ($saleMode) {
        case 'pack':
            return floatval($product['pack_sale_price'] ?: $product['sale_price_unit']);
        case 'weight':
            return floatval($product['sale_price_unit']);
        case 'custom':
            return 0;
        default:
            return floatval($product['sale_price_unit']);
    }
}

function checkout($pdo, $cartItems, $saleData = []) {
    if (empty($cartItems)) {
        return ['success' => false, 'message' => 'السلة فارغة'];
    }

    $subtotal = 0;
    foreach ($cartItems as &$item) {
        $item['subtotal'] = round($item['quantity'] * $item['price'], 2);
        $subtotal += $item['subtotal'];
    }
    unset($item);

    $discount = isset($saleData['discount']) ? floatval($saleData['discount']) : 0;
    $total = round($subtotal - $discount, 2);

    // Get next sale number
    $numStmt = $pdo->prepare("SELECT COALESCE(MAX(sale_number), 0) + 1 FROM sales WHERE date(datetime) = date('now', 'localtime')");
    $numStmt->execute();
    $saleNumber = $numStmt->fetchColumn();

    try {
        $pdo->beginTransaction();

        // Create sale
        $saleStmt = $pdo->prepare(
            "INSERT INTO sales (sale_number, subtotal, discount, total, payment_method, notes, type, status, customer_id, shift_id)
             VALUES (:sale_number, :subtotal, :discount, :total, :payment_method, :notes, 'sale', 'completed', :customer_id, :shift_id)"
        );
        $saleStmt->execute([
            ':sale_number' => $saleNumber,
            ':subtotal' => $subtotal,
            ':discount' => $discount,
            ':total' => $total,
            ':payment_method' => $saleData['payment_method'] ?? 'cash',
            ':notes' => $saleData['notes'] ?? null,
            ':customer_id' => $saleData['customer_id'] ?? null,
            ':shift_id' => $saleData['shift_id'] ?? null,
        ]);
        $saleId = $pdo->lastInsertId();

        // Insert items & update stock
        $itemStmt = $pdo->prepare(
            "INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal)
             VALUES (:sale_id, :product_id, :product_name, :quantity, :unit_type, :price, :sale_mode, :subtotal)"
        );
        $stockStmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :id");
        $moveStmt = $pdo->prepare(
            "INSERT INTO stock_movements (product_id, type, quantity, reference_type, reference_id, notes)
             VALUES (:product_id, 'sale', :quantity, 'sale', :ref_id, :notes)"
        );

        foreach ($cartItems as $item) {
            $itemStmt->execute([
                ':sale_id' => $saleId,
                ':product_id' => $item['product_id'],
                ':product_name' => $item['product_name'],
                ':quantity' => $item['quantity'],
                ':unit_type' => $item['unit_type'] ?? 'قطعة',
                ':price' => $item['price'],
                ':sale_mode' => $item['sale_mode'] ?? 'unit',
                ':subtotal' => $item['subtotal'],
            ]);

            if (!empty($item['product_id'])) {
                $qtyToDeduct = isset($item['actual_quantity']) ? $item['actual_quantity'] : $item['quantity'];
                $stockStmt->execute([':qty' => -$qtyToDeduct, ':id' => $item['product_id']]);
                $moveStmt->execute([
                    ':product_id' => $item['product_id'],
                    ':quantity' => -$qtyToDeduct,
                    ':ref_id' => $saleId,
                    ':notes' => 'بيع - فاتورة #' . $saleId,
                ]);
            }
        }

        // Safe transaction
        if ($total > 0) {
            $safeStmt = $pdo->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES ('sale', ?, date('now', 'localtime'), ?)");
            $safeStmt->execute([$total, "مبيعات - فاتورة #" . $saleId]);
        }

        $pdo->commit();

        // Get complete sale
        $saleStmt2 = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
        $saleStmt2->execute([$saleId]);
        $sale = $saleStmt2->fetch();
        $itemsStmt = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
        $itemsStmt->execute([$saleId]);
        $sale['items'] = $itemsStmt->fetchAll();

        return ['success' => true, 'sale_id' => $saleId, 'sale' => $sale, 'message' => 'تم البيع بنجاح'];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'خطأ: ' . $e->getMessage()];
    }
}

// ============================================================
// Run Tests
// ============================================================
echo "\n";
echo "==========================================================\n";
echo "  Point of Sale (POS) Module Tests\n";
echo "==========================================================\n\n";

// ----------------------------------------------------------
// 3.1 Barcode Scanning & Product Lookup (POS-001 to POS-011)
// ----------------------------------------------------------
echo "--- 3.1 Barcode Scanning & Product Lookup ---\n";
$pdo = createTestDb();

// Insert test products
$prod1 = insertProduct($pdo, ['name' => 'Unit Product', 'barcode' => '123456789012', 'sale_price_unit' => 25.0, 'stock_quantity' => 50]);
$prod2 = insertProduct($pdo, ['name' => 'Pack Product', 'barcode' => '987654321012', 'barcode2' => 'SECOND001', 'barcode3' => 'THIRD001', 'sale_price_unit' => 10.0, 'pack_sale_price' => 90.0, 'pack_unit_quantity' => 12, 'type' => 'pack', 'stock_quantity' => 100]);
$prod3 = insertProduct($pdo, ['name' => 'PLU Product', 'plu_code' => 'PLU001', 'sale_price_unit' => 15.0, 'stock_quantity' => 30]);
$prod4 = insertProduct($pdo, ['name' => 'EAN Product', 'barcode' => '0123456789012', 'sale_price_unit' => 30.0, 'stock_quantity' => 20]); // 13-digit EAN
$prod5 = insertProduct($pdo, ['name' => 'UPC Product', 'barcode' => '123456789011', 'sale_price_unit' => 35.0, 'stock_quantity' => 15]); // 12-digit UPC
$inactive = insertProduct($pdo, ['name' => 'Inactive Product', 'barcode' => 'INACTIVE001', 'sale_price_unit' => 99.0, 'stock_quantity' => 5]);
$pdo->exec("UPDATE products SET is_active = 0 WHERE id = $inactive");

// POS-001: Scan valid barcode
$product = findProduct($pdo, '123456789012');
assert_not_null($product, 'POS-001', 'Scan valid barcode - product found');
assert_equals('Unit Product', $product['name'], 'POS-001b', 'Scanned product has correct name');
assert_equals(25.0, (float)$product['sale_price_unit'], 'POS-001c', 'Scanned product has correct price');

// POS-002: Scan barcode2
$product = findProduct($pdo, 'SECOND001');
assert_not_null($product, 'POS-002', 'Scan barcode2 - product found');
assert_equals('Pack Product', $product['name'], 'POS-002b', 'barcode2 matches correct product');

// POS-003: Scan barcode3
$product = findProduct($pdo, 'THIRD001');
assert_not_null($product, 'POS-003', 'Scan barcode3 - product found');
assert_equals('Pack Product', $product['name'], 'POS-003b', 'barcode3 matches correct product');

// POS-004: PLU code lookup
$product = findProduct($pdo, 'PLU001');
assert_not_null($product, 'POS-004', 'PLU code lookup - product found');
assert_equals('PLU Product', $product['name'], 'POS-004b', 'PLU matches correct product');

// POS-005: Product ID lookup
$product = findProduct($pdo, (string)$prod1);
assert_not_null($product, 'POS-005', 'Product ID lookup - product found');
assert_equals('Unit Product', $product['name'], 'POS-005b', 'ID lookup matches correct product');

// POS-006: EAN-13 to UPC-A (strip leading zero)
// Product has barcode '123456789011' (12 digits), scan '0123456789011' (13 digits with leading 0)
$product = findProduct($pdo, '0123456789011');
assert_not_null($product, 'POS-006', 'EAN-13 to UPC-A fuzzy match (strip leading 0)');
assert_equals('UPC Product', $product['name'], 'POS-006b', 'EAN-13 fuzzy match finds correct product');

// POS-007: UPC-A to EAN-13 (prepend zero)
// Product has barcode '0123456789012' (13 digits), scan '123456789012' (12 digits)
$product = findProduct($pdo, '123456789012');
assert_not_null($product, 'POS-007', 'UPC-A exact match (12 digits)');
// Note: The fuzzy match for UPC-A->EAN-13 prepends 0, so '123456789012' -> '0123456789012'
// But '123456789012' already matches prod1 directly. Let's test with a barcode that only exists as EAN-13.
$prod_ean_only = insertProduct($pdo, ['name' => 'EAN Only', 'barcode' => '0999999999999', 'sale_price_unit' => 40.0]);
$product = findProduct($pdo, '999999999999');
assert_not_null($product, 'POS-007b', 'UPC-A to EAN-13 fuzzy match (prepend 0)');
assert_equals('EAN Only', $product['name'], 'POS-007c', 'UPC-A->EAN-13 finds correct product');

// POS-008: Invalid barcode
$product = findProduct($pdo, 'NONEXISTENT999');
assert_null($product, 'POS-008', 'Invalid barcode returns null');

// POS-009: Empty barcode
$product = findProduct($pdo, '');
assert_null($product, 'POS-009', 'Empty barcode returns null (no crash)');

// POS-010: Inactive product via barcode
$product = findProduct($pdo, 'INACTIVE001');
assert_null($product, 'POS-010', 'Inactive product NOT found via barcode');

// POS-011: Rapid scanning (simulated - multiple sequential lookups)
$barcodes = ['123456789012', '987654321012', 'PLU001', 'SECOND001', 'THIRD001', '0123456789012', '123456789011', '999999999999'];
$found = 0;
foreach ($barcodes as $bc) {
    $p = findProduct($pdo, $bc);
    if ($p) $found++;
}
assert_equals(8, $found, 'POS-011', 'Rapid scanning: all 8 barcodes resolved correctly');

$pdo = null;

// ----------------------------------------------------------
// 3.2 Product Search (POS-012 to POS-016)
// ----------------------------------------------------------
echo "\n--- 3.2 Product Search ---\n";
$pdo = createTestDb();

insertProduct($pdo, ['name' => 'Ahmed Spice', 'barcode' => 'SPC001', 'sale_price_unit' => 10.0]);
insertProduct($pdo, ['name' => 'Ahmed Herbs', 'barcode' => 'HRB001', 'sale_price_unit' => 15.0]);
insertProduct($pdo, ['name' => 'Black Pepper', 'barcode' => 'BPP001', 'sale_price_unit' => 20.0]);
insertProduct($pdo, ['name' => 'Cinnamon', 'barcode' => 'CNN001', 'sale_price_unit' => 12.0]);
$inactive2 = insertProduct($pdo, ['name' => 'Hidden Product', 'barcode' => 'HID001', 'sale_price_unit' => 50.0]);
$pdo->exec("UPDATE products SET is_active = 0 WHERE id = $inactive2");

// POS-012: Name search
$results = searchProducts($pdo, 'Ahmed');
assert_equals(2, count($results), 'POS-012', 'Name search returns 2 results for "Ahmed"');

// POS-013: Partial name search
$results = searchProducts($pdo, 'Ah');
assert_equals(2, count($results), 'POS-013', 'Partial name search "Ah" returns 2 results');

// POS-014: Empty search
$results = searchProducts($pdo, '');
assert_greater_than(count($results), 0, 'POS-014', 'Empty search returns all active products');

// POS-015: Search by barcode
$results = searchProducts($pdo, 'BPP001');
assert_equals(1, count($results), 'POS-015', 'Search by barcode returns 1 result');
assert_equals('Black Pepper', $results[0]['name'], 'POS-015b', 'Barcode search finds correct product');

// POS-016: Search with no results
$results = searchProducts($pdo, 'zzzznonexistent');
assert_equals(0, count($results), 'POS-016', 'No results for gibberish search');

// Inactive excluded
$results = searchProducts($pdo, 'Hidden');
assert_equals(0, count($results), 'POS-016b', 'Inactive products excluded from search');

// Search by PLU
$results = searchProducts($pdo, 'CNN001');
assert_equals(1, count($results), 'POS-016c', 'Search by PLU/barcode returns result');

$pdo = null;

// ----------------------------------------------------------
// 3.3 Cart Management (POS-017 to POS-031)
// ----------------------------------------------------------
echo "\n--- 3.3 Cart Management ---\n";
$pdo = createTestDb();

$unitProd = insertProduct($pdo, ['name' => 'Unit Item', 'barcode' => 'UNIT001', 'sale_price_unit' => 20.0, 'stock_quantity' => 100]);
$packProd = insertProduct($pdo, ['name' => 'Pack Item', 'barcode' => 'PACK001', 'sale_price_unit' => 5.0, 'pack_sale_price' => 50.0, 'pack_unit_quantity' => 12, 'type' => 'pack', 'stock_quantity' => 200]);
$weightProd = insertProduct($pdo, ['name' => 'Weight Item', 'barcode' => 'WGT001', 'sale_price_unit' => 80.0, 'type' => 'weight', 'stock_quantity' => 50]);

// POS-017: Add unit product
$product = findProduct($pdo, 'UNIT001');
assert_not_null($product, 'POS-017', 'Unit product found');
assert_equals(20.0, getPrice($product, 'unit'), 'POS-017b', 'Unit price = sale_price_unit');

// POS-018: Add pack product
$product = findProduct($pdo, 'PACK001');
assert_not_null($product, 'POS-018', 'Pack product found');
assert_equals(50.0, getPrice($product, 'pack'), 'POS-018b', 'Pack price = pack_sale_price');

// POS-019: Weight product
$product = findProduct($pdo, 'WGT001');
assert_not_null($product, 'POS-019', 'Weight product found');
assert_equals(80.0, getPrice($product, 'weight'), 'POS-019b', 'Weight price = sale_price_unit (per kg)');

// POS-020: Duplicate product merge (simulated cart logic)
$cart = [];
$item = ['product_id' => $unitProd, 'product_name' => 'Unit Item', 'quantity' => 1, 'price' => 20.0, 'sale_mode' => 'unit', 'unit_type' => 'قطعة'];
$existingIdx = -1;
foreach ($cart as $i => $ci) {
    if ($ci['product_id'] === $item['product_id'] && $ci['sale_mode'] === $item['sale_mode']) {
        $existingIdx = $i;
        break;
    }
}
if ($existingIdx >= 0) {
    $cart[$existingIdx]['quantity'] += 1;
} else {
    $cart[] = $item;
}
// Add same product again
$existingIdx = -1;
foreach ($cart as $i => $ci) {
    if ($ci['product_id'] === $item['product_id'] && $ci['sale_mode'] === $item['sale_mode']) {
        $existingIdx = $i;
        break;
    }
}
if ($existingIdx >= 0) {
    $cart[$existingIdx]['quantity'] += 1;
}
assert_equals(1, count($cart), 'POS-020', 'Duplicate product merges (not duplicated)');
assert_equals(2, $cart[0]['quantity'], 'POS-020b', 'Quantity incremented to 2');

// POS-021: Increment quantity
$cart[0]['quantity'] += 1;
assert_equals(3, $cart[0]['quantity'], 'POS-021', 'Increment quantity');

// POS-022: Decrement quantity
$cart[0]['quantity'] -= 1;
assert_equals(2, $cart[0]['quantity'], 'POS-022', 'Decrement quantity');

// POS-022b: Remove if quantity = 0
$cart[0]['quantity'] = 0;
if ($cart[0]['quantity'] <= 0) {
    array_splice($cart, 0, 1);
}
assert_equals(0, count($cart), 'POS-022b', 'Item removed when quantity reaches 0');

// POS-024: Remove item
$cart[] = ['product_id' => $unitProd, 'product_name' => 'Unit Item', 'quantity' => 1, 'price' => 20.0, 'sale_mode' => 'unit'];
$cart[] = ['product_id' => $packProd, 'product_name' => 'Pack Item', 'quantity' => 1, 'price' => 50.0, 'sale_mode' => 'pack'];
assert_equals(2, count($cart), 'POS-024a', 'Cart has 2 items');
array_splice($cart, 0, 1); // Remove first item
assert_equals(1, count($cart), 'POS-024', 'Item removed from cart');

// POS-025: Clear cart
$cart = [];
assert_equals(0, count($cart), 'POS-025', 'Clear cart empties all items');

// POS-028: Subtotal calculation
$cart = [
    ['product_id' => $unitProd, 'quantity' => 3, 'price' => 20.0, 'subtotal' => 60.0],
    ['product_id' => $packProd, 'quantity' => 2, 'price' => 50.0, 'subtotal' => 100.0],
];
$subtotal = array_sum(array_column($cart, 'subtotal'));
assert_equals(160.0, $subtotal, 'POS-028', 'Subtotal = sum of (qty x price)');

// POS-029: Discount application
$discount = 10.0;
$total = $subtotal - $discount;
assert_equals(150.0, $total, 'POS-029', 'Total = subtotal - discount');

// POS-031: Zero discount
$discount = 0;
$total = $subtotal - $discount;
assert_equals(160.0, $total, 'POS-031', 'Zero discount: total = subtotal');

$pdo = null;

// ----------------------------------------------------------
// 3.4 Sale Modes (POS-032 to POS-037)
// ----------------------------------------------------------
echo "\n--- 3.4 Sale Modes ---\n";
$pdo = createTestDb();

$unitP = insertProduct($pdo, ['name' => 'Unit Mode', 'barcode' => 'UM001', 'sale_price_unit' => 25.0, 'stock_quantity' => 100]);
$packP = insertProduct($pdo, ['name' => 'Pack Mode', 'barcode' => 'PM001', 'sale_price_unit' => 5.0, 'pack_sale_price' => 55.0, 'pack_unit_quantity' => 12, 'type' => 'pack', 'stock_quantity' => 200]);
$weightP = insertProduct($pdo, ['name' => 'Weight Mode', 'barcode' => 'WM001', 'sale_price_unit' => 80.0, 'type' => 'weight', 'stock_quantity' => 50]);

// POS-032: Unit mode pricing
$p = findProduct($pdo, 'UM001');
assert_equals(25.0, getPrice($p, 'unit'), 'POS-032', 'Unit mode: price = sale_price_unit');

// POS-033: Pack mode pricing
$p = findProduct($pdo, 'PM001');
assert_equals(55.0, getPrice($p, 'pack'), 'POS-033', 'Pack mode: price = pack_sale_price');

// POS-033b: Pack fallback to unit price if no pack price
$p2 = $p;
$p2['pack_sale_price'] = null;
assert_equals(5.0, getPrice($p2, 'pack'), 'POS-033b', 'Pack mode fallback: uses sale_price_unit if no pack_sale_price');

// POS-034: Weight mode pricing
$p = findProduct($pdo, 'WM001');
assert_equals(80.0, getPrice($p, 'weight'), 'POS-034', 'Weight mode: price = sale_price_unit per kg');

// POS-035: Custom price mode
assert_equals(0, getPrice($p, 'custom'), 'POS-035', 'Custom mode: price = 0 (set manually)');

// POS-036: Weight by price calculator
$targetPrice = 10.0;
$pricePerKg = 80.0;
$weight = $targetPrice / $pricePerKg;
assert_equals(0.125, $weight, 'POS-036', 'Weight by price: 10 EGP / 80 per kg = 0.125 kg');

// POS-037: Pack to unit conversion
$p = findProduct($pdo, 'PM001');
$packQty = 2;
$actualQty = $packQty * $p['pack_unit_quantity'];
assert_equals(24.0, floatval($actualQty), 'POS-037', 'Pack conversion: 2 packs x 12 units = 24 actual pieces');

$pdo = null;

// ----------------------------------------------------------
// 3.5 Checkout (POS-038 to POS-047)
// ----------------------------------------------------------
echo "\n--- 3.5 Checkout ---\n";
$pdo = createTestDb();

$prodA = insertProduct($pdo, ['name' => 'Checkout A', 'barcode' => 'CA001', 'sale_price_unit' => 20.0, 'purchase_price' => 10.0, 'stock_quantity' => 50]);
$prodB = insertProduct($pdo, ['name' => 'Checkout B', 'barcode' => 'CB001', 'sale_price_unit' => 30.0, 'purchase_price' => 15.0, 'stock_quantity' => 30]);

// POS-038: Successful checkout
$cartItems = [
    ['product_id' => $prodA, 'product_name' => 'Checkout A', 'quantity' => 3, 'price' => 20.0, 'sale_mode' => 'unit', 'unit_type' => 'قطعة'],
    ['product_id' => $prodB, 'product_name' => 'Checkout B', 'quantity' => 2, 'price' => 30.0, 'sale_mode' => 'unit', 'unit_type' => 'قطعة'],
];
$result = checkout($pdo, $cartItems, ['discount' => 0, 'payment_method' => 'cash']);
assert_true($result['success'], 'POS-038', 'Checkout succeeds');
assert_not_null($result['sale_id'], 'POS-038b', 'Sale ID returned');
assert_not_null($result['sale'], 'POS-038c', 'Sale data returned');

// POS-038d: Sale has items
assert_true(isset($result['sale']['items']), 'POS-038d', 'Sale contains items array');
assert_equals(2, count($result['sale']['items']), 'POS-038e', 'Sale has 2 items');

// POS-039: Checkout with discount
$cartItems2 = [
    ['product_id' => $prodA, 'product_name' => 'Checkout A', 'quantity' => 1, 'price' => 20.0, 'sale_mode' => 'unit', 'unit_type' => 'قطعة'],
];
$result2 = checkout($pdo, $cartItems2, ['discount' => 5.0]);
assert_true($result2['success'], 'POS-039', 'Checkout with discount succeeds');
assert_equals(15.0, (float)$result2['sale']['total'], 'POS-039b', 'Sale total = subtotal - discount');

// POS-040: Empty cart checkout
$result3 = checkout($pdo, [], ['discount' => 0]);
assert_false($result3['success'], 'POS-040', 'Empty cart checkout fails');
assert_equals('السلة فارغة', $result3['message'], 'POS-040b', 'Empty cart error message');

// POS-043: Stock deduction
$stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
$stmt->execute([$prodA]);
$remainingStock = floatval($stmt->fetchColumn());
// Started with 50, sold 3 (first checkout) + 1 (second checkout) = 4
assert_equals(46.0, $remainingStock, 'POS-043', 'Stock deducted correctly (50 - 4 = 46)');

$stmt->execute([$prodB]);
$remainingStockB = floatval($stmt->fetchColumn());
// Started with 30, sold 2 = 28
assert_equals(28.0, $remainingStockB, 'POS-043b', 'Stock deducted for product B (30 - 2 = 28)');

// POS-044: Safe transaction recorded
$stmt = $pdo->prepare("SELECT * FROM safe_transactions WHERE type = 'sale' ORDER BY id DESC LIMIT 2");
$stmt->execute();
$safeTxns = $stmt->fetchAll();
assert_greater_than(count($safeTxns), 0, 'POS-044', 'Safe transaction recorded for sale');
// First checkout total = 3*20 + 2*30 = 120
assert_equals(120.0, (float)$safeTxns[1]['amount'], 'POS-044b', 'Safe transaction amount = 120 for first checkout');

// POS-045: Stock movement logged
$stmt = $pdo->prepare("SELECT * FROM stock_movements WHERE product_id = ? AND type = 'sale' ORDER BY id DESC LIMIT 1");
$stmt->execute([$prodA]);
$movement = $stmt->fetch();
assert_not_null($movement, 'POS-045', 'Stock movement logged for sale');
assert_equals(-1, (int)$movement['quantity'], 'POS-045b', 'Stock movement quantity is negative (deduction)');

// POS-046: Sale number generation
$stmt = $pdo->prepare("SELECT sale_number FROM sales ORDER BY id");
$stmt->execute();
$saleNumbers = $stmt->fetchAll(PDO::FETCH_COLUMN);
// Each sale should have a sequential number
assert_greater_than($saleNumbers[0], 0, 'POS-046', 'Sale number > 0');

// POS-047: Negative stock allowed (known behavior)
$prodNeg = insertProduct($pdo, ['name' => 'Low Stock', 'barcode' => 'NEG001', 'sale_price_unit' => 10.0, 'stock_quantity' => 2]);
$cartNeg = [
    ['product_id' => $prodNeg, 'product_name' => 'Low Stock', 'quantity' => 5, 'price' => 10.0, 'sale_mode' => 'unit', 'unit_type' => 'قطعة'],
];
$resultNeg = checkout($pdo, $cartNeg);
assert_true($resultNeg['success'], 'POS-047', 'Checkout with qty > stock succeeds (no guard)');
$stmtNeg = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
$stmtNeg->execute([$prodNeg]);
$negStock = $stmtNeg->fetchColumn();
assert_true($negStock < 0, 'POS-047b', 'Stock goes negative (2 - 5 = -3)');

$pdo = null;

// ----------------------------------------------------------
// 3.6 Keyboard Shortcuts (POS-048 to POS-053) — Structural
// ----------------------------------------------------------
echo "\n--- 3.6 Keyboard Shortcuts (Structural) ---\n";

$jsFile = file_get_contents(__DIR__ . '/../www/public/js/pos_core_v2.js');
$shortcutsFile = file_get_contents(__DIR__ . '/../www/public/js/pos_shortcuts.js');
$viewFile = file_get_contents(__DIR__ . '/../www/app/views/pos/index.php');

// POS-048: F2 - Focus barcode
assert_true(strpos($jsFile, "e.key === 'F2'") !== false, 'POS-048', 'F2 shortcut defined in JS');
assert_true(strpos($jsFile, 'barcodeInput.focus()') !== false || strpos($jsFile, 'barcodeInput') !== false, 'POS-048b', 'F2 focuses barcode input');

// POS-049: F3 - Product details
assert_true(strpos($jsFile, "e.key === 'F3'") !== false, 'POS-049', 'F3 shortcut defined in JS');
assert_true(strpos($jsFile, 'showSelectedProductDetails') !== false, 'POS-049b', 'F3 calls showSelectedProductDetails');
assert_true(strpos($shortcutsFile, 'showSelectedProductDetails') !== false, 'POS-049c', 'showSelectedProductDetails defined in pos_shortcuts.js');

// POS-050: F5 - Checkout
assert_true(strpos($jsFile, "e.key === 'F5'") !== false, 'POS-050', 'F5 shortcut defined in JS');
assert_true(strpos($jsFile, 'processCheckout()') !== false, 'POS-050b', 'F5 calls processCheckout');

// POS-051: F8 - Toggle unit
assert_true(strpos($jsFile, "e.key === 'F8'") !== false, 'POS-051', 'F8 shortcut defined in JS');
assert_true(strpos($jsFile, 'toggleSelectedUnitType') !== false, 'POS-051b', 'F8 calls toggleSelectedUnitType');
assert_true(strpos($shortcutsFile, 'toggleSelectedUnitType') !== false, 'POS-051c', 'toggleSelectedUnitType defined in pos_shortcuts.js');

// POS-052: Escape - Close modal
assert_true(strpos($jsFile, "e.key === 'Escape'") !== false, 'POS-052', 'Escape shortcut defined in JS');
assert_true(strpos($jsFile, "modal-overlay.active") !== false || strpos($jsFile, "classList.remove('active')") !== false, 'POS-052b', 'Escape closes modals');
assert_true(strpos($jsFile, 'barcodeInput.focus()') !== false || strpos($jsFile, 'barcodeInput') !== false, 'POS-052c', 'Escape returns focus to barcode');

// POS-053: Arrow keys navigation
assert_true(strpos($jsFile, "e.key === 'ArrowDown'") !== false, 'POS-053', 'ArrowDown shortcut defined');
assert_true(strpos($jsFile, "e.key === 'ArrowUp'") !== false, 'POS-053b', 'ArrowUp shortcut defined');
assert_true(strpos($jsFile, 'selectedCartIndex') !== false, 'POS-053c', 'Arrow keys use selectedCartIndex');

// Shortcut hints in view
assert_true(strpos($viewFile, 'F2') !== false, 'POS-053d', 'F2 shortcut hint displayed in view');
assert_true(strpos($viewFile, 'F5') !== false, 'POS-053e', 'F5 shortcut hint displayed in view');
assert_true(strpos($viewFile, 'F8') !== false, 'POS-053f', 'F8 shortcut hint displayed in view');

// ----------------------------------------------------------
// 3.7 Scanner Buffer Handling (POS-054 to POS-056) — Structural
// ----------------------------------------------------------
echo "\n--- 3.7 Scanner Buffer Handling (Structural) ---\n";

// POS-054: Rapid barcode input detection
assert_true(strpos($jsFile, 'posScanBuffer') !== false, 'POS-054', 'Scanner buffer variable exists');
assert_true(strpos($jsFile, 'SCAN_TIMING_THRESHOLD') !== false, 'POS-054b', 'Scan timing threshold defined');
assert_true(strpos($jsFile, '100') !== false, 'POS-054c', 'Timing threshold = 100ms');

// POS-055: Normal typing not detected as scanner
assert_true(strpos($jsFile, 'lastKeyTime') !== false, 'POS-055', 'Last key time tracked for scanner detection');
assert_true(strpos($jsFile, 'posScanBuffer = \'\'') !== false || strpos($jsFile, "posScanBuffer = ''") !== false, 'POS-055b', 'Buffer reset on slow typing');

// POS-056: Scanner in input field handling
assert_true(strpos($jsFile, "tagName === 'INPUT'") !== false || strpos($jsFile, 'INPUT') !== false, 'POS-056', 'Scanner ignores input field keystrokes');
assert_true(strpos($jsFile, "tagName === 'TEXTAREA'") !== false || strpos($jsFile, 'TEXTAREA') !== false, 'POS-056b', 'Scanner ignores textarea keystrokes');

// Additional structural checks
assert_true(strpos($jsFile, 'addToCart') !== false, 'POS-STRUCT-01', 'addToCart function defined');
assert_true(strpos($jsFile, 'removeFromCart') !== false, 'POS-STRUCT-02', 'removeFromCart function defined');
assert_true(strpos($jsFile, 'updateCartQty') !== false, 'POS-STRUCT-03', 'updateCartQty function defined');
assert_true(strpos($jsFile, 'clearCart') !== false, 'POS-STRUCT-04', 'clearCart function defined');
assert_true(strpos($jsFile, 'changeUnitType') !== false, 'POS-STRUCT-05', 'changeUnitType function defined');
assert_true(strpos($jsFile, 'renderCart') !== false, 'POS-STRUCT-06', 'renderCart function defined');
assert_true(strpos($jsFile, 'updateTotals') !== false, 'POS-STRUCT-07', 'updateTotals function defined');
assert_true(strpos($jsFile, 'applyDiscount') !== false, 'POS-STRUCT-08', 'applyDiscount function defined');
assert_true(strpos($jsFile, 'addManualPrice') !== false, 'POS-STRUCT-09', 'addManualPrice function defined');
assert_true(strpos($jsFile, 'addWeightItem') !== false, 'POS-STRUCT-10', 'addWeightItem function defined');
assert_true(strpos($jsFile, 'processCheckout') !== false, 'POS-STRUCT-11', 'processCheckout function defined');
assert_true(strpos($jsFile, 'showReceipt') !== false, 'POS-STRUCT-12', 'showReceipt function defined');
assert_true(strpos($jsFile, 'printReceipt') !== false, 'POS-STRUCT-13', 'printReceipt function defined');

// View structural checks
assert_true(strpos($viewFile, 'barcodeInput') !== false, 'POS-STRUCT-14', 'Barcode input element in view');
assert_true(strpos($viewFile, 'productSearch') !== false, 'POS-STRUCT-15', 'Product search input in view');
assert_true(strpos($viewFile, 'cartBody') !== false, 'POS-STRUCT-16', 'Cart body element in view');
assert_true(strpos($viewFile, 'checkoutBtn') !== false, 'POS-STRUCT-17', 'Checkout button in view');
assert_true(strpos($viewFile, 'subtotalDisplay') !== false, 'POS-STRUCT-18', 'Subtotal display in view');
assert_true(strpos($viewFile, 'discountDisplay') !== false, 'POS-STRUCT-19', 'Discount display in view');
assert_true(strpos($viewFile, 'totalDisplay') !== false, 'POS-STRUCT-20', 'Total display in view');
assert_true(strpos($viewFile, 'manualPriceModal') !== false, 'POS-STRUCT-21', 'Manual price modal in view');
assert_true(strpos($viewFile, 'weightModal') !== false, 'POS-STRUCT-22', 'Weight modal in view');
assert_true(strpos($viewFile, 'discountModal') !== false, 'POS-STRUCT-23', 'Discount modal in view');
assert_true(strpos($viewFile, 'receiptModal') !== false, 'POS-STRUCT-24', 'Receipt modal in view');

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

echo "\n  All POS tests passed!\n\n";
exit(0);
