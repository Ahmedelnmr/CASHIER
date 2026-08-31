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

    /**
     * Validate and normalize a date string (Y-m-d format).
     * Returns the normalized date or false if invalid.
     */
    private static function validateDate($dateStr) {
        if (!is_string($dateStr) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return false;
        }
        $ts = strtotime($dateStr);
        if ($ts === false) return false;
        // Verify it round-trips (catches Feb 30, etc.)
        return date('Y-m-d', $ts) === $dateStr ? date('Y-m-d', $ts) : false;
    }

    /**
     * Validate a date range. Returns [from, to] or exits with error.
     */
    private function requireDateRange($fromKey = 'from', $toKey = 'to') {
        $from = $_GET[$fromKey] ?? '';
        $to = $_GET[$toKey] ?? '';

        $fromNorm = self::validateDate($from);
        $toNorm = self::validateDate($to);

        if (!$fromNorm || !$toNorm) {
            Response::validationError(['date' => 'الرجاء اختيار تاريخ صحيح بصيغة YYYY-MM-DD']);
            exit;
        }
        if ($fromNorm > $toNorm) {
            Response::validationError(['date' => 'تاريخ البداية لا يمكن أن يكون بعد تاريخ النهاية']);
            exit;
        }
        return [$fromNorm, $toNorm];
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
        $dateNorm = self::validateDate($date);
        if (!$dateNorm) {
            $dateNorm = date('Y-m-d');
        }
        $report = $this->reportService->dailySales($dateNorm);
        Response::success($report);
    }

    /**
     * API: Range sales report
     */
    public function range() {
        [$from, $to] = $this->requireDateRange();
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
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');

        $fromNorm = self::validateDate($from);
        $toNorm = self::validateDate($to);
        if (!$fromNorm) $fromNorm = date('Y-m-01');
        if (!$toNorm) $toNorm = date('Y-m-d');
        if ($fromNorm > $toNorm) {
            [$fromNorm, $toNorm] = [$toNorm, $fromNorm];
        }

        $data = $this->reportService->financialReport($fromNorm, $toNorm);
        
        Response::success($data);
    }

    /**
     * API: Sales by category
     */
    public function salesByCategory() {
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        if ($from) {
            $fromNorm = self::validateDate($from);
            $toNorm = $to ? self::validateDate($to) : $fromNorm;
            if (!$fromNorm) $fromNorm = null;
            if (!$toNorm) $toNorm = $fromNorm;
        } else {
            $fromNorm = null;
            $toNorm = null;
        }
        $data = $this->reportService->salesByCategory($fromNorm, $toNorm);
        Response::success($data);
    }

    /**
     * API: Product profit margin
     */
    public function productProfit() {
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        if ($from) {
            $fromNorm = self::validateDate($from);
            $toNorm = $to ? self::validateDate($to) : $fromNorm;
            if (!$fromNorm) $fromNorm = null;
            if (!$toNorm) $toNorm = $fromNorm;
        } else {
            $fromNorm = null;
            $toNorm = null;
        }
        $data = $this->reportService->productProfitMargin($fromNorm, $toNorm);
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
        if ($from) {
            $fromNorm = self::validateDate($from);
            $toNorm = $to ? self::validateDate($to) : $fromNorm;
            if (!$fromNorm) $fromNorm = null;
            if (!$toNorm) $toNorm = $fromNorm;
        } else {
            $fromNorm = null;
            $toNorm = null;
        }
        $data = $this->reportService->cashierPerformance($fromNorm, $toNorm);
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

        $fromNorm = self::validateDate($from) ?: date('Y-m-01');
        $toNorm = self::validateDate($to) ?: date('Y-m-d');
        if ($fromNorm > $toNorm) {
            [$fromNorm, $toNorm] = [$toNorm, $fromNorm];
        }

        $data = [];
        $filename = 'report_' . $type . '_' . date('Y-m-d') . '.csv';

        switch ($type) {
            case 'daily':
                $report = $this->reportService->dailySales($fromNorm);
                $data = $report['sales'];
                break;
            case 'weekly':
                $weekFrom = date('Y-m-d', strtotime('-7 days'));
                $weekTo = date('Y-m-d');
                $report = $this->reportService->salesByRange($weekFrom, $weekTo);
                $data = $report['daily'];
                break;
            case 'monthly':
                $monthFrom = date('Y-m-01');
                $monthTo = date('Y-m-d');
                $report = $this->reportService->salesByRange($monthFrom, $monthTo);
                $data = $report['daily'];
                break;
            case 'topProducts':
                $data = $this->reportService->topProducts(50, $fromNorm, $toNorm);
                break;
            case 'leastProducts':
                $data = $this->reportService->leastProducts(50);
                break;
            case 'lowStock':
                $data = $this->reportService->lowStock();
                break;
            case 'suppliers':
                $data = $this->reportService->purchasesBySupplier($fromNorm, $toNorm);
                break;
            case 'financial':
                $report = $this->reportService->financialReport($fromNorm, $toNorm);
                $data = $report['daily'];
                break;
            case 'category':
                $data = $this->reportService->salesByCategory($fromNorm, $toNorm);
                break;
            case 'profit':
                $data = $this->reportService->productProfitMargin($fromNorm, $toNorm, 50);
                break;
            case 'deadStock':
                $days = intval($_GET['days'] ?? 30);
                $data = $this->reportService->deadStock($days);
                break;
            case 'cashier':
                $data = $this->reportService->cashierPerformance($fromNorm, $toNorm);
                break;
            case 'debtors':
                $data = $this->reportService->customerDebtors();
                break;
            default:
                http_response_code(400);
                header('Content-Type: text/plain; charset=utf-8');
                echo 'نوع التقرير غير معروف';
                exit;
        }

        if (empty($data)) {
            http_response_code(204);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'لا توجد بيانات للتصدير';
            exit;
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
