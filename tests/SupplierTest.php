<?php
/**
 * Supplier Management Module Tests
 * Tests: SUP-001 through SUP-008
 *
 * Run: php tests/SupplierTest.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

function assert_not_null($value, $testId, $description) {
    assert_true($value !== null && $value !== false, $testId, $description);
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
// Replicate SupplierRepository methods
// ============================================================
function supplierGetAll($pdo, $search = '') {
    $sql = "SELECT * FROM suppliers WHERE is_active = 1";
    $params = [];
    if ($search !== '') {
        $sql .= " AND (name LIKE :search OR phone LIKE :search2)";
        $params[':search'] = "%$search%";
        $params[':search2'] = "%$search%";
    }
    $sql .= " ORDER BY name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function supplierFindById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function supplierCreate($pdo, $data) {
    $stmt = $pdo->prepare(
        "INSERT INTO suppliers (name, phone, address, notes) VALUES (:name, :phone, :address, :notes)"
    );
    $stmt->execute([
        ':name' => $data['name'],
        ':phone' => !empty($data['phone']) ? $data['phone'] : null,
        ':address' => !empty($data['address']) ? $data['address'] : null,
        ':notes' => !empty($data['notes']) ? $data['notes'] : null,
    ]);
    return $pdo->lastInsertId();
}

function supplierUpdate($pdo, $id, $data) {
    $stmt = $pdo->prepare(
        "UPDATE suppliers SET name = :name, phone = :phone, address = :address, notes = :notes, updated_at = datetime('now', 'localtime') WHERE id = :id"
    );
    return $stmt->execute([
        ':id' => $id,
        ':name' => $data['name'],
        ':phone' => !empty($data['phone']) ? $data['phone'] : null,
        ':address' => !empty($data['address']) ? $data['address'] : null,
        ':notes' => !empty($data['notes']) ? $data['notes'] : null,
    ]);
}

function supplierDelete($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE suppliers SET is_active = 0, updated_at = datetime('now', 'localtime') WHERE id = :id");
    return $stmt->execute([':id' => $id]);
}

function supplierCount($pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM suppliers WHERE is_active = 1");
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

// ============================================================
// Replicate SupplierService create/update with validation
// ============================================================
function serviceCreate($pdo, $data) {
    $v = new Validator($data);
    $v->required('name', 'اسم المورد');
    if (!$v->passes()) {
        return ['success' => false, 'errors' => $v->errors()];
    }
    $id = supplierCreate($pdo, $data);
    return ['success' => true, 'id' => $id];
}

function serviceUpdate($pdo, $id, $data) {
    $v = new Validator($data);
    $v->required('name', 'اسم المورد');
    if (!$v->passes()) {
        return ['success' => false, 'errors' => $v->errors()];
    }
    supplierUpdate($pdo, $id, $data);
    return ['success' => true];
}

// ============================================================
// Run Tests
// ============================================================
echo "\n";
echo "==========================================================\n";
echo "  Supplier Management Module Tests (SUP-001 to SUP-008)\n";
echo "==========================================================\n\n";

// ----------------------------------------------------------
// SUP-008: Default supplier exists (seed data from migration)
// ----------------------------------------------------------
echo "--- SUP-008: Default supplier (seed data) ---\n";
$pdo = createTestDb();

$default = supplierFindById($pdo, 1);
assert_not_null($default, 'SUP-008', 'Default supplier (id=1) exists from seed data');
assert_equals('مورد افتراضي', $default['name'], 'SUP-008b', 'Default supplier name is correct');
assert_equals(1, intval($default['is_active']), 'SUP-008c', 'Default supplier is active');

$pdo = null;

// ----------------------------------------------------------
// SUP-001: List all suppliers
// ----------------------------------------------------------
echo "\n--- SUP-001: List suppliers ---\n";
$pdo = createTestDb();

// Default supplier + add 2 more
supplierCreate($pdo, ['name' => 'Alpha Trading', 'phone' => '01000000001', 'address' => 'Cairo', 'notes' => '']);
supplierCreate($pdo, ['name' => 'Beta Imports', 'phone' => '01000000002', 'address' => 'Alex', 'notes' => 'Main supplier']);

$suppliers = supplierGetAll($pdo);
assert_equals(3, count($suppliers), 'SUP-001', 'List all active suppliers (3 total including default)');

// Verify ordering by name ASC
assert_true($suppliers[0]['name'] <= $suppliers[1]['name'], 'SUP-001b', 'Suppliers ordered by name ASC (1st <= 2nd)');
assert_true($suppliers[1]['name'] <= $suppliers[2]['name'], 'SUP-001c', 'Suppliers ordered by name ASC (2nd <= 3rd)');

// Inactive excluded
supplierDelete($pdo, $suppliers[0]['id']);
$suppliersAfter = supplierGetAll($pdo);
assert_equals(2, count($suppliersAfter), 'SUP-001d', 'Deleted supplier excluded from listing');

$pdo = null;

// ----------------------------------------------------------
// SUP-002: Search suppliers
// ----------------------------------------------------------
echo "\n--- SUP-002: Search suppliers ---\n";
$pdo = createTestDb();

supplierCreate($pdo, ['name' => 'Ahmed Spices', 'phone' => '01011111111', 'address' => '', 'notes' => '']);
supplierCreate($pdo, ['name' => 'Ahmed Herbs', 'phone' => '01022222222', 'address' => '', 'notes' => '']);
supplierCreate($pdo, ['name' => 'Cairo Goods', 'phone' => '01033333333', 'address' => '', 'notes' => '']);

// Search by name
$results = supplierGetAll($pdo, 'Ahmed');
assert_equals(2, count($results), 'SUP-002', 'Search by name returns 2 results');

// Search by phone
$results = supplierGetAll($pdo, '010333');
assert_equals(1, count($results), 'SUP-002b', 'Search by phone returns 1 result');
assert_equals('Cairo Goods', $results[0]['name'], 'SUP-002c', 'Phone search finds correct supplier');

// Search with no results
$results = supplierGetAll($pdo, 'zzzznonexistent');
assert_equals(0, count($results), 'SUP-002d', 'No results for gibberish search');

// Empty search returns all
$results = supplierGetAll($pdo, '');
assert_greater_than(count($results), 0, 'SUP-002e', 'Empty search returns all active suppliers');

$pdo = null;

// ----------------------------------------------------------
// SUP-003: Create supplier
// ----------------------------------------------------------
echo "\n--- SUP-003: Create supplier ---\n";
$pdo = createTestDb();

$result = serviceCreate($pdo, ['name' => 'New Supplier', 'phone' => '01099999999', 'address' => 'Giza', 'notes' => 'Test notes']);
assert_true($result['success'], 'SUP-003', 'Create supplier succeeds');
assert_not_null($result['id'], 'SUP-003b', 'Supplier ID returned');

$s = supplierFindById($pdo, $result['id']);
assert_equals('New Supplier', $s['name'], 'SUP-003c', 'Supplier name saved correctly');
assert_equals('01099999999', $s['phone'], 'SUP-003d', 'Supplier phone saved correctly');
assert_equals('Giza', $s['address'], 'SUP-003e', 'Supplier address saved correctly');
assert_equals('Test notes', $s['notes'], 'SUP-003f', 'Supplier notes saved correctly');
assert_equals(1, intval($s['is_active']), 'SUP-003g', 'Supplier is active by default');

// Create with minimal data (only name)
$result2 = serviceCreate($pdo, ['name' => 'Minimal Supplier', 'phone' => '', 'address' => '', 'notes' => '']);
assert_true($result2['success'], 'SUP-003h', 'Create supplier with only name succeeds');

$pdo = null;

// ----------------------------------------------------------
// SUP-004: Required name validation
// ----------------------------------------------------------
echo "\n--- SUP-004: Required name validation ---\n";
$pdo = createTestDb();

// Empty name
$result = serviceCreate($pdo, ['name' => '', 'phone' => '01000000000', 'address' => '', 'notes' => '']);
assert_false($result['success'], 'SUP-004', 'Empty name validation fails');
assert_array_key_exists('name', $result['errors'], 'SUP-004b', 'Name error key exists');

// Missing name key
$result = serviceCreate($pdo, ['phone' => '01000000000', 'address' => '', 'notes' => '']);
assert_false($result['success'], 'SUP-004c', 'Missing name validation fails');

// Whitespace-only name
$result = serviceCreate($pdo, ['name' => '   ', 'phone' => '', 'address' => '', 'notes' => '']);
assert_false($result['success'], 'SUP-004d', 'Whitespace-only name validation fails');

// Update with empty name
$validId = supplierCreate($pdo, ['name' => 'Valid', 'phone' => '', 'address' => '', 'notes' => '']);
$result = serviceUpdate($pdo, $validId, ['name' => '', 'phone' => '', 'address' => '', 'notes' => '']);
assert_false($result['success'], 'SUP-004e', 'Update with empty name fails');

$pdo = null;

// ----------------------------------------------------------
// SUP-005: Edit supplier
// ----------------------------------------------------------
echo "\n--- SUP-005: Edit supplier ---\n";
$pdo = createTestDb();

$editId = supplierCreate($pdo, ['name' => 'Original Name', 'phone' => '01000000000', 'address' => 'Old Address', 'notes' => 'Old notes']);

// Edit name
$result = serviceUpdate($pdo, $editId, ['name' => 'Updated Name', 'phone' => '01000000000', 'address' => 'Old Address', 'notes' => 'Old notes']);
assert_true($result['success'], 'SUP-005', 'Edit supplier succeeds');
$s = supplierFindById($pdo, $editId);
assert_equals('Updated Name', $s['name'], 'SUP-005b', 'Supplier name updated');

// Edit phone
$result = serviceUpdate($pdo, $editId, ['name' => 'Updated Name', 'phone' => '01011111111', 'address' => 'Old Address', 'notes' => 'Old notes']);
assert_true($result['success'], 'SUP-005c', 'Edit phone succeeds');
$s = supplierFindById($pdo, $editId);
assert_equals('01011111111', $s['phone'], 'SUP-005d', 'Supplier phone updated');

// Edit address
$result = serviceUpdate($pdo, $editId, ['name' => 'Updated Name', 'phone' => '01011111111', 'address' => 'New Address', 'notes' => 'Old notes']);
assert_true($result['success'], 'SUP-005e', 'Edit address succeeds');
$s = supplierFindById($pdo, $editId);
assert_equals('New Address', $s['address'], 'SUP-005f', 'Supplier address updated');

// Edit notes
$result = serviceUpdate($pdo, $editId, ['name' => 'Updated Name', 'phone' => '01011111111', 'address' => 'New Address', 'notes' => 'New notes']);
assert_true($result['success'], 'SUP-005g', 'Edit notes succeeds');
$s = supplierFindById($pdo, $editId);
assert_equals('New notes', $s['notes'], 'SUP-005h', 'Supplier notes updated');

$pdo = null;

// ----------------------------------------------------------
// SUP-006: Delete supplier (soft delete)
// ----------------------------------------------------------
echo "\n--- SUP-006: Delete supplier ---\n";
$pdo = createTestDb();

$delId = supplierCreate($pdo, ['name' => 'Delete Me', 'phone' => '01000000000', 'address' => '', 'notes' => '']);

// Verify exists before delete
$s = supplierFindById($pdo, $delId);
assert_not_null($s, 'SUP-006', 'Supplier exists before delete');
assert_equals(1, intval($s['is_active']), 'SUP-006b', 'Supplier is active before delete');

// Delete
supplierDelete($pdo, $delId);

// Verify soft deleted
$s = supplierFindById($pdo, $delId);
assert_equals(0, intval($s['is_active']), 'SUP-006c', 'Supplier is_active = 0 after delete');

// Verify hidden from listing
$all = supplierGetAll($pdo);
$found = false;
foreach ($all as $sup) {
    if ($sup['id'] == $delId) { $found = true; break; }
}
assert_false($found, 'SUP-006d', 'Deleted supplier hidden from listing');

// Verify hidden from search
$results = supplierGetAll($pdo, 'Delete Me');
assert_equals(0, count($results), 'SUP-006e', 'Deleted supplier not found in search');

// Verify count decreased
$countBefore = supplierCount($pdo);
supplierDelete($pdo, supplierCreate($pdo, ['name' => 'Temp', 'phone' => '', 'address' => '', 'notes' => '']));
$countAfter = supplierCount($pdo);
assert_true($countAfter < $countBefore + 1, 'SUP-006f', 'Count reflects deletion');

$pdo = null;

// ----------------------------------------------------------
// SUP-007: Supplier profile (find by ID)
// ----------------------------------------------------------
echo "\n--- SUP-007: Supplier profile ---\n";
$pdo = createTestDb();

$profId = supplierCreate($pdo, ['name' => 'Profile Supplier', 'phone' => '01055555555', 'address' => 'Mansoura', 'notes' => 'Preferred supplier']);

// Find by ID returns full data
$s = supplierFindById($pdo, $profId);
assert_not_null($s, 'SUP-007', 'Supplier found by ID');
assert_equals('Profile Supplier', $s['name'], 'SUP-007b', 'Profile name correct');
assert_equals('01055555555', $s['phone'], 'SUP-007c', 'Profile phone correct');
assert_equals('Mansoura', $s['address'], 'SUP-007d', 'Profile address correct');
assert_equals('Preferred supplier', $s['notes'], 'SUP-007e', 'Profile notes correct');

// Non-existent ID returns null/false
$s = supplierFindById($pdo, 99999);
assert_true($s === null || $s === false, 'SUP-007f', 'Non-existent supplier returns null/false');

// Profile with purchase history (structural - purchases table exists)
$pdo->exec("SELECT COUNT(*) FROM purchase_invoices"); // Verify table exists
assert_true(true, 'SUP-007g', 'Purchase invoices table exists for profile history');

$pdo = null;

// ----------------------------------------------------------
// Additional: Supplier count
// ----------------------------------------------------------
echo "\n--- Additional Tests ---\n";
$pdo = createTestDb();

assert_equals(1, supplierCount($pdo), 'EXTRA-01', 'Initial count = 1 (default supplier)');

supplierCreate($pdo, ['name' => 'Extra A', 'phone' => '', 'address' => '', 'notes' => '']);
supplierCreate($pdo, ['name' => 'Extra B', 'phone' => '', 'address' => '', 'notes' => '']);
assert_equals(3, supplierCount($pdo), 'EXTRA-02', 'Count = 3 after adding 2 more');

// Count excludes inactive
$extraId = supplierCreate($pdo, ['name' => 'Extra C', 'phone' => '', 'address' => '', 'notes' => '']);
supplierDelete($pdo, $extraId);
assert_equals(3, supplierCount($pdo), 'EXTRA-03', 'Count excludes deleted suppliers');

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

echo "\n  All Supplier Management tests passed!\n\n";
exit(0);
