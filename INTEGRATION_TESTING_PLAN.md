# POS System - Full Integration Testing Plan

**Version:** 1.0
**Date:** 2026-08-25
**Scope:** End-to-end integration testing covering all modules, business logic, edge cases, and cross-module interactions

---

## Table of Contents

1. [Products Module](#1-products-module)
2. [Suppliers Module](#2-suppliers-module)
3. [Purchase Invoices Module](#3-purchase-invoices-module)
4. [POS & Sales Module](#4-pos--sales-module)
5. [Returns & Voids Module](#5-returns--voids-module)
6. [Inventory & Stock Module](#6-inventory--stock-module)
7. [Customers & Credit Module](#7-customers--credit-module)
8. [Shifts Module](#8-shifts-module)
9. [Safe (Cash Register) Module](#9-safe-cash-register-module)
10. [Employees & Payroll Module](#10-employees--payroll-module)
11. [Users & Authentication Module](#11-users--authentication-module)
12. [Reports Module](#12-reports-module)
13. [Settings Module](#13-settings-module)
14. [Cross-Module Integration Scenarios](#14-cross-module-integration-scenarios)
15. [Edge Cases & Boundary Conditions](#15-edge-cases--boundary-conditions)

---

## 1. Products Module

### 1.1 Product CRUD Operations

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-PROD-001 | Create unit product with all fields | Create product with name, barcode, PLU, prices, stock, category | Product created with correct values; ID returned | Product: "أرز مصري" (Egyptian Rice) |
| INT-PROD-002 | Create pack product with multi-unit | Create pack product with pack_type, pack_unit_quantity, pack_prices | Pack fields stored correctly | Product: "مياه معدنية كرتونة" (Mineral Water Pack) |
| INT-PROD-003 | Create weight product | Create product with type='weight' | Product type stored as 'weight' | Product: "جبنة رومي" (Rumi Cheese) |
| INT-PROD-004 | Create product with minimal fields | Create with only name and prices | Defaults applied: type='unit', stock=0, is_active=1 | Product: "سكر" (Sugar) |
| INT-PROD-005 | Create product with 3 barcodes | Create with barcode, barcode2, barcode3 | All 3 barcodes stored and indexed | Product: "شاي ليبتون" (Lipton Tea) |
| INT-PROD-006 | Update product name and prices | Update existing product fields | Changes persisted correctly | Update "سكر" prices |
| INT-PROD-007 | Soft delete product | Delete product (set is_active=0) | Product marked inactive; still in DB | Delete inactive product |
| INT-PROD-008 | Search products by name | Search for partial Arabic name | Matching products returned | Search "أرز" |
| INT-PROD-009 | Find product by barcode | Scan/enter exact barcode | Correct product returned | Barcode "6221234567890" |
| INT-PROD-010 | Find product by PLU | Enter PLU code | Correct product returned | PLU "1001" |
| INT-PROD-011 | Find product by ID | Enter numeric ID | Correct product returned | Product ID 1 |
| INT-PROD-012 | Get low stock products | Query products where stock <= min_stock | Only products with min_stock > 0 returned | Products with low stock |
| INT-PROD-013 | Get categories list | Query distinct categories | All categories returned including NULL | Multiple categories |

### 1.2 Product Validation

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-PROD-020 | Create product without name | Submit with empty name | Validation error: "الاسم مطلوب" | - |
| INT-PROD-021 | Create product with name > 60 chars | Submit with 61+ character name | Validation error: max length | - |
| INT-PROD-022 | Create product with negative price | Submit purchase_price = -5 | Validation error: must be >= 0 | - |
| INT-PROD-023 | Create product with non-numeric price | Submit price = "abc" | Validation error: must be numeric | - |
| INT-PROD-024 | Duplicate barcode on create | Use existing barcode | Error: barcode already exists | Existing barcode "6221234567890" |
| INT-PROD-025 | Duplicate barcode2 on create | Use existing barcode2 | Error: barcode already exists | Existing barcode2 |
| INT-PROD-026 | Duplicate barcode3 on create | Use existing barcode3 | Error: barcode already exists | Existing barcode3 |
| INT-PROD-027 | Duplicate PLU on create | Use existing PLU code | Error: PLU already exists | Existing PLU "1001" |
| INT-PROD-028 | Update product with own barcode | Update product keeping its own barcode | No duplicate error (self-exclusion) | Update product 1 with its own barcode |
| INT-PROD-029 | Product with zero prices | Create with purchase_price=0, sale_price=0 | Allowed (no price validation) | Free sample product |

### 1.3 Product Edge Cases

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-PROD-030 | Product with Arabic name special chars | Create with Arabic name containing diacritics | Stored and searchable correctly | Product: "مُسَلَّم" |
| INT-PROD-031 | Product with very long barcode | Create with 50-digit barcode | Stored correctly | Long barcode product |
| INT-PROD-032 | Product with NULL category | Create without category | Category stored as NULL | Product without category |
| INT-PROD-033 | Product with stock = 0 | Create with zero stock | Stock stored as 0 | Zero stock product |
| INT-PROD-034 | Product with min_stock = 0 | Product with min_stock=0 | Never appears in low stock alerts | Most products |
| INT-PROD-035 | Inactive product not found by POS | Search for inactive product via POS | Not returned in results | Inactive product |

---

## 2. Suppliers Module

### 2.1 Supplier CRUD

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-SUP-001 | Create supplier with all fields | Create with name, phone, address, notes | Supplier created | Supplier: "شركة النيل" (Nile Company) |
| INT-SUP-002 | Create supplier with name only | Create with only name | Defaults applied | Supplier: "مورد القاهرة" (Cairo Supplier) |
| INT-SUP-003 | Update supplier | Change name and phone | Changes persisted | Update supplier 2 |
| INT-SUP-004 | Delete supplier with no invoices | Delete supplier with no purchase history | Deleted successfully | Supplier with no invoices |
| INT-SUP-005 | Delete supplier with invoices | Try to delete supplier with purchase invoices | FK RESTRICT: deletion blocked | Supplier with invoices |
| INT-SUP-006 | Search suppliers by name | Search partial name | Matching suppliers returned | Search "النيل" |
| INT-SUP-007 | Supplier with Arabic phone | Create with Arabic-formatted phone | Stored correctly | Phone "٠١٢٣٤٥٦٧٨٩" |

---

## 3. Purchase Invoices Module

### 3.1 Create Purchase Invoices

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-PUR-001 | Create normal purchase invoice | Create invoice with 3 normal items | Invoice created; stock increased; safe transaction recorded | Purchase from supplier 1 |
| INT-PUR-002 | Create purchase with pack items | Create with unit_mode='pack', quantity=10, pack_unit_quantity=12 | Stock increased by 120 (10*12) | Pack purchase |
| INT-PUR-003 | Create purchase with bonus items | Create with is_bonus=true items | Bonus items have subtotal=0; no cost impact | Bonus items purchase |
| INT-PUR-004 | Create purchase with return items | Create with is_return=true items | Return items have negative subtotal; stock decreased | Mixed purchase/return |
| INT-PUR-005 | Create purchase with discount | Create with discount amount | Net total = items_total - discount | Discounted purchase |
| INT-PUR-006 | Create purchase with negative net total | Returns exceed purchases in same invoice | Net total < 0; safe recorded as 'deposit' | Large return purchase |
| INT-PUR-007 | Create purchase updates product prices | Purchase with new prices | Product purchase_price and sale_price updated | Price update purchase |
| INT-PUR-008 | Bonus item does NOT update prices | Purchase with bonus item (price=0) | Product prices unchanged | Bonus purchase |
| INT-PUR-009 | Create purchase without supplier | Submit without supplier_id | Validation error | - |
| INT-PUR-010 | Create purchase without items | Submit with empty items | Validation error: at least 1 item required | - |
| INT-PUR-011 | Create purchase with invoice number | Include invoice_number field | Invoice number stored | Invoice "INV-2026-001" |

### 3.2 Purchase Returns

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-PUR-020 | Return full purchase | Return all items from original invoice | Stock decreased; safe deposit recorded | Return full purchase |
| INT-PUR-021 | Return partial purchase | Return some items | Stock decreased for returned items only | Partial return |
| INT-PUR-022 | Return with pack conversion | Return pack items | Stock decreased by pack_unit_quantity * quantity | Pack return |
| INT-PUR-023 | Return creates RET- prefix invoice | Process return | New invoice number has "RET-" prefix | Return invoice |
| INT-PUR-024 | Return from voided invoice | Try to return from voided purchase | Error: cannot return from voided | Voided invoice |

### 3.3 Purchase Voids & Deletion

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-PUR-030 | Void normal purchase | Void invoice | Stock decreased; safe deposit recorded | Void purchase |
| INT-PUR-031 | Void already-voided purchase | Try to void again | Error: already voided | Voided purchase |
| INT-PUR-032 | Delete purchase | Delete invoice | Stock reversed; safe transaction reversed | Delete purchase |
| INT-PUR-033 | Delete voided purchase | Delete already-voided invoice | No double stock reversal | Voided then deleted |
| INT-PUR-034 | Void purchase with pack items | Void pack purchase | Stock correctly reversed using stock_movements lookup | Pack purchase void |

### 3.4 Purchase Updates

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-PUR-040 | Update purchase items | Change quantities and prices | Old stock reversed; new stock applied; safe updated | Update purchase |
| INT-PUR-041 | Update purchase add items | Add new items to existing invoice | New items added; stock increased | Add items |
| INT-PUR-042 | Update purchase remove items | Remove items from invoice | Items removed; stock decreased | Remove items |

---

## 4. POS & Sales Module

### 4.1 Product Lookup at POS

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-POS-001 | Find product by exact barcode | Scan barcode "6221234567890" | Product returned | Product with barcode |
| INT-POS-002 | Find product by barcode2 | Scan secondary barcode | Product returned | Product with barcode2 |
| INT-POS-003 | Find product by barcode3 | Scan tertiary barcode | Product returned | Product with barcode3 |
| INT-POS-004 | Find product by PLU code | Enter PLU "1001" | Product returned | Product with PLU |
| INT-POS-005 | Find product by numeric ID | Enter product ID | Product returned | Any product |
| INT-POS-006 | EAN-13 to UPC-A fuzzy match | Scan 13-digit with leading 0 | Product found via 12-digit match | Product with UPC-A barcode |
| INT-POS-007 | UPC-A to EAN-13 fuzzy match | Scan 12-digit barcode | Product found via 13-digit match | Product with EAN-13 barcode |
| INT-POS-008 | Product not found | Enter unknown barcode | Null returned | - |
| INT-POS-009 | Inactive product not found | Scan inactive product barcode | Null returned | Inactive product |
| INT-POS-010 | Search products by name | Search partial name | Matching active products returned | Search "شاي" |

### 4.2 Pricing at POS

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-POS-020 | Unit mode pricing | Get price with sale_mode='unit' | Returns sale_price_unit | Unit product |
| INT-POS-021 | Pack mode pricing with pack price | Get price with sale_mode='pack' | Returns pack_sale_price | Pack product |
| INT-POS-022 | Pack mode fallback to unit price | Get price for product without pack_sale_price | Returns sale_price_unit | Product without pack price |
| INT-POS-023 | Weight mode pricing | Get price with sale_mode='weight' | Returns sale_price_unit (per kg) | Weight product |
| INT-POS-024 | Custom mode pricing | Get price with sale_mode='custom' | Returns 0 (cashier sets manually) | Any product |

### 4.3 Checkout Process

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-POS-030 | Single item cash sale | Add 1 item, checkout as cash | Sale created; stock deducted; safe transaction recorded | Single item sale |
| INT-POS-031 | Multi-item sale | Add 3 different items | Sale with correct subtotal per item | Multi-item sale |
| INT-POS-032 | Sale with discount | Apply discount | Total = subtotal - discount | Discounted sale |
| INT-POS-033 | Sale with 100% discount | Discount = subtotal | Total = 0; no safe transaction | Free sale |
| INT-POS-034 | Card payment sale | Checkout with payment_method='card' | Sale recorded with card payment | Card sale |
| INT-POS-035 | Other payment method | Checkout with payment_method='other' | Sale recorded | Other payment |
| INT-POS-036 | Sale with pack items (actual_quantity) | Pack item with actual_quantity for stock | Stock deducted by actual_quantity, not quantity | Pack sale |
| INT-POS-037 | Sale with weight items | Weight product, quantity=0.5 | Subtotal = 0.5 * price | Weight sale |
| INT-POS-038 | Sale with custom price | Custom mode, cashier sets price | Price used as set | Custom price sale |
| INT-POS-039 | Empty cart checkout | Try checkout with no items | Error: "السلة فارغة" | - |
| INT-POS-040 | Sale with customer association | Link sale to customer | customer_id stored on sale | Customer sale |
| INT-POS-041 | Sale with shift association | Link sale to current shift | shift_id stored on sale | Shift sale |
| INT-POS-042 | Sale with notes | Add notes to sale | Notes stored | Noted sale |
| INT-POS-043 | Sale number auto-increment | Create multiple sales same day | sale_number increments (1,2,3...) | Multiple sales |
| INT-POS-044 | Sale number resets daily | Create sales on different days | sale_number resets to 1 each day | Cross-day sales |
| INT-POS-045 | Zero-quantity item in cart | Add item with quantity=0 | Subtotal = 0; sale proceeds | Zero qty item |
| INT-POS-046 | Very large quantity | quantity = 99999 | Sale processes correctly | Large qty sale |
| INT-POS-047 | Negative stock allowed | Sell more than available stock | Stock goes negative; sale succeeds | Low stock product |
| INT-POS-048 | Sale with NULL product_id | Item without product_id | Stock deduction skipped | Custom item |

### 4.4 Sale Viewing & Management

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-POS-050 | View sale receipt | View completed sale | All items and totals displayed | Any sale |
| INT-POS-051 | List sales with date filter | Filter by date range | Only sales in range returned | Sales across dates |
| INT-POS-052 | Get sale by ID | API call to get sale | Sale data with items returned | Any sale |
| INT-POS-053 | Edit sale items | Change quantities/items | Old stock reversed; new stock applied; safe updated | Edit sale |
| INT-POS-054 | Edit sale discount | Change discount amount | Total recalculated | Edit discount |
| INT-POS-055 | Edit sale as new (void + reload) | Void original, load cart in POS | Original voided; new sale created | Edit as new |

---

## 5. Returns & Voids Module

### 5.1 Sale Returns

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-RET-001 | Full return of sale | Return all items | Return sale created with negative total; stock restored; safe transaction | Full return |
| INT-RET-002 | Partial return of sale | Return some items | Partial return; stock restored for returned items only | Partial return |
| INT-RET-003 | Return with quantity validation | Try to return more than sold | Error: return qty exceeds original qty | Over-return attempt |
| INT-RET-004 | Return on voided sale | Try to return voided sale | Error: cannot return voided sale | Voided sale |
| INT-RET-005 | Return on return sale | Try to return a return | Error: cannot return a return | Return sale |
| INT-RET-006 | Return creates negative safe transaction | Process return | sale_return transaction recorded (money out) | Return sale |
| INT-RET-007 | Return stock movement recorded | Process return | Stock movement with type='sale_return' created | Return sale |
| INT-RET-008 | Return with weight product | Return fractional quantity | Stock restored correctly | Weight return |

### 5.2 Sale Voids

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-VOID-001 | Void completed sale | Void sale | Status='voided'; stock restored; safe_void transaction | Void sale |
| INT-VOID-002 | Void already-voided sale | Try to void again | Error: already voided | Voided sale |
| INT-VOID-003 | Void sale with multiple items | Void multi-item sale | All items' stock restored | Multi-item void |
| INT-VOID-004 | Void sale safe reversal | Void sale | sale_void transaction recorded (money out) | Void sale |
| INT-VOID-005 | Void sale stock movement | Void sale | Stock movements with type='adjustment', ref='sale_void' | Void sale |
| INT-VOID-006 | Delete sale (physical) | Delete sale | Stock reversed; safe reversed; items deleted | Delete sale |
| INT-VOID-007 | Delete voided sale | Delete already-voided sale | No double stock reversal | Voided delete |
| INT-VOID-008 | Void sale excluded from reports | Void sale, run daily report | Voided sale not in totals | Voided sale |

### 5.3 Purchase Returns & Voids

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-PRET-001 | Return purchase items | Process purchase return | Stock decreased; safe deposit | Purchase return |
| INT-PRET-002 | Void purchase invoice | Void purchase | Stock decreased; safe deposit | Void purchase |
| INT-PRET-003 | Return from mixed invoice | Return from invoice with normal+bonus+return items | Correct stock reversal | Mixed invoice return |

---

## 6. Inventory & Stock Module

### 6.1 Stock Adjustments

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-INV-001 | Manual stock increase | Adjust stock from 100 to 150 | Stock = 150; movement recorded (+50) | Product with 100 stock |
| INT-INV-002 | Manual stock decrease | Adjust stock from 100 to 50 | Stock = 50; movement recorded (-50) | Product with 100 stock |
| INT-INV-003 | Adjust stock to zero | Set stock to 0 | Stock = 0; movement recorded | Zero adjustment |
| INT-INV-004 | Adjust stock to negative | Set stock to -5 | Stock = -5 (allowed); movement recorded | Negative adjustment |
| INT-INV-005 | Adjust non-existent product | Adjust stock for invalid ID | Error: product not found | - |
| INT-INV-006 | Stock movement audit trail | Check movements after multiple operations | All movements recorded with correct types and references | Multiple operations |

### 6.2 Stock Movement Tracking

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-INV-010 | Purchase creates stock movement | Create purchase | Movement type='purchase', positive quantity | Purchase |
| INT-INV-011 | Sale creates stock movement | Create sale | Movement type='sale', negative quantity | Sale |
| INT-INV-012 | Void sale creates adjustment | Void sale | Movement type='adjustment', positive quantity | Void |
| INT-INV-013 | Return creates stock movement | Process return | Movement type='sale_return', positive quantity | Return |
| INT-INV-014 | Manual adjustment creates movement | Adjust stock | Movement type='adjustment', reference='manual' | Manual adjust |
| INT-INV-015 | Get movements by product | Query movements for product | Only that product's movements returned | Product movements |
| INT-INV-016 | Get movements with limit | Query with limit=10 | Only 10 most recent returned | Many movements |

### 6.3 Inventory Summary

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-INV-020 | Total products count | Get summary | Count of active products | Multiple products |
| INT-INV-021 | Total inventory value | Get summary | SUM(stock_quantity * purchase_price) | Products with stock |
| INT-INV-022 | Low stock count | Get summary | Count where stock <= min_stock AND min_stock > 0 | Low stock products |
| INT-INV-023 | Low stock list | Get low stock products | Products ordered by stock ASC | Low stock products |
| INT-INV-024 | Products with min_stock=0 excluded | Check low stock | Products with min_stock=0 not in list | min_stock=0 products |

---

## 7. Customers & Credit Module

### 7.1 Customer CRUD

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-CUST-001 | Create customer with all fields | Create with name, phone, address, credit_limit | Customer created | Customer: "أحمد محمد" |
| INT-CUST-002 | Create customer minimal | Create with name only | Defaults: balance=0, credit_limit=0 | Customer: "محمد علي" |
| INT-CUST-003 | Update customer | Change phone and address | Changes persisted | Update customer |
| INT-CUST-004 | Soft delete customer | Delete customer | is_active=0 | Delete customer |
| INT-CUST-005 | Search customers by name | Search partial name | Matching customers returned | Search "أحمد" |
| INT-CUST-006 | Search customers by phone | Search by phone | Matching customers returned | Search phone |
| INT-CUST-007 | Get debtors list | Query customers with balance > 0 | Only customers with positive balance | Debtors |

### 7.2 Credit Sales

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-CUST-010 | Credit sale within limit | Sale amount < credit_limit | Balance increased; transaction recorded | Credit sale |
| INT-CUST-011 | Credit sale exceeds limit | Sale amount > credit_limit | Error: exceeds credit limit | Over-limit attempt |
| INT-CUST-012 | Credit sale with limit=0 (unlimited) | Any amount sale | Allowed (no limit enforcement) | Unlimited customer |
| INT-CUST-013 | Credit sale exactly at limit | Sale amount = credit_limit | Allowed (at limit) | At-limit sale |
| INT-CUST-014 | Multiple credit sales accumulate | Multiple sales | Balance accumulates correctly | Multiple sales |
| INT-CUST-015 | Credit sale transaction recorded | Check customer_transactions | Type='sale', balance_after correct | Credit sale |

### 7.3 Customer Payments

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-CUST-020 | Record full payment | Pay full balance | Balance = 0; transaction recorded | Full payment |
| INT-CUST-021 | Record partial payment | Pay part of balance | Balance reduced; transaction recorded | Partial payment |
| INT-CUST-022 | Overpayment (balance goes negative) | Pay more than owed | Balance < 0 (customer credit); transaction recorded | Overpayment |
| INT-CUST-023 | Payment creates safe deposit | Record payment | safe_transaction type='deposit' created | Payment |
| INT-CUST-024 | Payment with zero amount | Try to pay 0 | Validation error: amount must be > 0 | - |
| INT-CUST-025 | Payment transaction balance_after | Check transaction | balance_after reflects new balance | Payment |

### 7.4 Customer Edge Cases

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-CUST-030 | Customer with no transactions | Check profile | balance=0, no transactions | New customer |
| INT-CUST-031 | Customer with mixed transactions | Sales + payments + adjustments | Balance calculated correctly | Active customer |
| INT-CUST-032 | Deleted customer not in search | Search after soft delete | Not returned in results | Deleted customer |
| INT-CUST-033 | Customer balance after void | Void sale linked to customer | Balance should be reversed (TODO: check if implemented) | Void linked sale |

---

## 8. Shifts Module

### 8.1 Shift Lifecycle

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-SHIFT-001 | Open shift | Open with opening_cash=500 | Shift created; status='open'; safe 'opening' transaction | Shift open |
| INT-SHIFT-002 | Open shift with zero cash | Open with opening_cash=0 | Shift created; no safe transaction | Zero cash shift |
| INT-SHIFT-003 | Open second shift (same user) | Try to open while one is open | Error: user already has open shift | Open shift |
| INT-SHIFT-004 | Close shift | Close with closing_cash | Shift closed; totals calculated | Close shift |
| INT-SHIFT-005 | Close shift with correct cash | closing_cash = expected_cash | cash_difference = 0 | Balanced shift |
| INT-SHIFT-006 | Close shift with overage | closing_cash > expected_cash | cash_difference > 0 (positive) | Overage shift |
| INT-SHIFT-007 | Close shift with shortage | closing_cash < expected_cash | cash_difference < 0 (negative) | Shortage shift |
| INT-SHIFT-008 | Close already-closed shift | Try to close again | Error: shift not open | Closed shift |
| INT-SHIFT-009 | Get current shift | Query for open shift | Returns open shift or null | Open shift |
| INT-SHIFT-010 | Get shift by ID | Query specific shift | Shift data returned | Any shift |

### 8.2 Shift Totals Calculation

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-SHIFT-020 | Sales total in shift | Calculate totals | total_sales = sum of non-voided sales | Sales in shift |
| INT-SHIFT-021 | Returns total in shift | Calculate totals | total_returns = sum of ABS(return totals) | Returns in shift |
| INT-SHIFT-022 | Voided sales excluded | Calculate totals | Voided sales not in total_sales | Voided sales |
| INT-SHIFT-023 | Expenses total in shift | Calculate totals | total_expenses from safe_transactions | Expenses |
| INT-SHIFT-024 | Cash IN total | Calculate totals | sale + deposit + opening | Cash IN types |
| INT-SHIFT-025 | Cash OUT total | Calculate totals | expense + purchase + withdrawal + salary + advance + sale_return + sale_void | Cash OUT types |
| INT-SHIFT-026 | Expected cash calculation | Calculate | opening_cash + cash_in - cash_out | Shift with transactions |
| INT-SHIFT-027 | Sale count in shift | Calculate totals | Count of non-voided sales | Multiple sales |

### 8.3 X-Report & Z-Report

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-SHIFT-030 | X-Report while shift open | Generate X-Report | Shows calculated totals; shift remains open | Open shift |
| INT-SHIFT-031 | Z-Report after closing | Generate Z-Report | Shows stored final totals | Closed shift |
| INT-SHIFT-032 | X-Report includes expected cash | Check report | expected_cash = opening + cash_in - cash_out | Shift with sales |
| INT-SHIFT-033 | Z-Report includes cash difference | Check report | cash_difference = closing - expected | Closed shift |

---

## 9. Safe (Cash Register) Module

### 9.1 Safe Transactions

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-SAFE-001 | Add expense transaction | Create expense | Balance decreased | Expense "إيجار" (Rent) |
| INT-SAFE-002 | Add deposit transaction | Create deposit | Balance increased | Deposit |
| INT-SAFE-003 | Add withdrawal transaction | Create withdrawal | Balance decreased | Owner withdrawal |
| INT-SAFE-004 | Add salary transaction with employee | Create salary linked to employee | Balance decreased; employee_id stored | Salary payment |
| INT-SAFE-005 | Add advance transaction with employee | Create advance linked to employee | Balance decreased; employee_id stored | Employee advance |
| INT-SAFE-006 | Delete safe transaction | Delete transaction | Transaction removed; balance reversed | Delete transaction |
| INT-SAFE-007 | Filter by type | Filter safe transactions | Only matching type returned | Multiple types |
| INT-SAFE-008 | Filter by date range | Filter by month | Only transactions in range | Transactions across months |
| INT-SAFE-009 | Filter by employee | Filter by employee_id | Only that employee's transactions | Employee transactions |

### 9.2 Safe Balance Calculation

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-SAFE-020 | Balance = IN - OUT | Calculate balance | Correct sum of all ins and outs | Multiple transactions |
| INT-SAFE-021 | Sale increases balance | POS sale | Balance += sale total | Sale |
| INT-SAFE-022 | Sale void decreases balance | Void sale | Balance -= sale total | Void |
| INT-SAFE-023 | Sale return decreases balance | Return sale | Balance -= ABS(return total) | Return |
| INT-SAFE-024 | Purchase decreases balance | Purchase invoice | Balance -= purchase total | Purchase |
| INT-SAFE-025 | Purchase return increases balance | Purchase return | Balance += return total | Purchase return |
| INT-SAFE-026 | Customer payment increases balance | Record payment | Balance += payment amount | Payment |
| INT-SAFE-027 | Opening cash increases balance | Shift open | Balance += opening_cash | Shift open |
| INT-SAFE-028 | Advance requires employee | Try advance without employee | Validation error | - |
| INT-SAFE-029 | Salary requires employee | Try salary without employee | Validation error | - |

### 9.3 Safe Edge Cases

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-SAFE-030 | Zero amount transaction | Try to add 0 amount | Validation error or allowed (check) | - |
| INT-SAFE-031 | Negative amount transaction | Try negative amount | Validation error or handled | - |
| INT-SAFE-032 | Delete auto-generated transaction | Try to delete sale/purchase transaction | Should it be allowed? (current: hard delete) | Auto transaction |
| INT-SAFE-033 | Safe balance with all transaction types | Mix of all types | Correct net balance | All types |

---

## 10. Employees & Payroll Module

### 10.1 Employee Management

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-EMP-001 | Create employee | Create with name, phone, salary | Employee created | Employee: "كريم أحمد" |
| INT-EMP-002 | Create employee with zero salary | Create with base_salary=0 | Employee created; no accrual | Zero salary employee |
| INT-EMP-003 | Update employee salary | Change base_salary | Salary updated for future accruals | Update salary |
| INT-EMP-004 | Deactivate employee | Set is_active=0 | Employee inactive | Deactivate |
| INT-EMP-005 | List employees with balances | Get all with balance | Each employee shows current balance | Multiple employees |

### 10.2 Monthly Accruals

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-EMP-010 | Run monthly accrual | Run for current month | Accruals created for all active employees | Active employees |
| INT-EMP-011 | Idempotent accrual | Run accrual twice | No duplicate accruals (UNIQUE constraint) | Already accrued |
| INT-EMP-012 | Skip zero-salary employees | Run accrual | Employees with salary=0 skipped | Zero salary employee |
| INT-EMP-013 | Skip inactive employees | Run accrual | Inactive employees skipped | Inactive employee |

### 10.3 Employee Balance

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-EMP-020 | Balance = accruals - salary - advances | Calculate balance | Correct formula | Employee with all types |
| INT-EMP-021 | Positive balance (company owes) | After accrual, before payment | balance > 0 | Accrued employee |
| INT-EMP-022 | Negative balance (advance debt) | After advance > accruals | balance < 0 | Advance employee |
| INT-EMP-023 | Zero balance | After full payment | balance = 0 | Paid employee |
| INT-EMP-024 | Balance across months | Multiple months accruals | Cumulative balance | Multi-month employee |

---

## 11. Users & Authentication Module

### 11.1 Authentication

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-AUTH-001 | Login with valid credentials | Login admin/admin123 | Success; session set | Default admin |
| INT-AUTH-002 | Login with wrong password | Login admin/wrong | Failure; no session | - |
| INT-AUTH-003 | Login with non-existent user | Login unknown/user | Failure | - |
| INT-AUTH-004 | Login with inactive user | Login as inactive user | Failure (is_active=0) | Inactive user |
| INT-AUTH-005 | Logout | Logout | Session cleared | Logged in user |
| INT-AUTH-006 | Check isLoggedIn | After login | Returns true | Logged in |
| INT-AUTH-007 | Check getCurrentUser | After login | Returns user data | Logged in |

### 11.2 User Management

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-AUTH-010 | Create new user | Create cashier user | User created with bcrypt password | New cashier |
| INT-AUTH-011 | Create user with duplicate username | Use existing username | Error: username exists | Existing username |
| INT-AUTH-012 | Change user password | Update password | New bcrypt hash stored | Change password |
| INT-AUTH-013 | Update user role | Change role | Role updated | Role change |
| INT-AUTH-014 | Soft delete user | Delete user | is_active=0 | Delete user |
| INT-AUTH-015 | Delete last admin | Try to delete last admin | Error: cannot delete last admin | Last admin |
| INT-AUTH-016 | Delete non-last admin | Delete admin when others exist | Allowed | Multiple admins |

### 11.3 Role-Based Access

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-AUTH-020 | Admin role level | hasRole('admin') | True for admin | Admin user |
| INT-AUTH-021 | Manager role level | hasRole('manager') | True for admin and manager | Manager user |
| INT-AUTH-022 | Cashier role level | hasRole('cashier') | True for all roles | Cashier user |
| INT-AUTH-023 | Cashier cannot access purchases | Access purchase page | Denied (requires manager+) | Cashier user |
| INT-AUTH-024 | Cashier cannot access safe | Access safe page | Denied (requires admin) | Cashier user |
| INT-AUTH-025 | Manager can access purchases | Access purchase page | Allowed | Manager user |
| INT-AUTH-026 | Manager cannot access safe | Access safe page | Denied (requires admin) | Manager user |

---

## 12. Reports Module

### 12.1 Daily Sales Report

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-RPT-001 | Daily sales with data | Report for date with sales | Sales listed; summary with count and total | Sales today |
| INT-RPT-002 | Daily sales no data | Report for date with no sales | Empty list; count=0, total=0 | Empty date |
| INT-RPT-003 | Daily sales excludes voided | Report with voided sales | Voided sales not in totals | Voided sales |
| INT-RPT-004 | Daily sales excludes returns | Report with returns | Returns not in sales total | Return sales |
| INT-RPT-005 | Daily sales with discount | Sales with discounts | Discount included in summary | Discounted sales |

### 12.2 Sales Range Report

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-RPT-010 | Weekly sales | Last 7 days | Daily breakdown returned | Week of sales |
| INT-RPT-011 | Monthly sales | Current month | Daily breakdown returned | Month of sales |
| INT-RPT-012 | Custom date range | Specific from/to | Only dates in range | Range sales |
| INT-RPT-013 | Empty range | Range with no sales | Empty daily; totals=0 | Empty range |

### 12.3 Product Reports

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-RPT-020 | Top products | Get top 20 | Products ordered by quantity sold DESC | Multiple sales |
| INT-RPT-021 | Least products | Get least 20 | Products ordered by quantity sold ASC | Multiple sales |
| INT-RPT-022 | Low stock report | Get low stock | Products where stock <= min_stock | Low stock products |
| INT-RPT-023 | Dead stock (never sold) | Get dead stock | Products with stock > 0, never sold | Never-sold product |
| INT-RPT-024 | Dead stock (old sales) | Get dead stock with days=30 | Products not sold in 30+ days | Old-sale product |
| INT-RPT-025 | Product profit margin | Get profit report | Profit = revenue - cost; margin % calculated | Products with sales |
| INT-RPT-026 | Sales by category | Get category report | Sales grouped by category | Multiple categories |

### 12.4 Financial Reports

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-RPT-030 | Financial P&L | Get financial report | Income - Expenses = Net Profit | All transaction types |
| INT-RPT-031 | Financial includes deposits | Check report | Deposits counted as income | Deposits |
| INT-RPT-032 | Financial excludes withdrawals | Check report | Withdrawals not in operating expenses | Withdrawals |
| INT-RPT-033 | Purchases by supplier | Get supplier report | Purchases grouped by supplier | Multiple suppliers |

### 12.5 Other Reports

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-RPT-040 | Cashier performance | Get performance | Sales per cashier via shifts | Multiple cashiers |
| INT-RPT-041 | Customer debtors | Get debtors | Customers with balance > 0 | Debtors |
| INT-RPT-042 | Dashboard summary | Get summary | Today's sales, returns, expenses, net | Today's activity |

### 12.6 CSV Export

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-RPT-050 | Export daily sales CSV | Export | Valid CSV with UTF-8 BOM | Sales data |
| INT-RPT-051 | Export top products CSV | Export | Valid CSV | Products data |
| INT-RPT-052 | Export category sales CSV | Export | Valid CSV | Category data |
| INT-RPT-053 | Export profit CSV | Export | Valid CSV | Profit data |
| INT-RPT-054 | Export dead stock CSV | Export | Valid CSV | Dead stock |
| INT-RPT-055 | Export debtors CSV | Export | Valid CSV | Debtors |

---

## 13. Settings Module

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-SET-001 | Get default settings | Query settings | Default values returned | Default settings |
| INT-SET-002 | Update single setting | Change store_name | New value persisted | Update setting |
| INT-SET-003 | Update multiple settings | Bulk update | All values persisted | Bulk update |
| INT-SET-004 | Get non-existent setting | Query missing key | Default value returned | - |
| INT-SET-005 | Get store info | Query store info | All store fields returned with defaults | Store settings |
| INT-SET-006 | Setting with NULL value | Create setting with NULL | Stored as NULL | NULL setting |

---

## 14. Cross-Module Integration Scenarios

### 14.1 Full Purchase-to-Sale Flow

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-CROSS-001 | Purchase then sell | 1. Create product (stock=0) 2. Purchase 50 units 3. Sell 10 units | Stock = 40; safe balance correct | Full flow |
| INT-CROSS-002 | Purchase, sell, then void sale | 1. Purchase 2. Sell 3. Void sale | Stock restored to purchase level | Void flow |
| INT-CROSS-003 | Purchase, sell, then return | 1. Purchase 2. Sell 3. Return partial | Stock partially restored | Return flow |
| INT-CROSS-004 | Purchase with pack, sell as unit | 1. Purchase 10 packs (12 each) 2. Sell 5 units | Stock = 120 - 5 = 115 | Pack-to-unit flow |

### 14.2 Customer Credit Flow

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-CROSS-010 | Credit sale then payment | 1. Credit sale 2. Record payment | Balance = 0; safe has deposit | Credit flow |
| INT-CROSS-011 | Credit sale then void | 1. Credit sale 2. Void sale | Customer balance reversed | Credit void flow |
| INT-CROSS-012 | Multiple credit sales then partial pay | 1. Sale 1 2. Sale 2 3. Partial payment | Balance = total_sales - payment | Multi-credit flow |
| INT-CROSS-013 | Credit sale at limit then add more | 1. Sale at limit 2. Try another sale | Second sale rejected | Limit flow |

### 14.3 Shift Lifecycle Flow

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-CROSS-020 | Full shift lifecycle | 1. Open shift 2. Make sales 3. Process returns 4. Add expenses 5. Close shift | All totals correct in shift report | Full shift |
| INT-CROSS-021 | Shift with voided sales | 1. Open shift 2. Make sales 3. Void some sales 4. Close | Voided excluded from totals; safe reversals counted | Void shift |
| INT-CROSS-022 | Multiple shifts same day | 1. Open/close shift A 2. Open/close shift B | Each shift has independent totals | Two shifts |
| INT-CROSS-023 | Shift with customer payments | 1. Open shift 2. Record payments 3. Close | Payments in cash_in total | Payment shift |

### 14.4 Employee Payroll Flow

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-CROSS-030 | Monthly accrual then salary payment | 1. Run accrual 2. Pay salary | Balance = 0; safe transaction | Payroll flow |
| INT-CROSS-031 | Advance then accrual offset | 1. Give advance 2. Run accrual | Balance = accrual - advance | Advance flow |
| INT-CROSS-032 | Multiple months accumulation | 1. Jan accrual 2. Feb accrual 3. Pay partial | Balance = total - paid | Multi-month flow |

### 14.5 Inventory Audit Flow

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-CROSS-040 | Full inventory audit | 1. Purchase 2. Sell 3. Return 4. Void 5. Manual adjust 6. Check movements | All 5 movement types recorded | All operations |
| INT-CROSS-041 | Stock value after all operations | Calculate total value | SUM(stock * purchase_price) correct | Complex inventory |
| INT-CROSS-042 | Low stock alert after sales | Sell until low stock | Product appears in low stock | Low stock trigger |

### 14.6 End-of-Day Flow

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-CROSS-050 | Complete EOD process | 1. All day sales 2. Process returns 3. Record expenses 4. Close shift 5. Run reports | All reports match; shift balanced | Full day |
| INT-CROSS-051 | EOD with discrepancies | 1. Sales 2. Close with wrong cash | cash_difference detected | Discrepancy day |

---

## 15. Edge Cases & Boundary Conditions

### 15.1 Data Integrity

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-EDGE-001 | FK constraint: delete supplier with invoices | Try to delete | Error: FK RESTRICT | Supplier with invoices |
| INT-EDGE-002 | FK constraint: delete product with sales | Try to delete | Error: FK RESTRICT (sale_items references) | Product with sales |
| INT-EDGE-003 | FK constraint: sale_items CASCADE delete | Delete sale | Sale items deleted automatically | Sale with items |
| INT-EDGE-004 | FK constraint: product SET NULL on delete | Delete product after sale | sale_items.product_id = NULL | Product with past sales |
| INT-EDGE-005 | CHECK constraint: product type | Insert invalid type | Error: CHECK constraint | - |
| INT-EDGE-006 | CHECK constraint: payment_method | Insert invalid method | Error: CHECK constraint | - |
| INT-EDGE-007 | CHECK constraint: sale type | Insert invalid type | Error: CHECK constraint | - |
| INT-EDGE-008 | CHECK constraint: user role | Insert invalid role | Error: CHECK constraint | - |
| INT-EDGE-009 | UNIQUE constraint: username | Insert duplicate username | Error: UNIQUE constraint | Existing user |
| INT-EDGE-010 | UNIQUE constraint: employee accrual | Insert duplicate (employee, month) | Error: UNIQUE constraint | Existing accrual |

### 15.2 Numeric Boundaries

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-EDGE-020 | Very large sale amount | Sale with total = 999999.99 | Stored correctly | Large sale |
| INT-EDGE-021 | Very small quantity | quantity = 0.01 (weight) | Stored correctly | Tiny quantity |
| INT-EDGE-022 | Rounding: 0.1 + 0.2 | Calculate subtotal | 0.30 (rounded to 2 decimals) | Rounding test |
| INT-EDGE-023 | Discount larger than subtotal | discount > subtotal | Total becomes negative | Over-discount |
| INT-EDGE-024 | Zero price item | price = 0 | subtotal = 0; sale proceeds | Free item |
| INT-EDGE-025 | Stock at exact min_stock | stock = min_stock | Appears in low stock | Exact threshold |

### 15.3 Date & Time Edge Cases

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-EDGE-030 | Sale at midnight | Sale datetime at 00:00:00 | Recorded correctly | Midnight sale |
| INT-EDGE-031 | Sale at end of day | Sale datetime at 23:59:59 | Recorded correctly | End-of-day sale |
| INT-EDGE-032 | Report for date boundary | Report at midnight | Correct date filtering | Boundary sales |
| INT-EDGE-033 | Shift spanning midnight | Open before midnight, close after | Totals include both days | Overnight shift |

### 15.4 Arabic & Localization

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-EDGE-040 | Arabic product names | Create/search with Arabic | Correct UTF-8 handling | Arabic products |
| INT-EDGE-041 | Arabic customer names | Create/search with Arabic | Correct handling | Arabic customers |
| INT-EDGE-042 | Arabic error messages | Trigger validation errors | Arabic messages returned | Validation triggers |
| INT-EDGE-043 | Arabic in notes/description | Store Arabic text | Correct storage and retrieval | Arabic notes |
| INT-EDGE-044 | Mixed Arabic/English | Store mixed text | Correct handling | Mixed text |

### 15.5 Concurrency & Race Conditions

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-EDGE-050 | Two sales same stock | Two sales for last item | Both succeed; stock = -1 (negative allowed) | Last-item product |
| INT-EDGE-051 | Sale during stock adjustment | Adjust stock while selling | DB transaction isolation protects | Concurrent ops |
| INT-EDGE-052 | Sale number race condition | Two sales at same time | Both get sale_number (no UNIQUE constraint) | Simultaneous sales |

### 15.6 Safe Transaction Type Coverage

| Test ID | Scenario | Steps | Expected Result | Seed Data |
|---------|----------|-------|-----------------|-----------|
| INT-EDGE-060 | All safe IN types | Create each IN type | Balance increases for each | All IN types |
| INT-EDGE-061 | All safe OUT types | Create each OUT type | Balance decreases for each | All OUT types |
| INT-EDGE-062 | Safe balance formula verification | Mix all types | Balance = sum(IN) - sum(OUT) | All types mixed |

---

## Seed Data Summary

The seed data file `seed_data.sql` contains the following data categories designed to cover ALL scenarios above:

### Users (3)
- **admin** (admin role, password: admin123) - Default system admin
- **manager1** (manager role, password: manager123) - For purchase/report access
- **cashier1** (cashier role, password: cashier123) - For POS operations

### Suppliers (3)
- **شركة النيل** (Nile Company) - Active, with purchases
- **مورد القاهرة** (Cairo Supplier) - Active, with purchases
- **مورد محذوف** (Deleted Supplier) - Inactive, no purchases

### Products (15)
- **Unit products** (8): Various categories, barcodes, stock levels
- **Pack products** (4): With pack_unit_quantity, pack prices
- **Weight products** (2): For weight-based selling
- **Inactive product** (1): For soft-delete testing
- **Low stock products** (2): For low stock alerts
- **Zero stock product** (1): For edge case testing

### Customers (5)
- **أحمد محمد** - With credit limit, has transactions
- **محمد علي** - No credit limit (unlimited), has balance
- **فاطمة حسن** - Zero balance, no transactions
- **عميل محذوف** - Soft deleted
- **عميل مدين** - Has debt (balance > 0)

### Employees (3)
- **كريم أحمد** - Active, with salary, has accruals
- **سارة محمود** - Active, with salary, has advance
- **موظف محذوف** - Inactive

### Shifts (3)
- **Closed shift** - With sales, returns, expenses
- **Open shift** - Currently active
- **Old closed shift** - For historical reports

### Sales (10+)
- Normal cash sale
- Card payment sale
- Sale with discount
- Multi-item sale
- Sale linked to customer (credit)
- Sale linked to shift
- Voided sale
- Return sale (negative)
- Sale with pack items
- Sale with weight items

### Purchase Invoices (5+)
- Normal purchase
- Purchase with pack items
- Purchase with bonus items
- Purchase with discount
- Voided purchase
- Purchase return

### Safe Transactions (10+)
- Sale income
- Sale void (out)
- Sale return (out)
- Purchase (out)
- Deposit (in)
- Expense (out)
- Withdrawal (out)
- Salary payment (out)
- Advance (out)
- Opening cash (in)

### Stock Movements (15+)
- Purchase movements
- Sale movements
- Adjustment movements
- Return movements
- Void movements

### Settings (7)
- All default settings populated

---

## Test Execution Order

1. **Phase 1: Foundation** - Products, Suppliers, Users, Settings (INT-PROD, INT-SUP, INT-AUTH, INT-SET)
2. **Phase 2: Inventory** - Stock operations (INT-INV)
3. **Phase 3: Purchases** - Purchase invoices and returns (INT-PUR, INT-PRET)
4. **Phase 4: Sales** - POS operations (INT-POS)
5. **Phase 5: Returns/Voids** - Return and void operations (INT-RET, INT-VOID)
6. **Phase 6: Customers** - Credit operations (INT-CUST)
7. **Phase 7: Shifts** - Shift lifecycle (INT-SHIFT)
8. **Phase 8: Safe** - Cash register (INT-SAFE)
9. **Phase 9: Employees** - Payroll (INT-EMP)
10. **Phase 10: Reports** - All reports (INT-RPT)
11. **Phase 11: Cross-Module** - Integration scenarios (INT-CROSS)
12. **Phase 12: Edge Cases** - Boundary conditions (INT-EDGE)
