<?php
/**
 * Product Service - Business Logic Layer
 */

class ProductService {
    private $productRepo;

    public function __construct() {
        $this->productRepo = new ProductRepository();
    }

    public function getAll($search = '', $category = '', $type = '') {
        return $this->productRepo->getAll($search, $category, $type);
    }

    public function findById($id) {
        return $this->productRepo->findById($id);
    }

    public function findByBarcode($barcode) {
        return $this->productRepo->findByBarcode($barcode);
    }

    public function findByPLU($plu) {
        return $this->productRepo->findByPLU($plu);
    }

    public function search($query) {
        return $this->productRepo->search($query);
    }

    public function create($data) {
        // Sanitize
        if (isset($data['barcode'])) {
            $data['barcode'] = trim($data['barcode']);
        }
        if (isset($data['plu_code'])) {
            $data['plu_code'] = trim($data['plu_code']);
        }

        // Validate
        $validator = new Validator($data);
        $validator->required('name', 'اسم المنتج')
                  ->maxLength('name', 60, 'اسم المنتج')
                  ->required('pack_type', 'نوع التعبئة')
                  ->numeric('purchase_price', 'سعر الشراء')
                  ->numeric('sale_price_unit', 'سعر البيع')
                  ->min('purchase_price', 0, 'سعر الشراء')
                  ->min('sale_price_unit', 0, 'سعر البيع');

        if (!$validator->passes()) {
            return ['success' => false, 'errors' => $validator->errors()];
        }

        // Check duplicate barcodes (1, 2, 3)
        $barcodesToCheck = [];
        if (!empty($data['barcode'])) $barcodesToCheck['barcode'] = $data['barcode'];
        if (!empty($data['barcode2'])) $barcodesToCheck['barcode2'] = $data['barcode2'];
        if (!empty($data['barcode3'])) $barcodesToCheck['barcode3'] = $data['barcode3'];

        foreach ($barcodesToCheck as $field => $code) {
            $existing = $this->productRepo->findByBarcode($code);
            if ($existing) {
                return ['success' => false, 'errors' => [$field => "الباركود ($code) موجود بالفعل في منتج: {$existing['name']}"]];
            }
        }

        // Check duplicate PLU code
        if (!empty($data['plu_code'])) {
            $existing = $this->productRepo->findByPLU($data['plu_code']);
            if ($existing) {
                return ['success' => false, 'errors' => ['plu_code' => 'كود PLU موجود بالفعل']];
            }
        }

        $id = $this->productRepo->create($data);
        return ['success' => true, 'id' => $id];
    }

    public function update($id, $data) {
        // Sanitize
        if (isset($data['barcode'])) {
            $data['barcode'] = trim($data['barcode']);
        }
        if (isset($data['plu_code'])) {
            $data['plu_code'] = trim($data['plu_code']);
        }

        $validator = new Validator($data);
        $validator->required('name', 'اسم المنتج')
                  ->maxLength('name', 60, 'اسم المنتج')
                  ->required('pack_type', 'نوع التعبئة')
                  ->numeric('purchase_price', 'سعر الشراء')
                  ->numeric('sale_price_unit', 'سعر البيع');

        if (!$validator->passes()) {
            return ['success' => false, 'errors' => $validator->errors()];
        }

        // Check duplicate barcodes (exclude current)
        $barcodesToCheck = [];
        if (!empty($data['barcode'])) $barcodesToCheck['barcode'] = $data['barcode'];
        if (!empty($data['barcode2'])) $barcodesToCheck['barcode2'] = $data['barcode2'];
        if (!empty($data['barcode3'])) $barcodesToCheck['barcode3'] = $data['barcode3'];

        foreach ($barcodesToCheck as $field => $code) {
            $existing = $this->productRepo->findByBarcode($code);
            if ($existing && $existing['id'] != $id) {
                return ['success' => false, 'errors' => [$field => "الباركود ($code) موجود بالفعل في منتج: {$existing['name']}"]];
            }
        }

        // Check duplicate PLU code (exclude current)
        if (!empty($data['plu_code'])) {
            $existing = $this->productRepo->findByPLU($data['plu_code']);
            if ($existing && $existing['id'] != $id) {
                return ['success' => false, 'errors' => ['plu_code' => 'كود PLU موجود بالفعل']];
            }
        }

        $this->productRepo->update($id, $data);
        return ['success' => true];
    }

    public function delete($id) {
        return $this->productRepo->delete($id);
    }

    public function getLowStock() {
        return $this->productRepo->getLowStock();
    }

    public function getCategories() {
        return $this->productRepo->getCategories();
    }

    public function count() {
        return $this->productRepo->count();
    }
}
