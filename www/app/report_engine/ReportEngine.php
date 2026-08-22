<?php
/**
 * Report Engine - Report Generation Logic
 */

class ReportEngine {
    private $reportService;

    public function __construct() {
        $this->reportService = new ReportService();
    }

    public function dailySales($date = null) {
        return $this->reportService->dailySales($date);
    }

    public function salesByRange($from, $to) {
        return $this->reportService->salesByRange($from, $to);
    }

    public function weeklySales() {
        $to = date('Y-m-d');
        $from = date('Y-m-d', strtotime('-7 days'));
        return $this->reportService->salesByRange($from, $to);
    }

    public function monthlySales() {
        $to = date('Y-m-d');
        $from = date('Y-m-01');
        return $this->reportService->salesByRange($from, $to);
    }

    public function semiAnnualSales() {
        $to = date('Y-m-d');
        $from = date('Y-m-d', strtotime('-6 months'));
        return $this->reportService->salesByRange($from, $to);
    }

    public function topProducts($limit = 20) {
        return $this->reportService->topProducts($limit);
    }

    public function leastProducts($limit = 20) {
        return $this->reportService->leastProducts($limit);
    }

    public function lowStock() {
        return $this->reportService->lowStock();
    }

    public function purchasesBySupplier() {
        return $this->reportService->purchasesBySupplier();
    }

    public function dashboardSummary() {
        return $this->reportService->dashboardSummary();
    }
}
