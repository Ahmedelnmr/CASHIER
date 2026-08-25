<?php
/**
 * Employee Management Module Tests
 * Tests: EMP-001 to EMP-010
 *
 * Run: php tests/EmployeeTest.php
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
// Replicate EmployeeRepository methods
// ============================================================
function getAllActive($pdo) {
    $stmt = $pdo->query("SELECT * FROM employees WHERE is_active = 1");
    return $stmt->fetchAll();
}

function findEmployee($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function createEmployee($pdo, $name, $phone = '', $baseSalary = 0) {
    $stmt = $pdo->prepare("INSERT INTO employees (name, phone, base_salary) VALUES (?, ?, ?)");
    $stmt->execute([$name, $phone, $baseSalary]);
    return $pdo->lastInsertId();
}

function updateEmployee($pdo, $id, $name, $phone = '', $baseSalary = 0) {
    $stmt = $pdo->prepare("UPDATE employees SET name = ?, phone = ?, base_salary = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $baseSalary, $id]);
}

function deleteEmployee($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE employees SET is_active = 0 WHERE id = ?");
    $stmt->execute([$id]);
}

function hasAccrualForMonth($pdo, $employeeId, $month) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee_accruals WHERE employee_id = ? AND month = ?");
    $stmt->execute([$employeeId, $month]);
    return $stmt->fetchColumn() > 0;
}

function createAccrual($pdo, $employeeId, $month, $amount) {
    $stmt = $pdo->prepare("INSERT INTO employee_accruals (employee_id, month, amount) VALUES (?, ?, ?)");
    return $stmt->execute([$employeeId, $month, $amount]);
}

function getTotalAccruals($pdo, $employeeId) {
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM employee_accruals WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    return floatval($stmt->fetchColumn() ?: 0);
}

function getTotalSalaryPaid($pdo, $employeeId) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM safe_transactions WHERE employee_id = ? AND type IN ('salary', 'salary_payment')");
    $stmt->execute([$employeeId]);
    return floatval($stmt->fetchColumn() ?: 0);
}

function getTotalAdvances($pdo, $employeeId) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM safe_transactions WHERE employee_id = ? AND type = 'advance'");
    $stmt->execute([$employeeId]);
    return floatval($stmt->fetchColumn() ?: 0);
}

function getBalance($pdo, $employeeId) {
    return getTotalAccruals($pdo, $employeeId) - getTotalSalaryPaid($pdo, $employeeId) - getTotalAdvances($pdo, $employeeId);
}

function getAllWithBalance($pdo) {
    $employees = getAllActive($pdo);
    foreach ($employees as &$emp) {
        $emp['ledger_balance'] = getBalance($pdo, $emp['id']);
    }
    return $employees;
}

function getTransactions($pdo, $employeeId) {
    $sql = "
        SELECT id, date(created_at) as date, 'استحقاق راتب' as type_label, 'accrual' as type, amount, 'راتب شهر ' || month as description
        FROM employee_accruals WHERE employee_id = ?
        UNION ALL
        SELECT id, date(date) as date,
            CASE WHEN type = 'advance' THEN 'سلفة' WHEN type IN ('salary', 'salary_payment') THEN 'سداد راتب' ELSE type END as type_label,
            type, amount, description
        FROM safe_transactions WHERE employee_id = ? AND type IN ('advance', 'salary', 'salary_payment')
        ORDER BY date DESC, id DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$employeeId, $employeeId]);
    return $stmt->fetchAll();
}

function runMonthlyAccrual($pdo) {
    $currentMonth = date('Y-m');
    $employees = getAllActive($pdo);
    $count = 0;
    foreach ($employees as $emp) {
        if (!hasAccrualForMonth($pdo, $emp['id'], $currentMonth)) {
            $amount = floatval($emp['base_salary']);
            if ($amount > 0) {
                createAccrual($pdo, $emp['id'], $currentMonth, $amount);
                $count++;
            }
        }
    }
    return $count;
}

// ============================================================
// Run Tests
// ============================================================
echo "\n";
echo "==========================================================\n";
echo "  Employee Management Module Tests (EMP-001 to EMP-010)\n";
echo "==========================================================\n\n";

// ----------------------------------------------------------
// EMP-002: Add employee
// ----------------------------------------------------------
echo "--- EMP-002: Add employee ---\n";
$pdo = createTestDb();

$emp1 = createEmployee($pdo, 'Ahmed Ali', '01000000001', 3000);
assert_greater_than($emp1, 0, 'EMP-002', 'Add employee succeeds');

$emp = findEmployee($pdo, $emp1);
assert_equals('Ahmed Ali', $emp['name'], 'EMP-002b', 'Employee name saved');
assert_equals('01000000001', $emp['phone'], 'EMP-002c', 'Employee phone saved');
assert_equals(3000.0, floatval($emp['base_salary']), 'EMP-002d', 'Employee salary saved');
assert_equals(1, intval($emp['is_active']), 'EMP-002e', 'Employee is active by default');

$emp2 = createEmployee($pdo, 'Sara Mohamed', '01000000002', 2500);
$emp3 = createEmployee($pdo, 'Omar Hassan', '01000000003', 4000);

// ----------------------------------------------------------
// EMP-001: List employees
// ----------------------------------------------------------
echo "\n--- EMP-001: List employees ---\n";

$employees = getAllActive($pdo);
assert_equals(3, count($employees), 'EMP-001', 'List all active employees (3)');

// With balance
$employeesWithBalance = getAllWithBalance($pdo);
assert_equals(3, count($employeesWithBalance), 'EMP-001b', 'getAllWithBalance returns 3 employees');
assert_true(isset($employeesWithBalance[0]['ledger_balance']), 'EMP-001c', 'Each employee has ledger_balance');

// ----------------------------------------------------------
// EMP-003: Edit employee
// ----------------------------------------------------------
echo "\n--- EMP-003: Edit employee ---\n";

updateEmployee($pdo, $emp1, 'Ahmed Ali Updated', '01099999999', 3500);
$emp = findEmployee($pdo, $emp1);
assert_equals('Ahmed Ali Updated', $emp['name'], 'EMP-003', 'Employee name updated');
assert_equals('01099999999', $emp['phone'], 'EMP-003b', 'Employee phone updated');
assert_equals(3500.0, floatval($emp['base_salary']), 'EMP-003c', 'Employee salary updated');

// ----------------------------------------------------------
// EMP-004: Delete employee (soft delete)
// ----------------------------------------------------------
echo "\n--- EMP-004: Delete employee ---\n";

deleteEmployee($pdo, $emp3);
$emp = findEmployee($pdo, $emp3);
assert_equals(0, intval($emp['is_active']), 'EMP-004', 'Employee soft-deleted (is_active=0)');

$employees = getAllActive($pdo);
assert_equals(2, count($employees), 'EMP-004b', 'Deleted employee excluded from active list');

// ----------------------------------------------------------
// EMP-006: Monthly salary accrual
// ----------------------------------------------------------
echo "\n--- EMP-006: Monthly salary accrual ---\n";

$currentMonth = date('Y-m');

// No accruals yet
assert_false(hasAccrualForMonth($pdo, $emp1, $currentMonth), 'EMP-006', 'No accrual before running');

$count = runMonthlyAccrual($pdo);
assert_greater_than($count, 0, 'EMP-006b', 'runMonthlyAccrual created accruals');

// Check accruals were created for active employees with salary > 0
assert_true(hasAccrualForMonth($pdo, $emp1, $currentMonth), 'EMP-006c', 'Accrual created for emp1');
assert_true(hasAccrualForMonth($pdo, $emp2, $currentMonth), 'EMP-006d', 'Accrual created for emp2');

// Deleted employee (emp3) should NOT get accrual
assert_false(hasAccrualForMonth($pdo, $emp3, $currentMonth), 'EMP-006e', 'No accrual for deleted employee');

// Accrual amount = base_salary
$accruals = getTotalAccruals($pdo, $emp1);
assert_equals(3500.0, $accruals, 'EMP-006f', 'Accrual amount = base_salary (3500)');

// ----------------------------------------------------------
// EMP-007: No duplicate accrual
// ----------------------------------------------------------
echo "\n--- EMP-007: No duplicate accrual ---\n";

// Run again - should not create duplicates
$count2 = runMonthlyAccrual($pdo);
assert_equals(0, $count2, 'EMP-007', 'Second run creates 0 new accruals (no duplicates)');

// Total accruals still = base_salary (not doubled)
$accruals = getTotalAccruals($pdo, $emp1);
assert_equals(3500.0, $accruals, 'EMP-007b', 'Accrual amount unchanged after duplicate run');

// UNIQUE constraint exists
$caught = false;
try {
    createAccrual($pdo, $emp1, $currentMonth, 3500);
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'UNIQUE') !== false) {
        $caught = true;
    }
}
assert_true($caught, 'EMP-007c', 'UNIQUE constraint prevents duplicate accrual');

// ----------------------------------------------------------
// EMP-008: Balance calculation
// ----------------------------------------------------------
echo "\n--- EMP-008: Balance calculation ---\n";

// Balance = accruals - salary_paid - advances
// emp1: accruals=3500, salary_paid=0, advances=0 → balance=3500
$balance = getBalance($pdo, $emp1);
assert_equals(3500.0, $balance, 'EMP-008', 'Balance = accruals (3500) with no payments');

// Add salary payment
$pdo->prepare("INSERT INTO safe_transactions (type, amount, date, description, employee_id) VALUES ('salary', 3500, ?, 'Monthly salary', ?)")
    ->execute([date('Y-m-d'), $emp1]);
$balance = getBalance($pdo, $emp1);
assert_equals(0.0, $balance, 'EMP-008b', 'Balance = 0 after full salary payment (3500 - 3500)');

// Add advance
$pdo->prepare("INSERT INTO safe_transactions (type, amount, date, description, employee_id) VALUES ('advance', 500, ?, 'Salary advance', ?)")
    ->execute([date('Y-m-d'), $emp1]);
$balance = getBalance($pdo, $emp1);
assert_equals(-500.0, $balance, 'EMP-008c', 'Balance = -500 after advance (0 - 500)');

// ----------------------------------------------------------
// EMP-009: Positive balance (company owes)
// ----------------------------------------------------------
echo "\n--- EMP-009: Positive balance ---\n";

// emp2: accruals=2500, no payments → balance=2500
$balance = getBalance($pdo, $emp2);
assert_greater_than($balance, 0, 'EMP-009', 'Positive balance: company owes employee');
assert_equals(2500.0, $balance, 'EMP-009b', 'Balance = 2500 (unpaid salary)');

// ----------------------------------------------------------
// EMP-010: Negative balance (employee owes)
// ----------------------------------------------------------
echo "\n--- EMP-010: Negative balance ---\n";

// emp1: balance = -500 (advance > accruals after payment)
$balance = getBalance($pdo, $emp1);
assert_less_than($balance, 0, 'EMP-010', 'Negative balance: employee owes company');
assert_equals(-500.0, $balance, 'EMP-010b', 'Balance = -500 (advance outstanding)');

// ----------------------------------------------------------
// EMP-005: Employee history
// ----------------------------------------------------------
echo "\n--- EMP-005: Employee history ---\n";

$history = getTransactions($pdo, $emp1);
assert_greater_than(count($history), 0, 'EMP-005', 'Employee history has entries');

// Should include accrual
$hasAccrual = false;
$hasSalary = false;
$hasAdvance = false;
foreach ($history as $h) {
    if ($h['type'] === 'accrual') $hasAccrual = true;
    if ($h['type'] === 'salary') $hasSalary = true;
    if ($h['type'] === 'advance') $hasAdvance = true;
}
assert_true($hasAccrual, 'EMP-005b', 'History includes accrual entry');
assert_true($hasSalary, 'EMP-005c', 'History includes salary payment');
assert_true($hasAdvance, 'EMP-005d', 'History includes advance');

// Each entry has required fields
$first = $history[0];
assert_true(isset($first['date']), 'EMP-005e', 'History entry has date');
assert_true(isset($first['type_label']), 'EMP-005f', 'History entry has type_label');
assert_true(isset($first['amount']), 'EMP-005g', 'History entry has amount');
assert_true(isset($first['description']), 'EMP-005h', 'History entry has description');

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

echo "\n  All Employee Management tests passed!\n\n";
exit(0);
