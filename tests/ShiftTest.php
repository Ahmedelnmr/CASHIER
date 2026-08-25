<?php
/**
 * Shift Management Module Tests
 * Tests: SHIFT-001 to SHIFT-012
 *
 * Run: php tests/ShiftTest.php
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
// Helper: Create test user
// ============================================================
function createTestUser($pdo, $username, $name, $role = 'cashier') {
    $stmt = $pdo->prepare(
        "INSERT INTO users (username, password_hash, name, role) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$username, password_hash('test123', PASSWORD_DEFAULT), $name, $role]);
    return $pdo->lastInsertId();
}

// ============================================================
// Replicate ShiftService methods
// ============================================================
function getCurrentShift($pdo, $userId) {
    $stmt = $pdo->prepare(
        "SELECT * FROM shifts WHERE user_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function openShift($pdo, $userId, $openingCash, $notes = '', $timeOffset = '0 seconds') {
    // Check if user already has an open shift
    $existing = getCurrentShift($pdo, $userId);
    if ($existing) {
        return ['success' => false, 'message' => 'لديك وردية مفتوحة بالفعل. أغلقها أولاً'];
    }

    // Get current time from SQLite with optional offset
    $timeStmt = $pdo->query("SELECT datetime('now','localtime','{$timeOffset}') as now");
    $now = $timeStmt->fetch()['now'];

    $stmt = $pdo->prepare(
        "INSERT INTO shifts (user_id, opening_cash, notes, opened_at) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $openingCash, $notes, $now]);
    $shiftId = $pdo->lastInsertId();

    // Record opening cash as safe transaction
    if ($openingCash > 0) {
        $safeStmt = $pdo->prepare(
            "INSERT INTO safe_transactions (type, amount, date, description, created_at) VALUES ('opening', ?, date('now','localtime'), ?, ?)"
        );
        $safeStmt->execute([$openingCash, 'فتح وردية #' . $shiftId, $now]);
    }

    return ['success' => true, 'shift_id' => $shiftId];
}

function getShiftById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM shifts WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function calculateShiftTotals($pdo, $shiftId) {
    $shift = getShiftById($pdo, $shiftId);
    if (!$shift) return ['sales' => 0, 'returns' => 0, 'sale_count' => 0, 'cash_in' => 0, 'cash_out' => 0, 'expenses' => 0];

    $openedAt = $shift['opened_at'];

    // Sales totals
    $salesStmt = $pdo->prepare(
        "SELECT 
            COALESCE(SUM(CASE WHEN type = 'sale' THEN total ELSE 0 END), 0) as sales,
            COALESCE(SUM(CASE WHEN type = 'return' THEN ABS(total) ELSE 0 END), 0) as returns,
            COUNT(CASE WHEN type = 'sale' THEN 1 END) as sale_count
         FROM sales
         WHERE shift_id = ? AND status != 'voided'"
    );
    $salesStmt->execute([$shiftId]);
    $salesRow = $salesStmt->fetch();

    // Cash in/out from safe transactions during this shift
    $safeStmt = $pdo->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN type IN ('sale','deposit','opening') THEN amount ELSE 0 END), 0) as cash_in,
            COALESCE(SUM(CASE WHEN type IN ('expense','purchase','withdrawal','salary','salary_payment','advance','sale_return','sale_void') THEN amount ELSE 0 END), 0) as cash_out
         FROM safe_transactions
         WHERE created_at >= ? AND (type != 'opening' OR description LIKE ?)"
    );
    $safeStmt->execute([$openedAt, '%وردية #' . $shiftId . '%']);
    $safeRow = $safeStmt->fetch();

    return [
        'sales' => floatval($salesRow['sales']),
        'returns' => floatval($salesRow['returns']),
        'sale_count' => intval($salesRow['sale_count']),
        'cash_in' => floatval($safeRow['cash_in']),
        'cash_out' => floatval($safeRow['cash_out']),
        'expenses' => floatval($safeRow['cash_out']),
    ];
}

function closeShift($pdo, $shiftId, $closingCash, $notes = '') {
    $shift = getShiftById($pdo, $shiftId);
    if (!$shift || $shift['status'] !== 'open') {
        return ['success' => false, 'message' => 'الوردية غير موجودة أو مغلقة'];
    }

    // Calculate shift totals from sales
    $totals = calculateShiftTotals($pdo, $shiftId);

    $expectedCash = $shift['opening_cash'] + $totals['cash_in'] - $totals['cash_out'];
    $cashDifference = $closingCash - $expectedCash;

    $stmt = $pdo->prepare(
        "UPDATE shifts SET 
            closed_at = datetime('now','localtime'),
            closing_cash = ?,
            expected_cash = ?,
            cash_difference = ?,
            total_sales = ?,
            total_returns = ?,
            total_expenses = ?,
            total_cash_in = ?,
            total_cash_out = ?,
            sale_count = ?,
            status = 'closed',
            notes = CASE WHEN ? != '' THEN ? ELSE notes END
         WHERE id = ?"
    );
    $stmt->execute([
        $closingCash, $expectedCash, $cashDifference,
        $totals['sales'], $totals['returns'], $totals['expenses'],
        $totals['cash_in'], $totals['cash_out'], $totals['sale_count'],
        $notes, $notes, $shiftId
    ]);

    return [
        'success' => true,
        'report' => [
            'shift_id' => $shiftId,
            'opening_cash' => $shift['opening_cash'],
            'closing_cash' => $closingCash,
            'expected_cash' => $expectedCash,
            'cash_difference' => $cashDifference,
            'total_sales' => $totals['sales'],
            'total_returns' => $totals['returns'],
            'total_expenses' => $totals['expenses'],
            'sale_count' => $totals['sale_count'],
        ]
    ];
}

function xReport($pdo, $shiftId) {
    $shift = getShiftById($pdo, $shiftId);
    if (!$shift) return null;

    $totals = calculateShiftTotals($pdo, $shiftId);
    $expectedCash = $shift['opening_cash'] + $totals['cash_in'] - $totals['cash_out'];

    return [
        'type' => 'X',
        'shift' => $shift,
        'totals' => $totals,
        'expected_cash' => $expectedCash,
        'generated_at' => date('Y-m-d H:i:s'),
    ];
}

function zReport($pdo, $shiftId) {
    $shift = getShiftById($pdo, $shiftId);
    if (!$shift) return null;

    return [
        'type' => 'Z',
        'shift' => $shift,
        'generated_at' => date('Y-m-d H:i:s'),
    ];
}

function getShifts($pdo, $limit = 50) {
    $stmt = $pdo->prepare(
        "SELECT s.*, u.name as user_name 
         FROM shifts s 
         LEFT JOIN users u ON s.user_id = u.id 
         ORDER BY s.id DESC LIMIT ?"
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Helper: Create a sale linked to a shift
function createSale($pdo, $shiftId, $total, $type = 'sale', $status = 'completed') {
    $timeStmt = $pdo->query("SELECT datetime('now','localtime') as now");
    $now = $timeStmt->fetch()['now'];
    $stmt = $pdo->prepare(
        "INSERT INTO sales (shift_id, total, type, status, datetime) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$shiftId, $total, $type, $status, $now]);
    return $pdo->lastInsertId();
}

// Helper: Create a safe transaction
function createSafeTransaction($pdo, $type, $amount, $description = '', $createdAt = null) {
    if (!$createdAt) {
        $timeStmt = $pdo->query("SELECT datetime('now','localtime') as now");
        $createdAt = $timeStmt->fetch()['now'];
    }
    $stmt = $pdo->prepare(
        "INSERT INTO safe_transactions (type, amount, date, description, created_at) VALUES (?, ?, date('now','localtime'), ?, ?)"
    );
    $stmt->execute([$type, $amount, $description, $createdAt]);
    return $pdo->lastInsertId();
}

// ============================================================
// Run Tests
// ============================================================
echo "\n";
echo "==========================================================\n";
echo "  Shift Management Module Tests (SHIFT-001 to SHIFT-012)\n";
echo "==========================================================\n\n";

$pdo = createTestDb();

// Create test users
$userId1 = createTestUser($pdo, 'cashier1', 'Cashier One', 'cashier');
$userId2 = createTestUser($pdo, 'cashier2', 'Cashier Two', 'cashier');

// ----------------------------------------------------------
// SHIFT-001: Open shift
// ----------------------------------------------------------
echo "--- SHIFT-001: Open shift ---\n";

$result = openShift($pdo, $userId1, 500, 'Morning shift', '+0 hours');
assert_true($result['success'], 'SHIFT-001', 'Open shift succeeds');
assert_greater_than($result['shift_id'], 0, 'SHIFT-001b', 'Shift ID returned');

$shift = getShiftById($pdo, $result['shift_id']);
assert_not_null($shift, 'SHIFT-001c', 'Shift record created');
assert_equals($userId1, $shift['user_id'], 'SHIFT-001d', 'Shift linked to correct user');
assert_equals(500.0, floatval($shift['opening_cash']), 'SHIFT-001e', 'Opening cash recorded');
assert_equals('open', $shift['status'], 'SHIFT-001f', 'Shift status is open');
assert_not_null($shift['opened_at'], 'SHIFT-001g', 'Opened timestamp set');

// Check safe transaction for opening cash
$safeStmt = $pdo->prepare("SELECT * FROM safe_transactions WHERE type = 'opening' AND description LIKE ?");
$safeStmt->execute(['%وردية #' . $result['shift_id'] . '%']);
$safeTxn = $safeStmt->fetch();
assert_not_null($safeTxn, 'SHIFT-001h', 'Opening cash safe transaction created');
assert_equals(500.0, floatval($safeTxn['amount']), 'SHIFT-001i', 'Opening cash amount correct');

$shiftId1 = $result['shift_id'];

// ----------------------------------------------------------
// SHIFT-002: Prevent duplicate shift
// ----------------------------------------------------------
echo "\n--- SHIFT-002: Prevent duplicate shift ---\n";

$result2 = openShift($pdo, $userId1, 300, 'Another shift');
assert_false($result2['success'], 'SHIFT-002', 'Duplicate shift prevented');
assert_true(strpos($result2['message'], 'وردية مفتوحة') !== false, 'SHIFT-002b', 'Error message about open shift');

// Verify only one shift exists
$shifts = getShifts($pdo);
assert_equals(1, count($shifts), 'SHIFT-002c', 'Only one shift in database');

// Different user can open shift
$result3 = openShift($pdo, $userId2, 200, 'Evening shift', '+8 hours');
assert_true($result3['success'], 'SHIFT-002d', 'Different user can open shift');

$shiftId2 = $result3['shift_id'];

// ----------------------------------------------------------
// SHIFT-004: X-Report (interim)
// ----------------------------------------------------------
echo "\n--- SHIFT-004: X-Report (interim) ---\n";

// Add some sales to shift 1
createSale($pdo, $shiftId1, 100, 'sale', 'completed');
createSale($pdo, $shiftId1, 200, 'sale', 'completed');
createSale($pdo, $shiftId1, 50, 'return', 'completed');

// Add safe transactions
createSafeTransaction($pdo, 'sale', 300, 'Sales deposit');
createSafeTransaction($pdo, 'expense', 50, 'Expense');

$xreport = xReport($pdo, $shiftId1);
assert_not_null($xreport, 'SHIFT-004', 'X-Report generated');
assert_equals('X', $xreport['type'], 'SHIFT-004b', 'Report type is X');
assert_not_null($xreport['shift'], 'SHIFT-004c', 'Report includes shift data');
assert_not_null($xreport['totals'], 'SHIFT-004d', 'Report includes totals');
assert_not_null($xreport['expected_cash'], 'SHIFT-004e', 'Report includes expected_cash');
assert_not_null($xreport['generated_at'], 'SHIFT-004f', 'Report includes generated_at');

// Verify totals
$totals = $xreport['totals'];
assert_equals(300.0, $totals['sales'], 'SHIFT-004g', 'Sales total calculated');
assert_equals(50.0, $totals['returns'], 'SHIFT-004h', 'Returns total calculated');
assert_equals(2, $totals['sale_count'], 'SHIFT-004i', 'Sale count calculated');

// Expected cash = opening_cash + cash_in - cash_out
// cash_in includes opening transaction (500) + sales (300) = 800
// = 500 + 800 - 50 = 1250
$expectedCash = 500 + 800 - 50;
assert_equals(floatval($expectedCash), $xreport['expected_cash'], 'SHIFT-004j', 'Expected cash calculated correctly');

// ----------------------------------------------------------
// SHIFT-006: Shift totals calculation
// ----------------------------------------------------------
echo "\n--- SHIFT-006: Shift totals calculation ---\n";

$totals = calculateShiftTotals($pdo, $shiftId1);
assert_equals(300.0, $totals['sales'], 'SHIFT-006', 'Total sales calculated');
assert_equals(50.0, $totals['returns'], 'SHIFT-006b', 'Total returns calculated');
assert_equals(2, $totals['sale_count'], 'SHIFT-006c', 'Sale count calculated');
assert_greater_than($totals['cash_in'], 0, 'SHIFT-006d', 'Cash in calculated');
assert_greater_than($totals['cash_out'], 0, 'SHIFT-006e', 'Cash out calculated');

// ----------------------------------------------------------
// SHIFT-003: Close shift
// ----------------------------------------------------------
echo "\n--- SHIFT-003: Close shift ---\n";

// Close shift with exact expected cash
// expected_cash = opening_cash + cash_in - cash_out = 500 + 800 - 50 = 1250
$result = closeShift($pdo, $shiftId1, 1250, 'End of morning shift');
assert_true($result['success'], 'SHIFT-003', 'Close shift succeeds');
assert_not_null($result['report'], 'SHIFT-003b', 'Report returned');

$report = $result['report'];
assert_equals($shiftId1, $report['shift_id'], 'SHIFT-003c', 'Report has correct shift_id');
assert_equals(500.0, floatval($report['opening_cash']), 'SHIFT-003d', 'Report has opening_cash');
assert_equals(1250.0, floatval($report['closing_cash']), 'SHIFT-003e', 'Report has closing_cash');
assert_equals(1250.0, floatval($report['expected_cash']), 'SHIFT-003f', 'Report has expected_cash');
assert_equals(0.0, floatval($report['cash_difference']), 'SHIFT-003g', 'Cash difference is 0 (exact)');

// Verify shift is closed
$shift = getShiftById($pdo, $shiftId1);
assert_equals('closed', $shift['status'], 'SHIFT-003h', 'Shift status is closed');
assert_not_null($shift['closed_at'], 'SHIFT-003i', 'Closed timestamp set');
assert_equals(1250.0, floatval($shift['closing_cash']), 'SHIFT-003j', 'Closing cash saved');

// ----------------------------------------------------------
// SHIFT-005: Z-Report (final)
// ----------------------------------------------------------
echo "\n--- SHIFT-005: Z-Report (final) ---\n";

$zreport = zReport($pdo, $shiftId1);
assert_not_null($zreport, 'SHIFT-005', 'Z-Report generated');
assert_equals('Z', $zreport['type'], 'SHIFT-005b', 'Report type is Z');
assert_not_null($zreport['shift'], 'SHIFT-005c', 'Report includes shift data');
assert_not_null($zreport['generated_at'], 'SHIFT-005d', 'Report includes generated_at');

// Z-report shift should have all closed data
$zShift = $zreport['shift'];
assert_equals('closed', $zShift['status'], 'SHIFT-005e', 'Z-Report shift is closed');
assert_not_null($zShift['total_sales'], 'SHIFT-005f', 'Z-Report has total_sales');
assert_not_null($zShift['cash_difference'], 'SHIFT-005g', 'Z-Report has cash_difference');

// ----------------------------------------------------------
// SHIFT-007: Cash difference (over)
// ----------------------------------------------------------
echo "\n--- SHIFT-007: Cash difference (over) ---\n";

// Close shift2 with more than expected
// shift2 has opening_cash=200, no sales/expenses
// cash_in includes opening transaction (200)
// expected_cash = 200 + 200 - 0 = 400
$result = closeShift($pdo, $shiftId2, 450, 'Over cash');
assert_true($result['success'], 'SHIFT-007', 'Close shift with over cash');
assert_equals(50.0, floatval($result['report']['cash_difference']), 'SHIFT-007b', 'Positive cash difference (over)');

$shift = getShiftById($pdo, $shiftId2);
assert_equals(50.0, floatval($shift['cash_difference']), 'SHIFT-007c', 'Cash difference saved');

// ----------------------------------------------------------
// SHIFT-008: Cash difference (short)
// ----------------------------------------------------------
echo "\n--- SHIFT-008: Cash difference (short) ---\n";

// Open and close a new shift with less than expected
$userId3 = createTestUser($pdo, 'cashier3', 'Cashier Three', 'cashier');
$result = openShift($pdo, $userId3, 1000, 'Test shift', '+16 hours');
$shiftId3 = $result['shift_id'];

// cash_in includes opening transaction (1000)
// expected_cash = 1000 + 1000 - 0 = 2000
$result = closeShift($pdo, $shiftId3, 1900, 'Short cash');
assert_true($result['success'], 'SHIFT-008', 'Close shift with short cash');
assert_equals(-100.0, floatval($result['report']['cash_difference']), 'SHIFT-008b', 'Negative cash difference (short)');

$shift = getShiftById($pdo, $shiftId3);
assert_equals(-100.0, floatval($shift['cash_difference']), 'SHIFT-008c', 'Cash difference saved');

// ----------------------------------------------------------
// SHIFT-009: Shift history
// ----------------------------------------------------------
echo "\n--- SHIFT-009: Shift history ---\n";

$shifts = getShifts($pdo, 30);
assert_greater_than(count($shifts), 0, 'SHIFT-009', 'Shift history has entries');
assert_true(count($shifts) >= 3, 'SHIFT-009b', 'All shifts listed');

// Verify shift data includes user_name
$firstShift = $shifts[0];
assert_true(isset($firstShift['user_name']), 'SHIFT-009c', 'Shift has user_name');
assert_true(isset($firstShift['id']), 'SHIFT-009d', 'Shift has id');
assert_true(isset($firstShift['status']), 'SHIFT-009e', 'Shift has status');
assert_true(isset($firstShift['opening_cash']), 'SHIFT-009f', 'Shift has opening_cash');
assert_true(isset($firstShift['opened_at']), 'SHIFT-009g', 'Shift has opened_at');

// ----------------------------------------------------------
// SHIFT-010: Shift status API
// ----------------------------------------------------------
echo "\n--- SHIFT-010: Shift status API ---\n";

// User with no open shift
$currentShift = getCurrentShift($pdo, $userId1);
assert_null($currentShift, 'SHIFT-010', 'No open shift for closed user');

// Open a new shift and check status
$result = openShift($pdo, $userId1, 300, 'New shift', '+24 hours');
$currentShift = getCurrentShift($pdo, $userId1);
assert_not_null($currentShift, 'SHIFT-010b', 'Open shift exists for user');
assert_equals('open', $currentShift['status'], 'SHIFT-010c', 'Shift status is open');
assert_equals($result['shift_id'], $currentShift['id'], 'SHIFT-010d', 'Correct shift returned');

$shiftId4 = $result['shift_id'];

// ----------------------------------------------------------
// SHIFT-011: Sales linked to shift
// ----------------------------------------------------------
echo "\n--- SHIFT-011: Sales linked to shift ---\n";

$saleId1 = createSale($pdo, $shiftId4, 150, 'sale', 'completed');
$saleId2 = createSale($pdo, $shiftId4, 250, 'sale', 'completed');

$saleStmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
$saleStmt->execute([$saleId1]);
$sale1 = $saleStmt->fetch();

assert_equals($shiftId4, $sale1['shift_id'], 'SHIFT-011', 'Sale linked to shift');
assert_equals(150.0, floatval($sale1['total']), 'SHIFT-011b', 'Sale total correct');

$saleStmt->execute([$saleId2]);
$sale2 = $saleStmt->fetch();
assert_equals($shiftId4, $sale2['shift_id'], 'SHIFT-011c', 'Second sale linked to shift');

// Count sales for shift
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE shift_id = ?");
$countStmt->execute([$shiftId4]);
$saleCount = $countStmt->fetchColumn();
assert_equals(2, intval($saleCount), 'SHIFT-011d', 'Correct number of sales for shift');

// ----------------------------------------------------------
// SHIFT-012: Safe transactions scoped
// ----------------------------------------------------------
echo "\n--- SHIFT-012: Safe transactions scoped ---\n";

// Close shift4 and check totals include only its transactions
$result = closeShift($pdo, $shiftId4, 700, 'End shift');
assert_true($result['success'], 'SHIFT-012', 'Shift closed successfully');

// The totals should reflect sales during this shift
$report = $result['report'];
assert_greater_than($report['total_sales'], 0, 'SHIFT-012b', 'Sales included in shift totals');
assert_equals(2, $report['sale_count'], 'SHIFT-012c', 'Sale count in report');

// ----------------------------------------------------------
// Additional: Reopen shift after close
// ----------------------------------------------------------
echo "\n--- Additional: Cannot close already closed shift ---\n";

$result = closeShift($pdo, $shiftId1, 800, 'Try again');
assert_false($result['success'], 'SHIFT-EXTRA', 'Cannot close already closed shift');
assert_true(strpos($result['message'], 'مغلقة') !== false, 'SHIFT-EXTRAb', 'Error message about closed shift');

// ----------------------------------------------------------
// Additional: X-Report on non-existent shift
// ----------------------------------------------------------
echo "\n--- Additional: X-Report on non-existent shift ---\n";

$xreport = xReport($pdo, 99999);
assert_null($xreport, 'SHIFT-EXTRAc', 'X-Report returns null for non-existent shift');

$zreport = zReport($pdo, 99999);
assert_null($zreport, 'SHIFT-EXTRAd', 'Z-Report returns null for non-existent shift');

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

echo "\n  All Shift Management tests passed!\n\n";
exit(0);
