<?php


class SafeController {
    
    private $db;
    
    public function __construct() {
        $auth = new AuthService();
        if (!$auth->hasRole('admin')) {
            header('Location: ?page=dashboard');
            exit;
        }
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Alias for view
    public function view() {
        $this->index();
    }
    
    // Dashboard & Transactions List
    public function index() {
        // Handle filter
        $month = $_GET['month'] ?? date('Y-m');
        $type = $_GET['type'] ?? '';
        
        $sql = "SELECT t.*, e.name as employee_name 
                FROM safe_transactions t 
                LEFT JOIN employees e ON t.employee_id = e.id 
                WHERE strftime('%Y-%m', t.date) = :month ";
                
        $params = [':month' => $month];
        
        if (!empty($type)) {
            $sql .= " AND t.type = :type";
            $params[':type'] = $type;
        }
        
        $sql .= " ORDER BY t.date DESC, t.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate Balance (Total)
        $balance = $this->calculateCurrentBalance();
        
        // Employees for dropdown
        $empStmt = $this->db->query("SELECT * FROM employees WHERE is_active = 1 ORDER BY name ASC");
        $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);
        
        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/safe/index.php';
        include APP_PATH . '/views/layout/footer.php';
    }
    
    // Get Current Balance
    private function calculateCurrentBalance() {
        // IN:  sales, deposits, openings, purchase voids
        // sale_void cancels a previous sale (reduces income)
        // OUT: purchases, expenses, withdrawals, salaries, advances, sale returns
        $sql = "SELECT 
                    SUM(CASE WHEN type IN ('deposit', 'opening', 'sale', 'purchase_void') THEN amount ELSE 0 END) as total_in,
                    SUM(CASE WHEN type IN ('sale_void', 'expense', 'withdrawal', 'advance', 'salary', 'salary_payment', 'purchase', 'sale_return') THEN amount ELSE 0 END) as total_out
                FROM safe_transactions";
                
        $row = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        return ($row['total_in'] ?? 0) - ($row['total_out'] ?? 0);
    }
    
    // Add Transaction
    public function store() {
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validation
            if (empty($data['amount']) || empty($data['type'])) {
                echo json_encode(['success' => false, 'message' => 'البيانات غير مكتملة']);
                return;
            }
            
            $type = $data['type'];
            $amount = floatval($data['amount']);
            $date = $data['date'] ?? date('Y-m-d');
            $desc = $data['description'] ?? '';
            $empId = !empty($data['employee_id']) ? $data['employee_id'] : null;
            
            // Logic Check for Advances and Salary
            if (($type === 'advance' || $type === 'salary') && empty($empId)) {
                echo json_encode(['success' => false, 'message' => 'يجب اختيار الموظف']);
                return;
            }
            
            // Record in Safe (always positive amount)
            $stmt = $this->db->prepare("INSERT INTO safe_transactions (type, amount, date, description, employee_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$type, abs($amount), $date, $desc, $empId]);
            
            echo json_encode(['success' => true, 'message' => 'تم تسجيل العملية بنجاح']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
        }
    }
    
    // Delete Transaction
    public function delete() {
        header('Content-Type: application/json');
        try {
            $id = $_GET['id'] ?? 0;
            $stmt = $this->db->prepare("DELETE FROM safe_transactions WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'تم حذف العملية']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
        }
    }
}
