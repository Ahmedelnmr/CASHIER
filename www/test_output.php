<?php
/**
 * Test script to capture raw output from the POS API
 * Simulates what the browser would receive
 */

// Start output buffering like index.php does
ob_start();

// Capture ALL output
echo "=== TEST START ===\n";

// Simulate the request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/?page=pos&action=checkShift';
$_GET['page'] = 'pos';
$_GET['action'] = 'checkShift';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_test.log');

// Set timezone
date_default_timezone_set('Africa/Cairo');

// Set encoding
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// Base path constant
define('BASE_PATH', __DIR__);
define('APP_PATH', __DIR__ . '/app');

// Autoload helpers
require_once APP_PATH . '/config/database.php';
require_once APP_PATH . '/helpers/Response.php';
require_once APP_PATH . '/helpers/Validator.php';
require_once APP_PATH . '/services/AuthService.php';

// Initialize database & run migrations
$db = Database::getInstance();
$db->migrate();

// Auto-accrue employee salaries
try {
    require_once APP_PATH . '/repositories/EmployeeRepository.php';
    require_once APP_PATH . '/services/EmployeeService.php';
    $empService = new EmployeeService();
    $empService->runMonthlyAccrual();
} catch (Exception $e) {
    // Silent
}

// Check what's in the buffer so far
$buffered = ob_get_contents();
echo "=== BUFFER CONTENTS AFTER INIT ===\n";
echo $buffered;
echo "=== END BUFFER CONTENTS ===\n";

// Now simulate the controller call
// First, let's check if we have a session user_id
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Force admin for testing
}

// Load the POS controller
$page = 'pos';
$action = 'checkShift';

$routes = [
    'pos' => 'POSController',
];

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
            $controller->index();
        }
    }
}

// If we get here, the controller didn't call exit (which Response::json does)
echo "\n=== NO RESPONSE SENT ===\n";
echo "Buffer level: " . ob_get_level() . "\n";
echo "Buffer contents: " . ob_get_contents() . "\n";
