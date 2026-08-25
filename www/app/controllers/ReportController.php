<?php
/**
 * Report Controller
 */

class ReportController {
    private $reportService;

    public function __construct() {
        $auth = new AuthService();
        if (!$auth->hasRole('manager')) {
            header('Location: ?page=dashboard');
            exit;
        }
        $this->reportService = new ReportService();
    }

    public function index() {
        $summary = $this->reportService->dashboardSummary();

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/reports/index.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    /**
     * API: Daily sales report
     */
    public function daily() {
        $date = $_GET['date'] ?? date('Y-m-d');
        $report = $this->reportService->dailySales($date);
        Response::success($report);
    }

    /**
     * API: Range sales report
     */
    public function range() {
        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
        $to = $_GET['to'] ?? date('Y-m-d');
        $report = $this->reportService->salesByRange($from, $to);
        Response::success($report);
    }

    /**
     * API: Top products
     */
    public function topProducts() {
        $report = $this->reportService->topProducts(20);
        Response::success($report);
    }

    /**
     * API: Least products
     */
    public function leastProducts() {
        $report = $this->reportService->leastProducts(20);
        Response::success($report);
    }

    /**
     * API: Low stock
     */
    public function lowStock() {
        $report = $this->reportService->lowStock();
        Response::success($report);
    }

    /**
     * API: Purchases by supplier
     */
    public function supplierPurchases() {
        $report = $this->reportService->purchasesBySupplier();
        Response::success($report);
    }

    public function financial() {
        $from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
        $to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

        $data = $this->reportService->financialReport($from, $to);
        
        Response::success($data);
    }

    /**
     * API: Sales by category
     */
    public function salesByCategory() {
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $data = $this->reportService->salesByCategory($from, $to);
        Response::success($data);
    }

    /**
     * API: Product profit margin
     */
    public function productProfit() {
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $data = $this->reportService->productProfitMargin($from, $to);
        Response::success($data);
    }

    /**
     * API: Dead stock
     */
    public function deadStock() {
        $days = intval($_GET['days'] ?? 30);
        $data = $this->reportService->deadStock($days);
        Response::success($data);
    }

    /**
     * API: Cashier performance
     */
    public function cashierPerformance() {
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $data = $this->reportService->cashierPerformance($from, $to);
        Response::success($data);
    }

    /**
     * API: Customer debtors
     */
    public function debtors() {
        $data = $this->reportService->customerDebtors();
        Response::success($data);
    }

    /**
     * Export report as CSV
     */
    public function export() {
        $type = $_GET['type'] ?? '';
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');

        $data = [];
        $filename = 'report_' . $type . '_' . date('Y-m-d') . '.csv';

        switch ($type) {
            case 'daily':
                $report = $this->reportService->dailySales($from);
                $data = $report['sales'];
                break;
            case 'topProducts':
                $data = $this->reportService->topProducts(50, $from, $to);
                break;
            case 'category':
                $data = $this->reportService->salesByCategory($from, $to);
                break;
            case 'profit':
                $data = $this->reportService->productProfitMargin($from, $to, 50);
                break;
            case 'deadStock':
                $days = intval($_GET['days'] ?? 30);
                $data = $this->reportService->deadStock($days);
                break;
            case 'debtors':
                $data = $this->reportService->customerDebtors();
                break;
            default:
                Response::error('نوع التقرير غير معروف');
                return;
        }

        if (empty($data)) {
            Response::error('لا توجد بيانات للتصدير');
            return;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');

        $output = fopen('php://output', 'w');
        // BOM for UTF-8 Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header row
        fputcsv($output, array_keys($data[0]));

        // Data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
