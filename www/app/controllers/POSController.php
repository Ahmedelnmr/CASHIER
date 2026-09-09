<?php
/**
 * POS Controller
 * Handles all POS API endpoints and the main POS view
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
        $settingsService = new SettingsService();
        $storeInfo = $settingsService->getStoreInfo();
        
        // Check for open shift
        $shiftService = new ShiftService();
        $currentShift = $shiftService->getCurrentShift();
        
        // Get categories for filter
        $productRepo = new ProductRepository();
        $categories = $productRepo->getCategories();
        
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
        $category = $_GET['category'] ?? '';
        $results = $this->posEngine->searchProducts($query, 20, $category);
        Response::success($results);
    }

    /**
     * API: List products for POS grid (paginated, with category filter)
     */
    public function listProducts() {
        $category = $_GET['category'] ?? '';
        $page = max(1, intval($_GET['p'] ?? $_GET['page_num'] ?? (is_numeric($_GET['page'] ?? null) ? $_GET['page'] : 1)));
        $perPage = min(50, max(10, intval($_GET['per_page'] ?? 20)));
        
        $result = $this->posEngine->getProductsForGrid($category, $page, $perPage);
        Response::success($result);
    }

    /**
     * API: Get product categories
     */
    public function getCategories() {
        $productRepo = new ProductRepository();
        $categories = $productRepo->getCategories();
        Response::success($categories);
    }

    /**
     * API: Search customers
     */
    public function searchCustomers() {
        $query = $_GET['q'] ?? '';
        $customerService = new CustomerService();
        $results = $customerService->search($query);
        Response::success($results);
    }

    /**
     * API: Get customer by ID
     */
    public function getCustomer() {
        $id = intval($_GET['id'] ?? 0);
        $customerService = new CustomerService();
        $customer = $customerService->findById($id);
        if ($customer) {
            Response::success($customer);
        } else {
            Response::error('العميل غير موجود');
        }
    }

    /**
     * API: Quick-create customer from POS
     */
    public function createCustomer() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['name'])) {
            Response::error('اسم العميل مطلوب');
        }

        $customerService = new CustomerService();
        $result = $customerService->create([
            'name' => trim($input['name']),
            'phone' => trim($input['phone'] ?? ''),
            'address' => '',
            'notes' => 'أُضيف من نقطة البيع',
            'credit_limit' => floatval($input['credit_limit'] ?? 0),
        ]);

        if ($result['success']) {
            $customer = $customerService->findById($result['id']);
            Response::success($customer, 'تم إضافة العميل بنجاح');
        } else {
            Response::error($result['message'] ?? 'خطأ في إضافة العميل');
        }
    }

    /**
     * API: Check current shift status
     */
    public function checkShift() {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            Response::error('غير مسجل الدخول');
        }
        
        $shiftService = new ShiftService();
        $shift = $shiftService->getCurrentShift($userId);
        if ($shift) {
            Response::success($shift);
        } else {
            Response::error('لا توجد وردية مفتوحة');
        }
    }

    /**
     * API: Process checkout
     */
    public function checkout() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['items'])) {
            Response::error('السلة فارغة');
        }

        // Anti-duplicate: check nonce
        $nonce = $input['checkout_nonce'] ?? '';
        if ($nonce && isset($_SESSION['last_checkout_nonce']) && $_SESSION['last_checkout_nonce'] === $nonce) {
            Response::error('تم معالجة هذه العملية بالفعل. يرجى عدم الضغط مرتين.');
        }

        $result = $this->posEngine->checkout(
            $input['items'],
            [
                'discount' => $input['discount'] ?? 0,
                'payment_method' => $input['payment_method'] ?? 'cash',
                'notes' => $input['notes'] ?? '',
                'customer_id' => $input['customer_id'] ?? null,
                'amount_paid' => $input['amount_paid'] ?? null,
            ]
        );

        if ($result['success']) {
            // Save nonce to prevent duplicate
            if ($nonce) {
                $_SESSION['last_checkout_nonce'] = $nonce;
            }
            Response::success($result);
        } else {
            Response::error($result['message'], 400);
        }
    }
}
