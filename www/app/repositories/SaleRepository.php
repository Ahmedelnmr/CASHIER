<?php
/**
 * Sale Repository - Data Access Layer
 */

class SaleRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a sale with items (within existing transaction)
     */
    public function create($saleData, $items) {
        // Generate invoice number using the business date from the sale
        $generator = new InvoiceNumberGenerator();
        $businessDate = isset($saleData['datetime'])
            ? substr($saleData['datetime'], 0, 10)
            : date('Y-m-d');
        $invoiceNumber = $generator->generate($businessDate);

        $stmt = $this->db->prepare(
            "INSERT INTO sales (sale_number, invoice_number, subtotal, discount, total, payment_method, notes, type, status, parent_sale_id, customer_id, shift_id, amount_paid, change_amount)
             VALUES (:sale_number, :invoice_number, :subtotal, :discount, :total, :payment_method, :notes, :type, :status, :parent_sale_id, :customer_id, :shift_id, :amount_paid, :change_amount)"
        );
        $stmt->execute([
            ':sale_number' => $saleData['sale_number'] ?? null,
            ':invoice_number' => $invoiceNumber,
            ':subtotal' => $saleData['subtotal'],
            ':discount' => $saleData['discount'] ?? 0,
            ':total' => $saleData['total'],
            ':payment_method' => $saleData['payment_method'] ?? 'cash',
            ':notes' => $saleData['notes'] ?? null,
            ':type' => $saleData['type'] ?? 'sale',
            ':status' => $saleData['status'] ?? 'completed',
            ':parent_sale_id' => $saleData['parent_sale_id'] ?? null,
            ':customer_id' => $saleData['customer_id'] ?? null,
            ':shift_id' => $saleData['shift_id'] ?? null,
            ':amount_paid' => $saleData['amount_paid'] ?? null,
            ':change_amount' => $saleData['change_amount'] ?? null,
        ]);

        $saleId = $this->db->lastInsertId();

        // Insert items
        $itemStmt = $this->db->prepare(
            "INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal)
             VALUES (:sale_id, :product_id, :product_name, :quantity, :unit_type, :price, :sale_mode, :subtotal)"
        );

        foreach ($items as $item) {
            $itemStmt->execute([
                ':sale_id' => $saleId,
                ':product_id' => $item['product_id'],
                ':product_name' => $item['product_name'],
                ':quantity' => $item['quantity'],
                ':unit_type' => $item['unit_type'] ?? 'قطعة',
                ':price' => $item['price'],
                ':sale_mode' => $item['sale_mode'] ?? 'unit',
                ':subtotal' => $item['subtotal'],
            ]);
        }

        return ['saleId' => $saleId, 'invoiceNumber' => $invoiceNumber];
    }

    /**
     * Get sale by invoice number with items
     */
    public function findByInvoiceNumber($invoiceNumber) {
        $stmt = $this->db->prepare("SELECT * FROM sales WHERE invoice_number = :invoice_number");
        $stmt->execute([':invoice_number' => $invoiceNumber]);
        $sale = $stmt->fetch();

        if ($sale) {
            $itemStmt = $this->db->prepare("SELECT * FROM sale_items WHERE sale_id = :sale_id");
            $itemStmt->execute([':sale_id' => $sale['id']]);
            $sale['items'] = $itemStmt->fetchAll();
        }

        return $sale;
    }

    /**
     * Get sale by ID with items
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM sales WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $sale = $stmt->fetch();

        if ($sale) {
            $itemStmt = $this->db->prepare("SELECT * FROM sale_items WHERE sale_id = :sale_id");
            $itemStmt->execute([':sale_id' => $id]);
            $sale['items'] = $itemStmt->fetchAll();
        }

        return $sale;
    }

    /**
     * Get sales list with filters
     */
    public function getAll($dateFrom = '', $dateTo = '', $limit = 500, $type = '', $status = '') {
        $sql = "SELECT * FROM sales WHERE 1=1";
        $params = [];

        if ($type !== '') {
            $sql .= " AND type = :type";
            $params[':type'] = $type;
        }
        if ($status === 'active') {
            $sql .= " AND status != 'voided'";
        } elseif ($status !== '') {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        if ($dateFrom !== '') {
            $endExclusive = ($dateTo !== '') ? date('Y-m-d', strtotime($dateTo . ' +1 day')) : date('Y-m-d', strtotime($dateFrom . ' +1 day'));
            $sql .= " AND datetime >= :date_start AND datetime < :date_end";
            $params[':date_start'] = $dateFrom . ' 00:00:00';
            $params[':date_end'] = $endExclusive . ' 00:00:00';
        }

        $sql .= " ORDER BY datetime DESC LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get today's sales summary
     */
    public function getTodaySummary() {
        $today = date('Y-m-d');
        $start = $today . ' 00:00:00';
        $endExclusive = date('Y-m-d', strtotime($today . ' +1 day')) . ' 00:00:00';
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total 
             FROM sales WHERE datetime >= :start AND datetime < :end AND status != 'voided' AND type = 'sale'"
        );
        $stmt->execute([':start' => $start, ':end' => $endExclusive]);
        return $stmt->fetch();
    }

    /**
     * Get sales totals by date range
     */
    public function getSalesTotals($dateFrom, $dateTo) {
        $start = $dateFrom . ' 00:00:00';
        $endExclusive = date('Y-m-d', strtotime($dateTo . ' +1 day')) . ' 00:00:00';
        $stmt = $this->db->prepare(
            "SELECT date(datetime) as sale_date, COUNT(*) as count, SUM(total) as total
             FROM sales 
             WHERE datetime >= :start AND datetime < :end AND status != 'voided' AND type = 'sale'
             GROUP BY date(datetime)
             ORDER BY sale_date DESC"
        );
        $stmt->execute([':start' => $start, ':end' => $endExclusive]);
        return $stmt->fetchAll();
    }

    /**
     * Delete a sale and its items
     */
    public function delete($id) {
        $itemStmt = $this->db->prepare("DELETE FROM sale_items WHERE sale_id = :id");
        $itemStmt->execute([':id' => $id]);

        $saleStmt = $this->db->prepare("DELETE FROM sales WHERE id = :id");
        $saleStmt->execute([':id' => $id]);
    }

    /**
     * Delete only sale items (for update)
     */
    public function deleteItems($saleId) {
        $stmt = $this->db->prepare("DELETE FROM sale_items WHERE sale_id = :id");
        $stmt->execute([':id' => $saleId]);
    }

    /**
     * Add single item to sale
     */
    public function addItem($saleId, $item) {
        $stmt = $this->db->prepare(
            "INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_type, price, sale_mode, subtotal)
             VALUES (:sale_id, :product_id, :product_name, :quantity, :unit_type, :price, :sale_mode, :subtotal)"
        );
        $stmt->execute([
            ':sale_id' => $saleId,
            ':product_id' => $item['product_id'],
            ':product_name' => $item['product_name'],
            ':quantity' => $item['quantity'],
            ':unit_type' => $item['unit_type'] ?? 'قطعة',
            ':price' => $item['price'],
            ':sale_mode' => $item['sale_mode'] ?? 'unit',
            ':subtotal' => $item['subtotal']
        ]);
    }

    /**
     * Update sale totals
     */
    public function updateTotals($saleId, $total, $discount, $reason) {
        $stmt = $this->db->prepare(
            "UPDATE sales 
             SET total = :total, discount = :discount, subtotal = :subtotal, notes = :reason
             WHERE id = :id"
        );
        $stmt->execute([
            ':id' => $saleId,
            ':total' => $total,
            ':discount' => $discount,
            ':subtotal' => $total + $discount,
            ':reason' => $reason
        ]);
    }

    /**
     * Update sale status
     */
    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE sales SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    /**
     * Count total sales (excluding voided)
     */
    public function count() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM sales WHERE status != 'voided'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
