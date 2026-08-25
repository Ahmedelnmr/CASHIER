<?php
/**
 * Users Controller - Admin Only
 */

class UsersController {
    private $auth;

    public function __construct() {
        $this->auth = new AuthService();
        // Only admin can manage users
        if (!$this->auth->hasRole('admin')) {
            header('Location: ?page=dashboard');
            exit;
        }
    }

    /**
     * List all users
     */
    public function index() {
        $users = $this->auth->getAllUsers();
        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/users/index.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    /**
     * Create new user
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=users');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $role = $_POST['role'] ?? 'cashier';

        if (empty($username) || empty($password) || empty($name)) {
            $_SESSION['toast_error'] = 'جميع الحقول مطلوبة';
            header('Location: ?page=users');
            exit;
        }

        $result = $this->auth->createUser($username, $password, $name, $role);

        if ($result['success']) {
            $_SESSION['toast_success'] = 'تم إضافة المستخدم بنجاح';
        } else {
            $_SESSION['toast_error'] = $result['message'];
        }

        header('Location: ?page=users');
        exit;
    }

    /**
     * Update user
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=users');
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $role = $_POST['role'] ?? 'cashier';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $this->auth->updateUser($id, $name, $role, $isActive);
        $_SESSION['toast_success'] = 'تم تحديث المستخدم';
        header('Location: ?page=users');
        exit;
    }

    /**
     * Change password
     */
    public function changePassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=users');
            exit;
        }

        $id = intval($_POST['user_id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($newPassword) || strlen($newPassword) < 4) {
            $_SESSION['toast_error'] = 'كلمة المرور قصيرة جداً';
            header('Location: ?page=users');
            exit;
        }

        $this->auth->changePassword($id, $newPassword);
        $_SESSION['toast_success'] = 'تم تغيير كلمة المرور';
        header('Location: ?page=users');
        exit;
    }

    /**
     * Delete (deactivate) user
     */
    public function delete() {
        $id = intval($_GET['id'] ?? 0);

        if ($id == $_SESSION['user_id']) {
            $_SESSION['toast_error'] = 'لا يمكنك حذف نفسك';
            header('Location: ?page=users');
            exit;
        }

        $result = $this->auth->deleteUser($id);

        if ($result['success']) {
            $_SESSION['toast_success'] = 'تم حذف المستخدم';
        } else {
            $_SESSION['toast_error'] = $result['message'];
        }

        header('Location: ?page=users');
        exit;
    }
}
