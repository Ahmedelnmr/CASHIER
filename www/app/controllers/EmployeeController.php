<?php


class EmployeeController {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // List All Employees
    public function index() {
        $employeeService = new EmployeeService();
        
        // Get Employees with Ledger Balance
        $employees = $employeeService->getAllWithBalance();
        
        include APP_PATH . '/views/employees/index.php';
    }
    
    // Get Employee History
    public function history() {
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            Response::json(['success' => false, 'message' => 'Employee ID required']);
            return;
        }
        
        $employeeService = new EmployeeService();
        $history = $employeeService->getHistory($id);
        
        Response::json(['success' => true, 'data' => $history]);
    }

    // Add New Employee
    public function store() {
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['name'])) {
                echo json_encode(['success' => false, 'message' => 'الاسم مطلوب']);
                return;
            }
            
            $stmt = $this->db->prepare("INSERT INTO employees (name, phone, base_salary) VALUES (?, ?, ?)");
            $stmt->execute([
                $data['name'],
                $data['phone'] ?? '',
                $data['base_salary'] ?? 0
            ]);
            
            echo json_encode(['success' => true, 'message' => 'تم إضافة الموظف بنجاح']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
        }
    }
    
    // Update Employee
    public function update() {
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id']) || empty($data['name'])) {
                echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة']);
                return;
            }
            
            $stmt = $this->db->prepare("UPDATE employees SET name = ?, phone = ?, base_salary = ? WHERE id = ?");
            $stmt->execute([
                $data['name'],
                $data['phone'] ?? '',
                $data['base_salary'] ?? 0,
                $data['id']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'تم تحديث البيانات']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
        }
    }
    
    // Delete (Soft Delete)
    public function delete() {
        header('Content-Type: application/json');
        
        try {
            $id = $_GET['id'] ?? 0;
            
            $stmt = $this->db->prepare("UPDATE employees SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'تم حذف الموظف']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
        }
    }
    
    
}
