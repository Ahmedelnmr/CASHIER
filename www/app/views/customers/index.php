<!-- Customers List View -->

<div class="page-header">
    <div>
        <h1>👥 إدارة العملاء</h1>
        <p class="subtitle">قاعدة بيانات العملاء والمبيعات الآجلة</p>
    </div>
    <button class="btn btn-primary" onclick="openAddCustomerModal()">➕ إضافة عميل</button>
</div>

<?php if ($toastSuccess): ?>
<script>document.addEventListener('DOMContentLoaded', function() { showToast('<?= htmlspecialchars($toastSuccess) ?>', 'success'); });</script>
<?php endif; ?>
<?php if ($toastError): ?>
<script>document.addEventListener('DOMContentLoaded', function() { showToast('<?= htmlspecialchars($toastError) ?>', 'error'); });</script>
<?php endif; ?>

<!-- Search -->
<div class="card" style="padding:14px 20px;">
    <form style="display:flex;gap:12px;" method="GET">
        <input type="hidden" name="page" value="customers">
        <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو رقم الهاتف..." value="<?= htmlspecialchars($search) ?>" style="max-width:350px;">
        <button type="submit" class="btn btn-outline">بحث</button>
        <?php if ($search): ?>
        <a href="?page=customers" class="btn btn-ghost">الكل</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <?php if (!empty($customers)): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>الرصيد (آجل)</th>
                    <th>الحد الائتماني</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                <tr>
                    <td><?= $c['id'] ?></td>
                    <td class="fw-bold"><a href="?page=customers&action=profile&id=<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></a></td>
                    <td><?= htmlspecialchars($c['phone'] ?: '—') ?></td>
                    <td>
                        <?php if ($c['balance'] > 0): ?>
                        <span class="badge badge-warning"><?= number_format($c['balance'], 2) ?></span>
                        <?php else: ?>
                        <span class="text-muted">0.00</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $c['credit_limit'] > 0 ? number_format($c['credit_limit'], 2) : '—' ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="?page=customers&action=profile&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline">📄</a>
                            <a href="?page=customers&action=delete&id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد؟')">🗑️</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <p>لا يوجد عملاء<?= $search ? ' مطابقون للبحث' : '' ?></p>
    </div>
    <?php endif; ?>
</div>

<!-- Add Customer Modal -->
<div class="modal-overlay" id="addCustomerModal">
    <div class="modal">
        <div class="modal-header">
            <h3>إضافة عميل جديد</h3>
            <button class="modal-close" onclick="closeModal('addCustomerModal')">&times;</button>
        </div>
        <form id="addCustomerForm" method="POST" action="?page=customers&action=store" onsubmit="return validateCustomerForm('add');">
            <div class="modal-body">
                <div class="form-group">
                    <label>اسم العميل *</label>
                    <input type="text" name="name" class="form-control" id="addCustName" required>
                    <small class="field-error" id="addCustNameError" style="color:var(--danger);display:none;"></small>
                </div>
                <div class="form-group">
                    <label>رقم الهاتف</label>
                    <input type="text" name="phone" class="form-control" id="addCustPhone" maxlength="15" dir="ltr">
                    <small class="field-error" id="addCustPhoneError" style="color:var(--danger);display:none;"></small>
                </div>
                <div class="form-group">
                    <label>العنوان</label>
                    <input type="text" name="address" class="form-control" id="addCustAddress" maxlength="255">
                </div>
                <div class="form-group">
                    <label>الحد الائتماني</label>
                    <input type="number" name="credit_limit" class="form-control" step="0.01" min="0" max="999999.99" value="0" dir="ltr">
                    <small class="text-muted">0 = بلا حد</small>
                </div>
                <div class="form-group">
                    <label>ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="2" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addCustomerModal')">إلغاء</button>
                <button type="submit" class="btn btn-primary">إضافة</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddCustomerModal() {
    document.getElementById('addCustomerForm').reset();
    clearCustFieldError('addCustName');
    clearCustFieldError('addCustPhone');
    openModal('addCustomerModal');
}

function validateCustomerForm(prefix) {
    var valid = true;
    var nameId = prefix + 'CustName';
    var phoneId = prefix + 'CustPhone';
    var name = document.getElementById(nameId).value.trim();
    var phone = document.getElementById(phoneId).value.trim();

    clearCustFieldError(nameId);
    clearCustFieldError(phoneId);

    // Name validation
    if (!name) {
        showCustFieldError(nameId, 'اسم العميل مطلوب');
        valid = false;
    } else if (name.length < 2) {
        showCustFieldError(nameId, 'الاسم يجب أن يكون حرفين على الأقل');
        valid = false;
    } else if (name.length > 100) {
        showCustFieldError(nameId, 'الاسم يجب أن لا يتجاوز 100 حرف');
        valid = false;
    } else if (/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/.test(name)) {
        showCustFieldError(nameId, 'الاسم يحتوي على أحرف غير صالحة');
        valid = false;
    } else if (/\s{2,}/.test(name)) {
        showCustFieldError(nameId, 'الاسم يحتوي على مسافات متكررة');
        valid = false;
    }

    // Phone validation
    if (phone && !/^[0-9]{7,15}$/.test(phone)) {
        showCustFieldError(phoneId, 'رقم الهاتف يجب أن يحتوي على أرقام فقط (7-15 رقم)');
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

// Strip non-digits from phone inputs
document.getElementById('addCustPhone').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    clearCustFieldError('addCustPhone');
});
document.getElementById('addCustName').addEventListener('input', function() {
    clearCustFieldError('addCustName');
});
</script>
