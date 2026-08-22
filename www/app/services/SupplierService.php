<?php
/**
 * Supplier Service - Business Logic Layer
 */

class SupplierService {
    private $supplierRepo;

    public function __construct() {
        $this->supplierRepo = new SupplierRepository();
    }

    public function getAll($search = '') {
        return $this->supplierRepo->getAll($search);
    }

    public function findById($id) {
        return $this->supplierRepo->findById($id);
    }

    public function create($data) {
        $validator = new Validator($data);
        $validator->required('name', 'اسم المورد');

        if (!$validator->passes()) {
            return ['success' => false, 'errors' => $validator->errors()];
        }

        $id = $this->supplierRepo->create($data);
        return ['success' => true, 'id' => $id];
    }

    public function update($id, $data) {
        $validator = new Validator($data);
        $validator->required('name', 'اسم المورد');

        if (!$validator->passes()) {
            return ['success' => false, 'errors' => $validator->errors()];
        }

        $this->supplierRepo->update($id, $data);
        return ['success' => true];
    }

    public function delete($id) {
        return $this->supplierRepo->delete($id);
    }

    public function count() {
        return $this->supplierRepo->count();
    }
}
