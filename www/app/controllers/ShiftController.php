<?php
/**
 * Shift Controller - Cashier Shift Management
 */

class ShiftController {
    private $shiftService;

    public function __construct() {
        $this->shiftService = new ShiftService();
    }

    /**
     * Show shift management page
     */
    public function index() {
        $currentShift = $this->shiftService->getCurrentShift();
        $shifts = $this->shiftService->getShifts(30);
        $toastSuccess = $_SESSION['toast_success'] ?? '';
        $toastError = $_SESSION['toast_error'] ?? '';
        unset($_SESSION['toast_success'], $_SESSION['toast_error']);

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/shifts/index.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    /**
     * Open a new shift
     */
    public function open() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=shifts');
            exit;
        }

        $openingCash = floatval($_POST['opening_cash'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        $result = $this->shiftService->openShift($_SESSION['user_id'], $openingCash, $notes);

        if ($result['success']) {
            $_SESSION['current_shift_id'] = $result['shift_id'];
            $_SESSION['toast_success'] = 'تم فتح الوردية بنجاح';
        } else {
            $_SESSION['toast_error'] = $result['message'];
        }

        header('Location: ?page=shifts');
        exit;
    }

    /**
     * Close current shift
     */
    public function close() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=shifts');
            exit;
        }

        $shiftId = intval($_POST['shift_id'] ?? 0);
        $closingCash = floatval($_POST['closing_cash'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        $result = $this->shiftService->closeShift($shiftId, $closingCash, $notes);

        if ($result['success']) {
            unset($_SESSION['current_shift_id']);
            $_SESSION['toast_success'] = 'تم إغلاق الوردية';
            // Store report for display
            $_SESSION['shift_report'] = $result['report'];
        } else {
            $_SESSION['toast_error'] = $result['message'];
        }

        header('Location: ?page=shifts');
        exit;
    }

    /**
     * X-Report (interim)
     */
    public function xreport() {
        $shiftId = intval($_GET['id'] ?? 0);
        $report = $this->shiftService->xReport($shiftId);

        if (!$report) {
            $_SESSION['toast_error'] = 'الوردية غير موجودة';
            header('Location: ?page=shifts');
            exit;
        }

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/shifts/report.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    /**
     * Z-Report (final)
     */
    public function zreport() {
        $shiftId = intval($_GET['id'] ?? 0);
        $report = $this->shiftService->zReport($shiftId);

        if (!$report) {
            $_SESSION['toast_error'] = 'الوردية غير موجودة';
            header('Location: ?page=shifts');
            exit;
        }

        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/shifts/report.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    /**
     * API: Get current shift status
     */
    public function status() {
        $shift = $this->shiftService->getCurrentShift();
        Response::success($shift);
    }
}
