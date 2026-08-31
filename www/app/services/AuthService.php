<?php
/**
 * Authentication Service - Login/Logout/Session Management
 */

class AuthService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Authenticate user by username and password
     */
    public function login($username, $password) {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE username = :username AND is_active = 1"
        );
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'اسم المستخدم غير موجود'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'كلمة المرور غير صحيحة'];
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'],
                'role' => $user['role'],
            ]
        ];
    }

    /**
     * Logout current user
     */
    public function logout() {
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) return null;
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role'],
        ];
    }

    /**
     * Check if current user has required role
     */
    public function hasRole($requiredRole) {
        if (!$this->isLoggedIn()) return false;
        $hierarchy = ['cashier' => 1, 'manager' => 2, 'admin' => 3];
        $userLevel = $hierarchy[$_SESSION['user_role']] ?? 0;
        $requiredLevel = $hierarchy[$requiredRole] ?? 0;
        return $userLevel >= $requiredLevel;
    }

    /**
     * Get all users (admin only)
     */
    public function getAllUsers() {
        $stmt = $this->db->query("SELECT id, username, name, role, is_active, created_at FROM users WHERE is_active = 1 ORDER BY id");
        return $stmt->fetchAll();
    }

    /**
     * Create new user
     */
    public function createUser($username, $password, $name, $role = 'cashier') {
        // Check duplicate username
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'اسم المستخدم موجود بالفعل'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password_hash, name, role) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$username, $hash, $name, $role]);

        return ['success' => true, 'id' => $this->db->lastInsertId()];
    }

    /**
     * Update user
     */
    public function updateUser($id, $name, $role, $isActive) {
        $stmt = $this->db->prepare(
            "UPDATE users SET name = ?, role = ?, is_active = ? WHERE id = ?"
        );
        $stmt->execute([$name, $role, $isActive, $id]);
        return ['success' => true];
    }

    /**
     * Change user password
     */
    public function changePassword($id, $newPassword) {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $id]);
        return ['success' => true];
    }

    /**
     * Delete user (soft delete)
     */
    public function deleteUser($id) {
        // Don't delete the last admin
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if ($user && $user['role'] === 'admin') {
            $adminCount = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn();
            if ($adminCount <= 1) {
                return ['success' => false, 'message' => 'لا يمكن حذف آخر مدير في النظام'];
            }
        }

        $stmt = $this->db->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        return ['success' => true];
    }
}
