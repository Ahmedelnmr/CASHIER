<?php
/**
 * Product Repository - Data Access Layer
 */

class ProductRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all active products
     */
    public function getAll($search = '', $category = '', $type = '') {
        $sql = "SELECT * FROM products WHERE is_active = 1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (name LIKE :search OR barcode LIKE :search2 OR plu_code LIKE :search3)";
            $params[':search'] = "%$search%";
            $params[':search2'] = "%$search%";
            $params[':search3'] = "%$search%";
        }

        if ($category !== '') {
            $sql .= " AND category_id = :category_id";
            $params[':category'] = $category;
        }

        if ($type !== '') {
            $sql .= " AND type = :type";
            $params[':type'] = $type;
        }

        $sql .= " ORDER BY name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find product by ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Find product by ANY barcode (1, 2, or 3)
     */
    public function findByBarcode($barcode) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE (barcode = :b OR barcode2 = :b OR barcode3 = :b) AND is_active = 1");
        $stmt->execute([':b' => $barcode]);
        return $stmt->fetch();
    }

    /**
     * Find product by PLU code
     */
    public function findByPLU($plu) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE plu_code = :plu AND is_active = 1");
        $stmt->execute([':plu' => $plu]);
        return $stmt->fetch();
    }

    /**
     * Search products by name (for POS quick search)
     */
    public function search($query, $limit = 20, $category = '') {
        $sql = "SELECT id, name, barcode, plu_code, type, 
                    purchase_price, sale_price_unit,
                    pack_type, pack_unit_quantity, pack_purchase_price, pack_sale_price,
                    stock_quantity, min_stock, category
             FROM products 
             WHERE is_active = 1 AND (name LIKE :q OR barcode LIKE :q2 OR barcode2 LIKE :q4 OR barcode3 LIKE :q5 OR plu_code LIKE :q3)";
        
        $params = [
            ':q' => "%$query%",
            ':q2' => "%$query%",
            ':q3' => "%$query%",
            ':q4' => "%$query%",
            ':q5' => "%$query%",
        ];
        
        if ($category !== '' && $category !== 'all') {
            $sql .= " AND category_id = :category_id";
            $params[':category'] = $category;
        }
        
        $sql .= " ORDER BY name ASC LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Create a new product
     */
    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO products (name, barcode, barcode2, barcode3, plu_code, type, purchase_price, sale_price_unit, pack_type, pack_unit_quantity, pack_purchase_price, pack_sale_price, stock_quantity, min_stock, category_id, notes)
             VALUES (:name, :barcode, :barcode2, :barcode3, :plu_code, :type, :purchase_price, :sale_price_unit, :pack_type, :pack_unit_quantity, :pack_purchase_price, :pack_sale_price, :stock_quantity, :min_stock, :category_id, :notes)"
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':barcode' => $data['barcode'] ?? null,
            ':barcode2' => $data['barcode2'] ?? null,
            ':barcode3' => $data['barcode3'] ?? null,
            ':plu_code' => $data['plu_code'] ?? null,
            ':type' => $data['type'] ?? 'unit',
            ':purchase_price' => $data['purchase_price'] ?? 0,
            ':sale_price_unit' => $data['sale_price_unit'] ?? 0,
            ':pack_type' => $data['pack_type'] ?? null,
            ':pack_unit_quantity' => $data['pack_unit_quantity'] ?? null,
            ':pack_purchase_price' => $data['pack_purchase_price'] ?? null,
            ':pack_sale_price' => $data['pack_sale_price'] ?? null,
            ':stock_quantity' => $data['stock_quantity'] ?? 0,
            ':min_stock' => $data['min_stock'] ?? 0,
            ':category_id' => $data['category_id'] ?? null,
            ':notes' => $data['notes'] ?? null,
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Update existing product (Partial or Full)
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        // Map allowed fields
        $allowed = [
            'name', 'barcode', 'barcode2', 'barcode3', 'plu_code', 'type', 
            'purchase_price', 'sale_price_unit',
            'pack_type', 'pack_unit_quantity',
            'pack_purchase_price', 'pack_sale_price',
            'stock_quantity', 'min_stock', 
            'category_id', 'notes', 'is_active'
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        // Always update timestamp
        $fields[] = "updated_at = datetime('now', 'localtime')";

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * Soft delete product
     */
    public function delete($id) {
        $stmt = $this->db->prepare("UPDATE products SET is_active = 0, updated_at = datetime('now', 'localtime') WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Update stock quantity
     */
    public function updateStock($id, $quantityChange) {
        $stmt = $this->db->prepare("UPDATE products SET stock_quantity = stock_quantity + :qty, updated_at = datetime('now', 'localtime') WHERE id = :id");
        return $stmt->execute([':qty' => $quantityChange, ':id' => $id]);
    }

    /**
     * Get low stock products
     */
    public function getLowStock() {
        $stmt = $this->db->prepare(
            "SELECT * FROM products WHERE is_active = 1 AND stock_quantity <= min_stock AND min_stock > 0 ORDER BY stock_quantity ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all distinct categories
     */
    public function getCategories() {
        $stmt = $this->db->query("SELECT id, name FROM categories ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count all active products
     */
    public function count() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE is_active = 1");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
