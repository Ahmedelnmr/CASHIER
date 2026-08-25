<?php
/**
 * Cash Safe / Ledger Module Tests
 * Tests: SAFE-001 to SAFE-018
 *
 * Run: php tests/SafeTest.php
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

function insertEmployee($pdo, $name = 'Test Employee', $salary = 3000) {
    $stmt = $pdo->prepare("INSERT INTO employees (name, phone, base_salary, is_active) VALUES (?, '', ?, 1)");
    $stmt->execute([$name, $salary]);
    return $pdo->lastInsertId();
}

// ============================================================
// Replicate SafeController logic
// ============================================================
function addTransaction($pdo, $type, $amount, $date = null, $description = '', $employeeId = null) {
    $date = $date ?: date('Y-m-d');

    // Validation for advances and salary
    if (($type === 'advance' || $type === 'salary') && empty($employeeId)) {
        return ['success' => false, 'message' => 'يجب اختيار الموظف'];
    }

    $stmt = $pdo->prepare("INSERT INTO safe_transactions (type, amount, date, description, employee_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$type, abs($amount), $date, $description, $employeeId]);
    return ['success' => true];
}

function calculateBalance($pdo) {
    $sql = "SELECT
                SUM(CASE WHEN type IN ('deposit', 'opening', 'sale', 'purchase_void') THEN amount ELSE 0 END) as total_in,
                SUM(CASE WHEN type IN ('sale_void', 'expense', 'withdrawal', 'advance', 'salary', 'salary_payment', 'purchase', 'sale_return') THEN amount ELSE 0 END) as total_out
            FROM safe_transactions";
    $row = $pdo->query($sql)->fetch();
    return ($row['total_in'] ?? 0) - ($row['total_out'] ?? 0);
}

function getTransactions($pdo, $month = null, $type = '') {
    $month = $month ?: date('Y-m');
    $sql = "SELECT t.*, e.name as employee_name
            FROM safe_transactions t
            LEFT JOIN employees e ON t.employee_id = e.id
            WHERE strftime('%Y-%m', t.date) = :month ";
    $params = [':month' => $month];

    if (!empty($type)) {
        $sql .= " AND t.type = :type";
        $params[':type'] = $type;
    }

    $sql .= " ORDER BY t.date DESC, t.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function deleteTransaction($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM safe_transactions WHERE id = ?");
    $stmt->execute([$id]);
}

function getTransaction($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM safe_transactions WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ============================================================
// Run Tests
// ============================================================
echo "\n";
echo "==========================================================\n";
echo "  Cash Safe / Ledger Module Tests (SAFE-001 to SAFE-018)\n";
echo "==========================================================\n\n";

// ----------------------------------------------------------
// SAFE-002: Current balance calculation (empty)
// ----------------------------------------------------------
echo "--- SAFE-002: Balance calculation ---\n";
$pdo = createTestDb();

$balance = calculateBalance($pdo);
assert_equals(0.0, floatval($balance), 'SAFE-002', 'Empty safe: balance = 0');

// ----------------------------------------------------------
// SAFE-003: Add expense
// ----------------------------------------------------------
echo "\n--- SAFE-003: Add expense ---\n";

$result = addTransaction($pdo, 'expense', 500, date('Y-m-d'), 'Electricity bill');
assert_true($result['success'], 'SAFE-003', 'Add expense succeeds');

$balance = calculateBalance($pdo);
assert_less_than(floatval($balance), 0, 'SAFE-003b', 'Balance decreased after expense');
assert_equals(-500.0, floatval($balance), 'SAFE-003c', 'Balance = -500 after expense');

$txn = getTransaction($pdo, 1);
assert_equals('expense', $txn['type'], 'SAFE-003d', 'Transaction type = expense');
assert_equals(500.0, floatval($txn['amount']), 'SAFE-003e', 'Transaction amount = 500');

// ----------------------------------------------------------
// SAFE-004: Add deposit
// ----------------------------------------------------------
echo "\n--- SAFE-004: Add deposit ---\n";

$result = addTransaction($pdo, 'deposit', 1000, date('Y-m-d'), 'Owner deposit');
assert_true($result['success'], 'SAFE-004', 'Add deposit succeeds');

$balance = calculateBalance($pdo);
assert_equals(500.0, floatval($balance), 'SAFE-004b', 'Balance = 500 after deposit (-500 + 1000)');

// ----------------------------------------------------------
// SAFE-005: Add withdrawal
// ----------------------------------------------------------
echo "\n--- SAFE-005: Add withdrawal ---\n";

$result = addTransaction($pdo, 'withdrawal', 200, date('Y-m-d'), 'Owner withdrawal');
assert_true($result['success'], 'SAFE-005', 'Add withdrawal succeeds');

$balance = calculateBalance($pdo);
assert_equals(300.0, floatval($balance), 'SAFE-005b', 'Balance = 300 after withdrawal (500 - 200)');

// ----------------------------------------------------------
// SAFE-006: Add advance (with employee)
// ----------------------------------------------------------
echo "\n--- SAFE-006: Add advance ---\n";

$empId = insertEmployee($pdo, 'Ahmed Worker', 3000);
$result = addTransaction($pdo, 'advance', 500, date('Y-m-d'), 'Salary advance', $empId);
assert_true($result['success'], 'SAFE-006', 'Add advance with employee succeeds');

$balance = calculateBalance($pdo);
assert_equals(-200.0, floatval($balance), 'SAFE-006b', 'Balance = -200 after advance (300 - 500)');

$advanceId = $pdo->lastInsertId();
$txn = getTransaction($pdo, $advanceId);
assert_equals('advance', $txn['type'], 'SAFE-006c', 'Transaction type = advance');
assert_equals($empId, $txn['employee_id'], 'SAFE-006d', 'Employee ID recorded');

// ----------------------------------------------------------
// SAFE-007: Add salary payment (with employee)
// ----------------------------------------------------------
echo "\n--- SAFE-007: Add salary payment ---\n";

$result = addTransaction($pdo, 'salary', 3000, date('Y-m-d'), 'Monthly salary', $empId);
assert_true($result['success'], 'SAFE-007', 'Add salary payment with employee succeeds');

$balance = calculateBalance($pdo);
assert_equals(-3200.0, floatval($balance), 'SAFE-007b', 'Balance = -3200 after salary (-200 - 3000)');

$salaryId = $pdo->lastInsertId();
$txn = getTransaction($pdo, $salaryId);
assert_equals('salary', $txn['type'], 'SAFE-007c', 'Transaction type = salary');
assert_equals($empId, $txn['employee_id'], 'SAFE-007d', 'Employee ID recorded for salary');

// ----------------------------------------------------------
// SAFE-001: View safe transactions
// ----------------------------------------------------------
echo "\n--- SAFE-001: View transactions ---\n";

$transactions = getTransactions($pdo, date('Y-m'));
assert_greater_than(count($transactions), 0, 'SAFE-001', 'Transactions listed');
assert_not_null($transactions[0]['type'], 'SAFE-001b', 'Transaction has type');
assert_not_null($transactions[0]['amount'], 'SAFE-001c', 'Transaction has amount');
assert_not_null($transactions[0]['date'], 'SAFE-001d', 'Transaction has date');

// Employee name joined
$advanceTxn = null;
foreach ($transactions as $t) {
    if ($t['type'] === 'advance') { $advanceTxn = $t; break; }
}
assert_not_null($advanceTxn, 'SAFE-001e', 'Advance transaction found');
assert_equals('Ahmed Worker', $advanceTxn['employee_name'], 'SAFE-001f', 'Employee name joined in transaction');

// ----------------------------------------------------------
// SAFE-008: Filter by month
// ----------------------------------------------------------
echo "\n--- SAFE-008: Filter by month ---\n";

// Add transaction in a different month
addTransaction($pdo, 'deposit', 100, '2025-01-15', 'Old deposit');

$currentMonthTxns = getTransactions($pdo, date('Y-m'));
$janTxns = getTransactions($pdo, '2025-01');
assert_greater_than(count($currentMonthTxns), count($janTxns), 'SAFE-008', 'Month filter: current month has more transactions than Jan 2025');
assert_equals(1, count($janTxns), 'SAFE-008b', 'Jan 2025 has exactly 1 transaction');

// ----------------------------------------------------------
// SAFE-009: Filter by type
// ----------------------------------------------------------
echo "\n--- SAFE-009: Filter by type ---\n";

$expenseTxns = getTransactions($pdo, date('Y-m'), 'expense');
assert_equals(1, count($expenseTxns), 'SAFE-009', 'Type filter: 1 expense transaction');
assert_equals('expense', $expenseTxns[0]['type'], 'SAFE-009b', 'Filtered transaction type = expense');

$depositTxns = getTransactions($pdo, date('Y-m'), 'deposit');
assert_equals(1, count($depositTxns), 'SAFE-009c', 'Type filter: 1 deposit transaction');

// ----------------------------------------------------------
// SAFE-010: Delete transaction (hard delete - BUG)
// ----------------------------------------------------------
echo "\n--- SAFE-010: Delete transaction ---\n";

$balanceBeforeDelete = calculateBalance($pdo);
// Find the expense transaction directly by type (avoid date filter issues)
$expenseTxn = $pdo->prepare("SELECT * FROM safe_transactions WHERE type = 'expense' ORDER BY id DESC LIMIT 1");
$expenseTxn->execute();
$expenseTxn = $expenseTxn->fetch();
assert_not_null($expenseTxn, 'SAFE-010', 'Expense transaction exists');
$expenseId = $expenseTxn['id'];
$expenseAmount = floatval($expenseTxn['amount']);

deleteTransaction($pdo, $expenseId);
$txnAfter = getTransaction($pdo, $expenseId);
assert_null($txnAfter, 'SAFE-010b', 'Transaction hard-deleted (BUG: violates audit trail)');

// Balance recalculated - expense was an OUT, removing it increases balance
$balance = calculateBalance($pdo);
assert_equals(floatval($balanceBeforeDelete) + $expenseAmount, floatval($balance), 'SAFE-010c', 'Balance recalculated after delete');

// ----------------------------------------------------------
// SAFE-011: Balance after sale
// ----------------------------------------------------------
echo "\n--- SAFE-011: Balance after sale ---\n";

$balanceBefore = calculateBalance($pdo);
addTransaction($pdo, 'sale', 150, date('Y-m-d'), 'مبيعات - فاتورة #1');
$balanceAfter = calculateBalance($pdo);
assert_greater_than(floatval($balanceAfter), floatval($balanceBefore), 'SAFE-011', 'Balance increases after sale');
assert_equals(floatval($balanceBefore) + 150, floatval($balanceAfter), 'SAFE-011b', 'Balance increased by sale amount');

// ----------------------------------------------------------
// SAFE-012: Balance after void
// ----------------------------------------------------------
echo "\n--- SAFE-012: Balance after void ---\n";

$balanceBefore = calculateBalance($pdo);
addTransaction($pdo, 'sale_void', 150, date('Y-m-d'), 'إلغاء فاتورة مبيعات #1');
$balanceAfter = calculateBalance($pdo);
assert_less_than(floatval($balanceAfter), floatval($balanceBefore), 'SAFE-012', 'Balance decreases after sale void');
assert_equals(floatval($balanceBefore) - 150, floatval($balanceAfter), 'SAFE-012b', 'Balance decreased by void amount');

// ----------------------------------------------------------
// SAFE-013: Balance after return
// ----------------------------------------------------------
echo "\n--- SAFE-013: Balance after return ---\n";

$balanceBefore = calculateBalance($pdo);
addTransaction($pdo, 'sale_return', 50, date('Y-m-d'), 'مرتجع مبيعات');
$balanceAfter = calculateBalance($pdo);
assert_less_than(floatval($balanceAfter), floatval($balanceBefore), 'SAFE-013', 'Balance decreases after return');
assert_equals(floatval($balanceBefore) - 50, floatval($balanceAfter), 'SAFE-013b', 'Balance decreased by return amount');

// ----------------------------------------------------------
// SAFE-014: Balance after purchase
// ----------------------------------------------------------
echo "\n--- SAFE-014: Balance after purchase ---\n";

$balanceBefore = calculateBalance($pdo);
addTransaction($pdo, 'purchase', 500, date('Y-m-d'), 'مشتريات - فاتورة #1');
$balanceAfter = calculateBalance($pdo);
assert_less_than(floatval($balanceAfter), floatval($balanceBefore), 'SAFE-014', 'Balance decreases after purchase');
assert_equals(floatval($balanceBefore) - 500, floatval($balanceAfter), 'SAFE-014b', 'Balance decreased by purchase amount');

// ----------------------------------------------------------
// SAFE-015: Balance after purchase void
// ----------------------------------------------------------
echo "\n--- SAFE-015: Balance after purchase void ---\n";

$balanceBefore = calculateBalance($pdo);
addTransaction($pdo, 'purchase_void', 500, date('Y-m-d'), 'إلغاء فاتورة شراء #1');
$balanceAfter = calculateBalance($pdo);
assert_greater_than(floatval($balanceAfter), floatval($balanceBefore), 'SAFE-015', 'Balance increases after purchase void');
assert_equals(floatval($balanceBefore) + 500, floatval($balanceAfter), 'SAFE-015b', 'Balance increased by purchase void amount');

// ----------------------------------------------------------
// SAFE-016: Opening cash from shift
// ----------------------------------------------------------
echo "\n--- SAFE-016: Opening cash ---\n";

$balanceBefore = calculateBalance($pdo);
$result = addTransaction($pdo, 'opening', 1000, date('Y-m-d'), 'Opening cash - Shift #1');
assert_true($result['success'], 'SAFE-016', 'Opening cash transaction succeeds');

$txn = getTransaction($pdo, $pdo->lastInsertId());
assert_equals('opening', $txn['type'], 'SAFE-016b', 'Transaction type = opening');
assert_equals(1000.0, floatval($txn['amount']), 'SAFE-016c', 'Opening amount = 1000');

// Opening increases balance (it's an IN type)
$balance = calculateBalance($pdo);
assert_equals(floatval($balanceBefore) + 1000, floatval($balance), 'SAFE-016d', 'Balance increased by opening cash');

// ----------------------------------------------------------
// SAFE-017: Employee required for advance
// ----------------------------------------------------------
echo "\n--- SAFE-017: Employee required for advance ---\n";

$result = addTransaction($pdo, 'advance', 500, date('Y-m-d'), 'Advance without employee', null);
assert_false($result['success'], 'SAFE-017', 'Advance without employee fails');
assert_equals('يجب اختيار الموظف', $result['message'], 'SAFE-017b', 'Correct error message');

// ----------------------------------------------------------
// SAFE-018: Employee required for salary
// ----------------------------------------------------------
echo "\n--- SAFE-018: Employee required for salary ---\n";

$result = addTransaction($pdo, 'salary', 3000, date('Y-m-d'), 'Salary without employee', null);
assert_false($result['success'], 'SAFE-018', 'Salary without employee fails');
assert_equals('يجب اختيار الموظف', $result['message'], 'SAFE-018b', 'Correct error message');

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

echo "\n  All Cash Safe tests passed!\n\n";
exit(0);
