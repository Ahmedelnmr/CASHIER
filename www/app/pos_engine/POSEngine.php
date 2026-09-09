<?php
/**
 * POS Engine - Core Point of Sale Logic
 * Handles cart management, pricing, checkout, stock/price validation
 * Enhanced: stock validation, credit sales, shift check, payment tracking
 */

class POSEngine {
    private $productRepo;
    private $saleRepo;
    private $stockMovementRepo;

    public function __construct() {
        $this->productRepo = new ProductRepository();
        $this->saleRepo = new SaleRepository();
        $this->stockMovementRepo = new StockMovementRepository();
    }

    /**
     * Find product for POS by barcode, PLU, or ID
     */
    public function findProduct($query) {
        $query = trim($query); // Always trim whitespace

        // 1. Try exact match (trimmed)
        $product = $this->productRepo->findByBarcode($query);
        if ($product) return $product;

        // 2. Try PLU
        $product = $this->productRepo->findByPLU($query);
        if ($product) return $product;

        // 3. Try ID
        if (is_numeric($query)) {
            $product = $this->productRepo->findById((int)$query);
            if ($product && $product['is_active']) return $product;
        }

        // 4. Fuzzy Barcode Matching (UPC-A vs EAN-13)
        if (is_numeric($query)) {
            // Case A: Scanned has leading zero (EAN-13), DB has UPC-A (12 digits)
            if (strlen($query) == 13 && substr($query, 0, 1) === '0') {
                $upc = substr($query, 1);
                $product = $this->productRepo->findByBarcode($upc);
                if ($product) return $product;
            }

            // Case B: Scanned is UPC-A (12 digits), DB has EAN-13 (leading zero)
            if (strlen($query) == 12) {
                $ean = '0' . $query;
                $product = $this->productRepo->findByBarcode($ean);
                if ($product) return $product;
            }
        }

        return null;
    }

    /**
     * Search products for POS
     */
    public function searchProducts($query, $limit = 20, $category = '') {
        return $this->productRepo->search($query, $limit, $category);
    }

    /**
     * Get paginated products for POS grid display
     */
    public function getProductsForGrid($category = '', $page = 1, $perPage = 20) {
        $db = Database::getInstance()->getConnection();
        
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT id, name, barcode, plu_code, type, 
                       sale_price_unit, stock_quantity, min_stock, category_id,
                       pack_type, pack_unit_quantity, pack_sale_price
                FROM products 
                WHERE is_active = 1";
        $params = [];
        
        if ($category !== '' && $category !== 'all') {
            $sql .= " AND category_id = :category_id";
            $params[':category_id'] = $category;
        }
        
        $sql .= " ORDER BY name ASC LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll();
        
        // Get total count for pagination
        $countSql = "SELECT COUNT(*) FROM products WHERE is_active = 1";
        if ($category !== '' && $category !== 'all') {
            $countSql .= " AND category_id = :category_id";
        }
        $countStmt = $db->prepare($countSql);
        if ($category !== '' && $category !== 'all') {
            $countStmt->bindValue(':category_id', $category);
        }
        $countStmt->execute();
        $totalCount = $countStmt->fetchColumn();
        
        return [
            'products' => $products,
            'total' => (int)$totalCount,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => ($offset + $perPage) < $totalCount
        ];
    }

    /**
     * Get product price based on sale mode
     */
    public function getPrice($product, $saleMode = 'unit') {
        switch ($saleMode) {
            case 'pack':
                return $product['pack_sale_price'] ?: $product['sale_price_unit'];
            case 'weight':
                return $product['sale_price_unit']; // price per kg/unit
            case 'custom':
                return 0; // Will be set manually
            default:
                return $product['sale_price_unit'];
        }
    }

    /**
     * Validate stock availability for all cart items
     * @return array ['valid' => bool, 'errors' => [...]]
     */
    public function validateStock($cartItems) {
        $errors = [];
        
        // Aggregate quantities by product_id (same product may appear multiple times)
        $aggregated = [];
        foreach ($cartItems as $item) {
            if (empty($item['product_id'])) continue; // Skip custom/manual items
            
            $pid = $item['product_id'];
            $qtyToDeduct = isset($item['actual_quantity']) ? floatval($item['actual_quantity']) : floatval($item['quantity']);
            
            if (!isset($aggregated[$pid])) {
                $aggregated[$pid] = ['qty' => 0, 'name' => $item['product_name'] ?? ''];
            }
            $aggregated[$pid]['qty'] += $qtyToDeduct;
        }
        
        // Check each product's stock
        foreach ($aggregated as $pid => $info) {
            $product = $this->productRepo->findById($pid);
            if (!$product) {
                $errors[] = "المنتج '{$info['name']}' غير موجود في قاعدة البيانات";
                continue;
            }
            if (!$product['is_active']) {
                $errors[] = "المنتج '{$info['name']}' غير نشط";
                continue;
            }
            
            $available = floatval($product['stock_quantity']);
            $requested = $info['qty'];
            
            if ($requested > $available) {
                $errors[] = "الكمية المطلوبة من '{$product['name']}' ({$requested}) تتجاوز المخزون المتاح ({$available})";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate and recalculate prices server-side
     * Returns recalculated totals from DB prices
     */
    public function validatePrices($cartItems) {
        $recalculatedSubtotal = 0;
        $validatedItems = [];
        
        foreach ($cartItems as $item) {
            if (empty($item['product_id'])) {
                // Custom/manual item — trust frontend price
                $price = floatval($item['price']);
                $qty = floatval($item['quantity']);
                $subtotal = round($qty * $price, 2);
                $recalculatedSubtotal += $subtotal;
                $validatedItems[] = $item;
                continue;
            }
            
            $product = $this->productRepo->findById($item['product_id']);
            if (!$product) continue;
            
            $saleMode = $item['sale_mode'] ?? 'unit';
            $dbPrice = $this->getPrice($product, $saleMode);
            $qty = floatval($item['quantity']);
            $subtotal = round($qty * $dbPrice, 2);
            $recalculatedSubtotal += $subtotal;
            
            // Update item with DB price
            $item['price'] = $dbPrice;
            $item['subtotal'] = $subtotal;
            $validatedItems[] = $item;
        }
        
        return [
            'items' => $validatedItems,
            'subtotal' => round($recalculatedSubtotal, 2)
        ];
    }

    /**
     * Check if a shift is currently open for the logged-in user
     */
    public function checkShift() {
        $shiftService = new ShiftService();
        $shift = $shiftService->getCurrentShift();
        return $shift;
    }

    /**
     * Checkout - Finalize the sale
     * @param array $cartItems [{product_id, product_name, quantity, price, sale_mode, subtotal, actual_quantity}]
     * @param array $saleData {discount, payment_method, notes, customer_id, amount_paid}
     * @return array
     */
    public function checkout($cartItems, $saleData = []) {
        if (empty($cartItems)) {
            return ['success' => false, 'message' => 'السلة فارغة'];
        }

        // 1. Check shift
        $shift = $this->checkShift();
        if (!$shift) {
            return ['success' => false, 'message' => 'لا توجد وردية مفتوحة. يرجى فتح وردية أولاً من صفحة الورديات.'];
        }

        // 2. Validate stock (hard-block)
        $stockCheck = $this->validateStock($cartItems);
        if (!$stockCheck['valid']) {
            return [
                'success' => false, 
                'message' => 'مخزون غير كافٍ',
                'stock_errors' => $stockCheck['errors']
            ];
        }

        // 3. Validate and recalculate prices from DB
        $priceValidation = $this->validatePrices($cartItems);
        $cartItems = $priceValidation['items'];
        $serverSubtotal = $priceValidation['subtotal'];

        $db = Database::getInstance();
        $paymentMethod = $saleData['payment_method'] ?? 'cash';
        $customerId = !empty($saleData['customer_id']) ? intval($saleData['customer_id']) : null;

        // 4. Validate credit sale requirements
        if ($paymentMethod === 'credit') {
            if (!$customerId) {
                return ['success' => false, 'message' => 'يجب اختيار عميل للبيع الآجل'];
            }
        }

        // Calculate totals using server-validated prices
        $subtotal = 0;
        foreach ($cartItems as &$item) {
            $item['subtotal'] = round(floatval($item['quantity']) * floatval($item['price']), 2);
            $subtotal += $item['subtotal'];
        }
        unset($item);

        $discount = isset($saleData['discount']) ? floatval($saleData['discount']) : 0;
        
        // Validate discount bounds
        if ($discount < 0) {
            return ['success' => false, 'message' => 'الخصم لا يمكن أن يكون سالباً'];
        }
        if ($discount > $subtotal) {
            return ['success' => false, 'message' => 'الخصم لا يمكن أن يتجاوز إجمالي الفاتورة'];
        }
        
        $total = round($subtotal - $discount, 2);
        $amountPaid = isset($saleData['amount_paid']) ? floatval($saleData['amount_paid']) : $total;
        $changeAmount = 0;
        
        if ($paymentMethod === 'cash') {
            $changeAmount = round($amountPaid - $total, 2);
            if ($changeAmount < 0) {
                return ['success' => false, 'message' => 'المبلغ المدفوع أقل من الإجمالي'];
            }
        } elseif ($paymentMethod === 'credit') {
            $amountPaid = 0;
            $changeAmount = 0;
        } else {
            // card or other
            $amountPaid = $total;
            $changeAmount = 0;
        }

        $saleRecord = [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'payment_method' => $paymentMethod,
            'notes' => $saleData['notes'] ?? null,
            'customer_id' => $customerId,
            'shift_id' => $shift['id'],
            'amount_paid' => $amountPaid,
            'change_amount' => $changeAmount,
        ];

        try {
            $db->beginTransaction();

            // 5. Create sale + items
            $saleResult = $this->saleRepo->create($saleRecord, $cartItems);
            $saleId = $saleResult['saleId'];
            $invoiceNumber = $saleResult['invoiceNumber'];

            // 6. Update stock for each item
            foreach ($cartItems as $item) {
                if (!empty($item['product_id'])) {
                    $qtyToDeduct = isset($item['actual_quantity']) ? floatval($item['actual_quantity']) : floatval($item['quantity']);
                    
                    // Decrease stock
                    $this->productRepo->updateStock($item['product_id'], -$qtyToDeduct);

                    // Record stock movement
                    $this->stockMovementRepo->create([
                        'product_id' => $item['product_id'],
                        'type' => 'sale',
                        'quantity' => -$qtyToDeduct,
                        'reference_type' => 'sale',
                        'reference_id' => $saleId,
                        'notes' => 'بيع - فاتورة ' . $invoiceNumber,
                    ]);
                }
            }

            // 7. Handle payment-method-specific operations
            if ($paymentMethod === 'credit') {
                // Credit sale: update customer balance
                if ($customerId && $total > 0) {
                    $conn = $db->getConnection();
                    
                    // Get current customer balance
                    $custStmt = $conn->prepare("SELECT balance, credit_limit, name FROM customers WHERE id = ?");
                    $custStmt->execute([$customerId]);
                    $customer = $custStmt->fetch();
                    
                    if (!$customer) {
                        throw new Exception('العميل غير موجود');
                    }
                    
                    $newBalance = floatval($customer['balance']) + $total;
                    
                    // Check credit limit
                    if ($customer['credit_limit'] > 0 && $newBalance > $customer['credit_limit']) {
                        throw new Exception('تجاوز الحد الائتماني للعميل (' . $customer['credit_limit'] . ')');
                    }
                    
                    // Update customer balance
                    $updateStmt = $conn->prepare("UPDATE customers SET balance = ?, updated_at = datetime('now','localtime') WHERE id = ?");
                    $updateStmt->execute([$newBalance, $customerId]);
                    
                    // Record customer transaction
                    $txnStmt = $conn->prepare(
                        "INSERT INTO customer_transactions (customer_id, type, amount, balance_after, reference_type, reference_id, description) 
                         VALUES (?, 'sale', ?, ?, 'sale', ?, ?)"
                    );
                    $txnStmt->execute([
                        $customerId, $total, $newBalance, $saleId,
                        'بيع آجل - فاتورة ' . $invoiceNumber
                    ]);
                }
                // No safe_transaction for credit sales (no cash exchanged)
            } else {
                // Cash/Card: Record Safe Transaction (Income)
                if ($total > 0) {
                    $stmt = $db->getConnection()->prepare(
                        "INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)"
                    );
                    $stmt->execute([
                        'sale',
                        $total,
                        date('Y-m-d'),
                        "مبيعات - فاتورة " . $invoiceNumber
                    ]);
                }
            }

            // 8. Update amount_paid and change on the sale record
            $updateSaleStmt = $db->getConnection()->prepare(
                "UPDATE sales SET amount_paid = ?, change_amount = ? WHERE id = ?"
            );
            $updateSaleStmt->execute([$amountPaid, $changeAmount, $saleId]);

            $db->commit();

            // Get the complete sale for receipt
            $sale = $this->saleRepo->findById($saleId);

            return [
                'success' => true,
                'sale_id' => $saleId,
                'sale' => $sale,
                'change' => $changeAmount,
                'message' => 'تم البيع بنجاح'
            ];

        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'خطأ: ' . $e->getMessage()];
        }
    }
}
