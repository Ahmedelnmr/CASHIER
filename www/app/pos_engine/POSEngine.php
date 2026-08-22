<?php
/**
 * POS Engine - Core Point of Sale Logic
 * Handles cart management, pricing, and checkout
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
    public function searchProducts($query) {
        return $this->productRepo->search($query, 20);
    }

    /**
     * Get product price based on sale mode
     */
    public function getPrice($product, $saleMode = 'unit') {
        switch ($saleMode) {
            case 'pack':
                return $product['sale_price_pack'] ?: $product['sale_price_unit'];
            case 'weight':
                return $product['sale_price_unit']; // price per kg/unit
            case 'custom':
                return 0; // Will be set manually
            default:
                return $product['sale_price_unit'];
        }
    }

    /**
     * Checkout - Finalize the sale
     * @param array $cartItems [{product_id, product_name, quantity, price, sale_mode, subtotal}]
     * @param array $saleData {discount, payment_method, notes}
     * @return array
     */
    public function checkout($cartItems, $saleData = []) {
        if (empty($cartItems)) {
            return ['success' => false, 'message' => 'السلة فارغة'];
        }

        $db = Database::getInstance();

        // Calculate totals
        $subtotal = 0;
        foreach ($cartItems as &$item) {
            $item['subtotal'] = round($item['quantity'] * $item['price'], 2);
            $subtotal += $item['subtotal'];
        }
        unset($item); // مهم: كسر المرجع بعد foreach بالمرجع

        $discount = isset($saleData['discount']) ? floatval($saleData['discount']) : 0;
        $total = round($subtotal - $discount, 2);

        $saleRecord = [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'payment_method' => $saleData['payment_method'] ?? 'cash',
            'notes' => $saleData['notes'] ?? null,
        ];

        try {
            $db->beginTransaction();

            // Create sale + items
            $saleId = $this->saleRepo->create($saleRecord, $cartItems);

            // Update stock for each item
        foreach ($cartItems as $item) {
            if (!empty($item['product_id'])) {
                // Use actual_quantity (in pieces) for stock deduction
                $qtyToDeduct = isset($item['actual_quantity']) ? $item['actual_quantity'] : $item['quantity'];
                
                // Decrease stock
                $this->productRepo->updateStock($item['product_id'], -$qtyToDeduct);

                // Record stock movement
                $this->stockMovementRepo->create([
                    'product_id' => $item['product_id'],
                    'type' => 'sale',
                    'quantity' => -$qtyToDeduct,
                    'reference_type' => 'sale',
                    'reference_id' => $saleId,
                    'notes' => 'بيع - فاتورة #' . $saleId,
                ]);
            }
        }
            // 3. Record Safe Transaction (Income)
            if ($total > 0) {
                $stmt = $db->getConnection()->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    'sale',    // Type corrected to 'sale'
                    $total,    // Amount
                    date('Y-m-d'), // Date
                    "مبيعات - فاتورة #" . $saleId // Description
                ]);
            }

            $db->commit();

            // Get the complete sale for receipt
            $sale = $this->saleRepo->findById($saleId);

            return [
                'success' => true,
                'sale_id' => $saleId,
                'sale' => $sale,
                'message' => 'تم البيع بنجاح'
            ];

        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'خطأ: ' . $e->getMessage()];
        }
    }
}
