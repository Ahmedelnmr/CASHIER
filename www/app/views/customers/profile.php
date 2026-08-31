<!-- Customer Profile View -->

<div class="page-header">
    <div>
        <h1>📄 ملف العميل — <?= htmlspecialchars($customer['name']) ?></h1>
        <p class="subtitle"><?= htmlspecialchars($customer['phone'] ?: '') ?> | <?= htmlspecialchars($customer['address'] ?: '') ?></p>
    </div>
    <div class="btn-group">
        <button class="btn btn-success" onclick="openModal('paymentModal')">💰 تسجيل تسديد</button>
        <button class="btn btn-outline" onclick="openEditCustomerModal()">✏️ تعديل</button>
        <a href="?page=customers" class="btn btn-outline">← العودة</a>
    </div>
</div>

<?php if ($toastSuccess): ?>
<script>document.addEventListener('DOMContentLoaded', function() { showToast('<?= htmlspecialchars($toastSuccess) ?>', 'success'); });</script>
<?php endif; ?>
<?php if ($toastError): ?>
<script>document.addEventListener('DOMContentLoaded', function() { showToast('<?= htmlspecialchars($toastError) ?>', 'error'); });</script>
<?php endif; ?>

<!-- Customer Stats -->
<div class="stats-grid">
    <div class="stat-card" style="border:2px solid <?= $customer['balance'] > 0 ? 'var(--warning)' : 'var(--success)' ?>;">
        <div class="stat-info">
            <h4 class="<?= $customer['balance'] > 0 ? 'text-warning' : 'text-success' ?>"><?= number_format($customer['balance'], 2) ?></h4>
            <p>الرصيد الحالي (آجل)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h4><?= $customer['credit_limit'] > 0 ? number_format($customer['credit_limit'], 2) : 'بلا حد' ?></h4>
            <p>الحد الائتماني</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h4><?= $customer['created_at'] ?></h4>
            <p>تاريخ التسجيل</p>
        </div>
    </div>
</div>

<!-- Transaction History -->
<div class="card">
    <div class="card-header">
        <h3>📋 حركة الحساب</h3>
    </div>
    <?php if (!empty($transactions)): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>التاريخ</th>
                    <th>النوع</th>
                    <th>المبلغ</th>
                    <th>الرصيد بعد</th>
                    <th>الوصف</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><?= $t['id'] ?></td>
                    <td class="fs-sm"><?= $t['created_at'] ?></td>
                    <td>
                        <?php
                        $typeLabels = ['sale' => 'بيع آجل', 'payment' => 'تسديد', 'credit' => 'دائن', 'adjustment' => 'تعديل'];
                        $typeBadges = ['sale' => 'badge-warning', 'payment' => 'badge-success', 'credit' => 'badge-info', 'adjustment' => 'badge-purple'];
                        ?>
                        <span class="badge <?= $typeBadges[$t['type']] ?? 'badge-info' ?>"><?= $typeLabels[$t['type']] ?? $t['type'] ?></span>
                    </td>
                    <td class="fw-bold <?= $t['type'] === 'payment' ? 'text-success' : 'text-danger' ?>">
                        <?= $t['type'] === 'payment' ? '-' : '+' ?><?= number_format($t['amount'], 2) ?>
                    </td>
                    <td><?= number_format($t['balance_after'], 2) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($t['description'] ?: '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <p>لا توجد حركات</p>
    </div>
    <?php endif; ?>
</div>

<!-- Payment Modal -->
<div class="modal-overlay" id="paymentModal">
    <div class="modal">
        <div class="modal-header">
            <h3>💰 تسجيل تسديد — <?= htmlspecialchars($customer['name']) ?></h3>
            <button class="modal-close" onclick="closeModal('paymentModal')">&times;</button>
        </div>
        <form method="POST" action="?page=customers&action=payment" onsubmit="return validatePaymentForm();">
            <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label>المبلغ *</label>
                    <input type="number" name="amount" class="form-control" id="paymentAmount" step="0.01" min="0.01" max="999999.99" required placeholder="0.00" dir="ltr">
                    <small class="field-error" id="paymentAmountError" style="color:var(--danger);display:none;"></small>
                </div>
                <div class="form-group">
                    <label>وصف</label>
                    <input type="text" name="description" class="form-control" placeholder="تسديد نقدي / تحويل بنكي ..." maxlength="255">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('paymentModal')">إلغاء</button>
                <button type="submit" class="btn btn-success">تسجيل التسديد</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal-overlay" id="editCustomerModal">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ تعديل بيانات العميل</h3>
            <button class="modal-close" onclick="closeModal('editCustomerModal')">&times;</button>
        </div>
        <form id="editCustomerForm" method="POST" action="?page=customers&action=update" onsubmit="return validateEditCustomerForm();">
            <input type="hidden" name="id" value="<?= $customer['id'] ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label>الاسم *</label>
                    <input type="text" name="name" class="form-control" id="editCustName" required value="<?= htmlspecialchars($customer['name']) ?>">
                    <small class="field-error" id="editCustNameError" style="color:var(--danger);display:none;"></small>
                </div>
                <div class="form-group">
                    <label>الهاتف</label>
                    <input type="text" name="phone" class="form-control" id="editCustPhone" maxlength="15" dir="ltr" value="<?= htmlspecialchars($customer['phone']) ?>">
                    <small class="field-error" id="editCustPhoneError" style="color:var(--danger);display:none;"></small>
                </div>
                <div class="form-group">
                    <label>العنوان</label>
                    <input type="text" name="address" class="form-control" maxlength="255" value="<?= htmlspecialchars($customer['address']) ?>">
                </div>
                <div class="form-group">
                    <label>الحد الائتماني</label>
                    <input type="number" name="credit_limit" class="form-control" step="0.01" min="0" max="999999.99" value="<?= $customer['credit_limit'] ?>" dir="ltr">
                </div>
                <div class="form-group">
                    <label>ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="2" maxlength="500"><?= htmlspecialchars($customer['notes']) ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editCustomerModal')">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditCustomerModal() {
    clearCustFieldError('editCustName');
    clearCustFieldError('editCustPhone');
    openModal('editCustomerModal');
}

function validateEditCustomerForm() {
    var valid = true;
    var name = document.getElementById('editCustName').value.trim();
    var phone = document.getElementById('editCustPhone').value.trim();

    clearCustFieldError('editCustName');
    clearCustFieldError('editCustPhone');

    if (!name) {
        showCustFieldError('editCustName', 'اسم العميل مطلوب');
        valid = false;
    } else if (name.length < 2) {
        showCustFieldError('editCustName', 'الاسم يجب أن يكون حرفين على الأقل');
        valid = false;
    } else if (name.length > 100) {
        showCustFieldError('editCustName', 'الاسم يجب أن لا يتجاوز 100 حرف');
        valid = false;
    } else if (/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/.test(name)) {
        showCustFieldError('editCustName', 'الاسم يحتوي على أحرف غير صالحة');
        valid = false;
    } else if (/\s{2,}/.test(name)) {
        showCustFieldError('editCustName', 'الاسم يحتوي على مسافات متكررة');
        valid = false;
    }

    if (phone && !/^[0-9]{7,15}$/.test(phone)) {
        showCustFieldError('editCustPhone', 'رقم الهاتف يجب أن يحتوي على أرقام فقط (7-15 رقم)');
        valid = false;
    }

    return valid;
}

function validatePaymentForm() {
    var valid = true;
    var amount = document.getElementById('paymentAmount').value;

    clearCustFieldError('paymentAmount');

    if (!amount || parseFloat(amount) <= 0) {
        showCustFieldError('paymentAmount', 'أدخل مبلغ صحيح');
        valid = false;
    } else if (parseFloat(amount) > 999999.99) {
        showCustFieldError('paymentAmount', 'المبلغ يتجاوز الحد المسموح');
        valid = false;
    }

    return valid;
}

function showCustFieldError(fieldId, message) {
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

function clearCustFieldError(fieldId) {
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
document.getElementById('editCustPhone').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    clearCustFieldError('editCustPhone');
});
document.getElementById('editCustName').addEventListener('input', function() {
    clearCustFieldError('editCustName');
});
</script>
