<?php
/**
 * Purchase Service - Business Logic Layer
 */

class PurchaseService {
    private $purchaseRepo;
    private $productRepo;
    private $stockMovementRepo;

    public function __construct() {
        $this->purchaseRepo = new PurchaseRepository();
        $this->productRepo = new ProductRepository();
        $this->stockMovementRepo = new StockMovementRepository();
    }

    /**
     * Create purchase invoice with stock update (transactional)
     */
    public function createInvoice($invoiceData, $items) {
        $db = Database::getInstance();
        
        // Validate
        if (empty($invoiceData['supplier_id'])) {
            return ['success' => false, 'message' => 'المورد مطلوب'];
        }
        if (empty($items)) {
            return ['success' => false, 'message' => 'يجب إضافة منتج واحد على الأقل'];
        }

        // Calculate total (handle returns)
        $purchaseTotal = 0;
        $returnTotal = 0;
        foreach ($items as &$item) {
            $isReturn = !empty($item['is_return']);
            $isBonus = !empty($item['is_bonus']);
            $item['subtotal'] = round($item['quantity'] * $item['purchase_price'], 2);
            
            if ($isBonus) {
                // Bonus: no cost impact
                $item['subtotal'] = 0;
            } elseif ($isReturn) {
                // Return: negative subtotal
                $returnTotal += $item['subtotal'];
                $item['subtotal'] = -$item['subtotal'];
            } else {
                $purchaseTotal += $item['subtotal'];
            }
        }
        unset($item); // مهم: كسر المرجع
        
        $netTotal = $purchaseTotal - $returnTotal;
        $discount = floatval($invoiceData['discount'] ?? 0);
        // Allow negative total (when returns > purchases)
        $invoiceData['total'] = round($netTotal - $discount, 2);
        $invoiceData['discount'] = $discount;

        try {
            $db->beginTransaction();

            // Create invoice + items
            $invoiceId = $this->purchaseRepo->create($invoiceData, $items);

            // Update stock and cost for each item
            foreach ($items as $item) {
                $productId = $item['product_id'];
                $qty = $item['quantity'];
                $isReturn = !empty($item['is_return']);
                $isBonus = !empty($item['is_bonus']);
                
                // Determine actual quantity
                $qtyChange = $qty;
                $isPack = isset($item['unit_mode']) && $item['unit_mode'] === 'pack';
                
                if ($isPack) {
                    $product = $this->productRepo->findById($productId);
                    if ($product && $product['pack_unit_quantity']) {
                        $qtyChange = $qty * $product['pack_unit_quantity'];
                    }
                } else {
                     $product = $this->productRepo->findById($productId);
                }

                if ($isReturn) {
                    // RETURN: Subtract from stock
                    $this->productRepo->updateStock($productId, -$qtyChange);
                    
                    // Record stock movement (negative)
                    $this->stockMovementRepo->create([
                        'product_id' => $productId,
                        'type' => 'adjustment',
                        'quantity' => -$qtyChange,
                        'reference_type' => 'purchase_return',
                        'reference_id' => $invoiceId,
                        'notes' => 'مرتجع مشتريات - فاتورة #' . $invoiceId,
                    ]);
                } else {
                    // PURCHASE: Add to stock
                    $this->productRepo->updateStock($productId, $qtyChange);

                    // Update Product Cost (Last Purchase Price) - Only if > 0 (Bonus Item Handling)
                    if ($item['purchase_price'] > 0 && !$isBonus) {
                        $updateData = [];
                        if ($isPack) {
                            $updateData['pack_purchase_price'] = $item['purchase_price'];
                            if ($product['pack_unit_quantity'] > 0) {
                                $updateData['purchase_price'] = $item['purchase_price'] / $product['pack_unit_quantity'];
                            }
                        } else {
                            $updateData['purchase_price'] = $item['purchase_price'];
                            if ($product['pack_unit_quantity'] > 0) {
                                $updateData['pack_purchase_price'] = $item['purchase_price'] * $product['pack_unit_quantity'];
                            }
                        }
                        if (!empty($updateData)) {
                            $this->productRepo->update($productId, $updateData);
                        }
                    }

                    // Record stock movement
                    $this->stockMovementRepo->create([
                        'product_id' => $productId,
                        'type' => 'purchase',
                        'quantity' => $qtyChange,
                        'reference_type' => 'purchase_invoice',
                        'reference_id' => $invoiceId,
                        'notes' => 'شراء - فاتورة #' . $invoiceId . ($isPack ? ' (عبوات)' : ''),
                    ]);
                }

            }

            // Record Safe Transaction
            if ($invoiceData['total'] > 0) {
                // Net purchase: money goes OUT of safe
                $stmt = $db->getConnection()->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    'purchase',
                    $invoiceData['total'],
                    $invoiceData['date'],
                    "مشتريات - فاتورة #" . $invoiceData['invoice_number']
                ]);
            } elseif ($invoiceData['total'] < 0) {
                // Net return: money comes BACK to safe (deposit)
                $stmt = $db->getConnection()->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    'deposit',
                    abs($invoiceData['total']),
                    $invoiceData['date'],
                    "مرتجع مشتريات - فاتورة #" . $invoiceData['invoice_number']
                ]);
            }

            $db->commit();
            return ['success' => true, 'id' => $invoiceId];

        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'خطأ: ' . $e->getMessage()];
        }
    }

    public function findById($id) {
        return $this->purchaseRepo->findById($id);
    }

    public function getAll($supplierId = '', $dateFrom = '', $dateTo = '') {
        return $this->purchaseRepo->getAll($supplierId, $dateFrom, $dateTo);
    }

    public function getLastInvoiceItems($supplierId) {
        return $this->purchaseRepo->getLastInvoiceItems($supplierId);
    }

    public function count() {
        return $this->purchaseRepo->count();
    }

    /**
     * Void Purchase Invoice
     * - Remove from Stock (Reverse Purchase)
     * - Refund Money to Safe (Deposit)
     * - Update Status to 'voided'
     */
    public function voidInvoice($id, $reason = 'Manual Void') {
        $invoice = $this->purchaseRepo->findById($id);
        if (!$invoice) {
            return ['success' => false, 'message' => 'الفاتورة غير موجودة'];
        }

        if ($invoice['status'] === 'voided') {
            return ['success' => false, 'message' => 'الفاتورة ملغاة بالفعل'];
        }

        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();

            // 1. Remove from Stock (Reverse Purchase)
            if (!empty($invoice['items'])) {
                foreach ($invoice['items'] as $item) {
                     // Determine quantity to remove (handle packs)
                     // Since we saved quantity in purchase_items as base units?
                     // Let's check PurchaseRepository logic. 
                     // It saved: quantity.
                     // And createInvoice performed conversion.
                     // But wait, PurchaseRepository 'create' saves what 'createInvoice' passes.
                     // createInvoice passed $qty (which was whatever the user entered).
                     // AND it updated stock with converted quantity.
                     // BUT stock_movements recorded converted quantity.
                     // The `quantity` in `purchase_items` is the raw quantity?
                     // Let's check PurchaseService::createInvoice logic again.
                     // It loops items, calculates $qtyToAdd (converted), calls updateStock($qtyToAdd).
                     // But PurchaseRepository::create receives $item['quantity'].
                     // Does it receive raw or converted?
                     // In createInvoice:
                     // foreach ($items as $item) ... $invoiceId = $this->purchaseRepo->create($invoiceData, $items);
                     // Then updates stock.
                     // The $items passed to repo are the raw items from input.
                     // So `purchase_items` has RAW quantity.
                     // We need to RE-CALCULATE converted quantity for stock reversal.
                     // OR check if product is pack.
                     
                     $product = $this->productRepo->findById($item['product_id']);
                     $qtyToRemove = $item['quantity'];
                     
                     // If the item in `purchase_items` doesn't store unit_mode, we might have a problem if we don't know if it was a pack.
                     // `purchase_items` schema: invoice_id, product_id, quantity, purchase_price, subtotal.
                     // It DOES NOT store unit_mode.
                     // However, usually purchase price indicates pack vs unit?
                     // Or we can assume input was in packs if we detect price match? Risky.
                     // Wait, `PurchaseService::createInvoice` calculated `$qtyToAdd`.
                     // If `purchase_items` doesn't know if it was a pack, we can't accurately reverse it if we only rely on quantity.
                     // BUT, `createInvoice` logic:
                     // $isPack = isset($item['unit_mode']) && $item['unit_mode'] === 'pack';
                     // This was from INPUT.
                     // The DB doesn't store it. This is a design flaw in the original code/schema.
                     // But we must work with it.
                     // If we void, we must guess? 
                     // Or maybe we can rely on `stock_movements`?
                     // We can find the stock movement associated with this invoice and product and reverse it!
                     // StockMovementReference: type='purchase_invoice', id=$id.
                     // YES! This is the robust way.
                     
                     // Find stock movement
                     $stmt = $db->getConnection()->prepare("SELECT quantity FROM stock_movements WHERE reference_type = 'purchase_invoice' AND reference_id = ? AND product_id = ?");
                     $stmt->execute([$id, $item['product_id']]);
                     $movement = $stmt->fetch();
                     
                     if ($movement) {
                         $qtyToRemove = $movement['quantity']; // This is the actual quantity added to stock
                         
                         $this->productRepo->updateStock($item['product_id'], -$qtyToRemove);
                         
                         $this->stockMovementRepo->create([
                             'product_id' => $item['product_id'],
                             'type' => 'adjustment',
                             'quantity' => -$qtyToRemove,
                             'reference_type' => 'purchase_void',
                             'reference_id' => $id,
                             'notes' => "إلغاء فاتورة شراء #{$invoice['invoice_number']} - $reason" 
                         ]);
                     }
                }
            }

            // 2. Refund Safe (Deposit)
            // If total > 0, we assume we paid cash/safe. So we get it back.
            if ($invoice['total'] > 0) {
                 $stmt = $db->getConnection()->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)");
                 $stmt->execute([
                     'deposit', 
                     $invoice['total'], 
                     date('Y-m-d'), 
                     "إلغاء فاتورة شراء #{$invoice['invoice_number']}"
                 ]);
            }

            // 3. Update Status
            $this->purchaseRepo->updateStatus($id, 'voided');

            $db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Create Purchase Return (Return Items to Supplier)
     */
    public function createReturn($originalId, $items) {
        $original = $this->purchaseRepo->findById($originalId);
        if (!$original) return ['success' => false, 'message' => 'الفاتورة غير موجودة'];

        $db = Database::getInstance();

        // Calculate Return Total
        $returnTotal = 0;
        foreach ($items as $item) {
            $returnTotal += ($item['quantity'] * $item['price']);
        }

        try {
            $db->beginTransaction();

            $invoiceData = [
                'supplier_id' => $original['supplier_id'],
                'invoice_number' => "RET-" . $original['invoice_number'],
                'date' => date('Y-m-d'),
                'total' => -$returnTotal,
                'discount' => 0,
                'notes' => "مرتجع من فاتورة #{$original['invoice_number']}",
                'type' => 'return',
                'status' => 'completed',
                'parent_invoice_id' => $originalId
            ];

            // Create Return Invoice
            // We need to pass items. The repo expects raw items.
            $formattedItems = [];
            foreach ($items as $item) {
                $formattedItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => -$item['quantity'], // Negative for display
                    'purchase_price' => $item['price'],
                    'subtotal' => -($item['quantity'] * $item['price'])
                ];
            }
            
            $returnId = $this->purchaseRepo->create($invoiceData, $formattedItems);
            
            // Update Stock and Log Movements
            foreach ($items as $item) {
                 // Determine actual quantity to remove from stock
                 $product = $this->productRepo->findById($item['product_id']);
                 $qtyToRemove = $item['quantity'];
                 $isPack = isset($item['unit_mode']) && $item['unit_mode'] === 'pack';
                 if ($isPack && $product['pack_unit_quantity']) {
                     $qtyToRemove = $item['quantity'] * $product['pack_unit_quantity'];
                 }

                // Update Stock (Decrease)
                $this->productRepo->updateStock($item['product_id'], -$qtyToRemove);
                
                // Log Movement
                $this->stockMovementRepo->create([
                    'product_id' => $item['product_id'],
                    'type' => 'adjustment',
                    'quantity' => -$item['quantity'], // Negative for purchase return (removal)
                    'reference_type' => 'purchase_return',
                    'reference_id' => $returnId,
                    'notes' => "مرتجع فاتورة شراء #{$returnId}"
                ]);
            }
            
            // Refund Safe (Deposit)
            if ($returnTotal > 0) {
                 $stmt = $db->getConnection()->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)");
                 $stmt->execute([
                     'deposit', 
                     $returnTotal, 
                     date('Y-m-d'), 
                     "مرتجع شراء - فاتورة #{$returnId}"
                 ]);
            }

            $db->commit();
            return ['success' => true, 'id' => $returnId];

        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Delete Purchase Invoice (Physical Delete with Stock Reversal)
     */
    public function deletePurchase($id) {
        $invoice = $this->purchaseRepo->findById($id);
        if (!$invoice) {
            return ['success' => false, 'message' => 'الفاتورة غير موجودة'];
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            // 1. Reverse Stock for each item
            if (!empty($invoice['items'])) {
                foreach ($invoice['items'] as $item) {
                    $productId = $item['product_id'];
                    $qty = $item['quantity'];
                    $isReturn = $item['subtotal'] < 0; // Return items have negative subtotal

                    // Determine base unit quantity
                    $product = $this->productRepo->findById($productId);
                    $qtyChange = $qty;
                    // Check actual movement to get precise converted quantity
                    $stmt = $db->getConnection()->prepare("SELECT quantity FROM stock_movements WHERE reference_type = 'purchase_invoice' AND reference_id = ? AND product_id = ?");
                    $stmt->execute([$id, $productId]);
                    $movement = $stmt->fetch();

                    if ($movement) {
                        $qtyChange = abs($movement['quantity']); // Use precise quantity from DB
                    }

                    if ($isReturn) {
                        // Was a return (subtracted stock) → Add it back
                        $this->productRepo->updateStock($productId, $qtyChange);
                    } else {
                        // Was a purchase (added stock) → Subtract it
                        $this->productRepo->updateStock($productId, -$qtyChange);
                    }

                    $this->stockMovementRepo->create([
                        'product_id' => $productId,
                        'type' => 'adjustment',
                        'quantity' => $isReturn ? $qtyChange : -$qtyChange,
                        'reference_type' => 'purchase_delete',
                        'reference_id' => $id,
                        'notes' => "حذف فاتورة شراء #" . ($invoice['invoice_number'] ?: $id)
                    ]);
                }
            }

            // 2. Reverse Safe Transaction
            if ($invoice['total'] > 0) {
                // Was a purchase (money went out) → Record as purchase reversal
                $stmt = $db->getConnection()->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    'purchase_void',
                    $invoice['total'],
                    date('Y-m-d'),
                    "إلغاء/حذف فاتورة شراء #" . ($invoice['invoice_number'] ?: $id)
                ]);
            } elseif ($invoice['total'] < 0) {
                // Was a net return (money came back) → Reverse it
                $stmt = $db->getConnection()->prepare("INSERT INTO safe_transactions (type, amount, date, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    'purchase_void',
                    abs($invoice['total']),
                    date('Y-m-d'),
                    "إلغاء فاتورة مرتجع شراء #" . ($invoice['invoice_number'] ?: $id)
                ]);
            }

            // 3. Delete Record
            $this->purchaseRepo->delete($id);

            $db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function getSupplierInvoiceCount($supplierId) {
        return $this->purchaseRepo->getSupplierInvoiceCount($supplierId);
    }

    public function updatePurchase($id, $invoiceData, $items) {
        // Find existing invoice
        $oldInvoice = $this->purchaseRepo->findById($id);
        if (!$oldInvoice) {
            return ['success' => false, 'message' => 'Invoice not found'];
        }

        // To safely update: delete old (safely using deletePurchase), then create new.
        // Since deletePurchase and createInvoice manage their own transactions, 
        // we can just call them sequentially. If delete succeeds but create fails, 
        // the user has to re-enter, but it's safe.
        // Wait, better approach: since the original code was doing this on frontend (delete then create),
        // we do it here sequentially on backend.
        
        $deleteResult = $this->deletePurchase($id);
        if (!$deleteResult['success']) {
            return $deleteResult;
        }

        return $this->createInvoice($invoiceData, $items);
    }
}
