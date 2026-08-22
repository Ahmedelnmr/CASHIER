<?php
/**
 * Supplier Repository - Data Access Layer
 */

class SupplierRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($search = '') {
        $sql = "SELECT * FROM suppliers WHERE is_active = 1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (name LIKE :search OR phone LIKE :search2)";
            $params[':search'] = "%$search%";
            $params[':search2'] = "%$search%";
        }

        $sql .= " ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO suppliers (name, phone, address, notes) VALUES (:name, :phone, :address, :notes)"
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':phone' => $data['phone'] ?: null,
            ':address' => $data['address'] ?: null,
            ':notes' => $data['notes'] ?: null,
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare(
            "UPDATE suppliers SET name = :name, phone = :phone, address = :address, notes = :notes, updated_at = datetime('now', 'localtime') WHERE id = :id"
        );
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':phone' => $data['phone'] ?: null,
            ':address' => $data['address'] ?: null,
            ':notes' => $data['notes'] ?: null,
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("UPDATE suppliers SET is_active = 0, updated_at = datetime('now', 'localtime') WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function count() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM suppliers WHERE is_active = 1");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
