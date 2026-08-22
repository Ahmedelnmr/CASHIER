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
     * Balance = Accruals − SalaryPaid − Advances
     * 
     * Positive = Company owes Employee (مستحق له).
     * Negative = Employee owes Company (مديونية - سلف).
     * 
     * Auto-accrual runs every month, so the cycle resets automatically.
     */
    public function getBalance($employeeId) {
        $accruals   = $this->employeeRepo->getTotalAccruals($employeeId);
        $salaryPaid = $this->employeeRepo->getTotalSalaryPaid($employeeId);
        $advances   = $this->employeeRepo->getTotalAdvances($employeeId);

        return $accruals - $salaryPaid - $advances;
    }

    public function getAllWithBalance() {
        $employees = $this->employeeRepo->getAllActive();
        foreach ($employees as &$emp) {
            $emp['ledger_balance'] = $this->getBalance($emp['id']);
        }
        return $employees;
    }

    public function getHistory($employeeId) {
        return $this->employeeRepo->getTransactions($employeeId);
    }
}
