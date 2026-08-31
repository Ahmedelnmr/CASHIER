<?php
/**
 * Supplier Service - Business Logic Layer
 */

class SupplierService {
    private $supplierRepo;

    public function __construct() {
        $this->supplierRepo = new SupplierRepository();
    }

    /**
     * Validate supplier data
     */
    private function validate($data) {
        $validator = new Validator($data);
        $validator->name('name', 'اسم المورد', true);
        $validator->phone('phone', 'رقم الهاتف', false);
        $validator->maxLength('address', 255, 'العنوان');
        $validator->maxLength('notes', 500, 'ملاحظات');
        return $validator;
    }

    public function getAll($search = '') {
        return $this->supplierRepo->getAll($search);
    }

    public function findById($id) {
        return $this->supplierRepo->findById($id);
    }

    public function create($data) {
        $data = Validator::trimAll($data);
        $validator = $this->validate($data);

        if (!$validator->passes()) {
            return ['success' => false, 'errors' => $validator->errors()];
        }

        $id = $this->supplierRepo->create($data);
        return ['success' => true, 'id' => $id];
    }

    public function update($id, $data) {
        if (!is_numeric($id) || $id <= 0) {
            return ['success' => false, 'errors' => ['id' => 'معرف المورد غير صالح']];
        }

        $existing = $this->supplierRepo->findById($id);
        if (!$existing) {
            return ['success' => false, 'errors' => ['id' => 'المورد غير موجود']];
        }

        $data = Validator::trimAll($data);
        $validator = $this->validate($data);

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
