<?php
/**
 * Customer Controller
 */

class CustomerController {
    private $customerService;

    public function __construct() {
        $this->customerService = new CustomerService();
    }

    /**
     * List customers
     */
    public function index() {
        $search = $_GET['search'] ?? '';
        $customers = $this->customerService->getAll($search);
        $toastSuccess = $_SESSION['toast_success'] ?? '';
        $toastError = $_SESSION['toast_error'] ?? '';
        unset($_SESSION['toast_success'], $_SESSION['toast_error']);

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/customers/index.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    /**
     * View customer profile
     */
    public function profile() {
        $id = intval($_GET['id'] ?? 0);
        $customer = $this->customerService->findById($id);
        if (!$customer) {
            header('Location: ?page=customers');
            exit;
        }
        $transactions = $this->customerService->getTransactions($id);
        $toastSuccess = $_SESSION['toast_success'] ?? '';
        $toastError = $_SESSION['toast_error'] ?? '';
        unset($_SESSION['toast_success'], $_SESSION['toast_error']);

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/customers/profile.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    /**
     * Create customer
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=customers');
            exit;
        }

        $data = Validator::trimAll($_POST);
        $result = $this->customerService->create($data);

        if ($result['success']) {
            $_SESSION['toast_success'] = 'تم إضافة العميل بنجاح';
        } else {
            $_SESSION['toast_error'] = $result['message'] ?? implode(', ', $result['errors'] ?? ['خطأ في الحفظ']);
        }
        header('Location: ?page=customers');
        exit;
    }

    /**
     * Update customer
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=customers');
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        $data = Validator::trimAll($_POST);
        $result = $this->customerService->update($id, $data);

        if ($result['success']) {
            $_SESSION['toast_success'] = 'تم تحديث بيانات العميل';
        } else {
            $_SESSION['toast_error'] = $result['message'] ?? implode(', ', $result['errors'] ?? ['خطأ في التحديث']);
        }
        header('Location: ?page=customers&action=profile&id=' . $id);
        exit;
    }

    /**
     * Delete customer
     */
    public function delete() {
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['toast_error'] = 'معرف العميل غير صالح';
            header('Location: ?page=customers');
            exit;
        }

        $result = $this->customerService->delete($id);
        if ($result['success']) {
            $_SESSION['toast_success'] = 'تم حذف العميل';
        } else {
            $_SESSION['toast_error'] = $result['message'];
        }
        header('Location: ?page=customers');
        exit;
    }

    /**
     * Record payment
     */
    public function payment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=customers');
            exit;
        }

        $customerId = intval($_POST['customer_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if ($customerId <= 0) {
            $_SESSION['toast_error'] = 'معرف العميل غير صالح';
            header('Location: ?page=customers');
            exit;
        }

        if ($amount <= 0) {
            $_SESSION['toast_error'] = 'أدخل مبلغ صحيح';
            header('Location: ?page=customers&action=profile&id=' . $customerId);
            exit;
        }

        if ($amount > 999999.99) {
            $_SESSION['toast_error'] = 'المبلغ يتجاوز الحد المسموح';
            header('Location: ?page=customers&action=profile&id=' . $customerId);
            exit;
        }

        $result = $this->customerService->recordPayment($customerId, $amount, $description);
        if ($result['success']) {
            $_SESSION['toast_success'] = 'تم تسجيل المبلغ بنجاح';
        } else {
            $_SESSION['toast_error'] = $result['message'];
        }
        header('Location: ?page=customers&action=profile&id=' . $customerId);
        exit;
    }

    /**
     * API: Search customers
     */
    public function search() {
        $query = $_GET['q'] ?? '';
        $results = $this->customerService->search($query);
        Response::success($results);
    }

    /**
     * API: Get customer by ID
     */
    public function get() {
        $id = intval($_GET['id'] ?? 0);
        $customer = $this->customerService->findById($id);
        if ($customer) {
            Response::success($customer);
        } else {
            Response::error('العميل غير موجود');
        }
    }
}
