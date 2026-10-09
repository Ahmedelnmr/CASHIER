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
        
        // Build date range from month string (e.g. "2026-09" → "2026-09-01" … "2026-09-30")
        $dateFrom = $month . '-01';
        $dateTo   = date('Y-m-t', strtotime($dateFrom)); // last day of month
        
        $sql = "SELECT t.*, e.name as employee_name 
                FROM safe_transactions t 
                LEFT JOIN employees e ON t.employee_id = e.id 
                WHERE date(t.date) BETWEEN :date_from AND :date_to ";
                
        $params = [':date_from' => $dateFrom, ':date_to' => $dateTo];
        
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
        // IN:  sales, deposits, openings, purchase voids, debt settlements, funding
        // OUT: purchases, expenses, withdrawals, salaries, advances, sale returns
        // Reversal entries (transaction_type='reversal') flip IN/OUT to cancel the original.
        // Reversed originals are counted normally — the reversal entry provides the offset.
        $inTypes = "('deposit', 'opening', 'sale', 'purchase_void', 'debt_settlement', 'funding')";
        $outTypes = "('sale_void', 'expense', 'withdrawal', 'advance', 'salary', 'salary_payment', 'purchase', 'sale_return')";
        $sql = "SELECT 
                    SUM(CASE 
                        WHEN transaction_type = 'reversal' AND type IN $outTypes THEN amount
                        WHEN transaction_type != 'reversal' AND type IN $inTypes THEN amount
                        ELSE 0 
                    END) as total_in,
                    SUM(CASE 
                        WHEN transaction_type = 'reversal' AND type IN $inTypes THEN amount
                        WHEN transaction_type != 'reversal' AND type IN $outTypes THEN amount
                        ELSE 0 
                    END) as total_out
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
            if (($type === 'advance' || $type === 'salary' || $type === 'salary_payment') && empty($empId)) {
                echo json_encode(['success' => false, 'message' => 'يجب اختيار الموظف']);
                return;
            }
            
            // Salary payment validation - check net payable
            if ($type === 'salary' || $type === 'salary_payment') {
                $employeeService = new EmployeeService();
                $balance = $employeeService->getBalance($empId);
                // balance > 0 means company owes employee (accruals - paid - advances)
                if ($balance <= 0) {
                    echo json_encode(['success' => false, 'message' => 'لا يوجد مستحقات لهذا الموظف. الرصيد الحالي: ' . number_format($balance, 2)]);
                    return;
                }
                if ($amount > $balance) {
                    echo json_encode(['success' => false, 'message' => 'المبلغ المطلوب (' . number_format($amount, 2) . ') يتجاوز المستحق (' . number_format($balance, 2) . '). المستحق = إجمالي الاستحقاقات - الرواتب المدفوعة - السلف. إذا كنت تريد دفع مبلغ إضافي، استخدم نوع "مكافأة" أو "دفع آخر"']);
                    return;
                }
            }
            
            // Debt settlement validation
            if ($type === 'debt_settlement') {
                if (empty($empId)) {
                    echo json_encode(['success' => false, 'message' => 'يجب اختيار الموظف لتسوية المديونية']);
                    return;
                }
                $employeeService = new EmployeeService();
                $balance = $employeeService->getBalance($empId);
                // balance < 0 means employee owes company
                if ($balance >= 0) {
                    echo json_encode(['success' => false, 'message' => 'لا توجد مديونية لهذا الموظف']);
                    return;
                }
                $maxSettlement = abs($balance);
                if ($amount > $maxSettlement) {
                    echo json_encode(['success' => false, 'message' => 'المبلغ المطلوب (' . number_format($amount, 2) . ') يتجاوز المديونية (' . number_format($maxSettlement, 2) . ')']);
                    return;
                }
            }
            
            // Record in Safe (always positive amount)
            $stmt = $this->db->prepare("INSERT INTO safe_transactions (type, amount, date, description, employee_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$type, abs($amount), $date, $desc, $empId]);
            
            echo json_encode(['success' => true, 'message' => 'تم تسجيل العملية بنجاح']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
        }
    }
    
    // Reverse Transaction
    public function reverse() {
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id'] ?? 0);
            $reason = trim($data['reason'] ?? '');
            
            // Validate reason
            if (empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'يجب إدخال سبب العكس']);
                return;
            }
            
            // Get original transaction
            $stmt = $this->db->prepare("SELECT * FROM safe_transactions WHERE id = ?");
            $stmt->execute([$id]);
            $original = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$original) {
                echo json_encode(['success' => false, 'message' => 'العملية غير موجودة']);
                return;
            }
            
            // Check if already reversed
            if ($original['status'] === 'reversed') {
                echo json_encode(['success' => false, 'message' => 'تم عكس هذه العملية بالفعل']);
                return;
            }
            
            // Get current user
            $currentUser = $_SESSION['user_name'] ?? 'غير معروف';
            
            // Begin transaction
            $this->db->beginTransaction();
            
            // Create reversal transaction (same amount, tagged as reversal)
            $reversalStmt = $this->db->prepare(
                "INSERT INTO safe_transactions (type, amount, date, description, employee_id, related_transaction_id, transaction_type, reversal_reason, reversed_by, reversed_at, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'reversal', ?, ?, datetime('now', 'localtime'), 'active')"
            );
            $reversalStmt->execute([
                $original['type'],
                $original['amount'],
                date('Y-m-d'),
                'عكس العملية #' . $id . ' - ' . $reason,
                $original['employee_id'],
                $id,
                $reason,
                $currentUser
            ]);
            
            $reversalId = $this->db->lastInsertId();
            
            // Mark original as reversed
            $updateStmt = $this->db->prepare(
                "UPDATE safe_transactions SET status = 'reversed', reversed_by = ?, reversed_at = datetime('now', 'localtime') WHERE id = ?"
            );
            $updateStmt->execute([$currentUser, $id]);
            
            $this->db->commit();
            
            echo json_encode(['success' => true, 'message' => 'تم عكس العملية بنجاح', 'reversal_id' => $reversalId]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
        }
    }
    
    // Cash Movement Report
    public function report() {
        header('Content-Type: application/json');
        
        try {
            $from = $_GET['from'] ?? date('Y-m-01');
            $to = $_GET['to'] ?? date('Y-m-d');
            
            // Get transactions for the period
            $stmt = $this->db->prepare(
                "SELECT t.*, e.name as employee_name
                 FROM safe_transactions t
                 LEFT JOIN employees e ON t.employee_id = e.id
                 WHERE date(t.date) BETWEEN :from AND :to
                 ORDER BY t.date ASC, t.id ASC"
            );
            $stmt->execute([':from' => $from, ':to' => $to]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate opening balance (before the period)
            // Reversal entries flip IN/OUT; reversed originals counted normally
            $inTypes = "('deposit', 'opening', 'sale', 'purchase_void', 'debt_settlement', 'funding')";
            $outTypes = "('sale_void', 'expense', 'withdrawal', 'advance', 'salary', 'salary_payment', 'purchase', 'sale_return')";
            $openingStmt = $this->db->prepare(
                "SELECT 
                    COALESCE(SUM(CASE 
                        WHEN transaction_type = 'reversal' AND type IN $outTypes THEN amount
                        WHEN transaction_type != 'reversal' AND type IN $inTypes THEN amount
                        ELSE 0 
                    END), 0) -
                    COALESCE(SUM(CASE 
                        WHEN transaction_type = 'reversal' AND type IN $inTypes THEN amount
                        WHEN transaction_type != 'reversal' AND type IN $outTypes THEN amount
                        ELSE 0 
                    END), 0)
                 FROM safe_transactions
                 WHERE date(date) < :from"
            );
            $openingStmt->execute([':from' => $from]);
            $openingBalance = floatval($openingStmt->fetchColumn());
            
            // Calculate period totals by type
            $totalsStmt = $this->db->prepare(
                "SELECT type, SUM(amount) as total
                 FROM safe_transactions
                 WHERE date(date) BETWEEN :from AND :to
                 GROUP BY type"
            );
            $totalsStmt->execute([':from' => $from, ':to' => $to]);
            $typeTotals = [];
            while ($row = $totalsStmt->fetch(PDO::FETCH_ASSOC)) {
                $typeTotals[$row['type']] = floatval($row['total']);
            }
            
            // Calculate closing balance (through end of period)
            $closingStmt = $this->db->prepare(
                "SELECT 
                    COALESCE(SUM(CASE 
                        WHEN transaction_type = 'reversal' AND type IN $outTypes THEN amount
                        WHEN transaction_type != 'reversal' AND type IN $inTypes THEN amount
                        ELSE 0 
                    END), 0) -
                    COALESCE(SUM(CASE 
                        WHEN transaction_type = 'reversal' AND type IN $inTypes THEN amount
                        WHEN transaction_type != 'reversal' AND type IN $outTypes THEN amount
                        ELSE 0 
                    END), 0)
                 FROM safe_transactions
                 WHERE date(date) <= :to"
            );
            $closingStmt->execute([':to' => $to]);
            $closingBalance = floatval($closingStmt->fetchColumn());
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'transactions' => $transactions,
                    'opening_balance' => $openingBalance,
                    'closing_balance' => $closingBalance,
                    'type_totals' => $typeTotals,
                    'from' => $from,
                    'to' => $to
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
        }
    }
    
    // Cash Reconciliation
    public function reconcile() {
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $actualCount = floatval($data['actual_count'] ?? 0);
            $notes = trim($data['notes'] ?? '');
            
            // Get expected balance
            $expectedBalance = $this->calculateCurrentBalance();
            $difference = $actualCount - $expectedBalance;
            
            // Determine result
            if (abs($difference) < 0.01) {
                $result = 'balanced';
            } elseif ($difference < 0) {
                $result = 'shortage';
            } else {
                $result = 'over';
            }
            
            $currentUser = $_SESSION['user_name'] ?? 'غير معروف';
            
            $stmt = $this->db->prepare(
                "INSERT INTO safe_reconciliations (expected_balance, actual_count, difference, result, notes, reconciled_by) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$expectedBalance, $actualCount, $difference, $result, $notes, $currentUser]);
            
            $resultLabel = $result === 'balanced' ? 'متوازن' : ($result === 'shortage' ? 'عجز' : 'زيادة');
            echo json_encode([
                'success' => true, 
                'message' => 'تم التسجيل بنجاح - ' . $resultLabel,
                'data' => [
                    'expected' => $expectedBalance,
                    'actual' => $actualCount,
                    'difference' => $difference,
                    'result' => $result
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
        }
    }
    
    // Get Reconciliation History
    public function reconciliationHistory() {
        header('Content-Type: application/json');
        
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM safe_reconciliations ORDER BY id DESC LIMIT 50"
            );
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $records]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
        }
    }
}
