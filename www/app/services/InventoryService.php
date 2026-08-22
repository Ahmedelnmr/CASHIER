<?php
/**
 * Inventory Service - Business Logic Layer
 */

class InventoryService {
    private $productRepo;
    private $stockMovementRepo;

    public function __construct() {
        $this->productRepo = new ProductRepository();
        $this->stockMovementRepo = new StockMovementRepository();
    }

    /**
     * Manual stock adjustment
     */
    public function adjustStock($productId, $newQuantity, $reason = '') {
        $product = $this->productRepo->findById($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'المنتج غير موجود'];
        }

        $db = Database::getInstance();
        $difference = $newQuantity - $product['stock_quantity'];

        try {
            $db->beginTransaction();

            // Update stock directly
            $this->productRepo->updateStock($productId, $difference);

            // Record movement
            $this->stockMovementRepo->create([
                'product_id' => $productId,
                'type' => 'adjustment',
                'quantity' => $difference,
                'reference_type' => 'manual',
                'reference_id' => null,
                'notes' => $reason ?: 'تعديل يدوي',
            ]);

            $db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getLowStock() {
        return $this->productRepo->getLowStock();
    }

    public function getMovements($productId = null, $limit = 50) {
        if ($productId) {
            return $this->stockMovementRepo->getByProduct($productId, $limit);
        }
        return $this->stockMovementRepo->getRecent($limit);
    }

    /**
     * Get inventory summary stats
     */
    public function getSummary() {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT COUNT(*) as total_products, 
                              SUM(stock_quantity * purchase_price) as total_value,
                              COUNT(CASE WHEN stock_quantity <= min_stock AND min_stock > 0 THEN 1 END) as low_stock_count
                              FROM products WHERE is_active = 1");
        $stmt->execute();
        return $stmt->fetch();
    }
}
