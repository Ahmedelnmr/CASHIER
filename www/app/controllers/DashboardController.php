<?php
/**
 * Dashboard Controller
 */

class DashboardController {
    private $reportService;

    public function __construct() {
        $this->reportService = new ReportService();
    }

    public function index() {
        $summary = $this->reportService->dashboardSummary();
        $lowStock = $this->reportService->lowStock();
        $recentSales = (new SaleRepository())->getAll('', '', 5, 'sale', 'active');

        
        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/dashboard/index.php';
        include APP_PATH . '/views/layout/footer.php';
    }
}
