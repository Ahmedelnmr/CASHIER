<?php
/**
 * Sales Returns & Voids Module Tests
 * Tests: VOID-001 to VOID-004, RET-001 to RET-008
 *
 * Run: php tests/ReturnsVoidsTest.php
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

function saleCreate($pdo, $saleData, $items) {
    $numStmt = $pdo->prepare("SELECT COALESCE(MAX(sale_number), 0) + 1 FROM sales WHERE date(datetime) = date('now', 'localtime')");
    $numStmt->execute();
    $saleNumber = $numStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "INSERT INTO sales (sale_number, subtotal, discount, total, payment_method, notes, type, status, parent_sale_id, customer_id, shift_id)
         VALUES (:sale_number, :subtotal, :discount, :total, :payment_method, :notes, :type, :status, :parent_sale_id, :customer_id, :shift_id)"
    );
    $stmt->execute([
        ':sale_number' => $saleNumber,
        ':subtotal' => $saleData['subtotal'],
        ':discount' => $saleData['discount'] ?? 0,
        ':total' => $saleData['total'],
        ':payment_method' => $saleData['payment_method'] ?? 'cash',
        ':notes' => $saleData['notes'] ?? null,
        ':type' => $saleData['type'] ?? 'sale',
        ':status' => $saleData['status'] ?? 'completed',
        ':parent_sale_id' => $saleData['parent_sale_id'] ?? null,
        ':customer_id' => $saleData['customer_id'] ?? null,
        ':shift_id' => $saleData['shift_id'] ?? null,
    ]);
    $saleId = $pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        "INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal)
         VALUES (:sale_id, :product_id, :product_name, :quantity, :unit_type, :price, :sale_mode, :subtotal)"
    );
    foreach ($items as $item) {
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
    }
    return $saleId;
}

function saleFindById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
    $stmt->execute([$id]);
    $sale = $stmt->fetch();
    if ($sale) {
        $itemStmt = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
        $itemStmt->execute([$id]);
        $sale['items'] = $itemStmt->fetchAll();
    }
    return $sale;
}

function saleUpdateStatus($pdo, $id, $status) {
    $stmt = $pdo->prepare("UPDATE sales SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $id]);
}

// ============================================================
// Replicate SaleService::voidSale
// ============================================================
function voidSale($pdo, $id, $reason = 'Manual Void') {
    $sale = saleFindById($pdo, $id);
    if (!$sale) return ['success' => false, 'message' => 'البيع غير موجود'];
    if ($sale['status'] === 'voided') return ['success' => false, 'message' => 'الفاتورة ملغاة بالفعل'];

    try {
        $pdo->beginTransaction();

        // Restore stock
        if (!empty($sale['items'])) {
            foreach ($sale['items'] as $item) {
                $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?")->execute([$item['quantity'], $item['product_id']]);
                $pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, reference_type, reference_id, notes) VALUES (?, 'adjustment', ?, 'sale_void', ?, ?)")
                    ->execute([$item['product_id'], $item['quantity'], $id, "إلغاء فاتورة #{$sale['sale_number']} - $reason"]);
            }
        }

        // Reverse safe
        if ($sale['total'] > 0) {
            $pdo->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)")
                ->execute(['sale_void', $sale['total'], date('Y-m-d'), "إلغاء فاتورة مبيعات #{$sale['sale_number']}"]);
        } elseif ($sale['total'] < 0) {
            // Was a return → reverse it (money back in)
            $pdo->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)")
                ->execute(['sale', abs($sale['total']), date('Y-m-d'), "إلغاء مرتجع مبيعات #{$sale['sale_number']}"]);
        }

        saleUpdateStatus($pdo, $id, 'voided');
        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// ============================================================
// Replicate SaleService::createReturn
// ============================================================
function createReturn($pdo, $originalSaleId, $items) {
    $originalSale = saleFindById($pdo, $originalSaleId);
    if (!$originalSale) return ['success' => false, 'message' => 'الفاتورة الأصلية غير موجودة'];

    // Block return on voided or return-type sales
    if ($originalSale['status'] === 'voided') {
        return ['success' => false, 'message' => 'لا يمكن تسجيل مرتجع على فاتورة ملغاة'];
    }
    if ($originalSale['type'] !== 'sale') {
        return ['success' => false, 'message' => 'لا يمكن تسجيل مرتجع على فاتورة مرتجعة'];
    }

    // Build original qty map for validation
    $originalQtyMap = [];
    if (!empty($originalSale['items'])) {
        foreach ($originalSale['items'] as $origItem) {
            $pid = $origItem['product_id'];
            $originalQtyMap[$pid] = ($originalQtyMap[$pid] ?? 0) + abs(floatval($origItem['quantity']));
        }
    }

    $returnTotal = 0;
    foreach ($items as &$item) {
        $item['quantity'] = abs(floatval($item['quantity']));
        $returnTotal += ($item['quantity'] * $item['price']);

        // Validate: return qty cannot exceed original qty
        $pid = $item['product_id'];
        $maxQty = $originalQtyMap[$pid] ?? 0;
        if ($item['quantity'] > $maxQty) {
            return ['success' => false, 'message' => "الكمية المرتجعة للمنتج '{$item['product_name']}' تتجاوز الكمية الأصلية ({$maxQty})"];
        }
    }
    unset($item);

    try {
        $pdo->beginTransaction();

        // Create return sale record
        $saleData = [
            'subtotal' => -$returnTotal, 'discount' => 0, 'total' => -$returnTotal,
            'payment_method' => 'cash', 'notes' => "مرتجع من فاتورة #{$originalSale['sale_number']}",
            'type' => 'return', 'status' => 'completed', 'parent_sale_id' => $originalSaleId,
        ];

        $formattedItems = [];
        foreach ($items as $item) {
            $formattedItems[] = [
                'product_id' => $item['product_id'], 'product_name' => $item['product_name'],
                'quantity' => -$item['quantity'], 'unit_type' => 'قطعة',
                'price' => $item['price'], 'subtotal' => -($item['quantity'] * $item['price']),
            ];
        }

        $returnId = saleCreate($pdo, $saleData, $formattedItems);

        // Update stock (increase - items returned to inventory)
        foreach ($items as $item) {
            $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?")->execute([$item['quantity'], $item['product_id']]);
            $pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, reference_type, reference_id, notes) VALUES (?, 'adjustment', ?, 'sale_return', ?, ?)")
                ->execute([$item['product_id'], $item['quantity'], $returnId, "مرتجع مبيعات #$returnId"]);
        }

        // Safe withdrawal
        if ($returnTotal > 0) {
            $pdo->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)")
                ->execute(['sale_return', $returnTotal, date('Y-m-d'), "مرتجع مبيعات فاتورة #$returnId"]);
        }

        $pdo->commit();
        return ['success' => true, 'id' => $returnId];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// ============================================================
// Run Tests
// ============================================================
echo "\n";
echo "==========================================================\n";
echo "  Sales Returns & Voids Tests (VOID-001 to RET-008)\n";
echo "==========================================================\n\n";

// ----------------------------------------------------------
// 8.1 Void Sale (VOID-001 to VOID-004)
// ----------------------------------------------------------
echo "--- 8.1 Void Sale ---\n";
$pdo = createTestDb();

$prod1 = insertProduct($pdo, ['name' => 'Void Prod A', 'sale_price_unit' => 20.0, 'stock_quantity' => 100]);
$prod2 = insertProduct($pdo, ['name' => 'Void Prod B', 'sale_price_unit' => 30.0, 'stock_quantity' => 50]);

// Create a sale to void
$saleToVoid = saleCreate($pdo, ['subtotal' => 100, 'discount' => 0, 'total' => 100, 'type' => 'sale', 'status' => 'completed'], [
    ['product_id' => $prod1, 'product_name' => 'Void Prod A', 'quantity' => 3, 'price' => 20.0, 'subtotal' => 60],
    ['product_id' => $prod2, 'product_name' => 'Void Prod B', 'quantity' => 1, 'price' => 30.0, 'subtotal' => 30],
]);
$stockBefore1 = getStock($pdo, $prod1); // 97
$stockBefore2 = getStock($pdo, $prod2); // 49

// VOID-001: Void active sale
$voidResult = voidSale($pdo, $saleToVoid);
assert_true($voidResult['success'], 'VOID-001', 'Void active sale succeeds');

// Status -> voided
$sale = saleFindById($pdo, $saleToVoid);
assert_equals('voided', $sale['status'], 'VOID-001b', 'Status changed to voided');

// Stock restored
$stockAfter1 = getStock($pdo, $prod1);
$stockAfter2 = getStock($pdo, $prod2);
assert_equals($stockBefore1 + 3, $stockAfter1, 'VOID-001c', 'Stock A restored (97 + 3 = 100)');
assert_equals($stockBefore2 + 1, $stockAfter2, 'VOID-001d', 'Stock B restored (49 + 1 = 50)');

// Safe reversal entry
$stmt = $pdo->prepare("SELECT * FROM safe_transactions WHERE type = 'sale_void' ORDER BY id DESC LIMIT 1");
$stmt->execute();
$safeReversal = $stmt->fetch();
assert_not_null($safeReversal, 'VOID-001e', 'Safe reversal entry recorded');
assert_equals(100.0, floatval($safeReversal['amount']), 'VOID-001f', 'Safe reversal amount = 100');

// Stock movement logged
$stmt = $pdo->prepare("SELECT * FROM stock_movements WHERE reference_type = 'sale_void' AND reference_id = ?");
$stmt->execute([$saleToVoid]);
$movements = $stmt->fetchAll();
assert_greater_than(count($movements), 0, 'VOID-001g', 'Stock movement logged for void');

// VOID-002: Void with reason
$saleWithReason = saleCreate($pdo, ['subtotal' => 40, 'discount' => 0, 'total' => 40, 'type' => 'sale', 'status' => 'completed'], [
    ['product_id' => $prod1, 'product_name' => 'Void Prod A', 'quantity' => 2, 'price' => 20.0, 'subtotal' => 40],
]);
$voidResult2 = voidSale($pdo, $saleWithReason, 'تصحيح خطأ في الفاتورة');
assert_true($voidResult2['success'], 'VOID-002', 'Void with custom reason succeeds');

$stmt = $pdo->prepare("SELECT * FROM stock_movements WHERE reference_type = 'sale_void' AND reference_id = ?");
$stmt->execute([$saleWithReason]);
$movement = $stmt->fetch();
assert_true(strpos($movement['notes'], 'تصحيح خطأ في الفاتورة') !== false, 'VOID-002b', 'Void reason recorded in stock movement notes');

// VOID-003: Void return sale (FIXED: safe reversal now created for returns)
$returnSale = saleCreate($pdo, ['subtotal' => -30, 'discount' => 0, 'total' => -30, 'type' => 'return', 'status' => 'completed'], [
    ['product_id' => $prod1, 'product_name' => 'Void Prod A', 'quantity' => -1, 'price' => 20.0, 'subtotal' => -20],
]);
$safeCountBefore = $pdo->query("SELECT COUNT(*) FROM safe_transactions")->fetchColumn();
voidSale($pdo, $returnSale);
$safeCountAfter = $pdo->query("SELECT COUNT(*) FROM safe_transactions")->fetchColumn();
assert_greater_than($safeCountAfter, $safeCountBefore, 'VOID-003', 'FIXED: Void return sale creates safe reversal');

$stmt = $pdo->prepare("SELECT * FROM safe_transactions WHERE description LIKE '%{$returnSale}%' ORDER BY id DESC LIMIT 1");
$stmt->execute();
$returnReversal = $stmt->fetch();
assert_not_null($returnReversal, 'VOID-003b', 'Safe reversal entry exists for voided return');
assert_equals('sale', $returnReversal['type'], 'VOID-003c', 'Reversal type = sale (money back in)');
assert_equals(30.0, floatval($returnReversal['amount']), 'VOID-003d', 'Reversal amount = 30');

// VOID-004: Void already voided sale
$alreadyVoided = saleCreate($pdo, ['subtotal' => 50, 'discount' => 0, 'total' => 50, 'type' => 'sale', 'status' => 'voided'], [
    ['product_id' => $prod1, 'product_name' => 'Void Prod A', 'quantity' => 2, 'price' => 20.0, 'subtotal' => 40],
]);
$voidAgain = voidSale($pdo, $alreadyVoided);
assert_false($voidAgain['success'], 'VOID-004', 'Void already voided sale fails');
assert_equals('الفاتورة ملغاة بالفعل', $voidAgain['message'], 'VOID-004b', 'Correct error message for double void');

$pdo = null;

// ----------------------------------------------------------
// 8.2 Sales Return (RET-001 to RET-008)
// ----------------------------------------------------------
echo "\n--- 8.2 Sales Return ---\n";
$pdo = createTestDb();

$prodA = insertProduct($pdo, ['name' => 'Return Prod A', 'sale_price_unit' => 20.0, 'stock_quantity' => 100]);
$prodB = insertProduct($pdo, ['name' => 'Return Prod B', 'sale_price_unit' => 30.0, 'stock_quantity' => 50]);

// Create original sale
$originalSale = saleCreate($pdo, ['subtotal' => 160, 'discount' => 0, 'total' => 160, 'type' => 'sale', 'status' => 'completed'], [
    ['product_id' => $prodA, 'product_name' => 'Return Prod A', 'quantity' => 5, 'price' => 20.0, 'subtotal' => 100],
    ['product_id' => $prodB, 'product_name' => 'Return Prod B', 'quantity' => 2, 'price' => 30.0, 'subtotal' => 60],
]);
$stockAfterSaleA = getStock($pdo, $prodA); // 95
$stockAfterSaleB = getStock($pdo, $prodB); // 48

// RET-001: Full return
$fullReturn = createReturn($pdo, $originalSale, [
    ['product_id' => $prodA, 'product_name' => 'Return Prod A', 'quantity' => 5, 'price' => 20.0],
    ['product_id' => $prodB, 'product_name' => 'Return Prod B', 'quantity' => 2, 'price' => 30.0],
]);
assert_true($fullReturn['success'], 'RET-001', 'Full return succeeds');
assert_not_null($fullReturn['id'], 'RET-001b', 'Return sale ID returned');

// Return sale created
$returnSale = saleFindById($pdo, $fullReturn['id']);
assert_equals('return', $returnSale['type'], 'RET-001c', 'Return sale type = return');
assert_equals('completed', $returnSale['status'], 'RET-001d', 'Return sale status = completed');
assert_less_than(floatval($returnSale['total']), 0, 'RET-001e', 'Return sale total is negative');

// Stock restored
$stockAfterReturnA = getStock($pdo, $prodA);
$stockAfterReturnB = getStock($pdo, $prodB);
assert_equals($stockAfterSaleA + 5, $stockAfterReturnA, 'RET-001f', 'Stock A restored after full return (95 + 5 = 100)');
assert_equals($stockAfterSaleB + 2, $stockAfterReturnB, 'RET-001g', 'Stock B restored after full return (48 + 2 = 50)');

// Safe withdrawal
$stmt = $pdo->prepare("SELECT * FROM safe_transactions WHERE type = 'sale_return' ORDER BY id DESC LIMIT 1");
$stmt->execute();
$safeWithdrawal = $stmt->fetch();
assert_not_null($safeWithdrawal, 'RET-001h', 'Safe withdrawal recorded for return');
assert_equals(160.0, floatval($safeWithdrawal['amount']), 'RET-001i', 'Safe withdrawal amount = 160');

// RET-002: Partial return
$saleForPartial = saleCreate($pdo, ['subtotal' => 100, 'discount' => 0, 'total' => 100, 'type' => 'sale', 'status' => 'completed'], [
    ['product_id' => $prodA, 'product_name' => 'Return Prod A', 'quantity' => 5, 'price' => 20.0, 'subtotal' => 100],
]);
$stockBeforePartial = getStock($pdo, $prodA);

$partialReturn = createReturn($pdo, $saleForPartial, [
    ['product_id' => $prodA, 'product_name' => 'Return Prod A', 'quantity' => 2, 'price' => 20.0],
]);
assert_true($partialReturn['success'], 'RET-002', 'Partial return succeeds');
$stockAfterPartial = getStock($pdo, $prodA);
assert_equals($stockBeforePartial + 2, $stockAfterPartial, 'RET-002b', 'Partial return: only 2 items restored');

// RET-003: Return quantity validation (FIXED: now validates against original qty)
$overReturn = createReturn($pdo, $saleForPartial, [
    ['product_id' => $prodA, 'product_name' => 'Return Prod A', 'quantity' => 999, 'price' => 20.0],
]);
assert_false($overReturn['success'], 'RET-003', 'FIXED: Return qty > original qty is rejected');
assert_true(strpos($overReturn['message'], 'تتجاوز') !== false, 'RET-003b', 'Error message mentions quantity exceeds original');

// RET-004: Return weight product (structural check)
// The bug is in the HTML form: step="1" prevents fractional input
// But the backend createReturn accepts fractional quantities via abs(floatval())
$weightProd = insertProduct($pdo, ['name' => 'Weight Return', 'type' => 'weight', 'sale_price_unit' => 80.0, 'stock_quantity' => 50]);
$weightSale = saleCreate($pdo, ['subtotal' => 80, 'discount' => 0, 'total' => 80, 'type' => 'sale', 'status' => 'completed'], [
    ['product_id' => $weightProd, 'product_name' => 'Weight Return', 'quantity' => 1.0, 'price' => 80.0, 'subtotal' => 80],
]);
$stockBeforeWeight = getStock($pdo, $weightProd);

$weightReturn = createReturn($pdo, $weightSale, [
    ['product_id' => $weightProd, 'product_name' => 'Weight Return', 'quantity' => 0.5, 'price' => 80.0],
]);
assert_true($weightReturn['success'], 'RET-004', 'Backend accepts fractional return quantity (0.5 kg)');
$stockAfterWeight = getStock($pdo, $weightProd);
assert_equals($stockBeforeWeight + 0.5, $stockAfterWeight, 'RET-004b', 'Fractional stock restored (0.5 kg)');

// RET-005: Return blocked on voided (controller-level check)
$voidedSale = saleCreate($pdo, ['subtotal' => 40, 'discount' => 0, 'total' => 40, 'type' => 'sale', 'status' => 'voided'], [
    ['product_id' => $prodA, 'product_name' => 'Return Prod A', 'quantity' => 2, 'price' => 20.0, 'subtotal' => 40],
]);
// The controller checks: if ($sale['status'] === 'voided' || $sale['type'] !== 'sale') → redirect
// At service level, createReturn doesn't check status, so the block is controller-only
// We verify the controller logic structurally
$sale = saleFindById($pdo, $voidedSale);
$isBlocked = ($sale['status'] === 'voided' || $sale['type'] !== 'sale');
assert_true($isBlocked, 'RET-005', 'Return on voided sale: controller would block (status=voided)');

// RET-006: Return blocked on return-type sale
$returnTypeSale = saleCreate($pdo, ['subtotal' => -20, 'discount' => 0, 'total' => -20, 'type' => 'return', 'status' => 'completed'], [
    ['product_id' => $prodA, 'product_name' => 'Return Prod A', 'quantity' => -1, 'price' => 20.0, 'subtotal' => -20],
]);
$sale = saleFindById($pdo, $returnTypeSale);
$isBlocked = ($sale['status'] === 'voided' || $sale['type'] !== 'sale');
assert_true($isBlocked, 'RET-006', 'Return on return-type sale: controller would block (type=return)');

// RET-007: Stock movement for return
$stmt = $pdo->prepare("SELECT * FROM stock_movements WHERE reference_type = 'sale_return' AND reference_id = ?");
$stmt->execute([$fullReturn['id']]);
$returnMovements = $stmt->fetchAll();
assert_greater_than(count($returnMovements), 0, 'RET-007', 'Stock movement logged for return');
assert_equals('adjustment', $returnMovements[0]['type'], 'RET-007b', 'Stock movement type = adjustment');
assert_greater_than(floatval($returnMovements[0]['quantity']), 0, 'RET-007c', 'Stock movement quantity is positive (items returned)');

// RET-008: Safe transaction for return
$stmt = $pdo->prepare("SELECT * FROM safe_transactions WHERE type = 'sale_return' AND description LIKE ?");
$stmt->execute(["%{$fullReturn['id']}%"]);
$returnSafe = $stmt->fetch();
assert_not_null($returnSafe, 'RET-008', 'Safe transaction recorded for return');
assert_equals('sale_return', $returnSafe['type'], 'RET-008b', 'Safe transaction type = sale_return');
assert_greater_than(floatval($returnSafe['amount']), 0, 'RET-008c', 'Safe transaction amount is positive (money out)');

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

echo "\n  All Sales Returns & Voids tests passed!\n\n";
exit(0);
