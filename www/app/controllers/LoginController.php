<?php
/**
 * Login Controller - Authentication
 */

class LoginController {

    /**
     * Show login page
     */
    public function index() {
        // If already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            header('Location: ?page=dashboard');
            exit;
        }
        include APP_PATH . '/views/login/index.php';
    }

    /**
     * Process login attempt
     */
    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=login');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = 'أدخل اسم المستخدم وكلمة المرور';
            header('Location: ?page=login');
            exit;
        }

        $auth = new AuthService();
        $result = $auth->login($username, $password);

        if ($result['success']) {
            header('Location: ?page=dashboard');
            exit;
        } else {
            $_SESSION['login_error'] = $result['message'];
            header('Location: ?page=login');
            exit;
        }
    }

    /**
     * Logout
     */
    public function logout() {
        $auth = new AuthService();
        $auth->logout();
        header('Location: ?page=login');
        exit;
    }
}
