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
        // Generate sale number for today
        $saleNumber = $this->getNextSaleNumber();

        $stmt = $this->db->prepare(
            "INSERT INTO sales (sale_number, subtotal, discount, total, payment_method, notes, type, status, parent_sale_id)
             VALUES (:sale_number, :subtotal, :discount, :total, :payment_method, :notes, :type, :status, :parent_sale_id)"
        );
        $stmt->execute([
            ':sale_number' => $saleNumber,
            ':subtotal' => $saleData['subtotal'],
            ':discount' => $saleData['discount'] ?? 0,
            ':total' => $saleData['total'],
            ':payment_method' => $saleData['payment_method'] ?? 'cash',
            ':notes' => $saleData['notes'] ?? null,
            ':type' => $saleData['type'] ?? 'sale',
            ':status' => $saleData['status'] ?? 'completed',
            ':parent_sale_id' => $saleData['parent_sale_id'] ?? null,
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

        return $saleId;
    }

    /**
     * Get next sale number for today
     */
    private function getNextSaleNumber() {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(sale_number), 0) + 1 FROM sales WHERE date(datetime) = date('now', 'localtime')"
        );
        $stmt->execute();
        return $stmt->fetchColumn();
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
            $sql .= " AND date(datetime) >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $sql .= " AND date(datetime) <= :date_to";
            $params[':date_to'] = $dateTo;
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
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total 
             FROM sales WHERE date(datetime) = date('now', 'localtime') AND status != 'voided' AND type = 'sale'"
        );
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Get sales totals by date range
     */
    public function getSalesTotals($dateFrom, $dateTo) {
        $stmt = $this->db->prepare(
            "SELECT date(datetime) as sale_date, COUNT(*) as count, SUM(total) as total
             FROM sales 
             WHERE date(datetime) >= :from AND date(datetime) <= :to AND status != 'voided' AND type = 'sale'
             GROUP BY date(datetime)
             ORDER BY sale_date DESC"
        );
        $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
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
             SET total = :total, discount = :discount, notes = :reason
             WHERE id = :id"
        );
        $stmt->execute([
            ':id' => $saleId,
            ':total' => $total,
            ':discount' => $discount,
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
