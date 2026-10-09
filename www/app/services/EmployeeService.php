<?php
/**
 * Employee Service - Business Logic for Payroll
 */

class EmployeeService {
    private $employeeRepo;

    public function __construct() {
        $this->employeeRepo = new EmployeeRepository();
    }

    /**
     * Run Auto-Accrual for the current month.
     * Should be called on system startup or dashboard load.
     */
    public function runMonthlyAccrual() {
        $currentMonth = date('Y-m');
        $employees = $this->employeeRepo->getAllActive();

        $count = 0;
        foreach ($employees as $emp) {
            // Check if salary already accrued for this month
            if (!$this->employeeRepo->hasAccrualForMonth($emp['id'], $currentMonth)) {
                // Determine salary amount
                $amount = floatval($emp['base_salary']);
                if ($amount > 0) {
                    $this->employeeRepo->createAccrual($emp['id'], $currentMonth, $amount);
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Calculate Running Balance for Employee
     * Balance = Accruals − SalaryPaid − Advances + DebtSettlements
     * 
     * Positive = Company owes Employee (مستحق له).
     * Negative = Employee owes Company (مديونية - سلف).
     * 
     * Auto-accrual runs every month, so the cycle resets automatically.
     */
    public function getBalance($employeeId) {
        $accruals     = $this->employeeRepo->getTotalAccruals($employeeId);
        $salaryPaid   = $this->employeeRepo->getTotalSalaryPaid($employeeId);
        $advances     = $this->employeeRepo->getTotalAdvances($employeeId);
        $settlements  = $this->employeeRepo->getTotalDebtSettlements($employeeId);

        return $accruals - $salaryPaid - $advances + $settlements;
    }

    /**
     * Get outstanding debt (how much employee owes company)
     * Returns 0 if employee doesn't owe anything.
     */
    public function getOutstandingDebt($employeeId) {
        return $this->employeeRepo->getOutstandingDebt($employeeId);
    }

    public function getAllWithBalance() {
        $employees = $this->employeeRepo->getAllActive();
        $balances  = $this->employeeRepo->getAllBalances(); // single bulk query
        foreach ($employees as &$emp) {
            $emp['ledger_balance'] = $balances[$emp['id']] ?? 0.0;
        }
        return $employees;
    }

    public function getHistory($employeeId) {
        return $this->employeeRepo->getTransactions($employeeId);
    }
}
