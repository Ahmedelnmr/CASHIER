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
                                   AND type IN ('salary', 'salary_payment')
                                   AND status != 'reversed' AND transaction_type != 'reversal'");
        $stmt->execute([$employeeId]);
        return $stmt->fetchColumn() ?: 0;
    }

    public function getTotalAdvances($employeeId) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) FROM safe_transactions 
                                   WHERE employee_id = ? 
                                   AND type = 'advance'
                                   AND status != 'reversed' AND transaction_type != 'reversal'");
        $stmt->execute([$employeeId]);
        return $stmt->fetchColumn() ?: 0;
    }

    public function getTotalDebtSettlements($employeeId) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) FROM safe_transactions 
                                   WHERE employee_id = ? 
                                   AND type = 'debt_settlement'
                                   AND status != 'reversed' AND transaction_type != 'reversal'");
        $stmt->execute([$employeeId]);
        return $stmt->fetchColumn() ?: 0;
    }

    /**
     * Get ledger balances for ALL active employees in one query.
     * Returns [ employee_id => balance, ... ]
     * balance > 0 → company owes employee; balance < 0 → employee owes company.
     */
    public function getAllBalances() {
        $sql = "
            SELECT
                e.id AS employee_id,
                COALESCE(acc.total_accruals, 0)
                  - COALESCE(st.total_salary, 0)
                  - COALESCE(st.total_advances, 0)
                  + COALESCE(st.total_settlements, 0) AS balance
            FROM employees e
            LEFT JOIN (
                SELECT employee_id, SUM(amount) AS total_accruals
                FROM employee_accruals
                GROUP BY employee_id
            ) acc ON acc.employee_id = e.id
            LEFT JOIN (
                SELECT
                    employee_id,
                    SUM(CASE WHEN type IN ('salary','salary_payment') THEN amount ELSE 0 END) AS total_salary,
                    SUM(CASE WHEN type = 'advance' THEN amount ELSE 0 END)                    AS total_advances,
                    SUM(CASE WHEN type = 'debt_settlement' THEN amount ELSE 0 END)            AS total_settlements
                FROM safe_transactions
                WHERE status != 'reversed' AND transaction_type != 'reversal'
                  AND type IN ('salary','salary_payment','advance','debt_settlement')
                GROUP BY employee_id
            ) st ON st.employee_id = e.id
            WHERE e.is_active = 1
        ";
        $stmt = $this->db->query($sql);
        $rows  = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map   = [];
        foreach ($rows as $row) {
            $map[$row['employee_id']] = floatval($row['balance']);
        }
        return $map;
    }

    public function getOutstandingDebt($employeeId) {
        $advances = $this->getTotalAdvances($employeeId);
        $settlements = $this->getTotalDebtSettlements($employeeId);
        return max(0, $advances - $settlements);
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
                    WHEN type = 'debt_settlement' THEN 'تسوية مديونية'
                    ELSE type 
                END as type_label,
                type, 
                amount, 
                description 
            FROM safe_transactions 
            WHERE employee_id = ? AND type IN ('advance', 'salary', 'salary_payment', 'debt_settlement')
              AND status != 'reversed' AND transaction_type != 'reversal'
            
            ORDER BY date DESC, id DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId, $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
