<?php


class EmployeeController {
    
    private $db;
    
    public function __construct() {
        $auth = new AuthService();
        if (!$auth->hasRole('admin')) {
            header('Location: ?page=dashboard');
            exit;
        }
        $this->db = Database::getInstance()->getConnection();
    }
    
    // List All Employees
    public function index() {
        $employeeService = new EmployeeService();
        
        // Get Employees with Ledger Balance
        $employees = $employeeService->getAllWithBalance();
        
        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/employees/index.php';
        include APP_PATH . '/views/layout/footer.php';
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
            $data = Validator::trimAll($data);
            
            $validator = new Validator($data);
            $validator->name('name', 'الاسم', true);
            $validator->phone('phone', 'التليفون', false);
            $validator->numeric('base_salary', 'الراتب الأساسي');
            $validator->nonNegative('base_salary', 'الراتب الأساسي');
            $validator->max('base_salary', 999999.99, 'الراتب الأساسي');
            
            if ($validator->fails()) {
                $errors = $validator->errors();
                echo json_encode(['success' => false, 'message' => reset($errors), 'errors' => $errors]);
                return;
            }
            
            $stmt = $this->db->prepare("INSERT INTO employees (name, phone, base_salary) VALUES (?, ?, ?)");
            $stmt->execute([
                $data['name'],
                $data['phone'] ?? '',
                floatval($data['base_salary'] ?? 0)
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
            $data = Validator::trimAll($data);
            
            $validator = new Validator($data);
            $validator->required('id', 'معرف الموظف');
            $validator->numeric('id', 'معرف الموظف');
            $validator->name('name', 'الاسم', true);
            $validator->phone('phone', 'التليفون', false);
            $validator->numeric('base_salary', 'الراتب الأساسي');
            $validator->nonNegative('base_salary', 'الراتب الأساسي');
            $validator->max('base_salary', 999999.99, 'الراتب الأساسي');
            
            if ($validator->fails()) {
                $errors = $validator->errors();
                echo json_encode(['success' => false, 'message' => reset($errors), 'errors' => $errors]);
                return;
            }
            
            $stmt = $this->db->prepare("UPDATE employees SET name = ?, phone = ?, base_salary = ? WHERE id = ?");
            $stmt->execute([
                $data['name'],
                $data['phone'] ?? '',
                floatval($data['base_salary'] ?? 0),
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
            
            if (!is_numeric($id) || $id <= 0) {
                echo json_encode(['success' => false, 'message' => 'معرف الموظف غير صالح']);
                return;
            }
            
            // Check for outstanding debt before deletion
            $employeeService = new EmployeeService();
            $balance = $employeeService->getBalance($id);
            if ($balance < 0) {
                echo json_encode(['success' => false, 'message' => 'لا يمكن حذف الموظف لوجود مديونية قدره ' . number_format(abs($balance), 2) . ' جنيه. يرجى تسوية المديونية أولاً']);
                return;
            }
            
            $stmt = $this->db->prepare("UPDATE employees SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'تم حذف الموظف']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
        }
    }
    
    
}
