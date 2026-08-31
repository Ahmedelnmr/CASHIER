<?php
/**
 * Shift Service - Business Logic Layer
 * Manages cashier shifts (open/close, X/Z reports)
 */

class ShiftService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get current open shift for logged-in user
     */
    public function getCurrentShift($userId = null) {
        if (!$userId && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        $stmt = $this->db->prepare(
            "SELECT * FROM shifts WHERE user_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Open a new shift
     */
    public function openShift($userId, $openingCash, $notes = '') {
        // Check if user already has an open shift
        $existing = $this->getCurrentShift($userId);
        if ($existing) {
            return ['success' => false, 'message' => 'لديك وردية مفتوحة بالفعل. أغلقها أولاً'];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO shifts (user_id, opening_cash, notes) VALUES (?, ?, ?)"
        );
        $stmt->execute([$userId, $openingCash, $notes]);
        $shiftId = $this->db->lastInsertId();

        // Record opening cash as safe transaction
        if ($openingCash > 0) {
            $safeStmt = $this->db->prepare(
                "INSERT INTO safe_transactions (type, amount, date, description) VALUES ('opening', ?, date('now','localtime'), ?)"
            );
            $safeStmt->execute([$openingCash, 'فتح وردية #' . $shiftId]);
        }

        return ['success' => true, 'shift_id' => $shiftId];
    }

    /**
     * Close current shift
     */
    public function closeShift($shiftId, $closingCash, $notes = '') {
        $shift = $this->getShiftById($shiftId);
        if (!$shift || $shift['status'] !== 'open') {
            return ['success' => false, 'message' => 'الوردية غير موجودة أو مغلقة'];
        }

        // Calculate shift totals from sales
        $totals = $this->calculateShiftTotals($shiftId);

        $expectedCash = $shift['opening_cash'] + $totals['cash_in'] - $totals['cash_out'];
        $cashDifference = $closingCash - $expectedCash;

        $stmt = $this->db->prepare(
            "UPDATE shifts SET 
                closed_at = datetime('now','localtime'),
                closing_cash = ?,
                expected_cash = ?,
                cash_difference = ?,
                total_sales = ?,
                total_returns = ?,
                total_expenses = ?,
                total_cash_in = ?,
                total_cash_out = ?,
                sale_count = ?,
                status = 'closed',
                notes = CASE WHEN ? != '' THEN ? ELSE notes END
             WHERE id = ?"
        );
        $stmt->execute([
            $closingCash, $expectedCash, $cashDifference,
            $totals['sales'], $totals['returns'], $totals['expenses'],
            $totals['cash_in'], $totals['cash_out'], $totals['sale_count'],
            $notes, $notes, $shiftId
        ]);

        return [
            'success' => true,
            'report' => [
                'shift_id' => $shiftId,
                'opening_cash' => $shift['opening_cash'],
                'closing_cash' => $closingCash,
                'expected_cash' => $expectedCash,
                'cash_difference' => $cashDifference,
                'total_sales' => $totals['sales'],
                'total_returns' => $totals['returns'],
                'total_expenses' => $totals['expenses'],
                'sale_count' => $totals['sale_count'],
            ]
        ];
    }

    /**
     * Calculate shift totals from sales and safe transactions
     */
    public function calculateShiftTotals($shiftId) {
        $shift = $this->getShiftById($shiftId);
        if (!$shift) return $this->emptyTotals();

        $openedAt = $shift['opened_at'];
        $userId = $shift['user_id'];

        // Sales totals (cash only for cash tracking)
        $salesStmt = $this->db->prepare(
            "SELECT 
                COALESCE(SUM(CASE WHEN type = 'sale' THEN total ELSE 0 END), 0) as sales,
                COALESCE(SUM(CASE WHEN type = 'return' THEN ABS(total) ELSE 0 END), 0) as returns,
                COUNT(CASE WHEN type = 'sale' THEN 1 END) as sale_count
             FROM sales
             WHERE shift_id = ? AND status != 'voided'"
        );
        $salesStmt->execute([$shiftId]);
        $salesRow = $salesStmt->fetch();

        // Cash in/out from safe transactions during this shift (exclude reversed/reversal)
        $safeStmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN type IN ('sale','deposit','opening','funding','debt_settlement') THEN amount ELSE 0 END), 0) as cash_in,
                COALESCE(SUM(CASE WHEN type IN ('expense','purchase','withdrawal','salary','salary_payment','advance','sale_return','sale_void') THEN amount ELSE 0 END), 0) as cash_out
             FROM safe_transactions
             WHERE created_at >= ? AND (type != 'opening' OR description LIKE ?)
               AND status != 'reversed' AND transaction_type != 'reversal'"
        );
        $safeStmt->execute([$openedAt, '%وردية #' . $shiftId . '%']);
        $safeRow = $safeStmt->fetch();

        return [
            'sales' => floatval($salesRow['sales']),
            'returns' => floatval($salesRow['returns']),
            'sale_count' => intval($salesRow['sale_count']),
            'cash_in' => floatval($safeRow['cash_in']),
            'cash_out' => floatval($safeRow['cash_out']),
            'expenses' => floatval($safeRow['cash_out']),
        ];
    }

    private function emptyTotals() {
        return ['sales' => 0, 'returns' => 0, 'sale_count' => 0, 'cash_in' => 0, 'cash_out' => 0, 'expenses' => 0];
    }

    /**
     * Get shift by ID
     */
    public function getShiftById($id) {
        $stmt = $this->db->prepare("SELECT * FROM shifts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get shift history
     */
    public function getShifts($limit = 50) {
        $stmt = $this->db->prepare(
            "SELECT s.*, u.name as user_name 
             FROM shifts s 
             LEFT JOIN users u ON s.user_id = u.id 
             ORDER BY s.id DESC LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Generate X-report (interim - shift still open)
     */
    public function xReport($shiftId) {
        $shift = $this->getShiftById($shiftId);
        if (!$shift) return null;

        $totals = $this->calculateShiftTotals($shiftId);
        $expectedCash = $shift['opening_cash'] + $totals['cash_in'] - $totals['cash_out'];

        return [
            'type' => 'X',
            'shift' => $shift,
            'totals' => $totals,
            'expected_cash' => $expectedCash,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Generate Z-report (final - shift closed)
     */
    public function zReport($shiftId) {
        $shift = $this->getShiftById($shiftId);
        if (!$shift) return null;

        return [
            'type' => 'Z',
            'shift' => $shift,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
