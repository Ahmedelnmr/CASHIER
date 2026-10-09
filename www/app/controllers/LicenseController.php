<?php
/**
 * LicenseController — صفحة التفعيل والترخيص
 * الوصول: ?page=license
 */
class LicenseController {

    public function index() {
        require_once APP_PATH . '/services/LicenseService.php';
        $licenseInfo = LicenseService::getLicenseInfo();
        
        require_once APP_PATH . '/views/license/index.php';
    }

    public function activate() {
        require_once APP_PATH . '/services/LicenseService.php';
        
        $code = trim($_POST['activation_code'] ?? '');
        
        if (empty($code)) {
            $_SESSION['license_error'] = 'يرجى إدخال كود التفعيل';
            header('Location: ?page=license');
            exit;
        }

        $result = LicenseService::activate($code);

        if ($result['success']) {
            $_SESSION['toast_success'] = $result['message'];
            header('Location: ?page=dashboard');
            exit;
        } else {
            $_SESSION['license_error'] = $result['message'];
            header('Location: ?page=license');
            exit;
        }
    }

    /**
     * API: إرجاع Hardware ID بصيغة JSON (للاستخدام في واجهة التفعيل)
     */
    public function getHardwareId() {
        require_once APP_PATH . '/services/LicenseService.php';
        require_once APP_PATH . '/helpers/Response.php';
        Response::success([
            'hardware_id' => LicenseService::getHardwareId(),
            'status'      => LicenseService::getLicenseStatus()
        ]);
    }
}
