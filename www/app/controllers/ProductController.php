<?php
/**
 * Product Controller
 */

class ProductController {
    private $productService;

    public function __construct() {
        $this->productService = new ProductService();
    }

    public function index() {
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $type = $_GET['type'] ?? '';
        $products = $this->productService->getAll($search, $category, $type);
        $categories = $this->productService->getCategories();

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/products/index.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    public function create() {
        $categories = $this->productService->getCategories();
        $product = null;
        
        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/products/form.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    public function edit() {
        $id = $_GET['id'] ?? 0;
        $product = $this->productService->findById($id);
        if (!$product) {
            header('Location: ?page=products');
            exit;
        }
        $categories = $this->productService->getCategories();

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/products/form.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    public function save() {
        $data = $_POST;
        $id = $data['id'] ?? '';

        if ($id) {
            $result = $this->productService->update($id, $data);
        } else {
            $result = $this->productService->create($data);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            // AJAX request
            if ($result['success']) {
                Response::success($result);
            } else {
                Response::error($result['errors'] ?? $result['message'] ?? 'خطأ');
            }
        } else {
            header('Location: ?page=products');
            exit;
        }
    }

    public function delete() {
        $id = $_GET['id'] ?? $_POST['id'] ?? 0;
        $this->productService->delete($id);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            Response::success(null, 'تم الحذف');
        } else {
            header('Location: ?page=products');
            exit;
        }
    }

    /**
     * API: Search products (AJAX)
     */
    public function search() {
        $q = $_GET['q'] ?? '';
        $results = $this->productService->search($q);
        Response::success($results);
    }

    /**
     * API: Get product by ID
     */
    public function get() {
        $id = $_GET['id'] ?? 0;
        $product = $this->productService->findById($id);
        if ($product) {
            Response::success($product);
        } else {
            Response::error('المنتج غير موجود');
        }
    }

    /**
     * API: Check for duplicate barcode or PLU code
     * Usage: ?page=products&action=checkDuplicate&field=plu_code&value=123&exclude_id=5
     */
    public function checkDuplicate() {
        $field = $_GET['field'] ?? '';
        $value = trim($_GET['value'] ?? '');
        $excludeId = $_GET['exclude_id'] ?? '';

        if (empty($value)) {
            echo json_encode(['exists' => false]);
            return;
        }

        $existing = null;
        if (in_array($field, ['barcode', 'barcode2', 'barcode3'])) {
            $existing = $this->productService->findByBarcode($value);
        } elseif ($field === 'plu_code') {
            $existing = $this->productService->findByPLU($value);
        }

        // If found and it's not the same product being edited
        if ($existing && (!$excludeId || $existing['id'] != $excludeId)) {
            echo json_encode([
                'exists' => true, 
                'product_name' => $existing['name'],
                'message' => ($field === 'barcode' ? 'الباركود' : 'كود PLU') . ' موجود بالفعل في منتج: ' . $existing['name']
            ]);
        } else {
            echo json_encode(['exists' => false]);
        }
    }
}
