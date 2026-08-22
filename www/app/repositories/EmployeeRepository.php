<?php
/**
 * Employee Repository
 * Handles database operations for employees and accruals
 */

class EmployeeRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllActive() {
        $stmt = $this->db->query("SELECT * FROM employees WHERE is_active = 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Accruals
    public function hasAccrualForMonth($employeeId, $month) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM employee_accruals WHERE employee_id = ? AND month = ?");
        $stmt->execute([$employeeId, $month]);
        return $stmt->fetchColumn() > 0;
    }

    public function createAccrual($employeeId, $month, $amount) {
        $stmt = $this->db->prepare("INSERT INTO employee_accruals (employee_id, month, amount) VALUES (?, ?, ?)");
        return $stmt->execute([$employeeId, $month, $amount]);
    }

    public function getTotalAccruals($employeeId) {
        $stmt = $this->db->prepare("SELECT SUM(amount) FROM employee_accruals WHERE employee_id = ?");
        $stmt->execute([$employeeId]);
        return $stmt->fetchColumn() ?: 0;
    }



    public function getTotalSalaryPaid($employeeId) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) FROM safe_transactions 
                                   WHERE employee_id = ? 
                                   AND type IN ('salary', 'salary_payment')");
        $stmt->execute([$employeeId]);
        return $stmt->fetchColumn() ?: 0;
    }

    public function getTotalAdvances($employeeId) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) FROM safe_transactions 
                                   WHERE employee_id = ? 
                                   AND type = 'advance'");
        $stmt->execute([$employeeId]);
        return $stmt->fetchColumn() ?: 0;
    }
    public function getTransactions($employeeId) {
        // Union Accruals (Credit) and Safe Transactions (Debit)
        $sql = "
            SELECT 
                id, 
                date(created_at) as date, 
                'استحقاق راتب' as type_label, 
                'accrual' as type, 
                amount, 
                'راتب شهر ' || month as description 
            FROM employee_accruals 
            WHERE employee_id = ?
            
            UNION ALL
            
            SELECT 
                id, 
                date(date) as date, 
                CASE 
                    WHEN type = 'advance' THEN 'سلفة'
                    WHEN type IN ('salary', 'salary_payment') THEN 'سداد راتب'
                    ELSE type 
                END as type_label,
                type, 
                amount, 
                description 
            FROM safe_transactions 
            WHERE employee_id = ? AND type IN ('advance', 'salary', 'salary_payment')
            
            ORDER BY date DESC, id DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId, $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
