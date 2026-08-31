# Validation Matrix - Employees, Customers & Suppliers

## Overview
All validation is enforced at both **frontend** (UX) and **backend** (authoritative) levels.
Frontend validation uses JavaScript; backend validation uses the centralized `Validator` class.

---

## Employee Module

| Field         | Required | Allowed Format                    | Min    | Max       | Unique | Notes                          |
|---------------|:--------:|-----------------------------------|:------:|:---------:|:------:|--------------------------------|
| name          | Yes      | Arabic/English letters, spaces    | 2 chars| 100 chars | No     | No control chars, no double spaces |
| phone         | No       | Digits only (0-9)                 | 7 digits| 15 digits| No     | Rejects all special characters |
| base_salary   | No       | Numeric (non-negative)            | 0      | 999,999.99| No     | Decimals allowed (2 places)    |

### Employee Create/Update Endpoints
- **Store**: `POST ?page=employees&action=store` (JSON body)
- **Update**: `POST ?page=employees&action=update` (JSON body)

---

## Customer Module

| Field         | Required | Allowed Format                    | Min    | Max       | Unique | Notes                          |
|---------------|:--------:|-----------------------------------|:------:|:---------:|:------:|--------------------------------|
| name          | Yes      | Arabic/English letters, spaces    | 2 chars| 100 chars | No     | No control chars, no double spaces |
| phone         | No       | Digits only (0-9)                 | 7 digits| 15 digits| No     | Rejects all special characters |
| address       | No       | Any text                          | -      | 255 chars | No     | Trimmed before save            |
| notes         | No       | Any text                          | -      | 500 chars | No     | Trimmed before save            |
| credit_limit  | No       | Numeric (non-negative)            | 0      | 999,999.99| No     | 0 = no limit                   |

### Customer Create/Update Endpoints
- **Create**: `POST ?page=customers&action=store` (form POST)
- **Update**: `POST ?page=customers&action=update` (form POST)
- **POS Create**: `POST ?page=pos&action=createCustomer` (JSON body)

---

## Supplier Module

| Field         | Required | Allowed Format                    | Min    | Max       | Unique | Notes                          |
|---------------|:--------:|-----------------------------------|:------:|:---------:|:------:|--------------------------------|
| name          | Yes      | Arabic/English letters, spaces    | 2 chars| 100 chars | No     | No control chars, no double spaces |
| phone         | No       | Digits only (0-9)                 | 7 digits| 15 digits| No     | Rejects all special characters |
| address       | No       | Any text                          | -      | 255 chars | No     | Trimmed before save            |
| notes         | No       | Any text                          | -      | 500 chars | No     | Trimmed before save            |

### Supplier Create/Update Endpoints
- **Create**: `POST ?page=suppliers&action=save` (form POST)
- **Update**: `POST ?page=suppliers&action=save` (form POST, with `id` field)

---

## Validation Rules Detail

### Phone Number Validation
- **Regex**: `/^[0-9]{7,15}$/`
- **Rejects**: `/`, `\`, `-`, `_`, `.`, `,`, `+`, spaces, letters, emojis, all special characters
- **Rejects**: Mixed values like `0123/89`, `0123ABC`, `01@23456789`
- **Rejects**: Whitespace before, after, or between digits
- **Empty allowed**: Yes (when field is not required)

### Name Validation
- **Regex**: `/^[\p{Arabic}\p{Latin}\s\.\-\']{2,100}$/u`
- **Supports**: Arabic, English, spaces, hyphens, apostrophes, dots
- **Rejects**: Control characters (`\x00-\x08`, `\x0B`, `\x0C`, `\x0E-\x1F`, `\x7F`)
- **Rejects**: Multiple consecutive spaces (`\s{2,}`)
- **Rejects**: Single character names
- **Rejects**: Names over 100 characters
- **Trims**: Leading/trailing whitespace before validation

### Email Validation
- Uses PHP's `filter_var(FILTER_VALIDATE_EMAIL)`
- **Rejects**: Whitespace within email
- **Max length**: 254 characters
- **Empty allowed**: Yes (when field is not required)

### Numeric Validation
- **Salary/Credit Limit**: Must be numeric, non-negative, max 999,999.99
- **Decimals**: Allowed (up to 2 decimal places)
- **Rejects**: Alphabetic characters, special characters, malformed decimals

---

## Error Messages (Arabic)

| Field         | Empty Required          | Invalid Format                              |
|---------------|-------------------------|---------------------------------------------|
| name          | `الاسم مطلوب`           | `الاسم يجب أن يكون حرفين على الأقل`         |
| phone         | `التليفون مطلوب`        | `رقم الهاتف يجب أن يحتوي على أرقام فقط (7-15 رقم)` |
| base_salary   | -                       | `الراتب الأساسي يجب أن يكون رقم`             |
| credit_limit  | -                       | `الحد الائتماني يجب أن يكون رقم`             |
| address       | -                       | `العنوان يجب أن لا يتجاوز 255 حرف`           |
| notes         | -                       | `ملاحظات يجب أن لا يتجاوز 500 حرف`           |

---

## Test Coverage

### Validation Tests (87 tests)
- Phone: 21 tests (valid formats, invalid characters, length boundaries)
- Name: 14 tests (Arabic, English, edge cases, control characters)
- Email: 7 tests (valid, invalid, required/optional)
- Numeric: 13 tests (integers, decimals, min/max, non-negative)
- Required: 5 tests (empty, whitespace, null, missing)
- Length: 6 tests (min/max, Arabic/English)
- Digits: 3 tests (valid, alphanumeric, spaces)
- Combined: 5 tests (full form validation)
- Edge Cases: 5 tests (Unicode, boundaries)

### Existing Module Tests
- Employee: 37 tests (all passing)
- Customer: 72 tests (all passing)
- Supplier: 49 tests (all passing)

---

## Files Modified

| File                                    | Change Description                              |
|-----------------------------------------|------------------------------------------------|
| `www/app/helpers/Validator.php`         | Enhanced with phone, name, email, nonNegative, minLength, digitsOnly, pattern, trimAll methods |
| `www/app/controllers/EmployeeController.php` | Added backend validation for store/update/delete |
| `www/app/controllers/CustomerController.php` | Added input trimming, ID validation            |
| `www/app/services/CustomerService.php`  | Added validate() method with full field validation |
| `www/app/services/SupplierService.php`  | Enhanced validate() with phone/name/address/notes validation |
| `www/app/views/employees/index.php`     | Added frontend JS validation for employee form  |
| `www/app/views/customers/index.php`     | Added frontend JS validation for add customer form |
| `www/app/views/customers/profile.php`   | Added frontend JS validation for edit/payment forms |
| `www/app/views/suppliers/form.php`      | Added frontend JS validation for supplier form  |
| `tests/ValidationTest.php`              | New: 87 comprehensive validation tests          |
