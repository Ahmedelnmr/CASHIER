<?php
/**
 * Sale Controller
 */

class SaleController {
    private $saleService;

    public function __construct() {
        $this->saleService = new SaleService();
    }

    public function index() {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        // Validate dates
        $fromTs = strtotime($dateFrom);
        $toTs = strtotime($dateTo);
        if ($fromTs === false || $toTs === false) {
            $dateFrom = date('Y-m-d');
            $dateTo = date('Y-m-d');
        } elseif ($fromTs > $toTs) {
            // Swap inverted dates
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $sales = $this->saleService->getAll($dateFrom, $dateTo);
        $todaySummary = $this->saleService->getTodaySummary();

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/sales/index.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    public function view() {
        $id = $_GET['id'] ?? 0;
        $sale = $this->saleService->findById($id);
        if (!$sale) {
            header('Location: ?page=sales');
            exit;
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            Response::success($sale);
        }

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/sales/receipt.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    /**
     * API: Get sale details
     */
    /**
     * API: Get sale details
     */
    public function get() {
        $id = $_GET['id'] ?? 0;
        $sale = $this->saleService->findById($id);
        if ($sale) {
            Response::success($sale);
        } else {
            Response::error('الفاتورة غير موجودة');
        }
    }

    /**
     * API: Search by invoice number
     */
    public function search() {
        $q = trim($_GET['q'] ?? '');
        if ($q === '') {
            Response::error('أدخل رقم الفاتورة');
            return;
        }

        // Try exact invoice number lookup first
        if (InvoiceNumberGenerator::isValidFormat($q)) {
            $sale = $this->saleService->findByInvoiceNumber($q);
            if ($sale) {
                Response::success($sale);
                return;
            }
        }

        // Fallback: search by invoice_number LIKE pattern
        Response::error('الفاتورة غير موجودة');
    }

    /**
     * View sale by invoice number (redirects to view)
     */
    public function view_by_invoice() {
        $invoice = trim($_GET['invoice'] ?? '');
        if ($invoice === '') {
            header('Location: ?page=sales');
            exit;
        }

        $sale = $this->saleService->findByInvoiceNumber($invoice);
        if ($sale) {
            header('Location: ?page=sales&action=view&id=' . $sale['id']);
            exit;
        }

        header('Location: ?page=sales&error=' . urlencode('الفاتورة غير موجودة: ' . $invoice));
        exit;
    }

    /**
     * Delete and restock sale
     */
    public function delete() {
        $id = $_GET['id'] ?? 0;
        $result = $this->saleService->deleteSale($id, 'User Requested Delete');
        
        if ($result['success']) {
            header('Location: ?page=sales&msg=deleted');
        } else {
            header('Location: ?page=sales&error=' . urlencode($result['message']));
        }
        exit;
    }

    /**
     * Show Edit Form
     */
    public function edit_form() {
        $id = $_GET['id'] ?? 0;
        $sale = $this->saleService->findById($id);
        
        if (!$sale) {
            header('Location: ?page=sales&error=' . urlencode('الفاتورة غير موجودة'));
            exit;
        }

        // Get all products for the datalist
        $productRepo = new ProductRepository();
        $products = $productRepo->getAll();

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/sales/edit.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    /**
     * Handle Update Request
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             header('Location: ?page=sales');
             exit;
        }

        $id = $_POST['sale_id'];
        $items = json_decode($_POST['items_json'], true);
        $total = floatval($_POST['total']);
        $discount = floatval($_POST['discount']);
        $reason = $_POST['reason'] ?? 'Manual Edit';


        if (empty($items)) {
             header('Location: ?page=sales&action=edit_form&id=' . $id . '&error=' . urlencode('لا توجد أصناف'));
             exit;
        }

        $result = $this->saleService->updateSale($id, $items, $total, $discount, $reason);


        if ($result['success']) {
            header('Location: ?page=sales&msg=updated');
        } else {
            header('Location: ?page=sales&action=edit_form&id=' . $id . '&error=' . urlencode($result['message']));
        }
        exit;
    } // End update

    /**
     * Handle Update Request -> Now Edit as New
     * Kept for backward compatibility or direct edits if needed, 
     * but we will mostly use edit_as_new
     */
    public function update_as_new_deprecated() { // Renamed to avoid redeclaration
        // ... (existing implementation or deprecated)
        // We can just redirect to edit_as_new if we want to enforce the new flow
        // But let's leave valid update logic if it exists, or just use edit_as_new
        $this->edit_as_new();
    }

    /**
     * Edit as New (Void original -> Load into POS)
     */
    public function edit_as_new() {
        $id = $_GET['id'] ?? $_POST['sale_id'] ?? 0;
        $sale = $this->saleService->findById($id);
        
        if (!$sale || $sale['status'] === 'voided') {
             header('Location: ?page=sales&error=' . urlencode('الفاتورة غير متاحة للتعديل'));
             exit;
        }

        // 1. Void the original sale
        $result = $this->saleService->voidSale($id, 'تعديل الفاتورة');
        if (!$result['success']) {
             header('Location: ?page=sales&error=' . urlencode($result['message']));
             exit;
        }

        // 2. Prepare Items for POS
        // Format: {id, name, price, qty, type, ...}
        $cartItems = [];
        foreach ($sale['items'] as $item) {
            $cartItems[] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'unit_type' => $item['unit_type'],
                // We need to fetch product to be safe? 
                // Or just trust sale record. Trust record is okay for re-edit.
                // But POS needs barcode to find product? 
                // POSEngine mostly uses ID or Barcode.
                // POS JS cart uses: id, name, price, qty.
            ];
        }

        // 3. Inject JS to save to localStorage and Redirect
        // We render a temporary view to handle the frontend handover
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>جاري التحويل...</title>
        </head>
        <body>
            <script>
                // format items for POS cart
                var items = <?= json_encode($cartItems) ?>;
                var cart = [];
                items.forEach(function(item) {
                    cart.push({
                        id: item.product_id,
                        name: item.product_name,
                        price: item.price,
                        qty: item.quantity,
                        type: 'unit' // Default
                    });
                });
                
                localStorage.setItem('pos_cart', JSON.stringify(cart));
                localStorage.setItem('pos_edit_mode', '1');
                window.location.href = '?page=pos';
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Void Sale
     */
    public function void() {
        $id = $_GET['id'] ?? 0;
        $result = $this->saleService->voidSale($id);
        
        if ($result['success']) {
            header('Location: ?page=sales&msg=voided');
        } else {
            header('Location: ?page=sales&error=' . urlencode($result['message']));
        }
        exit;
    }

    /**
     * Show Return Form
     */
    public function return_form() {
        $id = $_GET['id'] ?? 0;
        $sale = $this->saleService->findById($id);
        
        if (!$sale) {
            header('Location: ?page=sales&error=' . urlencode('الفاتورة غير موجودة'));
            exit;
        }

        // Block return on voided or already-returned invoices
        if ($sale['status'] === 'voided' || $sale['type'] !== 'sale') {
            header('Location: ?page=sales&error=' . urlencode('لا يمكن تسجيل مرتجع على فاتورة ملغاة أو مرتجعة'));
            exit;
        }

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/sales/return_form.php';
        include APP_PATH . '/views/layout/footer.php';
    }


    /**
     * Save Return
     */
    public function save_return() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             header('Location: ?page=sales');
             exit;
        }

        $originalId = $_POST['original_sale_id'];
        $items = json_decode($_POST['items_json'], true);

        if (empty($items)) {
             header('Location: ?page=sales&error=' . urlencode('لا توجد أصناف'));
             exit;
        }

        $result = $this->saleService->createReturn($originalId, $items);

        if ($result['success']) {
            header('Location: ?page=sales&msg=returned');
        } else {
            header('Location: ?page=sales&action=return_form&id=' . $originalId . '&error=' . urlencode($result['message']));
        }
        exit;
    } // End save_return
} // End Class
