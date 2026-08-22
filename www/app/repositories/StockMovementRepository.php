<?php
/**
 * Stock Movement Repository - Data Access Layer
 */

class StockMovementRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Record a stock movement
     */
    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO stock_movements (product_id, type, quantity, reference_type, reference_id, notes)
             VALUES (:product_id, :type, :quantity, :reference_type, :reference_id, :notes)"
        );
        return $stmt->execute([
            ':product_id' => $data['product_id'],
            ':type' => $data['type'],
            ':quantity' => $data['quantity'],
            ':reference_type' => $data['reference_type'] ?? null,
            ':reference_id' => $data['reference_id'] ?? null,
            ':notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Get movements for a product
     */
    public function getByProduct($productId, $limit = 50) {
        $stmt = $this->db->prepare(
            "SELECT sm.*, p.name as product_name 
             FROM stock_movements sm 
             LEFT JOIN products p ON sm.product_id = p.id 
             WHERE sm.product_id = :product_id 
             ORDER BY sm.created_at DESC 
             LIMIT :limit"
        );
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get recent movements
     */
    public function getRecent($limit = 50) {
        $stmt = $this->db->prepare(
            "SELECT sm.*, p.name as product_name 
             FROM stock_movements sm 
             LEFT JOIN products p ON sm.product_id = p.id 
             ORDER BY sm.created_at DESC 
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
