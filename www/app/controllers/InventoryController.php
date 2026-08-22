<?php
/**
 * Inventory Controller
 */

class InventoryController {
    private $inventoryService;

    public function __construct() {
        $this->inventoryService = new InventoryService();
    }

    public function index() {
        $products = (new ProductService())->getAll($_GET['search'] ?? '');
        $lowStock = $this->inventoryService->getLowStock();
        $summary = $this->inventoryService->getSummary();

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/inventory/index.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    /**
     * API: Adjust stock
     */
    public function adjust() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $productId = $input['product_id'] ?? 0;
        $newQuantity = $input['new_quantity'] ?? 0;
        $reason = $input['reason'] ?? '';

        $result = $this->inventoryService->adjustStock($productId, $newQuantity, $reason);

        if ($result['success']) {
            Response::success(null, 'تم تعديل المخزون');
        } else {
            Response::error($result['message']);
        }
    }

    /**
     * API: Get movements for a product
     */
    public function movements() {
        $productId = $_GET['product_id'] ?? null;
        $movements = $this->inventoryService->getMovements($productId);
        Response::success($movements);
    }

    /**
     * API: Low stock list
     */
    public function lowstock() {
        $lowStock = $this->inventoryService->getLowStock();
        Response::success($lowStock);
    }
}
