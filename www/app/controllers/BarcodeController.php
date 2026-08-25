<?php
/**
 * Barcode Controller - Generate & Print Barcodes
 */

class BarcodeController {
    private $productRepo;

    public function __construct() {
        $this->productRepo = new ProductRepository();
    }

    /**
     * Show barcode management page
     */
    public function index() {
        $products = $this->productRepo->getAll();
        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/barcode/index.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    /**
     * Generate barcode image (API)
     */
    public function image() {
        $data = $_GET['data'] ?? '';
        $width = intval($_GET['w'] ?? 2);
        $height = intval($_GET['h'] ?? 60);

        if (empty($data)) {
            header('HTTP/1.0 400 Bad Request');
            return;
        }

        require_once APP_PATH . '/helpers/BarcodeGenerator.php';
        BarcodeGenerator::output($data, $width, $height, true);
    }

    /**
     * Print labels page
     */
    public function labels() {
        $ids = $_GET['ids'] ?? '';
        $productIds = array_filter(array_map('intval', explode(',', $ids)));

        $products = [];
        foreach ($productIds as $id) {
            $p = $this->productRepo->findById($id);
            if ($p) $products[] = $p;
        }

        include APP_PATH . '/views/barcode/labels.php';
    }

    /**
     * Generate barcode for product (auto-generate if no barcode)
     */
    public function generate() {
        $id = intval($_GET['id'] ?? 0);
        $product = $this->productRepo->findById($id);

        if (!$product) {
            $_SESSION['toast_error'] = 'المنتج غير موجود';
            header('Location: ?page=barcode');
            exit;
        }

        // If product already has barcode, just redirect
        if (!empty($product['barcode'])) {
            $_SESSION['toast_success'] = 'المنتج لديه باركود بالفعل: ' . $product['barcode'];
            header('Location: ?page=barcode');
            exit;
        }

        // Generate a unique barcode (prefix 2 + product ID padded)
        $barcode = '2' . str_pad($product['id'], 6, '0', STR_PAD_LEFT);
        
        // Check uniqueness
        $existing = $this->productRepo->findByBarcode($barcode);
        if ($existing) {
            $barcode = '2' . str_pad($product['id'], 6, '0', STR_PAD_LEFT) . rand(0, 9);
        }

        $this->productRepo->update($id, ['barcode' => $barcode]);

        $_SESSION['toast_success'] = 'تم توليد الباركود: ' . $barcode;
        header('Location: ?page=barcode');
        exit;
    }
}
