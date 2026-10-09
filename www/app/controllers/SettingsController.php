<?php
/**
 * Settings Controller - System Configuration (Admin Only)
 */

class SettingsController {
    private $settingsService;

    public function __construct() {
        // Only admin can access settings
        $auth = new AuthService();
        if (!$auth->hasRole('admin')) {
            header('Location: ?page=dashboard');
            exit;
        }
        $this->settingsService = new SettingsService();
    }

    /**
     * Show settings page
     */
    public function index() {
        $settings = $this->settingsService->getAll();
        $licenseInfo = LicenseService::getLicenseInfo();
        $toastSuccess = $_SESSION['toast_success'] ?? '';
        $toastError = $_SESSION['toast_error'] ?? '';
        unset($_SESSION['toast_success'], $_SESSION['toast_error']);
        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/settings/index.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    /**
     * Save settings
     */
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=settings');
            exit;
        }

        $fields = ['store_name', 'store_phone', 'store_address', 'receipt_header', 'receipt_footer', 'currency_symbol', 'currency_name'];
        $settings = [];
        foreach ($fields as $field) {
            $settings[$field] = trim($_POST[$field] ?? '');
        }

        $this->settingsService->setMultiple($settings);

        $_SESSION['toast_success'] = 'تم حفظ الإعدادات بنجاح';
        header('Location: ?page=settings');
        exit;
    }
}
