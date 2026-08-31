<?php require_once 'app/views/layout/header.php'; ?>

<div class="page-header">
    <div>
        <h1>إدارة الموظفين</h1>
        <p class="subtitle">إجمالي الموظفين: <?= count($employees) ?></p>
    </div>
    <button class="btn btn-primary" onclick="openEmployeeModal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        إضافة موظف
    </button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>التليفون</th>
                    <th>الراتب الأساسي</th>
                    <th>الرصيد الحالي</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp): ?>
                <tr>
                    <td class="text-muted"><?= $emp['id'] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($emp['name']) ?></td>
                    <td><?= htmlspecialchars($emp['phone']) ?></td>
                    <td><?= number_format($emp['base_salary'], 2) ?></td>
                    <td style="font-weight:bold; direction:ltr; text-align:right;">
                        <?php 
                            $bal = $emp['ledger_balance'];
                            $color = $bal >= 0 ? 'var(--success)' : 'var(--danger)';
                            $sign = $bal >= 0 ? '+' : '';
                        ?>
                        <span style="color:<?= $color ?>">
                            <?= $sign . number_format($bal, 2) ?>
                            <br>
                            <small style="font-size:0.8em; color:var(--text-muted)">
                                <?= $bal >= 0 ? 'له (مستحق)' : 'عليه (مديونية)' ?>
                            </small>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline" onclick='showHistory(<?= $emp['id'] ?>, "<?= htmlspecialchars($emp['name']) ?>")'>📜 التفاصيل</button>
                            <?php if ($emp['ledger_balance'] < 0): ?>
                            <button class="btn btn-sm btn-success" onclick='openSettleModal(<?= $emp['id'] ?>, "<?= htmlspecialchars($emp['name']) ?>", <?= abs($emp['ledger_balance']) ?>)'>💰 تسوية</button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-ghost" onclick='editEmployee(<?= json_encode($emp) ?>)'>✏️</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteEmployee(<?= $emp['id'] ?>)">🗑️</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- History Modal -->
<div class="modal-overlay" id="historyModal">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header">
            <h3>سجل حركة: <span id="historyEmpName"></span></h3>
            <button class="modal-close" onclick="closeModal('historyModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>العملية</th>
                            <th>المبلغ</th>
                            <th>التفاصيل</th>
                        </tr>
                    </thead>
                    <tbody id="historyBody">
                        <tr><td colspan="4" class="text-center text-muted">جاري التحميل...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="closeModal('historyModal')">إغلاق</button>
        </div>
    </div>
</div>

<!-- Employee Modal -->
<div class="modal-overlay" id="employeeModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">إضافة موظف</h3>
            <button class="modal-close" onclick="closeModal('employeeModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="employeeForm" onsubmit="return false;">
                <input type="hidden" id="empId" name="id">
                <div class="form-group">
                    <label>الاسم *</label>
                    <input type="text" class="form-control" id="empName" name="name" required>
                    <small class="field-error" id="empNameError" style="color:var(--danger);display:none;"></small>
                </div>
                <div class="form-group">
                    <label>التليفون</label>
                    <input type="text" class="form-control" id="empPhone" name="phone" maxlength="15" dir="ltr">
                    <small class="field-error" id="empPhoneError" style="color:var(--danger);display:none;"></small>
                </div>
                <div class="form-group">
                    <label>الراتب الأساسي</label>
                    <input type="number" class="form-control" id="empSalary" name="base_salary" step="0.01" min="0" max="999999.99" dir="ltr">
                    <small class="field-error" id="empSalaryError" style="color:var(--danger);display:none;"></small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('employeeModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="saveEmployee()">حفظ</button>
        </div>
    </div>
</div>

<!-- Debt Settlement Modal -->
<div class="modal-overlay" id="settleModal">
    <div class="modal" style="min-width:450px;">
        <div class="modal-header">
            <h3>💰 تسوية مديونية موظف</h3>
            <button class="modal-close" onclick="closeModal('settleModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="background:var(--bg-secondary);padding:12px;border-radius:8px;margin-bottom:16px;">
                <p><strong>الموظف:</strong> <span id="settleEmpName"></span></p>
                <p><strong>المديونية الحالية:</strong> <span id="settleDebt" style="color:var(--danger);font-weight:bold;direction:ltr;display:inline-block;"></span> جنيه</p>
            </div>
            <div class="form-group">
                <label>مبلغ التسوية *</label>
                <input type="number" class="form-control" id="settleAmount" step="0.01" required>
            </div>
            <div class="form-group">
                <label>ملاحظات</label>
                <textarea class="form-control" id="settleNotes" rows="2" placeholder="سبب التسوية..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('settleModal')">إلغاء</button>
            <button class="btn btn-success" onclick="confirmSettle()">تسوية المديونية</button>
        </div>
    </div>
</div>

<script>
// Employee form validation
function validateEmployeeForm() {
    var valid = true;
    var name = document.getElementById('empName').value.trim();
    var phone = document.getElementById('empPhone').value.trim();
    var salary = document.getElementById('empSalary').value;

    // Clear errors
    clearFieldError('empName');
    clearFieldError('empPhone');
    clearFieldError('empSalary');

    // Name validation
    if (!name) {
        showFieldError('empName', 'الاسم مطلوب');
        valid = false;
    } else if (name.length < 2) {
        showFieldError('empName', 'الاسم يجب أن يكون حرفين على الأقل');
        valid = false;
    } else if (name.length > 100) {
        showFieldError('empName', 'الاسم يجب أن لا يتجاوز 100 حرف');
        valid = false;
    } else if (/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/.test(name)) {
        showFieldError('empName', 'الاسم يحتوي على أحرف غير صالحة');
        valid = false;
    } else if (/\s{2,}/.test(name)) {
        showFieldError('empName', 'الاسم يحتوي على مسافات متكررة');
        valid = false;
    }

    // Phone validation (optional but if provided must be digits only)
    if (phone && !/^[0-9]{7,15}$/.test(phone)) {
        showFieldError('empPhone', 'رقم الهاتف يجب أن يحتوي على أرقام فقط (7-15 رقم)');
        valid = false;
    }

    // Salary validation
    if (salary !== '' && salary !== null) {
        if (isNaN(salary) || parseFloat(salary) < 0) {
            showFieldError('empSalary', 'الراتب يجب أن يكون رقم موجب');
            valid = false;
        } else if (parseFloat(salary) > 999999.99) {
            showFieldError('empSalary', 'الراتب يتجاوز الحد المسموح');
            valid = false;
        }
    }

    return valid;
}

function showFieldError(fieldId, message) {
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

function clearFieldError(fieldId) {
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

// Clear errors on input
document.getElementById('empName').addEventListener('input', function() { clearFieldError('empName'); });
document.getElementById('empPhone').addEventListener('input', function() {
    clearFieldError('empPhone');
    // Strip non-digits on input
    this.value = this.value.replace(/[^0-9]/g, '');
});
document.getElementById('empSalary').addEventListener('input', function() { clearFieldError('empSalary'); });

function showHistory(id, name) {
    document.getElementById('historyEmpName').textContent = name;
    openModal('historyModal');
    
    apiRequest('?page=employees&action=history&id=' + id)
        .then(function(res) {
            if (res.success) {
                var tbody = document.getElementById('historyBody');
                if (res.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">لا توجد حركات مسجلة</td></tr>';
                    return;
                }
                
                var rows = '';
                for (var i = 0; i < res.data.length; i++) {
                    var t = res.data[i];
                    var color = 'var(--text-primary)';
                    var sign = '';
                    if (t.type === 'accrual' || t.type === 'salary') {
                        color = 'var(--success)'; sign = '+';
                    } else {
                        color = 'var(--danger)'; sign = '-';
                    }
                    
                    rows += '<tr>' +
                        '<td>' + t.date + '</td>' +
                        '<td><span class="badge ' + (t.type === 'accrual' ? 'badge-success' : t.type === 'salary' ? 'badge-teal' : 'badge-warning') + '">' + t.type_label + '</span></td>' +
                        '<td style="color:' + color + ';font-weight:bold;direction:ltr;text-align:right;">' + sign + parseFloat(t.amount).toFixed(2) + '</td>' +
                        '<td class="text-muted">' + (t.description || '-') + '</td>' +
                        '</tr>';
                }
                tbody.innerHTML = rows;
            } else {
                showToast(res.message, 'error');
            }
        });
}

function openEmployeeModal() {
    document.getElementById('modalTitle').textContent = 'إضافة موظف';
    document.getElementById('employeeForm').reset();
    document.getElementById('empId').value = '';
    clearFieldError('empName');
    clearFieldError('empPhone');
    clearFieldError('empSalary');
    openModal('employeeModal');
}

function editEmployee(emp) {
    document.getElementById('modalTitle').textContent = 'تعديل موظف';
    document.getElementById('empId').value = emp.id;
    document.getElementById('empName').value = emp.name;
    document.getElementById('empPhone').value = emp.phone;
    document.getElementById('empSalary').value = emp.base_salary;
    clearFieldError('empName');
    clearFieldError('empPhone');
    clearFieldError('empSalary');
    openModal('employeeModal');
}

function saveEmployee() {
    if (!validateEmployeeForm()) {
        return;
    }
    
    var id = document.getElementById('empId').value;
    var name = document.getElementById('empName').value.trim();
    var phone = document.getElementById('empPhone').value.trim();
    var salary = document.getElementById('empSalary').value;
    
    var url = id ? '?page=employees&action=update' : '?page=employees&action=store';
    
    apiRequest(url, {
        method: 'POST',
        body: { id: id, name: name, phone: phone, base_salary: salary }
    }).then(function(res) {
        if (res.success) {
            showToast(res.message);
            closeModal('employeeModal');
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            showToast(res.message, 'error');
        }
    });
}

function deleteEmployee(id) {
    if (confirm('هل أنت متأكد من حذف هذا الموظف؟')) {
        apiRequest('?page=employees&action=delete&id=' + id)
            .then(function(res) {
                if (res.success) {
                    showToast(res.message);
                    location.reload();
                } else {
                    showToast(res.message, 'error');
                }
            });
    }
}

var settleEmployeeId = 0;
var settleMaxAmount = 0;

function openSettleModal(id, name, maxAmount) {
    settleEmployeeId = id;
    settleMaxAmount = maxAmount;
    document.getElementById('settleEmpName').textContent = name;
    document.getElementById('settleDebt').textContent = parseFloat(maxAmount).toFixed(2);
    document.getElementById('settleAmount').value = '';
    document.getElementById('settleAmount').max = maxAmount;
    document.getElementById('settleNotes').value = '';
    openModal('settleModal');
}

function confirmSettle() {
    var amount = parseFloat(document.getElementById('settleAmount').value);
    var notes = document.getElementById('settleNotes').value;
    
    if (!amount || amount <= 0) {
        showToast('أدخل مبلغ صحيح', 'error');
        return;
    }
    
    if (amount > settleMaxAmount + 0.01) {
        showToast('المبلغ يتجاوز المديونية (' + parseFloat(settleMaxAmount).toFixed(2) + ')', 'error');
        return;
    }
    
    apiRequest('?page=safe&action=store', {
        method: 'POST',
        body: { 
            type: 'debt_settlement', 
            amount: amount, 
            date: new Date().toISOString().split('T')[0], 
            description: notes || 'تسوية مديونية موظف', 
            employee_id: settleEmployeeId 
        }
    }).then(function(res) {
        if (res.success) {
            showToast(res.message);
            closeModal('settleModal');
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            showToast(res.message, 'error');
        }
    });
}
</script>

<?php require_once 'app/views/layout/footer.php'; ?>
