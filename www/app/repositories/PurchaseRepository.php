<?php
/**
 * Purchase Repository - Data Access Layer
 */

class PurchaseRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create purchase invoice with items (within existing transaction)
     */
    public function create($invoiceData, $items) {
        $stmt = $this->db->prepare(
            "INSERT INTO purchase_invoices (supplier_id, invoice_number, date, total, discount, notes, type, status, parent_invoice_id)
             VALUES (:supplier_id, :invoice_number, :date, :total, :discount, :notes, :type, :status, :parent_invoice_id)"
        );
        $stmt->execute([
            ':supplier_id' => $invoiceData['supplier_id'],
            ':invoice_number' => $invoiceData['invoice_number'] ?? null,
            ':date' => $invoiceData['date'] ?? date('Y-m-d'),
            ':total' => $invoiceData['total'],
            ':discount' => $invoiceData['discount'] ?? 0,
            ':notes' => $invoiceData['notes'] ?? null,
            ':type' => $invoiceData['type'] ?? 'purchase',
            ':status' => $invoiceData['status'] ?? 'completed',
            ':parent_invoice_id' => $invoiceData['parent_invoice_id'] ?? null,
        ]);

        $invoiceId = $this->db->lastInsertId();

        $itemStmt = $this->db->prepare(
            "INSERT INTO purchase_items (invoice_id, product_id, quantity, purchase_price, subtotal)
             VALUES (:invoice_id, :product_id, :quantity, :purchase_price, :subtotal)"
        );

        foreach ($items as $item) {
            $itemStmt->execute([
                ':invoice_id' => $invoiceId,
                ':product_id' => $item['product_id'],
                ':quantity' => $item['quantity'],
                ':purchase_price' => $item['purchase_price'],
                ':subtotal' => $item['subtotal'],
            ]);
        }

        return $invoiceId;
    }

    /**
     * Get invoice by ID with items
     */
    public function findById($id) {
        $stmt = $this->db->prepare(
            "SELECT pi.*, s.name as supplier_name 
             FROM purchase_invoices pi 
             LEFT JOIN suppliers s ON pi.supplier_id = s.id 
             WHERE pi.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $invoice = $stmt->fetch();

        if ($invoice) {
            $itemStmt = $this->db->prepare(
                "SELECT pit.*, p.name as product_name 
                 FROM purchase_items pit 
                 LEFT JOIN products p ON pit.product_id = p.id 
                 WHERE pit.invoice_id = :invoice_id"
            );
            $itemStmt->execute([':invoice_id' => $id]);
            $invoice['items'] = $itemStmt->fetchAll();
        }

        return $invoice;
    }

    /**
     * Get all invoices
     */
    public function getAll($supplierId = '', $dateFrom = '', $dateTo = '') {
        $sql = "SELECT pi.*, s.name as supplier_name 
                FROM purchase_invoices pi 
                LEFT JOIN suppliers s ON pi.supplier_id = s.id 
                WHERE 1=1";
        $params = [];

        if ($supplierId !== '') {
            $sql .= " AND pi.supplier_id = :supplier_id";
            $params[':supplier_id'] = $supplierId;
        }
        if ($dateFrom !== '') {
            $sql .= " AND pi.date >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $sql .= " AND pi.date <= :date_to";
            $params[':date_to'] = $dateTo;
        }

        $sql .= " ORDER BY pi.date DESC, pi.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get total purchases for a supplier
     */
    public function getSupplierTotal($supplierId) {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM purchase_invoices WHERE supplier_id = :id AND status != 'voided' AND type = 'purchase'"
        );
        $stmt->execute([':id' => $supplierId]);
        return $stmt->fetchColumn();
    }

    /**
     * Get invoice count for a supplier (for auto-increment)
     */
    public function getSupplierInvoiceCount($supplierId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM purchase_invoices WHERE supplier_id = :id");
        $stmt->execute([':id' => $supplierId]);
        return $stmt->fetchColumn();
    }

    /**
     * Get items from the last invoice for a specific supplier
     */
    public function getLastInvoiceItems($supplierId) {
        // 1. Find last invoice ID
        $stmt = $this->db->prepare("SELECT id FROM purchase_invoices WHERE supplier_id = :supplier_id ORDER BY id DESC LIMIT 1");
        $stmt->execute([':supplier_id' => $supplierId]);
        $invoiceId = $stmt->fetchColumn();

        if (!$invoiceId) {
            return [];
        }

        // 2. Get items
        $stmt = $this->db->prepare("
            SELECT 
                pi.*, 
                p.name as product_name, p.barcode, p.type as product_type,
                p.pack_type, p.pack_unit_quantity,
                p.purchase_price as current_unit_cost,
                p.sale_price_unit as current_unit_price,
                p.pack_purchase_price as current_pack_cost,
                p.pack_sale_price as current_pack_price
            FROM purchase_items pi
            JOIN products p ON pi.product_id = p.id
            WHERE pi.invoice_id = :id
        ");
        $stmt->execute([':id' => $invoiceId]);
        return $stmt->fetchAll();
    }

    /**
     * Update invoice status
     */
    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE purchase_invoices SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function count() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM purchase_invoices WHERE status != 'voided'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Delete invoice and its items
     */
    public function delete($id) {
        $itemStmt = $this->db->prepare("DELETE FROM purchase_items WHERE invoice_id = :id");
        $itemStmt->execute([':id' => $id]);

        $stmt = $this->db->prepare("DELETE FROM purchase_invoices WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }
}
