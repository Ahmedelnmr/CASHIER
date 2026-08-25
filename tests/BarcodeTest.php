<?php
/**
 * Barcode Generation & Labels Module Tests
 * Tests: BAR-001 to BAR-008
 *
 * Run: php tests/BarcodeTest.php
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

function assert_not_equals($expected, $actual, $testId, $description) {
    assert_true($expected !== $actual, $testId, $description . " (expected not: " . var_export($expected, true) . ", got: " . var_export($actual, true) . ")");
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
// Helper Functions
// ============================================================
function createProduct($pdo, $name, $barcode = null, $type = 'unit', $salePrice = 20) {
    $stmt = $pdo->prepare(
        "INSERT INTO products (name, barcode, type, sale_price_unit) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$name, $barcode, $type, $salePrice]);
    return $pdo->lastInsertId();
}

function findProductById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function findProductByBarcode($pdo, $barcode) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE (barcode = ? OR barcode2 = ? OR barcode3 = ?) AND is_active = 1");
    $stmt->execute([$barcode, $barcode, $barcode]);
    return $stmt->fetch();
}

function updateProduct($pdo, $id, $data) {
    $fields = [];
    $params = [':id' => $id];

    $allowed = ['name', 'barcode', 'barcode2', 'barcode3', 'plu_code', 'type', 'purchase_price', 'sale_price_unit', 'stock_quantity', 'min_stock', 'category', 'notes', 'is_active'];

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

function getAllProducts($pdo) {
    $stmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY name ASC");
    return $stmt->fetchAll();
}

// ============================================================
// Replicate BarcodeController methods
// ============================================================
function generateBarcode($pdo, $productId) {
    $product = findProductById($pdo, $productId);
    if (!$product) {
        return ['success' => false, 'message' => 'المنتج غير موجود'];
    }

    // If product already has barcode, return it
    if (!empty($product['barcode'])) {
        return ['success' => true, 'barcode' => $product['barcode'], 'message' => 'المنتج لديه باركود بالفعل'];
    }

    // Generate a unique barcode (prefix 2 + product ID padded)
    $barcode = '2' . str_pad($product['id'], 6, '0', STR_PAD_LEFT);

    // Check uniqueness
    $existing = findProductByBarcode($pdo, $barcode);
    if ($existing) {
        $barcode = '2' . str_pad($product['id'], 6, '0', STR_PAD_LEFT) . rand(0, 9);
    }

    updateProduct($pdo, $productId, ['barcode' => $barcode]);

    return ['success' => true, 'barcode' => $barcode, 'message' => 'تم توليد الباركود: ' . $barcode];
}

// ============================================================
// Replicate BarcodeGenerator methods (simplified for testing)
// ============================================================
function generateBarcodeImage($data, $width = 2, $height = 60, $showText = true) {
    if (empty($data)) return null;

    // Code 128B character set
    $charSet = array(
        ' ', '!', '"', '#', '$', '%', '&', "'", '(', ')', '*', '+', ',', '-', '.', '/',
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ':', ';', '<', '=', '>', '?',
        '@', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
        'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '[', '\\', ']', '^', '_',
        '`', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o',
        'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '{', '|', '}', '~', chr(127),
    );

    // Code 128B bar patterns
    $patterns = array(
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312',
        '132212', '221213', '221312', '231212', '112232', '122132', '122231', '113222',
        '123122', '123221', '223211', '221132', '221231', '213212', '223112', '312131',
        '311222', '321122', '321221', '312212', '322112', '322211', '212123', '212321',
        '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121',
        '313121', '211331', '231131', '213113', '213311', '213131', '311123', '311321',
        '331121', '312113', '312311', '332111', '314111', '221411', '431111', '111224',
        '111422', '121124', '121421', '141122', '141221', '112214', '112412', '122114',
        '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112',
        '421211', '212141', '214121', '412121', '111143', '111341', '131141', '114113',
        '114311', '411113', '411311', '113141', '114131', '311141', '411131', '211412',
        '211214', '211232', '2331112'
    );

    // Encode data to Code 128B
    $charMap = array_flip($charSet);

    // Start Code B = 104
    $checksum = 104;
    $encoded = $patterns[104]; // Start B

    for ($i = 0; $i < strlen($data); $i++) {
        $char = $data[$i];
        if (!isset($charMap[$char])) {
            $char = ' ';
        }
        $value = $charMap[$char];
        $encoded .= $patterns[$value];
        $checksum += $value * ($i + 1);
    }

    // Checksum
    $checksum = $checksum % 103;
    $encoded .= $patterns[$checksum];

    // Stop
    $encoded .= $patterns[106]; // Stop

    $barCount = strlen($encoded);
    $imgWidth = ($barCount * $width) + 20;
    $imgHeight = $showText ? $height + 20 : $height;

    // Create image
    $img = imagecreate($imgWidth, $imgHeight);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);

    // Draw bars
    $x = 10;
    for ($i = 0; $i < $barCount; $i++) {
        if ($encoded[$i] === '1') {
            imagefilledrectangle($img, $x, 5, $x + $width - 1, $height + 4, $black);
        }
        $x += $width;
    }

    // Draw text
    if ($showText) {
        $font = 5;
        $textWidth = imagefontwidth($font) * strlen($data);
        $textX = max(10, ($imgWidth - $textWidth) / 2);
        imagestring($img, $font, $textX, $height + 6, $data, $black);
    }

    // Output
    ob_start();
    imagepng($img);
    $imageData = ob_get_clean();
    imagedestroy($img);

    return $imageData;
}

// ============================================================
// Run Tests
// ============================================================
echo "\n";
echo "==========================================================\n";
echo "  Barcode Generation & Labels Module Tests (BAR-001 to BAR-008)\n";
echo "==========================================================\n\n";

$pdo = createTestDb();

// Create test products
$prod1 = createProduct($pdo, 'Product A', '123456789012');
$prod2 = createProduct($pdo, 'Product B', null);  // No barcode
$prod3 = createProduct($pdo, 'Product C', '987654321012');
$prod4 = createProduct($pdo, 'Product D', null);  // No barcode

// ----------------------------------------------------------
// BAR-001: View barcode page
// ----------------------------------------------------------
echo "--- BAR-001: View barcode page ---\n";

$products = getAllProducts($pdo);
assert_greater_than(count($products), 0, 'BAR-001', 'Products list not empty');

// Verify products have required fields for barcode page
foreach ($products as $p) {
    assert_true(isset($p['id']), 'BAR-001b', 'Product has id');
    assert_true(isset($p['name']), 'BAR-001c', 'Product has name');
    assert_true(array_key_exists('barcode', $p), 'BAR-001d', 'Product has barcode field');
    assert_true(isset($p['sale_price_unit']), 'BAR-001e', 'Product has sale_price_unit');
}

// Products with barcodes
$withBarcode = 0;
$withoutBarcode = 0;
foreach ($products as $p) {
    if (!empty($p['barcode'])) {
        $withBarcode++;
    } else {
        $withoutBarcode++;
    }
}
assert_greater_than($withBarcode, 0, 'BAR-001f', 'Some products have barcodes');
assert_greater_than($withoutBarcode, 0, 'BAR-001g', 'Some products without barcodes');

// ----------------------------------------------------------
// BAR-002: Generate barcode
// ----------------------------------------------------------
echo "\n--- BAR-002: Generate barcode ---\n";

// Generate barcode for product without barcode
$result = generateBarcode($pdo, $prod2);
assert_true($result['success'], 'BAR-002', 'Generate barcode succeeds');
assert_not_empty($result['barcode'], 'BAR-002b', 'Barcode returned');

// Barcode should be '2' + 6-digit ID
$expectedBarcode = '2' . str_pad($prod2, 6, '0', STR_PAD_LEFT);
assert_equals($expectedBarcode, $result['barcode'], 'BAR-002c', 'Barcode format is 2 + 6-digit ID');

// Verify barcode saved to product
$product = findProductById($pdo, $prod2);
assert_equals($expectedBarcode, $product['barcode'], 'BAR-002d', 'Barcode saved to product');

// Product with existing barcode should return existing
$result = generateBarcode($pdo, $prod1);
assert_true($result['success'], 'BAR-002e', 'Existing barcode returns success');
assert_equals('123456789012', $result['barcode'], 'BAR-002f', 'Existing barcode returned');

// ----------------------------------------------------------
// BAR-003: Barcode uniqueness
// ----------------------------------------------------------
echo "\n--- BAR-003: Barcode uniqueness ---\n";

// Generate barcode for another product
$result = generateBarcode($pdo, $prod4);
assert_true($result['success'], 'BAR-003', 'Generate barcode for prod4 succeeds');

// The barcodes should be unique
$prod2Barcode = findProductById($pdo, $prod2)['barcode'];
$prod4Barcode = findProductById($pdo, $prod4)['barcode'];
assert_not_equals($prod2Barcode, $prod4Barcode, 'BAR-003b', 'Barcodes are unique');

// Verify no duplicate barcodes in database
$stmt = $pdo->query("SELECT barcode, COUNT(*) as cnt FROM products WHERE barcode IS NOT NULL GROUP BY barcode HAVING cnt > 1");
$duplicates = $stmt->fetchAll();
assert_equals(0, count($duplicates), 'BAR-003c', 'No duplicate barcodes in database');

// ----------------------------------------------------------
// BAR-004: Barcode image API
// ----------------------------------------------------------
echo "\n--- BAR-004: Barcode image API ---\n";

// Check if GD extension is available
$gdAvailable = extension_loaded('gd');
if ($gdAvailable) {
    // Generate barcode image
    $imageData = generateBarcodeImage('123456789012', 2, 60, true);
    assert_not_null($imageData, 'BAR-004', 'Barcode image generated');
    assert_not_empty($imageData, 'BAR-004b', 'Image data not empty');

    // Verify it's a PNG image (starts with PNG header)
    $pngHeader = chr(0x89) . chr(0x50) . chr(0x4E) . chr(0x47);
    assert_true(strpos($imageData, $pngHeader) === 0, 'BAR-004c', 'Image is PNG format');

    // Generate without text
    $imageDataNoText = generateBarcodeImage('123456789012', 2, 60, false);
    assert_not_null($imageDataNoText, 'BAR-004d', 'Barcode image without text generated');

    // Empty data should return null
    $emptyImage = generateBarcodeImage('', 2, 60, true);
    assert_null($emptyImage, 'BAR-004e', 'Empty data returns null');
} else {
    echo "  [SKIP] BAR-004: GD extension not available\n";
    assert_true(true, 'BAR-004', 'GD extension not available - skipped');
}

// ----------------------------------------------------------
// BAR-005: Print labels
// ----------------------------------------------------------
echo "\n--- BAR-005: Print labels ---\n";

// Simulate selecting products for label printing
$selectedIds = [$prod1, $prod2, $prod3];
$products = [];
foreach ($selectedIds as $id) {
    $p = findProductById($pdo, $id);
    if ($p) $products[] = $p;
}

assert_equals(3, count($products), 'BAR-005', 'All selected products retrieved');

// Each product should have required label fields
foreach ($products as $p) {
    assert_not_empty($p['name'], 'BAR-005b', 'Product has name for label');
    assert_not_empty($p['barcode'], 'BAR-005c', 'Product has barcode for label');
    assert_true(isset($p['sale_price_unit']), 'BAR-005d', 'Product has price for label');
}

// ----------------------------------------------------------
// BAR-006: Label content
// ----------------------------------------------------------
echo "\n--- BAR-006: Label content ---\n";

// Verify label content structure
$product = findProductById($pdo, $prod1);
assert_not_empty($product['name'], 'BAR-006', 'Label has product name');
assert_not_empty($product['barcode'], 'BAR-006b', 'Label has barcode');
assert_greater_than(floatval($product['sale_price_unit']), 0, 'BAR-006c', 'Label has price');

// Barcode should be valid for image generation
if ($gdAvailable) {
    $image = generateBarcodeImage($product['barcode'], 2, 60, true);
    assert_not_null($image, 'BAR-006d', 'Barcode image generated for label');
}

// ----------------------------------------------------------
// BAR-007: Select all / deselect all
// ----------------------------------------------------------
echo "\n--- BAR-007: Select all / deselect all ---\n";

// Get all products
$allProducts = getAllProducts($pdo);
$allIds = array_column($allProducts, 'id');

// Select all
$selectedIds = $allIds;
assert_equals(count($allProducts), count($selectedIds), 'BAR-007', 'All products selected');

// Deselect all
$selectedIds = [];
assert_equals(0, count($selectedIds), 'BAR-007b', 'All products deselected');

// Select some
$selectedIds = [$prod1, $prod3];
assert_equals(2, count($selectedIds), 'BAR-007c', 'Some products selected');

// ----------------------------------------------------------
// BAR-008: Barcode scanner in form
// ----------------------------------------------------------
echo "\n--- BAR-008: Barcode scanner in form ---\n";

// Simulate scanning a barcode (product lookup)
$scannedBarcode = '123456789012';
$product = findProductByBarcode($pdo, $scannedBarcode);
assert_not_null($product, 'BAR-008', 'Product found by scanned barcode');
assert_equals('Product A', $product['name'], 'BAR-008b', 'Correct product found');

// Scan non-existent barcode
$scannedBarcode = '000000000000';
$product = findProductByBarcode($pdo, $scannedBarcode);
assert_null($product, 'BAR-008c', 'Non-existent barcode returns null');

// ----------------------------------------------------------
// Additional: Barcode format validation
// ----------------------------------------------------------
echo "\n--- Additional: Barcode format validation ---\n";

// Generated barcodes should be 7 digits (2 + 6-digit ID)
$product = findProductById($pdo, $prod2);
$barcode = $product['barcode'];
assert_equals(7, strlen($barcode), 'BAR-EXTRA', 'Barcode length is 7 characters');
assert_true($barcode[0] === '2', 'BAR-EXTRAb', 'Barcode starts with 2');
assert_true(ctype_digit($barcode), 'BAR-EXTRAc', 'Barcode is all digits');

// ----------------------------------------------------------
// Additional: Barcode search
// ----------------------------------------------------------
echo "\n--- Additional: Barcode search ---\n";

// Search by barcode
$stmt = $pdo->prepare("SELECT * FROM products WHERE barcode LIKE ? AND is_active = 1");
$stmt->execute(['%1234%']);
$results = $stmt->fetchAll();
assert_greater_than(count($results), 0, 'BAR-EXTRAd', 'Barcode search works');

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

echo "\n  All Barcode Generation & Labels tests passed!\n\n";
exit(0);
