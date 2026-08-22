<?php
/**
 * Inventory Engine - Stock Management Logic
 */

class InventoryEngine {
    private $inventoryService;

    public function __construct() {
        $this->inventoryService = new InventoryService();
    }

    public function adjustStock($productId, $newQuantity, $reason = '') {
        return $this->inventoryService->adjustStock($productId, $newQuantity, $reason);
    }

    public function getLowStock() {
        return $this->inventoryService->getLowStock();
    }

    public function getMovements($productId = null, $limit = 50) {
        return $this->inventoryService->getMovements($productId, $limit);
    }

    public function getSummary() {
        return $this->inventoryService->getSummary();
    }
}
