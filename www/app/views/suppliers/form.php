<!-- Supplier Form (Create/Edit) -->
<?php $isEdit = !empty($supplier); ?>
<div class="page-header">
    <div>
        <h1><?= $isEdit ? '✏️ تعديل المورد' : '➕ إضافة مورد جديد' ?></h1>
    </div>
    <a href="?page=suppliers" class="btn btn-outline">← العودة</a>
</div>

<div class="card" style="max-width:600px;">
    <form id="supplierForm" method="POST" action="?page=suppliers&action=save" onsubmit="return validateSupplierForm();">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= $supplier['id'] ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>اسم المورد *</label>
            <input type="text" name="name" class="form-control" id="supName" required value="<?= htmlspecialchars($supplier['name'] ?? '') ?>">
            <small class="field-error" id="supNameError" style="color:var(--danger);display:none;"></small>
        </div>
        <div class="form-group">
            <label>رقم الهاتف</label>
            <input type="text" name="phone" class="form-control" id="supPhone" maxlength="15" dir="ltr" value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>">
            <small class="field-error" id="supPhoneError" style="color:var(--danger);display:none;"></small>
        </div>
        <div class="form-group">
            <label>العنوان</label>
            <input type="text" name="address" class="form-control" maxlength="255" value="<?= htmlspecialchars($supplier['address'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>ملاحظات</label>
            <textarea name="notes" class="form-control" rows="2" maxlength="500"><?= htmlspecialchars($supplier['notes'] ?? '') ?></textarea>
        </div>
        <div class="btn-group mt-2">
            <button type="submit" class="btn btn-primary btn-lg">💾 حفظ</button>
            <a href="?page=suppliers" class="btn btn-outline btn-lg">إلغاء</a>
        </div>
    </form>
</div>

<script>
function validateSupplierForm() {
    var valid = true;
    var name = document.getElementById('supName').value.trim();
    var phone = document.getElementById('supPhone').value.trim();

    clearSupFieldError('supName');
    clearSupFieldError('supPhone');

    // Name validation
    if (!name) {
        showSupFieldError('supName', 'اسم المورد مطلوب');
        valid = false;
    } else if (name.length < 2) {
        showSupFieldError('supName', 'الاسم يجب أن يكون حرفين على الأقل');
        valid = false;
    } else if (name.length > 100) {
        showSupFieldError('supName', 'الاسم يجب أن لا يتجاوز 100 حرف');
        valid = false;
    } else if (/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/.test(name)) {
        showSupFieldError('supName', 'الاسم يحتوي على أحرف غير صالحة');
        valid = false;
    } else if (/\s{2,}/.test(name)) {
        showSupFieldError('supName', 'الاسم يحتوي على مسافات متكررة');
        valid = false;
    }

    // Phone validation
    if (phone && !/^[0-9]{7,15}$/.test(phone)) {
        showSupFieldError('supPhone', 'رقم الهاتف يجب أن يحتوي على أرقام فقط (7-15 رقم)');
        valid = false;
    }

    return valid;
}

function showSupFieldError(fieldId, message) {
    var errorEl = document.getElementById(fieldId + 'Error');
    var inputEl = document.getElementById(fieldId);
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.style.display = 'block';
    }
    if (inputEl) {
        inputEl.style.borderColor = 'var(--danger)';
    }
}

function clearSupFieldError(fieldId) {
    var errorEl = document.getElementById(fieldId + 'Error');
    var inputEl = document.getElementById(fieldId);
    if (errorEl) {
        errorEl.textContent = '';
        errorEl.style.display = 'none';
    }
    if (inputEl) {
        inputEl.style.borderColor = '';
    }
}

// Strip non-digits from phone input
document.getElementById('supPhone').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    clearSupFieldError('supPhone');
});
document.getElementById('supName').addEventListener('input', function() {
    clearSupFieldError('supName');
});
</script>
