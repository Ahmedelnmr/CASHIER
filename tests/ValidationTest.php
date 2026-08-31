<?php
/**
 * Validation System Tests
 * Tests all validation rules for Employees, Customers, and Suppliers
 *
 * Run: php tests/ValidationTest.php
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

function assert_false($value, $testId, $description) {
    assert_true(!$value, $testId, $description);
}

function assert_equals($expected, $actual, $testId, $description) {
    assert_true($expected === $actual, $testId, $description . " (expected: " . var_export($expected, true) . ", got: " . var_export($actual, true) . ")");
}

// ============================================================
// Run Tests
// ============================================================
echo "\n";
echo "==========================================================\n";
echo "  Validation System Tests\n";
echo "==========================================================\n\n";

// ----------------------------------------------------------
// 1. Phone Validation
// ----------------------------------------------------------
echo "--- 1. Phone Validation ---\n";

// Valid phone numbers
$v = new Validator(['phone' => '01000000001']);
$v->phone('phone', 'الهاتف', false);
assert_true($v->passes(), 'PHONE-001', 'Valid 11-digit phone passes');

$v = new Validator(['phone' => '1234567']);
$v->phone('phone', 'الهاتف', false);
assert_true($v->passes(), 'PHONE-002', 'Valid 7-digit phone passes');

$v = new Validator(['phone' => '123456789012345']);
$v->phone('phone', 'الهاتف', false);
assert_true($v->passes(), 'PHONE-003', 'Valid 15-digit phone passes');

// Empty phone (optional)
$v = new Validator(['phone' => '']);
$v->phone('phone', 'الهاتف', false);
assert_true($v->passes(), 'PHONE-004', 'Empty optional phone passes');

$v = new Validator(['phone' => '  ']);
$v->phone('phone', 'الهاتف', false);
assert_true($v->passes(), 'PHONE-005', 'Whitespace-only optional phone passes');

// Required empty phone
$v = new Validator(['phone' => '']);
$v->phone('phone', 'الهاتف', true);
assert_false($v->passes(), 'PHONE-006', 'Empty required phone fails');

// Invalid phone numbers - special characters
$invalidPhones = [
    '0123456789/'  => 'PHONE-007',
    '0123456789\\' => 'PHONE-008',
    '01234/56789'  => 'PHONE-009',
    '01234\\56789' => 'PHONE-010',
    '01234-56789'  => 'PHONE-011',
    '01234_56789'  => 'PHONE-012',
    '01234.56789'  => 'PHONE-013',
    '01234,56789'  => 'PHONE-014',
    '01234 56789'  => 'PHONE-015',
    '01234ABCDEF'  => 'PHONE-016',
    '01234567ABC'  => 'PHONE-017',
    '01@23456789'  => 'PHONE-018',
    '+0123456789'  => 'PHONE-019',
];

foreach ($invalidPhones as $phone => $testId) {
    $v = new Validator(['phone' => $phone]);
    $v->phone('phone', 'الهاتف', false);
    assert_false($v->passes(), $testId, "Invalid phone rejected: '{$phone}'");
}

// Too short
$v = new Validator(['phone' => '123456']);
$v->phone('phone', 'الهاتف', false);
assert_false($v->passes(), 'PHONE-020', 'Phone too short (6 digits) rejected');

// Too long
$v = new Validator(['phone' => '1234567890123456']);
$v->phone('phone', 'الهاتف', false);
assert_false($v->passes(), 'PHONE-021', 'Phone too long (16 digits) rejected');

// ----------------------------------------------------------
// 2. Name Validation
// ----------------------------------------------------------
echo "\n--- 2. Name Validation ---\n";

// Valid names
$v = new Validator(['name' => 'Ahmed Ali']);
$v->name('name', 'الاسم', true);
assert_true($v->passes(), 'NAME-001', 'English name passes');

$v = new Validator(['name' => 'محمد أحمد']);
$v->name('name', 'الاسم', true);
assert_true($v->passes(), 'NAME-002', 'Arabic name passes');

$v = new Validator(['name' => 'محمد أحمد علي']);
$v->name('name', 'الاسم', true);
assert_true($v->passes(), 'NAME-003', 'Arabic name with spaces passes');

$v = new Validator(['name' => 'Ahmed Mohamed Ali']);
$v->name('name', 'الاسم', true);
assert_true($v->passes(), 'NAME-004', 'English name with spaces passes');

$v = new Validator(['name' => 'Jean-Pierre']);
$v->name('name', 'الاسم', true);
assert_true($v->passes(), 'NAME-005', 'Name with hyphen passes');

$v = new Validator(['name' => "O'Brien"]);
$v->name('name', 'الاسم', true);
assert_true($v->passes(), 'NAME-006', 'Name with apostrophe passes');

// Invalid names
$v = new Validator(['name' => '']);
$v->name('name', 'الاسم', true);
assert_false($v->passes(), 'NAME-007', 'Empty required name fails');

$v = new Validator(['name' => '   ']);
$v->name('name', 'الاسم', true);
assert_false($v->passes(), 'NAME-008', 'Whitespace-only name fails');

$v = new Validator(['name' => 'A']);
$v->name('name', 'الاسم', true);
assert_false($v->passes(), 'NAME-009', 'Single character name fails');

$v = new Validator(['name' => str_repeat('A', 101)]);
$v->name('name', 'الاسم', true);
assert_false($v->passes(), 'NAME-010', 'Name over 100 chars fails');

$v = new Validator(['name' => 'Ahmed  Ali']);
$v->name('name', 'الاسم', true);
assert_false($v->passes(), 'NAME-011', 'Name with double spaces fails');

$v = new Validator(['name' => "Ahmed\x00Ali"]);
$v->name('name', 'الاسم', true);
assert_false($v->passes(), 'NAME-012', 'Name with null byte fails');

$v = new Validator(['name' => "Ahmed\x01Ali"]);
$v->name('name', 'الاسم', true);
assert_false($v->passes(), 'NAME-013', 'Name with control character fails');

// Optional empty name
$v = new Validator(['name' => '']);
$v->name('name', 'الاسم', false);
assert_true($v->passes(), 'NAME-014', 'Empty optional name passes');

// ----------------------------------------------------------
// 3. Email Validation
// ----------------------------------------------------------
echo "\n--- 3. Email Validation ---\n";

// Valid emails
$v = new Validator(['email' => 'test@example.com']);
$v->email('email', 'البريد', false);
assert_true($v->passes(), 'EMAIL-001', 'Valid email passes');

$v = new Validator(['email' => 'user.name@domain.com']);
$v->email('email', 'البريد', false);
assert_true($v->passes(), 'EMAIL-002', 'Email with dot passes');

$v = new Validator(['email' => '']);
$v->email('email', 'البريد', false);
assert_true($v->passes(), 'EMAIL-003', 'Empty optional email passes');

// Invalid emails
$v = new Validator(['email' => 'invalid']);
$v->email('email', 'البريد', false);
assert_false($v->passes(), 'EMAIL-004', 'Invalid email rejected');

$v = new Validator(['email' => 'test @example.com']);
$v->email('email', 'البريد', false);
assert_false($v->passes(), 'EMAIL-005', 'Email with space rejected');

$v = new Validator(['email' => str_repeat('a', 250) . '@test.com']);
$v->email('email', 'البريد', false);
assert_false($v->passes(), 'EMAIL-006', 'Email over 254 chars rejected');

// Required empty email
$v = new Validator(['email' => '']);
$v->email('email', 'البريد', true);
assert_false($v->passes(), 'EMAIL-007', 'Empty required email fails');

// ----------------------------------------------------------
// 4. Numeric Validation
// ----------------------------------------------------------
echo "\n--- 4. Numeric Validation ---\n";

$v = new Validator(['amount' => '100']);
$v->numeric('amount', 'المبلغ');
assert_true($v->passes(), 'NUM-001', 'Integer string passes numeric');

$v = new Validator(['amount' => '100.50']);
$v->numeric('amount', 'المبلغ');
assert_true($v->passes(), 'NUM-002', 'Decimal string passes numeric');

$v = new Validator(['amount' => 100]);
$v->numeric('amount', 'المبلغ');
assert_true($v->passes(), 'NUM-003', 'Integer passes numeric');

$v = new Validator(['amount' => '']);
$v->numeric('amount', 'المبلغ');
assert_true($v->passes(), 'NUM-004', 'Empty string passes numeric');

$v = new Validator(['amount' => 'abc']);
$v->numeric('amount', 'المبلغ');
assert_false($v->passes(), 'NUM-005', 'Alphabetic string fails numeric');

$v = new Validator(['amount' => '12abc']);
$v->numeric('amount', 'المبلغ');
assert_false($v->passes(), 'NUM-006', 'Mixed alphanumeric fails numeric');

// Non-negative
$v = new Validator(['amount' => '100']);
$v->nonNegative('amount', 'المبلغ');
assert_true($v->passes(), 'NUM-007', 'Positive value passes nonNegative');

$v = new Validator(['amount' => '0']);
$v->nonNegative('amount', 'المبلغ');
assert_true($v->passes(), 'NUM-008', 'Zero passes nonNegative');

$v = new Validator(['amount' => '-1']);
$v->nonNegative('amount', 'المبلغ');
assert_false($v->passes(), 'NUM-009', 'Negative value fails nonNegative');

// Min/Max
$v = new Validator(['amount' => '50']);
$v->min('amount', 10, 'المبلغ');
assert_true($v->passes(), 'NUM-010', 'Value above min passes');

$v = new Validator(['amount' => '5']);
$v->min('amount', 10, 'المبلغ');
assert_false($v->passes(), 'NUM-011', 'Value below min fails');

$v = new Validator(['amount' => '50']);
$v->max('amount', 100, 'المبلغ');
assert_true($v->passes(), 'NUM-012', 'Value below max passes');

$v = new Validator(['amount' => '150']);
$v->max('amount', 100, 'المبلغ');
assert_false($v->passes(), 'NUM-013', 'Value above max fails');

// ----------------------------------------------------------
// 5. Required Field Validation
// ----------------------------------------------------------
echo "\n--- 5. Required Field Validation ---\n";

$v = new Validator(['field' => 'value']);
$v->required('field', 'الحقل');
assert_true($v->passes(), 'REQ-001', 'Non-empty value passes required');

$v = new Validator(['field' => '']);
$v->required('field', 'الحقل');
assert_false($v->passes(), 'REQ-002', 'Empty string fails required');

$v = new Validator(['field' => '   ']);
$v->required('field', 'الحقل');
assert_false($v->passes(), 'REQ-003', 'Whitespace-only fails required');

$v = new Validator(['field' => null]);
$v->required('field', 'الحقل');
assert_false($v->passes(), 'REQ-004', 'Null fails required');

$v = new Validator([]);
$v->required('field', 'الحقل');
assert_false($v->passes(), 'REQ-005', 'Missing field fails required');

// ----------------------------------------------------------
// 6. Length Validation
// ----------------------------------------------------------
echo "\n--- 6. Length Validation ---\n";

$v = new Validator(['field' => 'Hello']);
$v->maxLength('field', 10, 'الحقل');
assert_true($v->passes(), 'LEN-001', 'Value within maxLength passes');

$v = new Validator(['field' => 'Hello World!']);
$v->maxLength('field', 5, 'الحقل');
assert_false($v->passes(), 'LEN-002', 'Value exceeding maxLength fails');

$v = new Validator(['field' => 'مرحبا']);
$v->maxLength('field', 10, 'الحقل');
assert_true($v->passes(), 'LEN-003', 'Arabic value within maxLength passes');

$v = new Validator(['field' => 'مرحبا بالعالم']);
$v->maxLength('field', 5, 'الحقل');
assert_false($v->passes(), 'LEN-004', 'Arabic value exceeding maxLength fails');

$v = new Validator(['field' => 'Hello']);
$v->minLength('field', 3, 'الحقل');
assert_true($v->passes(), 'LEN-005', 'Value above minLength passes');

$v = new Validator(['field' => 'Hi']);
$v->minLength('field', 3, 'الحقل');
assert_false($v->passes(), 'LEN-006', 'Value below minLength fails');

// ----------------------------------------------------------
// 7. Digits Only Validation
// ----------------------------------------------------------
echo "\n--- 7. Digits Only Validation ---\n";

$v = new Validator(['field' => '12345']);
$v->digitsOnly('field', 'الحقل', false);
assert_true($v->passes(), 'DIG-001', 'Digits-only passes');

$v = new Validator(['field' => '12345abc']);
$v->digitsOnly('field', 'الحقل', false);
assert_false($v->passes(), 'DIG-002', 'Alphanumeric fails digitsOnly');

$v = new Validator(['field' => '123 456']);
$v->digitsOnly('field', 'الحقل', false);
assert_false($v->passes(), 'DIG-003', 'Digits with space fails digitsOnly');

// ----------------------------------------------------------
// 8. TrimAll Utility
// ----------------------------------------------------------
echo "\n--- 8. TrimAll Utility ---\n";

$data = ['name' => '  Ahmed  ', 'phone' => '  01000000001  ', 'amount' => 100];
$trimmed = Validator::trimAll($data);
assert_equals('Ahmed', $trimmed['name'], 'TRIM-001', 'String value trimmed');
assert_equals('01000000001', $trimmed['phone'], 'TRIM-002', 'Phone value trimmed');
assert_equals(100, $trimmed['amount'], 'TRIM-003', 'Numeric value unchanged');

// ----------------------------------------------------------
// 9. Error Messages
// ----------------------------------------------------------
echo "\n--- 9. Error Messages ---\n";

$v = new Validator(['phone' => '0123/456']);
$v->phone('phone', 'رقم الهاتف', false);
assert_false($v->passes(), 'ERR-001', 'Invalid phone has errors');
$errors = $v->errors();
assert_true(isset($errors['phone']), 'ERR-002', 'Error keyed by field name');
assert_true(strpos($errors['phone'], 'أرقام') !== false, 'ERR-003', 'Error message mentions digits');

$v = new Validator(['name' => '']);
$v->name('name', 'اسم المورد', true);
assert_false($v->passes(), 'ERR-004', 'Empty name has errors');
$firstError = $v->firstError();
assert_true(strpos($firstError, 'مطلوب') !== false, 'ERR-005', 'First error mentions required');

// ----------------------------------------------------------
// 10. Combined Validation (simulating form submission)
// ----------------------------------------------------------
echo "\n--- 10. Combined Validation ---\n";

// Valid employee data
$v = new Validator([
    'name' => 'محمد أحمد',
    'phone' => '01000000001',
    'base_salary' => '3000'
]);
$v->name('name', 'الاسم', true);
$v->phone('phone', 'التليفون', false);
$v->numeric('base_salary', 'الراتب');
$v->nonNegative('base_salary', 'الراتب');
$v->max('base_salary', 999999.99, 'الراتب');
assert_true($v->passes(), 'COMB-001', 'Valid employee data passes all validations');

// Invalid employee data - multiple errors
$v = new Validator([
    'name' => '',
    'phone' => '0123/456',
    'base_salary' => '-100'
]);
$v->name('name', 'الاسم', true);
$v->phone('phone', 'التليفون', false);
$v->numeric('base_salary', 'الراتب');
$v->nonNegative('base_salary', 'الراتب');
assert_false($v->passes(), 'COMB-002', 'Invalid employee data fails');
$errors = $v->errors();
assert_true(count($errors) >= 2, 'COMB-003', 'Multiple errors reported');

// Valid customer data
$v = new Validator([
    'name' => 'أحمد محمد',
    'phone' => '01099999999',
    'credit_limit' => '5000'
]);
$v->name('name', 'اسم العميل', true);
$v->phone('phone', 'رقم الهاتف', false);
$v->numeric('credit_limit', 'الحد الائتماني');
$v->nonNegative('credit_limit', 'الحد الائتماني');
assert_true($v->passes(), 'COMB-004', 'Valid customer data passes all validations');

// Valid supplier data
$v = new Validator([
    'name' => 'شركة التوريدات',
    'phone' => '01012345678',
    'address' => 'القاهرة'
]);
$v->name('name', 'اسم المورد', true);
$v->phone('phone', 'رقم الهاتف', false);
$v->maxLength('address', 255, 'العنوان');
assert_true($v->passes(), 'COMB-005', 'Valid supplier data passes all validations');

// ----------------------------------------------------------
// 11. Edge Cases
// ----------------------------------------------------------
echo "\n--- 11. Edge Cases ---\n";

// Unicode input
$v = new Validator(['name' => 'عبد الرحمن']);
$v->name('name', 'الاسم', true);
assert_true($v->passes(), 'EDGE-001', 'Arabic name with space passes');

$v = new Validator(['name' => 'José García']);
$v->name('name', 'الاسم', true);
assert_true($v->passes(), 'EDGE-002', 'Spanish name with accent passes');

// Very long valid input
$v = new Validator(['name' => str_repeat('أ', 100)]);
$v->name('name', 'الاسم', true);
assert_true($v->passes(), 'EDGE-003', '100-char Arabic name passes');

$v = new Validator(['name' => str_repeat('أ', 101)]);
$v->name('name', 'الاسم', true);
assert_false($v->passes(), 'EDGE-004', '101-char Arabic name fails');

// Phone with leading zeros
$v = new Validator(['phone' => '0012345678']);
$v->phone('phone', 'الهاتف', false);
assert_true($v->passes(), 'EDGE-005', 'Phone with leading zeros passes');

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

echo "\n  All Validation tests passed!\n\n";
exit(0);
