<?php
/**
 * Report Controller
 */

class ReportController {
    private $reportService;

    public function __construct() {
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
}
