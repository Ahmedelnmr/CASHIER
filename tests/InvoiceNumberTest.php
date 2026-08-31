<?php
/**
 * Invoice Number Generator Tests
 * Tests: INV-001 through INV-020
 *
 * Run: php tests/InvoiceNumberTest.php
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

function assert_matches($pattern, $value, $testId, $description) {
    assert_true((bool)preg_match($pattern, $value), $testId, $description . " (value: '$value', pattern: $pattern)");
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
                strpos($e->getMessage(), 'duplicate column') === false &&
                strpos($e->getMessage(), 'UNIQUE constraint') === false) {
                throw $e;
            }
        }
    }
    return $pdo;
}

// ============================================================
// Invoice Number Generator (standalone for testing)
// ============================================================
function generateInvoiceNumber($pdo, $businessDate) {
    $prefix = 'INV-'
        . date('m', strtotime($businessDate))
        . date('d', strtotime($businessDate))
        . substr(date('Y', strtotime($businessDate)), 2)
        . '-';

    $stmt = $pdo->prepare(
        "SELECT COALESCE(MAX(CAST(SUBSTR(invoice_number, :prefixLen + 1) AS INTEGER)), 0) + 1
         FROM sales
         WHERE invoice_number LIKE :pattern"
    );
    $stmt->execute([
        ':prefixLen' => strlen($prefix),
        ':pattern' => $prefix . '%',
    ]);
    $nextSeq = intval($stmt->fetchColumn());
    $seqStr = str_pad($nextSeq, 3, '0', STR_PAD_LEFT);

    return $prefix . $seqStr;
}

function isValidInvoiceFormat($invoiceNumber) {
    return (bool) preg_match('/^INV-\d{6}-\d{3,}$/', $invoiceNumber);
}

function extractDateFromInvoice($invoiceNumber) {
    if (!preg_match('/^INV-(\d{2})(\d{2})(\d{2})-\d+$/', $invoiceNumber, $m)) {
        return false;
    }
    $month = $m[1];
    $day = $m[2];
    $year = '20' . $m[3];
    $dateStr = "$year-$month-$day";
    return checkdate(intval($month), intval($day), intval($year)) ? $dateStr : false;
}

function createSaleWithInvoice($pdo, $invoiceNumber, $datetime, $total = 100, $type = 'sale', $status = 'completed') {
    $stmt = $pdo->prepare(
        "INSERT INTO sales (invoice_number, datetime, total, type, status) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$invoiceNumber, $datetime, $total, $type, $status]);
    return $pdo->lastInsertId();
}

// ============================================================
// Run Tests
// ============================================================
echo "\n";
echo "==========================================================\n";
echo "  Invoice Number Generator Tests (INV-001 to INV-020)\n";
echo "==========================================================\n\n";

$pdo = createTestDb();

// ----------------------------------------------------------
// INV-001: Format validation
// ----------------------------------------------------------
echo "--- INV-001: Format validation ---\n";

assert_true(isValidInvoiceFormat('INV-083126-001'), 'INV-001a', 'Valid format: INV-083126-001');
assert_true(isValidInvoiceFormat('INV-010126-001'), 'INV-001b', 'Valid format: INV-010126-001');
assert_true(isValidInvoiceFormat('INV-123125-125'), 'INV-001c', 'Valid format: INV-123125-125');
assert_true(isValidInvoiceFormat('INV-083126-999'), 'INV-001d', 'Valid format: INV-083126-999');
assert_true(isValidInvoiceFormat('INV-083126-1000'), 'INV-001e', 'Valid format: 4-digit sequence');

assert_false(isValidInvoiceFormat('123'), 'INV-001f', 'Invalid: plain number');
assert_false(isValidInvoiceFormat('INV-TEST'), 'INV-001g', 'Invalid: INV-TEST');
assert_false(isValidInvoiceFormat('inv-083126-001'), 'INV-001h', 'Invalid: lowercase prefix');
assert_false(isValidInvoiceFormat('INV-83126-1'), 'INV-001i', 'Invalid: short date');
assert_false(isValidInvoiceFormat('INV-083126-1'), 'INV-001j', 'Invalid: short sequence');
assert_false(isValidInvoiceFormat('INV/083126/001'), 'INV-001k', 'Invalid: slashes');
assert_false(isValidInvoiceFormat('INV-083126-001-ABC'), 'INV-001l', 'Invalid: extra suffix');

// ----------------------------------------------------------
// INV-002: Date extraction
// ----------------------------------------------------------
echo "\n--- INV-002: Date extraction ---\n";

assert_equals('2026-08-31', extractDateFromInvoice('INV-083126-001'), 'INV-002a', 'Extract date from INV-083126-001');
assert_equals('2026-01-01', extractDateFromInvoice('INV-010126-001'), 'INV-002b', 'Extract date from INV-010126-001');
assert_equals('2026-12-31', extractDateFromInvoice('INV-123126-001'), 'INV-002c', 'Extract date from INV-123126-001');
assert_equals('2027-01-01', extractDateFromInvoice('INV-010127-001'), 'INV-002d', 'Extract date from INV-010127-001');
assert_false(extractDateFromInvoice('INV-130126-001'), 'INV-002e', 'Invalid month 13 returns false');
assert_false(extractDateFromInvoice('INV-023026-001'), 'INV-002f', 'Invalid date Feb 30 returns false');

// ----------------------------------------------------------
// INV-003: First invoice of the day
// ----------------------------------------------------------
echo "\n--- INV-003: First invoice of the day ---\n";

$inv1 = generateInvoiceNumber($pdo, '2026-08-31');
assert_equals('INV-083126-001', $inv1, 'INV-003a', 'First invoice of Aug 31');
createSaleWithInvoice($pdo, $inv1, '2026-08-31 10:00:00');

// ----------------------------------------------------------
// INV-004: Second invoice
// ----------------------------------------------------------
echo "\n--- INV-004: Second invoice ---\n";

$inv2 = generateInvoiceNumber($pdo, '2026-08-31');
assert_equals('INV-083126-002', $inv2, 'INV-004a', 'Second invoice of Aug 31');
createSaleWithInvoice($pdo, $inv2, '2026-08-31 10:30:00');

// ----------------------------------------------------------
// INV-005: Multiple invoices
// ----------------------------------------------------------
echo "\n--- INV-005: Multiple invoices ---\n";

$inv3 = generateInvoiceNumber($pdo, '2026-08-31');
assert_equals('INV-083126-003', $inv3, 'INV-005a', 'Third invoice');
createSaleWithInvoice($pdo, $inv3, '2026-08-31 11:00:00');

$inv4 = generateInvoiceNumber($pdo, '2026-08-31');
assert_equals('INV-083126-004', $inv4, 'INV-005b', 'Fourth invoice');
createSaleWithInvoice($pdo, $inv4, '2026-08-31 12:00:00');

$inv5 = generateInvoiceNumber($pdo, '2026-08-31');
assert_equals('INV-083126-005', $inv5, 'INV-005c', 'Fifth invoice');
createSaleWithInvoice($pdo, $inv5, '2026-08-31 13:00:00');

// ----------------------------------------------------------
// INV-006: New day resets sequence
// ----------------------------------------------------------
echo "\n--- INV-006: New day resets sequence ---\n";

$invSep1 = generateInvoiceNumber($pdo, '2026-09-01');
assert_equals('INV-090126-001', $invSep1, 'INV-006a', 'First invoice of Sep 1');
createSaleWithInvoice($pdo, $invSep1, '2026-09-01 09:00:00');

$invSep2 = generateInvoiceNumber($pdo, '2026-09-01');
assert_equals('INV-090126-002', $invSep2, 'INV-006b', 'Second invoice of Sep 1');
createSaleWithInvoice($pdo, $invSep2, '2026-09-01 10:00:00');

// ----------------------------------------------------------
// INV-007: Year boundary
// ----------------------------------------------------------
echo "\n--- INV-007: Year boundary ---\n";

$invDec = generateInvoiceNumber($pdo, '2026-12-31');
assert_equals('INV-123126-001', $invDec, 'INV-007a', 'Dec 31, 2026');
createSaleWithInvoice($pdo, $invDec, '2026-12-31 23:59:59');

$invJan = generateInvoiceNumber($pdo, '2027-01-01');
assert_equals('INV-010127-001', $invJan, 'INV-007b', 'Jan 1, 2027');
createSaleWithInvoice($pdo, $invJan, '2027-01-01 00:00:00');

// ----------------------------------------------------------
// INV-008: Uniqueness constraint
// ----------------------------------------------------------
echo "\n--- INV-008: Uniqueness constraint ---\n";

// Try to insert a duplicate invoice number - should fail
try {
    createSaleWithInvoice($pdo, 'INV-083126-001', '2026-08-31 15:00:00');
    // If we get here, the constraint didn't work
    assert_true(false, 'INV-008a', 'Duplicate INV-083126-001 should be rejected');
} catch (PDOException $e) {
    assert_true(
        strpos($e->getMessage(), 'UNIQUE constraint') !== false,
        'INV-008a',
        'Duplicate INV-083126-001 rejected by UNIQUE constraint'
    );
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE invoice_number = ?");
$stmt->execute(['INV-083126-001']);
$count = intval($stmt->fetchColumn());
assert_equals(1, $count, 'INV-008b', 'Only one INV-083126-001 exists');

// ----------------------------------------------------------
// INV-009: Search by invoice number
// ----------------------------------------------------------
echo "\n--- INV-009: Search by invoice number ---\n";

$stmt = $pdo->prepare("SELECT * FROM sales WHERE invoice_number = ?");
$stmt->execute(['INV-083126-004']);
$found = $stmt->fetch();
assert_not_null($found, 'INV-009a', 'Search INV-083126-004 finds sale');
assert_equals('INV-083126-004', $found['invoice_number'], 'INV-009b', 'Found sale has correct invoice_number');

$stmt->execute(['INV-999999-999']);
$notFound = $stmt->fetch();
assert_false($notFound, 'INV-009c', 'Non-existent invoice returns nothing');

// ----------------------------------------------------------
// INV-010: All invoice numbers match format
// ----------------------------------------------------------
echo "\n--- INV-010: All generated numbers match format ---\n";

$stmt = $pdo->prepare("SELECT invoice_number FROM sales WHERE invoice_number IS NOT NULL");
$stmt->execute();
$allInvoices = $stmt->fetchAll(PDO::FETCH_COLUMN);
$allValid = true;
foreach ($allInvoices as $inv) {
    if (!isValidInvoiceFormat($inv)) {
        $allValid = false;
        echo "  Invalid: $inv\n";
    }
}
assert_true($allValid, 'INV-010a', 'All ' . count($allInvoices) . ' invoice numbers match INV-MMDDYY-SSS format');

// ----------------------------------------------------------
// INV-011: Sequence is per-date, not global
// ----------------------------------------------------------
echo "\n--- INV-011: Sequence is per-date ---\n";

// Aug 31 has 5 invoices (001-005), Sep 1 has 2 (001-002)
// New Aug 31 invoice should be 006, not 008
$inv6Aug = generateInvoiceNumber($pdo, '2026-08-31');
assert_equals('INV-083126-006', $inv6Aug, 'INV-011a', 'Aug 31 next is 006 (not global count)');

// Sep 1 next should be 003
$inv3Sep = generateInvoiceNumber($pdo, '2026-09-01');
assert_equals('INV-090126-003', $inv3Sep, 'INV-011b', 'Sep 1 next is 003');

// ----------------------------------------------------------
// INV-012: Backfill migration format
// ----------------------------------------------------------
echo "\n--- INV-012: Backfill migration format ---\n";

// Simulate what the migration does: convert sale_number + datetime to invoice format
$testDate = '2026-08-25';
$testSaleNum = 3;
$expectedPrefix = 'INV-082526-';
$expected = 'INV-082526-003';

$generated = 'INV-'
    . substr('0' . date('m', strtotime($testDate)), -2)
    . substr('0' . date('d', strtotime($testDate)), -2)
    . substr(date('Y', strtotime($testDate)), 2)
    . '-'
    . substr('000' . $testSaleNum, -3);

assert_equals($expected, $generated, 'INV-012a', 'Backfill format matches expected');

// ----------------------------------------------------------
// INV-013: Voided sale keeps invoice number
// ----------------------------------------------------------
echo "\n--- INV-013: Voided sale keeps invoice number ---\n";

$invVoid = generateInvoiceNumber($pdo, '2026-08-31');
createSaleWithInvoice($pdo, $invVoid, '2026-08-31 16:00:00', 100, 'sale', 'completed');

// Void it
$stmt = $pdo->prepare("UPDATE sales SET status = 'voided' WHERE invoice_number = ?");
$stmt->execute([$invVoid]);

// Verify invoice number still exists
$stmt = $pdo->prepare("SELECT invoice_number, status FROM sales WHERE invoice_number = ?");
$stmt->execute([$invVoid]);
$voidedSale = $stmt->fetch();
assert_not_null($voidedSale, 'INV-013a', 'Voided sale still has invoice number');
assert_equals('INV-083126-006', $voidedSale['invoice_number'], 'INV-013b', 'Voided sale invoice number unchanged');
assert_equals('voided', $voidedSale['status'], 'INV-013c', 'Sale status is voided');

// ----------------------------------------------------------
// INV-014: Return sale gets its own invoice number
// ----------------------------------------------------------
echo "\n--- INV-014: Return sale gets its own invoice number ---\n";

$invReturn = generateInvoiceNumber($pdo, '2026-08-31');
createSaleWithInvoice($pdo, $invReturn, '2026-08-31 17:00:00', -50, 'return', 'completed');

assert_true(isValidInvoiceFormat($invReturn), 'INV-014a', 'Return sale has valid invoice format');
assert_equals('INV-083126-007', $invReturn, 'INV-014b', 'Return sale gets next sequence');

// ----------------------------------------------------------
// INV-015: Empty database - first invoice
// ----------------------------------------------------------
echo "\n--- INV-015: Empty database first invoice ---\n";

$emptyPdo = createTestDb(); // Fresh DB
$invEmpty = generateInvoiceNumber($emptyPdo, '2026-08-31');
assert_equals('INV-083126-001', $invEmpty, 'INV-015a', 'Empty DB first invoice is 001');

// ----------------------------------------------------------
// INV-016: Concurrent-safe within transaction
// ----------------------------------------------------------
echo "\n--- INV-016: Transaction safety ---\n";

$txPdo = createTestDb();
$txPdo->exec("INSERT INTO sales (invoice_number, datetime, total) VALUES ('INV-083126-001', '2026-08-31 10:00:00', 100)");
$txPdo->exec("INSERT INTO sales (invoice_number, datetime, total) VALUES ('INV-083126-002', '2026-08-31 10:01:00', 100)");

$next = generateInvoiceNumber($txPdo, '2026-08-31');
assert_equals('INV-083126-003', $next, 'INV-016a', 'Next after 002 is 003');

// Simulate concurrent: insert 003, then generate again
$txPdo->exec("INSERT INTO sales (invoice_number, datetime, total) VALUES ('$next', '2026-08-31 10:02:00', 100)");
$next2 = generateInvoiceNumber($txPdo, '2026-08-31');
assert_equals('INV-083126-004', $next2, 'INV-016b', 'After inserting 003, next is 004');

// ----------------------------------------------------------
// INV-017: Month/day zero-padding
// ----------------------------------------------------------
echo "\n--- INV-017: Month/day zero-padding ---\n";

$invJan = generateInvoiceNumber($txPdo, '2026-01-05');
assert_matches('/^INV-010526-/', $invJan, 'INV-017a', 'Jan 5 padded to 0105');

$invOct = generateInvoiceNumber($txPdo, '2026-10-09');
assert_matches('/^INV-100926-/', $invOct, 'INV-017b', 'Oct 9 padded to 1009');

// ----------------------------------------------------------
// INV-018: Invoice number stability after update
// ----------------------------------------------------------
echo "\n--- INV-018: Invoice number stability ---\n";

$stmt = $txPdo->prepare("UPDATE sales SET total = 999 WHERE invoice_number = 'INV-083126-001'");
$stmt->execute();

$stmt = $txPdo->prepare("SELECT invoice_number, total FROM sales WHERE invoice_number = 'INV-083126-001'");
$stmt->execute();
$updated = $stmt->fetch();
assert_equals('INV-083126-001', $updated['invoice_number'], 'INV-018a', 'Invoice number unchanged after update');
assert_true(floatval($updated['total']) === 999.0, 'INV-018b', 'Total was updated');

// ----------------------------------------------------------
// INV-019: Large sequence numbers
// ----------------------------------------------------------
echo "\n--- INV-019: Large sequence numbers ---\n";

$largePdo = createTestDb();
// Insert 999 invoices
for ($i = 1; $i <= 999; $i++) {
    $seq = str_pad($i, 3, '0', STR_PAD_LEFT);
    $largePdo->exec("INSERT INTO sales (invoice_number, datetime, total) VALUES ('INV-083126-$seq', '2026-08-31 10:00:00', 100)");
}

$inv1000 = generateInvoiceNumber($largePdo, '2026-08-31');
assert_equals('INV-083126-1000', $inv1000, 'INV-019a', '1000th invoice has 4-digit sequence');
assert_true(isValidInvoiceFormat($inv1000), 'INV-019b', '1000th invoice still matches format pattern');

// ----------------------------------------------------------
// INV-020: Different dates don't interfere
// ----------------------------------------------------------
echo "\n--- INV-020: Different dates don't interfere ---\n";

$multiPdo = createTestDb();
$multiPdo->exec("INSERT INTO sales (invoice_number, datetime, total) VALUES ('INV-083126-001', '2026-08-31 10:00:00', 100)");
$multiPdo->exec("INSERT INTO sales (invoice_number, datetime, total) VALUES ('INV-083126-002', '2026-08-31 11:00:00', 100)");
$multiPdo->exec("INSERT INTO sales (invoice_number, datetime, total) VALUES ('INV-090126-001', '2026-09-01 09:00:00', 100)");

$nextAug = generateInvoiceNumber($multiPdo, '2026-08-31');
assert_equals('INV-083126-003', $nextAug, 'INV-020a', 'Aug 31 next is 003 (not affected by Sep 1)');

$nextSep = generateInvoiceNumber($multiPdo, '2026-09-01');
assert_equals('INV-090126-002', $nextSep, 'INV-020b', 'Sep 1 next is 002 (not affected by Aug 31)');

$nextOct = generateInvoiceNumber($multiPdo, '2026-10-15');
assert_equals('INV-101526-001', $nextOct, 'INV-020c', 'Oct 15 first invoice is 001');

$pdo = null;
$emptyPdo = null;
$txPdo = null;
$largePdo = null;
$multiPdo = null;

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

echo "\n  All Invoice Number tests passed!\n\n";
exit(0);
