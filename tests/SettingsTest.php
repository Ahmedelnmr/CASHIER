<?php
/**
 * System Settings Module Tests
 * Tests: SET-001 to SET-009
 *
 * Run: php tests/SettingsTest.php
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

function assert_not_empty($value, $testId, $description) {
    assert_true(!empty($value), $testId, $description);
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
// Replicate SettingsService methods
// ============================================================
function getSetting($pdo, $key, $default = '') {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

function getAllSettings($pdo) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

function setSetting($pdo, $key, $value) {
    $stmt = $pdo->prepare(
        "INSERT OR REPLACE INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, datetime('now', 'localtime'))"
    );
    $stmt->execute([$key, $value]);
    return true;
}

function setMultipleSettings($pdo, $settings) {
    $stmt = $pdo->prepare(
        "INSERT OR REPLACE INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, datetime('now', 'localtime'))"
    );
    foreach ($settings as $key => $value) {
        $stmt->execute([$key, $value]);
    }
    return true;
}

function getStoreInfo($pdo) {
    return [
        'name' => getSetting($pdo, 'store_name', 'تاجر'),
        'phone' => getSetting($pdo, 'store_phone', ''),
        'address' => getSetting($pdo, 'store_address', ''),
        'receipt_header' => getSetting($pdo, 'receipt_header', ''),
        'receipt_footer' => getSetting($pdo, 'receipt_footer', ''),
        'currency_symbol' => getSetting($pdo, 'currency_symbol', 'ج.م'),
        'currency_name' => getSetting($pdo, 'currency_name', 'جنيه مصري'),
    ];
}

// ============================================================
// Run Tests
// ============================================================
echo "\n";
echo "==========================================================\n";
echo "  System Settings Module Tests (SET-001 to SET-009)\n";
echo "==========================================================\n\n";

$pdo = createTestDb();

// ----------------------------------------------------------
// SET-009: Default settings
// ----------------------------------------------------------
echo "--- SET-009: Default settings ---\n";

$settings = getAllSettings($pdo);
assert_not_empty($settings, 'SET-009', 'Settings table has entries');

// Check default values from migration
$storeName = getSetting($pdo, 'store_name');
assert_equals('تاجر', $storeName, 'SET-009b', 'Default store name is تاجر');

$storePhone = getSetting($pdo, 'store_phone');
assert_equals('01096107040', $storePhone, 'SET-009c', 'Default store phone set');

$storeAddress = getSetting($pdo, 'store_address');
assert_not_empty($storeAddress, 'SET-009d', 'Default store address set');

$receiptFooter = getSetting($pdo, 'receipt_footer');
assert_not_empty($receiptFooter, 'SET-009e', 'Default receipt footer set');

$currencySymbol = getSetting($pdo, 'currency_symbol');
assert_equals('ج.م', $currencySymbol, 'SET-009f', 'Default currency symbol is ج.م');

$currencyName = getSetting($pdo, 'currency_name');
assert_equals('جنيه مصري', $currencyName, 'SET-009g', 'Default currency name is جنيه مصري');

// ----------------------------------------------------------
// SET-001: View settings
// ----------------------------------------------------------
echo "\n--- SET-001: View settings ---\n";

$settings = getAllSettings($pdo);
assert_not_empty($settings, 'SET-001', 'Settings retrieved');

// Verify all expected keys exist
$expectedKeys = ['store_name', 'store_phone', 'store_address', 'receipt_header', 'receipt_footer', 'currency_symbol', 'currency_name'];
foreach ($expectedKeys as $key) {
    assert_true(array_key_exists($key, $settings), 'SET-001b', "Settings has key: {$key}");
}

// Get store info
$storeInfo = getStoreInfo($pdo);
assert_not_null($storeInfo, 'SET-001c', 'Store info retrieved');
assert_true(isset($storeInfo['name']), 'SET-001d', 'Store info has name');
assert_true(isset($storeInfo['phone']), 'SET-001e', 'Store info has phone');
assert_true(isset($storeInfo['address']), 'SET-001f', 'Store info has address');
assert_true(isset($storeInfo['currency_symbol']), 'SET-001g', 'Store info has currency_symbol');

// ----------------------------------------------------------
// SET-002: Update store name
// ----------------------------------------------------------
echo "\n--- SET-002: Update store name ---\n";

$result = setSetting($pdo, 'store_name', 'متجري الجديد');
assert_true($result, 'SET-002', 'Update store name succeeds');

$value = getSetting($pdo, 'store_name');
assert_equals('متجري الجديد', $value, 'SET-002b', 'Store name updated');

// Store info should reflect the change
$storeInfo = getStoreInfo($pdo);
assert_equals('متجري الجديد', $storeInfo['name'], 'SET-002c', 'Store info shows new name');

// ----------------------------------------------------------
// SET-003: Update store phone
// ----------------------------------------------------------
echo "\n--- SET-003: Update store phone ---\n";

$result = setSetting($pdo, 'store_phone', '01234567890');
assert_true($result, 'SET-003', 'Update store phone succeeds');

$value = getSetting($pdo, 'store_phone');
assert_equals('01234567890', $value, 'SET-003b', 'Store phone updated');

// ----------------------------------------------------------
// SET-004: Update store address
// ----------------------------------------------------------
echo "\n--- SET-004: Update store address ---\n";

$result = setSetting($pdo, 'store_address', 'العنوان الجديد - القاهرة');
assert_true($result, 'SET-004', 'Update store address succeeds');

$value = getSetting($pdo, 'store_address');
assert_equals('العنوان الجديد - القاهرة', $value, 'SET-004b', 'Store address updated');

// ----------------------------------------------------------
// SET-005: Update receipt header
// ----------------------------------------------------------
echo "\n--- SET-005: Update receipt header ---\n";

$result = setSetting($pdo, 'receipt_header', 'مرحباً بكم في متجري');
assert_true($result, 'SET-005', 'Update receipt header succeeds');

$value = getSetting($pdo, 'receipt_header');
assert_equals('مرحباً بكم في متجري', $value, 'SET-005b', 'Receipt header updated');

// ----------------------------------------------------------
// SET-006: Update receipt footer
// ----------------------------------------------------------
echo "\n--- SET-006: Update receipt footer ---\n";

$result = setSetting($pdo, 'receipt_footer', 'شكراً لزيارتكم');
assert_true($result, 'SET-006', 'Update receipt footer succeeds');

$value = getSetting($pdo, 'receipt_footer');
assert_equals('شكراً لزيارتكم', $value, 'SET-006b', 'Receipt footer updated');

// ----------------------------------------------------------
// SET-007: Update currency symbol
// ----------------------------------------------------------
echo "\n--- SET-007: Update currency symbol ---\n";

$result = setSetting($pdo, 'currency_symbol', '$');
assert_true($result, 'SET-007', 'Update currency symbol succeeds');

$value = getSetting($pdo, 'currency_symbol');
assert_equals('$', $value, 'SET-007b', 'Currency symbol updated');

// Store info should reflect the change
$storeInfo = getStoreInfo($pdo);
assert_equals('$', $storeInfo['currency_symbol'], 'SET-007c', 'Store info shows new currency symbol');

// Update currency name too
$result = setSetting($pdo, 'currency_name', 'US Dollar');
$value = getSetting($pdo, 'currency_name');
assert_equals('US Dollar', $value, 'SET-007d', 'Currency name updated');

// ----------------------------------------------------------
// SET-008: Settings persistence
// ----------------------------------------------------------
echo "\n--- SET-008: Settings persistence ---\n";

// Update multiple settings at once
$newSettings = [
    'store_name' => 'اسم محفوظ',
    'store_phone' => '01111111111',
    'store_address' => 'عنوان محفوظ',
    'receipt_header' => 'هيدر محفوظ',
    'receipt_footer' => 'فوتر محفوظ',
    'currency_symbol' => 'ر.س',
    'currency_name' => 'ريال سعودي',
];
$result = setMultipleSettings($pdo, $newSettings);
assert_true($result, 'SET-008', 'Set multiple settings succeeds');

// Verify all settings persisted
$settings = getAllSettings($pdo);
assert_equals('اسم محفوظ', $settings['store_name'], 'SET-008b', 'store_name persisted');
assert_equals('01111111111', $settings['store_phone'], 'SET-008c', 'store_phone persisted');
assert_equals('عنوان محفوظ', $settings['store_address'], 'SET-008d', 'store_address persisted');
assert_equals('هيدر محفوظ', $settings['receipt_header'], 'SET-008e', 'receipt_header persisted');
assert_equals('فوتر محفوظ', $settings['receipt_footer'], 'SET-008f', 'receipt_footer persisted');
assert_equals('ر.س', $settings['currency_symbol'], 'SET-008g', 'currency_symbol persisted');
assert_equals('ريال سعودي', $settings['currency_name'], 'SET-008h', 'currency_name persisted');

// Verify persistence by re-reading from same database
$value = getSetting($pdo, 'store_name');
assert_equals('اسم محفوظ', $value, 'SET-008i', 'Settings persist after re-read');

// ----------------------------------------------------------
// Additional: Store info structure
// ----------------------------------------------------------
echo "\n--- Additional: Store info structure ---\n";

$storeInfo = getStoreInfo($pdo);
assert_equals('اسم محفوظ', $storeInfo['name'], 'SET-EXTRA', 'Store info name correct');
assert_equals('01111111111', $storeInfo['phone'], 'SET-EXTRAb', 'Store info phone correct');
assert_equals('عنوان محفوظ', $storeInfo['address'], 'SET-EXTRAc', 'Store info address correct');
assert_equals('هيدر محفوظ', $storeInfo['receipt_header'], 'SET-EXTRAd', 'Store info receipt_header correct');
assert_equals('فوتر محفوظ', $storeInfo['receipt_footer'], 'SET-EXTRAe', 'Store info receipt_footer correct');
assert_equals('ر.س', $storeInfo['currency_symbol'], 'SET-EXTRAf', 'Store info currency_symbol correct');
assert_equals('ريال سعودي', $storeInfo['currency_name'], 'SET-EXTRAg', 'Store info currency_name correct');

// ----------------------------------------------------------
// Additional: Empty values
// ----------------------------------------------------------
echo "\n--- Additional: Empty values ---\n";

setSetting($pdo, 'receipt_header', '');
$value = getSetting($pdo, 'receipt_header');
assert_equals('', $value, 'SET-EXTRAh', 'Empty value stored correctly');

// Default should be used when key doesn't exist
$value = getSetting($pdo, 'nonexistent_key', 'default_value');
assert_equals('default_value', $value, 'SET-EXTRAi', 'Default value returned for missing key');

$pdo = null;
$newPdo = null;

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

echo "\n  All System Settings tests passed!\n\n";
exit(0);
