<!-- Supplier Form (Create/Edit) -->
<?php $isEdit = !empty($supplier); ?>
<div class="page-header">
    <div>
        <h1><?= $isEdit ? '✏️ تعديل المورد' : '➕ إضافة مورد جديد' ?></h1>
    </div>
    <a href="?page=suppliers" class="btn btn-outline">← العودة</a>
</div>

<div class="card" style="max-width:600px;">
    <form method="POST" action="?page=suppliers&action=save">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= $supplier['id'] ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>اسم المورد *</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($supplier['name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>رقم الهاتف</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>العنوان</label>
            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($supplier['address'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>ملاحظات</label>
            <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($supplier['notes'] ?? '') ?></textarea>
        </div>
        <div class="btn-group mt-2">
            <button type="submit" class="btn btn-primary btn-lg">💾 حفظ</button>
            <a href="?page=suppliers" class="btn btn-outline btn-lg">إلغاء</a>
        </div>
    </form>
</div>
