<?php
ob_start();
/**
 * POS System - Main Entry Point & Router
 * Routes requests to appropriate controllers
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting — log to file only (display OFF to prevent PHPDesktop header overflow)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Set timezone
date_default_timezone_set('Africa/Cairo');

// Set encoding
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}
header('Content-Type: text/html; charset=utf-8');

// Base path constant
define('BASE_PATH', __DIR__);
define('APP_PATH', __DIR__ . '/app');

// Autoload helpers
require_once APP_PATH . '/config/database.php';
require_once APP_PATH . '/helpers/Response.php';
require_once APP_PATH . '/helpers/Validator.php';
require_once APP_PATH . '/services/AuthService.php';
require_once APP_PATH . '/services/LicenseService.php';

// Initialize database & run migrations (once per session)
$db = Database::getInstance();
if (empty($_SESSION['_db_migrated'])) {
    $db->migrate();
    $_SESSION['_db_migrated'] = true;
}

// Auto-accrue employee salaries for current month (runs once per calendar month)
$currentMonth = date('Y-m');
if (empty($_SESSION['_accrual_month']) || $_SESSION['_accrual_month'] !== $currentMonth) {
    try {
        require_once APP_PATH . '/repositories/EmployeeRepository.php';
        require_once APP_PATH . '/services/EmployeeService.php';
        $empService = new EmployeeService();
        $empService->runMonthlyAccrual();
        $_SESSION['_accrual_month'] = $currentMonth;
    } catch (Exception $e) {
        // Silent — don't block app if accrual fails
    }
}

// Determine the requested page and action
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

// Safeguard: If $page was overwritten by a numeric query param (e.g. pagination ?page=pos&...&page=1)
if (is_numeric($page) && !empty($_SERVER['QUERY_STRING'])) {
    if (preg_match('/(?:^|&)page=([a-zA-Z_]+)/', $_SERVER['QUERY_STRING'], $m)) {
        $page = $m[1];
    }
}

// =====================================================
// LICENSE CHECK — يجب التفعيل قبل أي شيء آخر
// =====================================================
$licensePublicPages = ['license'];
if (!in_array($page, $licensePublicPages)) {
    if (!LicenseService::isLicensed()) {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            Response::error('البرنامج غير مفعّل على هذا الجهاز — تواصل مع المطوّر', 403);
        }
        header('Location: ?page=license');
        exit;
    }
}

// =====================================================
// AUTHENTICATION CHECK
// =====================================================
$publicPages = ['login', 'license'];
$isPublicPage = in_array($page, $publicPages);

if (!$isPublicPage) {
    $auth = new AuthService();
    if (!$auth->isLoggedIn()) {
        // For AJAX/API requests, return JSON error instead of HTML redirect
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            Response::error('غير مصرح - يرجى تسجيل الدخول', 401);
        }
        header('Location: ?page=login');
        exit;
    }
}

// Route map
$routes = [
    'login'      => 'LoginController',
    'license'    => 'LicenseController',
    'dashboard'  => 'DashboardController',
    'pos'        => 'POSController',
    'products'   => 'ProductController',
    'suppliers'  => 'SupplierController',
    'purchases'  => 'PurchaseController',
    'inventory'  => 'InventoryController',
    'sales'      => 'SaleController',
    'reports'    => 'ReportController',
    'employees'  => 'EmployeeController',
    'safe'       => 'SafeController',
    'users'      => 'UsersController',
    'settings'   => 'SettingsController',
    'backup'     => 'BackupController',
    'shifts'     => 'ShiftController',
    'customers'  => 'CustomerController',
    'categories' => 'CategoryController',
    'barcode'    => 'BarcodeController',
];

// Load required files based on route
if (isset($routes[$page])) {
    $controllerName = $routes[$page];
    $controllerFile = APP_PATH . "/controllers/{$controllerName}.php";
    
    if (file_exists($controllerFile)) {
        // Autoload classes dynamically
        spl_autoload_register(function ($class) {
            $directories = [
                '/repositories/',
                '/services/',
                '/pos_engine/',
                '/inventory_engine/',
                '/report_engine/'
            ];
            foreach ($directories as $dir) {
                $file = APP_PATH . $dir . $class . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    return;
                }
            }
        });
        
        // Load controller
        require_once $controllerFile;
        
        $controller = new $controllerName();
        
        // Check if action method exists
        if (method_exists($controller, $action)) {
            $controller->$action();
        } else {
            // Default to index
            $controller->index();
        }
    } else {
        http_response_code(404);
        echo '<h1>Controller not found</h1>';
    }
} else {
    http_response_code(404);
    echo '<h1>Page not found</h1>';
}
