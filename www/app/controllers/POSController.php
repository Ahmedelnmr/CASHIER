<?php
/**
 * POS Controller
 */

class POSController {
    private $posEngine;

    public function __construct() {
        $this->posEngine = new POSEngine();
    }

    /**
     * POS Main Screen
     */
    public function index() {
        include APP_PATH . '/views/pos/index.php';
    }

    /**
     * API: Find product by barcode/PLU/ID
     */
    public function findProduct() {
        $query = $_GET['q'] ?? $_POST['q'] ?? '';
        if (empty($query)) {
            Response::error('أدخل باركود أو كود المنتج');
        }

        $product = $this->posEngine->findProduct($query);
        if ($product) {
            Response::success($product);
        } else {
            Response::error('المنتج غير موجود');
        }
    }

    /**
     * API: Search products
     */
    public function search() {
        $query = $_GET['q'] ?? '';
        // Return valid results even for empty query (initial load)
        $results = $this->posEngine->searchProducts($query);
        Response::success($results);
    }

    /**
     * API: Process checkout
     */
    public function checkout() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['items'])) {
            Response::error('السلة فارغة');
        }

        $result = $this->posEngine->checkout(
            $input['items'],
            [
                'discount' => $input['discount'] ?? 0,
                'payment_method' => $input['payment_method'] ?? 'cash',
                'notes' => $input['notes'] ?? '',
            ]
        );

        if ($result['success']) {
            Response::success($result);
        } else {
            Response::error($result['message']);
        }
    }
}
