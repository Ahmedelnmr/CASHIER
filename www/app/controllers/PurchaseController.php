<?php
/**
 * Purchase Controller
 */

class PurchaseController {
    private $purchaseService;

    public function __construct() {
        $auth = new AuthService();
        if (!$auth->hasRole('manager')) {
            header('Location: ?page=dashboard');
            exit;
        }
        $this->purchaseService = new PurchaseService();
    }

    public function index() {
        $supplierId = $_GET['supplier_id'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $invoices = $this->purchaseService->getAll($supplierId, $dateFrom, $dateTo);
        $suppliers = (new SupplierService())->getAll();

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/purchases/index.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    public function create() {
        $suppliers = (new SupplierService())->getAll();
        $products = (new ProductService())->getAll();

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/purchases/form.php';
        include APP_PATH . '/views/layout/footer.php';
    }



    public function save() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            // Form submit fallback
            $input = $_POST;
        }

        $invoiceData = [
            'supplier_id' => $input['supplier_id'] ?? '',
            'invoice_number' => $input['invoice_number'] ?? '',
            'date' => $input['date'] ?? date('Y-m-d'),
            'discount' => $input['discount'] ?? 0,
            'notes' => $input['notes'] ?? '',
        ];

        $items = $input['items'] ?? [];

        if (!empty($input['id'])) {
            // Edit mode
            $result = $this->purchaseService->updatePurchase($input['id'], $invoiceData, $items);
        } else {
            // Create mode
            $result = $this->purchaseService->createInvoice($invoiceData, $items);
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) {
            if ($result['success']) {
                Response::success($result);
            } else {
                Response::error($result['message'] ?? 'خطأ');
            }
        } else {
            header('Location: ?page=purchases');
            exit;
        }
    }

    public function view() {
        $id = $_GET['id'] ?? 0;
        $invoice = $this->purchaseService->findById($id);
        if (!$invoice) {
            header('Location: ?page=purchases');
            exit;
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            Response::success($invoice);
        } else {
            include APP_PATH . '/views/layout/header.php';
            include APP_PATH . '/views/purchases/view.php';
            include APP_PATH . '/views/layout/footer.php';
        }
    }

    public function getLastInvoice() {
        if (!isset($_GET['supplier_id'])) {
            Response::json(['success' => false, 'message' => 'Supplier ID required']);
            return;
        }
        
        $items = $this->purchaseService->getLastInvoiceItems($_GET['supplier_id']);
        Response::json(['success' => true, 'data' => $items]);
    }

    public function next_invoice_number() {
        if (!isset($_GET['supplier_id'])) {
            Response::json(['success' => false, 'message' => 'Supplier ID required']);
            return;
        }
        
        $count = $this->purchaseService->getSupplierInvoiceCount($_GET['supplier_id']);
        Response::json(['success' => true, 'next_number' => $count + 1]);
    }

    /**
     * Void Purchase Invoice
     */
    public function void() {
        $id = $_GET['id'] ?? 0;
        $result = $this->purchaseService->voidInvoice($id);
        
        if ($result['success']) {
            header('Location: ?page=purchases&msg=voided');
        } else {
            header('Location: ?page=purchases&error=' . urlencode($result['message']));
        }
        exit;
    }

    /**
     * Show Return Form (Return to Supplier)
     */
    public function return_form() {
        $id = $_GET['id'] ?? 0;
        $invoice = $this->purchaseService->findById($id);
        
        if (!$invoice) {
            header('Location: ?page=purchases&error=' . urlencode('الفاتورة غير موجودة'));
            exit;
        }

        // Block return on voided purchase invoices
        if (isset($invoice['status']) && $invoice['status'] === 'voided') {
            header('Location: ?page=purchases&error=' . urlencode('لا يمكن تسجيل مرتجع على فاتورة ملغاة'));
            exit;
        }

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/purchases/return_form.php';
        include APP_PATH . '/views/layout/footer.php';
    }


    /**
     * Save Return
     */
    public function save_return() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             header('Location: ?page=purchases');
             exit;
        }

        $originalId = $_POST['original_invoice_id'];
        $items = json_decode($_POST['items_json'], true);

        if (empty($items)) {
             header('Location: ?page=purchases&error=' . urlencode('لا توجد أصناف'));
             exit;
        }

        $result = $this->purchaseService->createReturn($originalId, $items);

        if ($result['success']) {
            header('Location: ?page=purchases&msg=returned');
        } else {
            header('Location: ?page=purchases&action=return_form&id=' . $originalId . '&error=' . urlencode($result['message']));
        }
        exit;
    }

    /**
     * Edit Form - Load invoice into form for editing
     */
    public function edit_form() {
        $id = $_GET['id'] ?? 0;
        $invoice = $this->purchaseService->findById($id);
        if (!$invoice) {
            header('Location: ?page=purchases&error=' . urlencode('الفاتورة غير موجودة'));
            exit;
        }

        $suppliers = (new SupplierService())->getAll();
        $products = (new ProductService())->getAll();

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/purchases/form.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    /**
     * Delete Purchase Invoice (Physical Delete)
     */
    public function delete() {
        $id = $_GET['id'] ?? 0;
        $result = $this->purchaseService->deletePurchase($id);

        // Support AJAX calls (from edit mode)
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false)) {
            if ($result['success']) {
                Response::success($result);
            } else {
                Response::error($result['message'] ?? 'خطأ');
            }
            return;
        }

        if ($result['success']) {
            header('Location: ?page=purchases&msg=deleted');
        } else {
            header('Location: ?page=purchases&error=' . urlencode($result['message']));
        }
        exit;
    }
}
