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
            <form id="employeeForm">
                <input type="hidden" id="empId" name="id">
                <div class="form-group">
                    <label>الاسم *</label>
                    <input type="text" class="form-control" id="empName" name="name" required>
                </div>
                <div class="form-group">
                    <label>التليفون</label>
                    <input type="text" class="form-control" id="empPhone" name="phone">
                </div>
                <div class="form-group">
                    <label>الراتب الأساسي</label>
                    <input type="number" class="form-control" id="empSalary" name="base_salary" step="0.01">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('employeeModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="saveEmployee()">حفظ</button>
        </div>
    </div>
</div>

<script>
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
    openModal('employeeModal');
}

function editEmployee(emp) {
    document.getElementById('modalTitle').textContent = 'تعديل موظف';
    document.getElementById('empId').value = emp.id;
    document.getElementById('empName').value = emp.name;
    document.getElementById('empPhone').value = emp.phone;
    document.getElementById('empSalary').value = emp.base_salary;
    openModal('employeeModal');
}

function saveEmployee() {
    var id = document.getElementById('empId').value;
    var name = document.getElementById('empName').value;
    var phone = document.getElementById('empPhone').value;
    var salary = document.getElementById('empSalary').value;
    
    if (!name) {
        showToast('الاسم مطلوب', 'error');
        return;
    }
    
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
</script>

<?php require_once 'app/views/layout/footer.php'; ?>
