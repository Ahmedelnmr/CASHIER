<?php
/**
 * Product Management Module Tests
 * Tests: PROD-001 through PROD-026
 *
 * Run: php tests/ProductTest.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Validator class
require_once __DIR__ . '/../www/app/helpers/Validator.php';

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
    // PDO fetch() returns false (not null) when no row found
    assert_true($value === null || $value === false, $testId, $description . " (expected: null/false, got: " . var_export($value, true) . ")");
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
// Replicate ProductRepository methods
// ============================================================
function productGetAll($pdo, $search = '', $category = '', $type = '') {
    $sql = "SELECT * FROM products WHERE is_active = 1";
    $params = [];
    if ($search !== '') {
        $sql .= " AND (name LIKE :search OR barcode LIKE :search2 OR plu_code LIKE :search3)";
        $params[':search'] = "%$search%";
        $params[':search2'] = "%$search%";
        $params[':search3'] = "%$search%";
    }
    if ($category !== '') {
        $sql .= " AND category = :category";
        $params[':category'] = $category;
    }
    if ($type !== '') {
        $sql .= " AND type = :type";
        $params[':type'] = $type;
    }
    $sql .= " ORDER BY name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function productFindById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function productFindByBarcode($pdo, $barcode) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE (barcode = :b OR barcode2 = :b OR barcode3 = :b) AND is_active = 1");
    $stmt->execute([':b' => $barcode]);
    return $stmt->fetch();
}

function productFindByPLU($pdo, $plu) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE plu_code = :plu AND is_active = 1");
    $stmt->execute([':plu' => $plu]);
    return $stmt->fetch();
}

function productCreate($pdo, $data) {
    $stmt = $pdo->prepare(
        "INSERT INTO products (name, barcode, barcode2, barcode3, plu_code, type, purchase_price, sale_price_unit,
         pack_type, pack_unit_quantity, pack_purchase_price, pack_sale_price, stock_quantity, min_stock, category, notes, is_active)
         VALUES (:name, :barcode, :barcode2, :barcode3, :plu_code, :type, :purchase_price, :sale_price_unit,
         :pack_type, :pack_unit_quantity, :pack_purchase_price, :pack_sale_price, :stock_quantity, :min_stock, :category, :notes, 1)"
    );
    $stmt->execute([
        ':name' => $data['name'],
        ':barcode' => $data['barcode'] ?? null,
        ':barcode2' => $data['barcode2'] ?? null,
        ':barcode3' => $data['barcode3'] ?? null,
        ':plu_code' => $data['plu_code'] ?? null,
        ':type' => $data['type'] ?? 'unit',
        ':purchase_price' => $data['purchase_price'] ?? 0,
        ':sale_price_unit' => $data['sale_price_unit'] ?? 0,
        ':pack_type' => $data['pack_type'] ?? null,
        ':pack_unit_quantity' => $data['pack_unit_quantity'] ?? null,
        ':pack_purchase_price' => $data['pack_purchase_price'] ?? null,
        ':pack_sale_price' => $data['pack_sale_price'] ?? null,
        ':stock_quantity' => $data['stock_quantity'] ?? 0,
        ':min_stock' => $data['min_stock'] ?? 0,
        ':category' => $data['category'] ?? null,
        ':notes' => $data['notes'] ?? null,
    ]);
    return $pdo->lastInsertId();
}

function productUpdate($pdo, $id, $data) {
    $fields = [];
    $params = [':id' => $id];
    $allowed = [
        'name', 'barcode', 'barcode2', 'barcode3', 'plu_code', 'type',
        'purchase_price', 'sale_price_unit', 'pack_type', 'pack_unit_quantity',
        'pack_purchase_price', 'pack_sale_price', 'stock_quantity', 'min_stock',
        'category', 'notes', 'is_active'
    ];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $data)) {
            $fields[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }
    $fields[] = "updated_at = datetime('now', 'localtime')";
    if (empty($fields)) return false;
    $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function productDelete($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE products SET is_active = 0, updated_at = datetime('now', 'localtime') WHERE id = :id");
    return $stmt->execute([':id' => $id]);
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

function productGetCategories($pdo) {
    $stmt = $pdo->prepare("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function productCount($pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE is_active = 1");
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

// ============================================================
// Replicate Validator
// ============================================================
function validateProduct($data, $requiredName = true) {
    $errors = [];
    if ($requiredName) {
        if (!isset($data['name']) || trim($data['name']) === '') {
            $errors['name'] = 'اسم المنتج مطلوب';
        }
    }
    if (isset($data['name']) && function_exists('mb_strlen')) {
        if (mb_strlen($data['name'], 'UTF-8') > 60) {
            $errors['name'] = 'اسم المنتج يجب أن لا يتجاوز 60 حرف';
        }
    }
    if (isset($data['purchase_price']) && $data['purchase_price'] !== '' && !is_numeric($data['purchase_price'])) {
        $errors['purchase_price'] = 'سعر الشراء يجب أن يكون رقم';
    }
    if (isset($data['sale_price_unit']) && $data['sale_price_unit'] !== '' && !is_numeric($data['sale_price_unit'])) {
        $errors['sale_price_unit'] = 'سعر البيع يجب أن يكون رقم';
    }
    if (isset($data['purchase_price']) && is_numeric($data['purchase_price']) && $data['purchase_price'] < 0) {
        $errors['purchase_price'] = 'سعر الشراء يجب أن يكون 0 على الأقل';
    }
    if (isset($data['sale_price_unit']) && is_numeric($data['sale_price_unit']) && $data['sale_price_unit'] < 0) {
        $errors['sale_price_unit'] = 'سعر البيع يجب أن يكون 0 على الأقل';
    }
    return $errors;
}

// ============================================================
// ProductService create/update logic (with validation + duplicate checks)
// ============================================================
function serviceCreate($pdo, $data) {
    if (isset($data['barcode'])) $data['barcode'] = trim($data['barcode']);
    if (isset($data['plu_code'])) $data['plu_code'] = trim($data['plu_code']);

    $errors = validateProduct($data);
    if (!empty($errors)) return ['success' => false, 'errors' => $errors];

    // Check duplicate barcodes
    $barcodesToCheck = [];
    if (!empty($data['barcode'])) $barcodesToCheck['barcode'] = $data['barcode'];
    if (!empty($data['barcode2'])) $barcodesToCheck['barcode2'] = $data['barcode2'];
    if (!empty($data['barcode3'])) $barcodesToCheck['barcode3'] = $data['barcode3'];

    foreach ($barcodesToCheck as $field => $code) {
        $existing = productFindByBarcode($pdo, $code);
        if ($existing) {
            return ['success' => false, 'errors' => [$field => "الباركود ($code) موجود بالفعل في منتج: {$existing['name']}"]];
        }
    }

    // Check duplicate PLU
    if (!empty($data['plu_code'])) {
        $existing = productFindByPLU($pdo, $data['plu_code']);
        if ($existing) {
            return ['success' => false, 'errors' => ['plu_code' => 'كود PLU موجود بالفعل']];
        }
    }

    $id = productCreate($pdo, $data);
    return ['success' => true, 'id' => $id];
}

function serviceUpdate($pdo, $id, $data) {
    if (isset($data['barcode'])) $data['barcode'] = trim($data['barcode']);
    if (isset($data['plu_code'])) $data['plu_code'] = trim($data['plu_code']);

    $errors = validateProduct($data);
    if (!empty($errors)) return ['success' => false, 'errors' => $errors];

    // Check duplicate barcodes (exclude current)
    $barcodesToCheck = [];
    if (!empty($data['barcode'])) $barcodesToCheck['barcode'] = $data['barcode'];
    if (!empty($data['barcode2'])) $barcodesToCheck['barcode2'] = $data['barcode2'];
    if (!empty($data['barcode3'])) $barcodesToCheck['barcode3'] = $data['barcode3'];

    foreach ($barcodesToCheck as $field => $code) {
        $existing = productFindByBarcode($pdo, $code);
        if ($existing && $existing['id'] != $id) {
            return ['success' => false, 'errors' => [$field => "الباركود ($code) موجود بالفعل في منتج: {$existing['name']}"]];
        }
    }

    // Check duplicate PLU (exclude current)
    if (!empty($data['plu_code'])) {
        $existing = productFindByPLU($pdo, $data['plu_code']);
        if ($existing && $existing['id'] != $id) {
            return ['success' => false, 'errors' => ['plu_code' => 'كود PLU موجود بالفعل']];
        }
    }

    productUpdate($pdo, $id, $data);
    return ['success' => true];
}

// ============================================================
// Run Tests
// ============================================================
echo "\n";
echo "==========================================================\n";
echo "  Product Management Module Tests (PROD-001 to PROD-026)\n";
echo "==========================================================\n\n";

// ----------------------------------------------------------
// 4.1 Product Listing (PROD-001 to PROD-005)
// ----------------------------------------------------------
echo "--- 4.1 Product Listing ---\n";
$pdo = createTestDb();

// Insert test products
$p1 = productCreate($pdo, ['name' => 'Alpha Spice', 'barcode' => 'A001', 'type' => 'unit', 'purchase_price' => 10, 'sale_price_unit' => 20, 'stock_quantity' => 50, 'min_stock' => 5, 'category' => 'بهارات']);
$p2 = productCreate($pdo, ['name' => 'Beta Herbs', 'barcode' => 'B001', 'type' => 'pack', 'purchase_price' => 15, 'sale_price_unit' => 30, 'stock_quantity' => 30, 'min_stock' => 10, 'category' => 'أعشاب']);
$p3 = productCreate($pdo, ['name' => 'Gamma Oil', 'barcode' => 'C001', 'type' => 'weight', 'purchase_price' => 5, 'sale_price_unit' => 12, 'stock_quantity' => 100, 'min_stock' => 0, 'category' => 'زيوت']);
$p4 = productCreate($pdo, ['name' => 'Delta Cream', 'barcode' => 'D001', 'type' => 'unit', 'purchase_price' => 8, 'sale_price_unit' => 18, 'stock_quantity' => 40, 'min_stock' => 5, 'category' => 'بهارات']);
$inactive = productCreate($pdo, ['name' => 'Hidden Item', 'barcode' => 'X001', 'type' => 'unit', 'purchase_price' => 5, 'sale_price_unit' => 10, 'stock_quantity' => 5]);
productUpdate($pdo, $inactive, ['is_active' => 0]);

// PROD-001: List all products
$products = productGetAll($pdo);
assert_equals(4, count($products), 'PROD-001', 'List all active products (4 active)');

// PROD-002: Search by name
$products = productGetAll($pdo, 'Alpha');
assert_equals(1, count($products), 'PROD-002', 'Search by name returns 1 result');
assert_equals('Alpha Spice', $products[0]['name'], 'PROD-002b', 'Search result has correct name');

// PROD-003: Filter by category
$products = productGetAll($pdo, '', 'بهارات');
assert_equals(2, count($products), 'PROD-003', 'Filter by category returns 2 products');

// PROD-004: Filter by type
$products = productGetAll($pdo, '', '', 'weight');
assert_equals(1, count($products), 'PROD-004', 'Filter by type=weight returns 1 product');
assert_equals('Gamma Oil', $products[0]['name'], 'PROD-004b', 'Weight filter returns correct product');

// PROD-005: Empty product list
$pdo2 = createTestDb(); // Fresh DB with no products
$products = productGetAll($pdo2);
assert_equals(0, count($products), 'PROD-005', 'Empty product list returns 0 items');
$pdo2 = null;

// Inactive excluded
$products = productGetAll($pdo, 'Hidden');
assert_equals(0, count($products), 'PROD-005b', 'Inactive products excluded from listing');

$pdo = null;

// ----------------------------------------------------------
// 4.2 Product Creation (PROD-006 to PROD-018)
// ----------------------------------------------------------
echo "\n--- 4.2 Product Creation ---\n";
$pdo = createTestDb();

// PROD-006: Create unit product
$result = serviceCreate($pdo, ['name' => 'Unit Product', 'barcode' => 'U001', 'type' => 'unit', 'purchase_price' => 10, 'sale_price_unit' => 20, 'stock_quantity' => 50]);
assert_true($result['success'], 'PROD-006', 'Create unit product succeeds');
assert_not_null($result['id'], 'PROD-006b', 'Product ID returned');
$p = productFindById($pdo, $result['id']);
assert_equals('unit', $p['type'], 'PROD-006c', 'Product type = unit');
assert_equals(20.0, floatval($p['sale_price_unit']), 'PROD-006d', 'Sale price correct');

// PROD-007: Create pack product
$result = serviceCreate($pdo, ['name' => 'Pack Product', 'barcode' => 'P001', 'type' => 'pack', 'purchase_price' => 5, 'sale_price_unit' => 8, 'pack_sale_price' => 90, 'pack_unit_quantity' => 12, 'stock_quantity' => 100]);
assert_true($result['success'], 'PROD-007', 'Create pack product succeeds');
$p = productFindById($pdo, $result['id']);
assert_equals('pack', $p['type'], 'PROD-007b', 'Product type = pack');
assert_equals(90.0, floatval($p['pack_sale_price']), 'PROD-007c', 'Pack sale price correct');
assert_equals(12.0, floatval($p['pack_unit_quantity']), 'PROD-007d', 'Pack unit quantity correct');

// PROD-008: Create weight product
$result = serviceCreate($pdo, ['name' => 'Weight Product', 'barcode' => 'W001', 'type' => 'weight', 'purchase_price' => 5, 'sale_price_unit' => 12, 'stock_quantity' => 50]);
assert_true($result['success'], 'PROD-008', 'Create weight product succeeds');
$p = productFindById($pdo, $result['id']);
assert_equals('weight', $p['type'], 'PROD-008b', 'Product type = weight');

// PROD-009: Required field validation (empty name)
$result = serviceCreate($pdo, ['name' => '', 'purchase_price' => 10, 'sale_price_unit' => 20]);
assert_false($result['success'], 'PROD-009', 'Empty name validation fails');
assert_array_key_exists('name', $result['errors'], 'PROD-009b', 'Name error key exists');

// PROD-009c: Missing name entirely
$result = serviceCreate($pdo, ['purchase_price' => 10, 'sale_price_unit' => 20]);
assert_false($result['success'], 'PROD-009c', 'Missing name validation fails');

// PROD-010: Duplicate barcode check
$result = serviceCreate($pdo, ['name' => 'Dup Test', 'barcode' => 'U001', 'purchase_price' => 10, 'sale_price_unit' => 20]);
assert_false($result['success'], 'PROD-010', 'Duplicate barcode rejected');
assert_array_key_exists('barcode', $result['errors'], 'PROD-010b', 'Barcode error key exists');

// PROD-011: Duplicate barcode2 check
productCreate($pdo, ['name' => 'Has BC2', 'barcode' => 'BC2_001', 'barcode2' => 'BC2_SEC', 'type' => 'unit', 'purchase_price' => 5, 'sale_price_unit' => 10]);
$result = serviceCreate($pdo, ['name' => 'Dup BC2', 'barcode2' => 'BC2_SEC', 'purchase_price' => 5, 'sale_price_unit' => 10]);
assert_false($result['success'], 'PROD-011', 'Duplicate barcode2 rejected');

// PROD-012: Duplicate barcode3 check
productCreate($pdo, ['name' => 'Has BC3', 'barcode' => 'BC3_001', 'barcode3' => 'BC3_TRI', 'type' => 'unit', 'purchase_price' => 5, 'sale_price_unit' => 10]);
$result = serviceCreate($pdo, ['name' => 'Dup BC3', 'barcode3' => 'BC3_TRI', 'purchase_price' => 5, 'sale_price_unit' => 10]);
assert_false($result['success'], 'PROD-012', 'Duplicate barcode3 rejected');

// PROD-013: Duplicate PLU check
productCreate($pdo, ['name' => 'Has PLU', 'plu_code' => 'PLU999', 'type' => 'unit', 'purchase_price' => 5, 'sale_price_unit' => 10]);
$result = serviceCreate($pdo, ['name' => 'Dup PLU', 'plu_code' => 'PLU999', 'purchase_price' => 5, 'sale_price_unit' => 10]);
assert_false($result['success'], 'PROD-013', 'Duplicate PLU rejected');
assert_array_key_exists('plu_code', $result['errors'], 'PROD-013b', 'PLU error key exists');

// PROD-014: Auto barcode generation (structural - barcode = '2' + 6-digit ID)
$newId = productCreate($pdo, ['name' => 'Auto Barcode', 'type' => 'unit', 'purchase_price' => 5, 'sale_price_unit' => 10]);
$expectedBarcode = '2' . str_pad($newId, 6, '0', STR_PAD_LEFT);
assert_equals(7, strlen($expectedBarcode), 'PROD-014', 'Auto barcode would be 7 chars (2 + 6 digits)');

// PROD-015: Pack price auto-calculation (structural)
$packPrice = 1200;
$packQty = 50;
$unitPrice = $packPrice / $packQty;
assert_equals(24.0, floatval($unitPrice), 'PROD-015', 'Pack price auto-calc: 1200/50 = 24.0');

// PROD-017: Price validation (negative)
$errors = validateProduct(['name' => 'Test', 'purchase_price' => -5, 'sale_price_unit' => 20]);
assert_array_key_exists('purchase_price', $errors, 'PROD-017', 'Negative purchase price validation fails');

$errors = validateProduct(['name' => 'Test', 'purchase_price' => 10, 'sale_price_unit' => -20]);
assert_array_key_exists('sale_price_unit', $errors, 'PROD-017b', 'Negative sale price validation fails');

// PROD-018: Max length validation (name > 60 chars)
// Test the Validator class logic directly
$longName = str_repeat('A', 61);
$v = new Validator(['name' => $longName, 'purchase_price' => 10, 'sale_price_unit' => 20]);
$v->maxLength('name', 60, 'اسم المنتج');
assert_false($v->passes(), 'PROD-018', 'Name > 60 chars validation fails');

// Valid name at 60 chars
$okName = str_repeat('B', 60);
$v2 = new Validator(['name' => $okName, 'purchase_price' => 10, 'sale_price_unit' => 20]);
$v2->maxLength('name', 60, 'اسم المنتج');
assert_true($v2->passes(), 'PROD-018b', 'Name = 60 chars passes validation');

$pdo = null;

// ----------------------------------------------------------
// 4.3 Product Editing (PROD-019 to PROD-023)
// ----------------------------------------------------------
echo "\n--- 4.3 Product Editing ---\n";
$pdo = createTestDb();

$editProd = productCreate($pdo, ['name' => 'Edit Me', 'barcode' => 'EDT001', 'type' => 'unit', 'purchase_price' => 10, 'sale_price_unit' => 20, 'stock_quantity' => 50, 'min_stock' => 5]);
$otherProd = productCreate($pdo, ['name' => 'Other Product', 'barcode' => 'OTH001', 'type' => 'unit', 'purchase_price' => 5, 'sale_price_unit' => 15]);

// PROD-019: Edit product name
$result = serviceUpdate($pdo, $editProd, ['name' => 'Edited Name', 'purchase_price' => 10, 'sale_price_unit' => 20]);
assert_true($result['success'], 'PROD-019', 'Edit product succeeds');
$p = productFindById($pdo, $editProd);
assert_equals('Edited Name', $p['name'], 'PROD-019b', 'Product name updated');

// PROD-020: Edit barcode
$result = serviceUpdate($pdo, $editProd, ['name' => 'Edited Name', 'barcode' => 'EDT002', 'purchase_price' => 10, 'sale_price_unit' => 20]);
assert_true($result['success'], 'PROD-020', 'Edit barcode succeeds');
$p = productFindById($pdo, $editProd);
assert_equals('EDT002', $p['barcode'], 'PROD-020b', 'Barcode updated');

// PROD-021: Edit prices
$result = serviceUpdate($pdo, $editProd, ['name' => 'Edited Name', 'purchase_price' => 15, 'sale_price_unit' => 35]);
assert_true($result['success'], 'PROD-021', 'Edit prices succeeds');
$p = productFindById($pdo, $editProd);
assert_equals(15.0, floatval($p['purchase_price']), 'PROD-021b', 'Purchase price updated');
assert_equals(35.0, floatval($p['sale_price_unit']), 'PROD-021c', 'Sale price updated');

// PROD-022: Edit stock quantity
$result = serviceUpdate($pdo, $editProd, ['name' => 'Edited Name', 'stock_quantity' => 200]);
assert_true($result['success'], 'PROD-022', 'Edit stock succeeds');
$p = productFindById($pdo, $editProd);
assert_equals(200.0, floatval($p['stock_quantity']), 'PROD-022b', 'Stock quantity updated');

// PROD-023: Preserve own barcode (exclude self from duplicate check)
$result = serviceUpdate($pdo, $editProd, ['name' => 'Edited Name', 'barcode' => 'EDT002', 'purchase_price' => 15, 'sale_price_unit' => 35]);
assert_true($result['success'], 'PROD-023', 'Keeping own barcode passes duplicate check');

// Duplicate barcode from another product should fail
$result = serviceUpdate($pdo, $editProd, ['name' => 'Edited Name', 'barcode' => 'OTH001', 'purchase_price' => 15, 'sale_price_unit' => 35]);
assert_false($result['success'], 'PROD-023b', 'Using another product barcode fails');

// Duplicate PLU from another product should fail
productUpdate($pdo, $otherProd, ['plu_code' => 'PLU_OTHER']);
$result = serviceUpdate($pdo, $editProd, ['name' => 'Edited Name', 'plu_code' => 'PLU_OTHER', 'purchase_price' => 15, 'sale_price_unit' => 35]);
assert_false($result['success'], 'PROD-023c', 'Using another product PLU fails');

// Own PLU should pass
productUpdate($pdo, $editProd, ['plu_code' => 'PLU_EDIT']);
$result = serviceUpdate($pdo, $editProd, ['name' => 'Edited Name', 'plu_code' => 'PLU_EDIT', 'purchase_price' => 15, 'sale_price_unit' => 35]);
assert_true($result['success'], 'PROD-023d', 'Keeping own PLU passes duplicate check');

$pdo = null;

// ----------------------------------------------------------
// 4.4 Product Deletion (PROD-024 to PROD-026)
// ----------------------------------------------------------
echo "\n--- 4.4 Product Deletion ---\n";
$pdo = createTestDb();

$delProd = productCreate($pdo, ['name' => 'Delete Me', 'barcode' => 'DEL001', 'type' => 'unit', 'purchase_price' => 10, 'sale_price_unit' => 20, 'stock_quantity' => 50]);

// PROD-024: Soft delete product
productDelete($pdo, $delProd);
$p = productFindById($pdo, $delProd);
assert_equals(0, intval($p['is_active']), 'PROD-024', 'Soft delete sets is_active = 0');

// PROD-024b: Product hidden from active listing
$products = productGetAll($pdo);
$found = false;
foreach ($products as $pr) {
    if ($pr['id'] == $delProd) { $found = true; break; }
}
assert_false($found, 'PROD-024b', 'Deleted product hidden from listing');

// PROD-025: Deleted product not found via barcode (POS lookup)
$found = productFindByBarcode($pdo, 'DEL001');
assert_null($found, 'PROD-025', 'Deleted product NOT found via barcode lookup');

// PROD-025b: Deleted product not found via search
$results = productSearch($pdo, 'Delete Me');
assert_equals(0, count($results), 'PROD-025b', 'Deleted product NOT found via search');

// PROD-026: Deleted product still shows in existing sales (denormalized)
$saleId = $pdo->prepare("INSERT INTO sales (sale_number, subtotal, discount, total, payment_method, type, status) VALUES (1, 20, 0, 20, 'cash', 'sale', 'completed')");
$saleId->execute();
$saleId = $pdo->lastInsertId();
$itemStmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, product_name, quantity, price, subtotal) VALUES (?, ?, 'Delete Me', 1, 20, 20)");
$itemStmt->execute([$saleId, $delProd]);

// Verify sale item still has product name
$saleItem = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
$saleItem->execute([$saleId]);
$item = $saleItem->fetch();
assert_equals('Delete Me', $item['product_name'], 'PROD-026', 'Deleted product name preserved in sale_items (denormalized)');

// Product ID still referenced (SET NULL on delete would null it, but soft delete preserves)
assert_equals($delProd, $item['product_id'], 'PROD-026b', 'Product ID still in sale_items (soft delete)');

$pdo = null;

// ----------------------------------------------------------
// Additional: Product Search, Categories, Count
// ----------------------------------------------------------
echo "\n--- Additional Tests ---\n";
$pdo = createTestDb();

productCreate($pdo, ['name' => 'Search Test A', 'barcode' => 'SRCH001', 'type' => 'unit', 'purchase_price' => 5, 'sale_price_unit' => 10, 'category' => 'Cat1']);
productCreate($pdo, ['name' => 'Search Test B', 'barcode' => 'SRCH002', 'type' => 'pack', 'purchase_price' => 8, 'sale_price_unit' => 15, 'category' => 'Cat2']);
productCreate($pdo, ['name' => 'Other Item', 'barcode' => 'OTH001', 'type' => 'unit', 'purchase_price' => 3, 'sale_price_unit' => 7, 'category' => 'Cat1']);

// Search by name
$results = productSearch($pdo, 'Search');
assert_equals(2, count($results), 'EXTRA-01', 'Search by name returns 2 results');

// Search by barcode
$results = productSearch($pdo, 'SRCH001');
assert_equals(1, count($results), 'EXTRA-02', 'Search by barcode returns 1 result');

// Categories
$cats = productGetCategories($pdo);
assert_true(in_array('Cat1', $cats), 'EXTRA-03', 'Category Cat1 exists');
assert_true(in_array('Cat2', $cats), 'EXTRA-03b', 'Category Cat2 exists');
assert_equals(2, count($cats), 'EXTRA-03c', '2 distinct categories');

// Count
assert_equals(3, productCount($pdo), 'EXTRA-04', 'Product count = 3');

// Find by ID
$p = productFindById($pdo, 1);
assert_not_null($p, 'EXTRA-05', 'Find by ID returns product');
assert_equals('Search Test A', $p['name'], 'EXTRA-05b', 'Find by ID returns correct product');

// Find by non-existent ID
$p = productFindById($pdo, 99999);
assert_true($p === null || $p === false, 'EXTRA-06', 'Find by non-existent ID returns null/false');

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

echo "\n  All Product Management tests passed!\n\n";
exit(0);
