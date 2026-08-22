<?php
/**
 * Sale Service - Business Logic Layer
 */

class SaleService {
    private $saleRepo;
    private $productRepo;
    private $stockMovementRepo;

    public function __construct() {
        $this->saleRepo = new SaleRepository();
        $this->productRepo = new ProductRepository();
        $this->stockMovementRepo = new StockMovementRepository();
    }

    /**
     * Void Sale (Cancel)
     * - Restore Stock
     * - Deduct Money from Safe
     * - Update Status to 'voided'
     */
    public function voidSale($id, $reason = 'Manual Void') {
        $sale = $this->saleRepo->findById($id);
        if (!$sale) {
            return ['success' => false, 'message' => 'البيع غير موجود'];
        }

        if ($sale['status'] === 'voided') {
            return ['success' => false, 'message' => 'الفاتورة ملغاة بالفعل'];
        }

        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();

            // 1. Restore Stock
            if (!empty($sale['items'])) {
                foreach ($sale['items'] as $item) {
                     $this->productRepo->updateStock($item['product_id'], $item['quantity']);
                     
                     $this->stockMovementRepo->create([
                         'product_id' => $item['product_id'],
                         'type' => 'adjustment',
                         'quantity' => $item['quantity'],
                         'reference_type' => 'sale_void',
                         'reference_id' => $id,
                         'notes' => "إلغاء فاتورة #{$sale['sale_number']} - $reason" 
                     ]);
                }
            }

            // 2. Revert Safe Transaction (cancel original sale income)
            if ($sale['total'] > 0) {
                 $stmt = $db->getConnection()->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)");
                 $stmt->execute([
                     'sale_void', 
                     $sale['total'], 
                     date('Y-m-d'), 
                     "إلغاء فاتورة مبيعات #{$sale['sale_number']}"
                 ]);
            }

            // 3. Update Status
            $this->saleRepo->updateStatus($id, 'voided');

            $db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Create Return (Murtaga3)
     */
    public function createReturn($originalSaleId, $items) {
        $originalSale = $this->saleRepo->findById($originalSaleId);
        if (!$originalSale) return ['success' => false, 'message' => 'الفاتورة الأصلية غير موجودة'];

        $db = Database::getInstance();

        // Calculate Return Total and enforce absolute positive quantities
        $returnTotal = 0;
        foreach ($items as &$item) {
            $item['quantity'] = abs(floatval($item['quantity'])); // Prevent negative quantities
            $returnTotal += ($item['quantity'] * $item['price']);
        }
        unset($item);

        try {
            $db->beginTransaction();

            // 1. Create Return Sale Record
            $saleData = [
                'subtotal' => -$returnTotal,
                'discount' => 0,
                'total' => -$returnTotal,
                'payment_method' => 'cash',
                'notes' => "مرتجع من فاتورة #{$originalSale['sale_number']}",
                'type' => 'return',
                'status' => 'completed',
                'parent_sale_id' => $originalSaleId
            ];
            
            // Format items for Repository
            $formattedItems = [];
            foreach ($items as $item) {
                $formattedItems[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => -$item['quantity'], // Negative quantity for display in receipt
                    'unit_type' => 'قطعة',
                    'price' => $item['price'],
                    'subtotal' => -($item['quantity'] * $item['price'])
                ];
            }

            $returnId = $this->saleRepo->create($saleData, $formattedItems);

            // 2. Update Stock (Increase)
            foreach ($items as $item) {
                // Return items to stock
                $this->productRepo->updateStock($item['product_id'], $item['quantity']);
                
                $this->stockMovementRepo->create([
                    'product_id' => $item['product_id'],
                    'type' => 'adjustment',
                    'quantity' => $item['quantity'],
                    'reference_type' => 'sale_return',
                    'reference_id' => $returnId,
                    'notes' => "مرتجع مبيعات #{$returnId}"
                ]);
            }

            // 3. Revert Safe (Withdrawal)
            if ($returnTotal > 0) {
                $stmt = $db->getConnection()->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    'sale_return', 
                    $returnTotal, 
                    date('Y-m-d'), 
                    "مرتجع مبيعات فاتورة #{$returnId}"
                ]);
            }

            $db->commit();
            return ['success' => true, 'id' => $returnId];

        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function findById($id) {
        return $this->saleRepo->findById($id);
    }

    public function getAll($dateFrom = '', $dateTo = '') {
        return $this->saleRepo->getAll($dateFrom, $dateTo);
    }

    public function getTodaySummary() {
        return $this->saleRepo->getTodaySummary();
    }

    public function getSalesTotals($dateFrom, $dateTo) {
        return $this->saleRepo->getSalesTotals($dateFrom, $dateTo);
    }

    /**
     * Delete Sale (Physical Delete with Stock Restoration)
     */
    public function deleteSale($id, $reason = '') {
        $sale = $this->saleRepo->findById($id);
        if (!$sale) {
            return ['success' => false, 'message' => 'البيع غير موجود'];
        }

        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();

            // 1. Restore Stock
            if (!empty($sale['items'])) {
                foreach ($sale['items'] as $item) {
                     $this->productRepo->updateStock($item['product_id'], $item['quantity']);
                     
                     $this->stockMovementRepo->create([
                         'product_id' => $item['product_id'],
                         'type' => 'adjustment',
                         'quantity' => $item['quantity'],
                         'reference_type' => 'sale_delete',
                         'reference_id' => $id,
                         'notes' => "حذف فاتورة #{$sale['sale_number']}" 
                     ]);
                }
            }

            // 2. Reverse Safe Transaction (cancel original sale income)
            if ($sale['total'] > 0) {
                 $stmt = $db->getConnection()->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)");
                 $stmt->execute([
                     'sale_void', 
                     $sale['total'], 
                     date('Y-m-d'), 
                     "حذف فاتورة مبيعات #{$sale['sale_number']}"
                 ]);
            }

            // 3. Delete Record
            $this->saleRepo->delete($id);

            $db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Update Sale (Full Logic)
     */
    public function updateSale($id, $newItems, $total, $discount, $reason) {
        $sale = $this->saleRepo->findById($id);
        if (!$sale) return ['success' => false, 'message' => 'Not Found'];

        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            // 1. Restore OLD Stock
            if (!empty($sale['items'])) {
                foreach ($sale['items'] as $item) {
                     $this->productRepo->updateStock($item['product_id'], $item['quantity']);
                     // Optional Log
                     $this->stockMovementRepo->create([
                         'product_id' => $item['product_id'],
                         'type' => 'adjustment',
                         'quantity' => $item['quantity'],
                         'reference_type' => 'sale_update_temp', // internal
                         'reference_id' => $id,
                         'notes' => "تعديل فاتورة - استرجاع مؤقت"
                     ]);
                }
            }

            // 2. Financial Adjustment (Reverse Old sale income)
            if ($sale['total'] > 0) {
                 $stmt = $db->getConnection()->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)");
                 $stmt->execute(['sale_void', $sale['total'], date('Y-m-d'), "تعديل فاتورة #{$sale['sale_number']} (إلغاء القديم)"]);
            }

            // 3. Delete Old Items
            $this->saleRepo->deleteItems($id);

            // 4. Insert New Items & Deduct New Stock
            foreach ($newItems as $item) {
                 // Add Item
                 $this->saleRepo->addItem($id, [
                     'product_id' => $item['product_id'],
                     'product_name' => $item['product_name'] ?? $item['name'],
                     'quantity' => $item['quantity'],
                     'unit_type' => $item['unit_type'] ?? 'قطعة',
                     'price' => $item['price'],
                     'sale_mode' => $item['sale_mode'] ?? 'unit',
                     'subtotal' => ($item['quantity'] * $item['price'])
                 ]);

                 // Deduct Stock
                 $this->productRepo->updateStock($item['product_id'], -$item['quantity']);
                 
                 // Log Movement
                 $this->stockMovementRepo->create([
                    'product_id' => $item['product_id'],
                    'type' => 'sale',
                    'quantity' => -$item['quantity'],
                    'reference_type' => 'sale_update',
                    'reference_id' => $id,
                    'notes' => "تعديل فاتورة"
                 ]);
            }

            // 5. Update Sale Totals
            // Ensure total logic matches (subtotal - discount)
            // But we accept passed total
            $this->saleRepo->updateTotals($id, $total, $discount, $reason);

            // 6. Financial Adjustment (Add New)
            if ($total > 0) {
                 $stmt = $db->getConnection()->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)");
                 $stmt->execute(['sale', $total, date('Y-m-d'), "تعديل فاتورة #{$sale['sale_number']} (إضافة الجديد)"]);
            }

            $db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function count() {
        return $this->saleRepo->count();
    }
}
