<?php
/**
 * Test script to capture raw output from the POS API - listProducts
 */

ob_start();

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/?page=pos&action=listProducts';
$_GET['page'] = 'pos';
$_GET['action'] = 'listProducts';
$_GET['category'] = '';
$_GET['per_page'] = '20';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_test.log');

date_default_timezone_set('Africa/Cairo');

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

define('BASE_PATH', __DIR__);
define('APP_PATH', __DIR__ . '/app');

require_once APP_PATH . '/config/database.php';
require_once APP_PATH . '/helpers/Response.php';
require_once APP_PATH . '/helpers/Validator.php';
require_once APP_PATH . '/services/AuthService.php';

$db = Database::getInstance();
$db->migrate();

try {
    require_once APP_PATH . '/repositories/EmployeeRepository.php';
    require_once APP_PATH . '/services/EmployeeService.php';
    $empService = new EmployeeService();
    $empService->runMonthlyAccrual();
} catch (Exception $e) {
    // Silent
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$page = 'pos';
$action = 'listProducts';

$routes = [
    'pos' => 'POSController',
];

if (isset($routes[$page])) {
    $controllerName = $routes[$page];
    $controllerFile = APP_PATH . "/controllers/{$controllerName}.php";
    
    if (file_exists($controllerFile)) {
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
        
        require_once $controllerFile;
        
        $controller = new $controllerName();
        
        if (method_exists($controller, $action)) {
            $controller->$action();
        } else {
            $controller->index();
        }
    }
}

echo "\n=== NO RESPONSE SENT ===\n";
