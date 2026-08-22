<?php
/**
 * Supplier Controller
 */

class SupplierController {
    private $supplierService;

    public function __construct() {
        $this->supplierService = new SupplierService();
    }

    public function index() {
        $search = $_GET['search'] ?? '';
        $suppliers = $this->supplierService->getAll($search);

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/suppliers/index.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    public function create() {
        $supplier = null;
        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/suppliers/form.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    public function edit() {
        $id = $_GET['id'] ?? 0;
        $supplier = $this->supplierService->findById($id);
        if (!$supplier) {
            header('Location: ?page=suppliers');
            exit;
        }

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/suppliers/form.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    public function save() {
        $data = $_POST;
        $id = $data['id'] ?? '';

        if ($id) {
            $result = $this->supplierService->update($id, $data);
        } else {
            $result = $this->supplierService->create($data);
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            if ($result['success']) {
                Response::success($result);
            } else {
                Response::error($result['errors'] ?? 'خطأ');
            }
        } else {
            if ($result['success']) {
                header('Location: ?page=suppliers&msg=saved');
            } else {
                $errorMsg = is_array($result['errors'] ?? null) ? implode(', ', $result['errors']) : ($result['message'] ?? 'خطأ في الحفظ');
                header('Location: ?page=suppliers&action=' . ($id ? 'edit&id='.$id : 'create') . '&error=' . urlencode($errorMsg));
            }
            exit;
        }
    }

    public function delete() {
        $id = $_GET['id'] ?? $_POST['id'] ?? 0;
        $this->supplierService->delete($id);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            Response::success(null, 'تم الحذف');
        } else {
            header('Location: ?page=suppliers');
            exit;
        }
    }

    /**
     * Supplier profile with purchase history
     */
    public function profile() {
        $id = $_GET['id'] ?? 0;
        $supplier = $this->supplierService->findById($id);
        if (!$supplier) {
            header('Location: ?page=suppliers');
            exit;
        }

        $purchaseService = new PurchaseService();
        $purchases = $purchaseService->getAll($id);

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/suppliers/profile.php';
        include APP_PATH . '/views/layout/footer.php';
    }
}
