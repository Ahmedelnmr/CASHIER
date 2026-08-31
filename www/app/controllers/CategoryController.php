<?php
/**
 * Category Controller - CRUD for product categories
 */

class CategoryController {
    private $db;
    
    public function __construct() {
        $auth = new AuthService();
        if (!$auth->hasRole('admin')) {
            header('Location: ?page=dashboard');
            exit;
        }
        $this->db = Database::getInstance()->getConnection();
    }
    
    // List all categories
    public function index() {
        $stmt = $this->db->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/categories/index.php';
        include APP_PATH . '/views/layout/footer.php';
    }
    
    // Create category
    public function store() {
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $name = trim($data['name'] ?? '');
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'اسم القسم مطلوب']);
                return;
            }
            
            // Check for duplicate
            $check = $this->db->prepare("SELECT id FROM categories WHERE name = ?");
            $check->execute([$name]);
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'هذا القسم موجود بالفعل']);
                return;
            }
            
            $stmt = $this->db->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            
            echo json_encode(['success' => true, 'message' => 'تم إضافة القسم بنجاح', 'id' => $this->db->lastInsertId()]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
        }
    }
    
    // Update category
    public function update() {
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id'] ?? 0);
            $name = trim($data['name'] ?? '');
            
            if (empty($id) || empty($name)) {
                echo json_encode(['success' => false, 'message' => 'البيانات غير مكتملة']);
                return;
            }
            
            // Check for duplicate (excluding current)
            $check = $this->db->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
            $check->execute([$name, $id]);
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'هذا القسم موجود بالفعل']);
                return;
            }
            
            $stmt = $this->db->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            
            echo json_encode(['success' => true, 'message' => 'تم تحديث القسم بنجاح']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
        }
    }
    
    // Delete category
    public function delete() {
        header('Content-Type: application/json');
        
        try {
            $id = intval($_GET['id'] ?? 0);
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'معرف القسم مطلوب']);
                return;
            }
            
            // Check if category has products
            $check = $this->db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $check->execute([$id]);
            $productCount = $check->fetchColumn();
            
            if ($productCount > 0) {
                echo json_encode(['success' => false, 'message' => 'لا يمكن حذف هذا القسم لاحتوائه على ' . $productCount . ' منتج. قم بتعديل المنتجات أولاً']);
                return;
            }
            
            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'تم حذف القسم بنجاح']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
        }
    }
    
    // API: Get all categories (for dropdowns)
    public function getAll() {
        header('Content-Type: application/json');
        $stmt = $this->db->query("SELECT id, name FROM categories ORDER BY name");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
}
