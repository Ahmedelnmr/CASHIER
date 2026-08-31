<?php
/**
 * Customer Service - Business Logic Layer
 * Manages customers, credit sales, and payment settlements
 */

class CustomerService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Validate customer data
     */
    private function validate($data, $isUpdate = false) {
        $validator = new Validator($data);
        $validator->name('name', 'اسم العميل', true);
        $validator->phone('phone', 'رقم الهاتف', false);
        $validator->maxLength('address', 255, 'العنوان');
        $validator->maxLength('notes', 500, 'ملاحظات');
        $validator->numeric('credit_limit', 'الحد الائتماني');
        $validator->nonNegative('credit_limit', 'الحد الائتماني');
        $validator->max('credit_limit', 999999.99, 'الحد الائتماني');
        return $validator;
    }

    /**
     * Get all customers
     */
    public function getAll($search = '') {
        $sql = "SELECT * FROM customers WHERE is_active = 1";
        $params = [];
        if ($search) {
            $sql .= " AND (name LIKE :search OR phone LIKE :search2)";
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }
        $sql .= " ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get customer by ID with transactions
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get customer transactions
     */
    public function getTransactions($customerId, $limit = 100) {
        $stmt = $this->db->prepare(
            "SELECT * FROM customer_transactions WHERE customer_id = ? ORDER BY id DESC LIMIT ?"
        );
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Create customer
     */
    public function create($data) {
        $validator = $this->validate($data);
        if (!$validator->passes()) {
            return ['success' => false, 'errors' => $validator->errors(), 'message' => $validator->firstError()];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO customers (name, phone, address, notes, credit_limit) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['name'],
            $data['phone'] ?? '',
            $data['address'] ?? '',
            $data['notes'] ?? '',
            floatval($data['credit_limit'] ?? 0)
        ]);
        return ['success' => true, 'id' => $this->db->lastInsertId()];
    }

    /**
     * Update customer
     */
    public function update($id, $data) {
        if (!is_numeric($id) || $id <= 0) {
            return ['success' => false, 'message' => 'معرف العميل غير صالح'];
        }

        $existing = $this->findById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'العميل غير موجود'];
        }

        $validator = $this->validate($data, true);
        if (!$validator->passes()) {
            return ['success' => false, 'errors' => $validator->errors(), 'message' => $validator->firstError()];
        }

        $stmt = $this->db->prepare(
            "UPDATE customers SET name=?, phone=?, address=?, notes=?, credit_limit=?, updated_at=datetime('now','localtime') WHERE id=?"
        );
        $stmt->execute([
            $data['name'],
            $data['phone'] ?? '',
            $data['address'] ?? '',
            $data['notes'] ?? '',
            floatval($data['credit_limit'] ?? 0),
            $id
        ]);
        return ['success' => true];
    }

    /**
     * Delete (deactivate) customer
     */
    public function delete($id) {
        $customer = $this->findById($id);
        if (!$customer) {
            return ['success' => false, 'message' => 'العميل غير موجود'];
        }
        if (floatval($customer['balance']) > 0) {
            return ['success' => false, 'message' => 'لا يمكن حذف العميل لوجود رصيد مستحق قدره ' . number_format($customer['balance'], 2) . ' جنيه. يرجى تسوية الرصيد أولاً'];
        }
        $stmt = $this->db->prepare("UPDATE customers SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        return ['success' => true];
    }

    /**
     * Add credit sale to customer
     */
    public function addCreditSale($customerId, $saleId, $amount, $description = '') {
        $customer = $this->findById($customerId);
        if (!$customer) return ['success' => false, 'message' => 'العميل غير موجود'];

        $newBalance = $customer['balance'] + $amount;

        // Check credit limit
        if ($customer['credit_limit'] > 0 && $newBalance > $customer['credit_limit']) {
            return ['success' => false, 'message' => 'تجاوز الحد الائتماني للعميل'];
        }

        $this->db->beginTransaction();
        try {
            // Update customer balance
            $stmt = $this->db->prepare("UPDATE customers SET balance = ?, updated_at = datetime('now','localtime') WHERE id = ?");
            $stmt->execute([$newBalance, $customerId]);

            // Record transaction
            $stmt = $this->db->prepare(
                "INSERT INTO customer_transactions (customer_id, type, amount, balance_after, reference_type, reference_id, description) VALUES (?, 'sale', ?, ?, 'sale', ?, ?)"
            );
            $stmt->execute([$customerId, $amount, $newBalance, $saleId, $description ?: 'بيع آجل - فاتورة #' . $saleId]);

            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Record payment from customer
     */
    public function recordPayment($customerId, $amount, $description = '') {
        $customer = $this->findById($customerId);
        if (!$customer) return ['success' => false, 'message' => 'العميل غير موجود'];

        $newBalance = $customer['balance'] - $amount;

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE customers SET balance = ?, updated_at = datetime('now','localtime') WHERE id = ?");
            $stmt->execute([$newBalance, $customerId]);

            $stmt = $this->db->prepare(
                "INSERT INTO customer_transactions (customer_id, type, amount, balance_after, description) VALUES (?, 'payment', ?, ?, ?)"
            );
            $stmt->execute([$customerId, $amount, $newBalance, $description ?: 'تسديد مبلغ']);

            // Record in safe as deposit
            $safeStmt = $this->db->prepare(
                "INSERT INTO safe_transactions (type, amount, date, description) VALUES ('deposit', ?, date('now','localtime'), ?)"
            );
            $safeStmt->execute([$amount, 'تسديد عميل: ' . $customer['name']]);

            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get customers with outstanding balances
     */
    public function getDebtors() {
        $stmt = $this->db->prepare(
            "SELECT * FROM customers WHERE balance > 0 AND is_active = 1 ORDER BY balance DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Search customers (for POS)
     */
    public function search($query) {
        $stmt = $this->db->prepare(
            "SELECT id, name, phone, balance FROM customers WHERE is_active = 1 AND (name LIKE ? OR phone LIKE ?) ORDER BY name LIMIT 20"
        );
        $like = '%' . $query . '%';
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll();
    }
}
