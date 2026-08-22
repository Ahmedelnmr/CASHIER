<?php require_once 'app/views/layout/header.php'; ?>

<div class="page-header">
    <h1>👥 إدارة الموظفين</h1>
    <button class="btn btn-primary" onclick="openEmployeeModal()">+ إضافة موظف</button>
</div>

<div class="card">
    <table class="table">
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
                <td><?= $emp['id'] ?></td>
                <td><?= htmlspecialchars($emp['name']) ?></td>
                <td><?= htmlspecialchars($emp['phone']) ?></td>
                <td><?= number_format($emp['base_salary'], 2) ?></td>
                <td style="font-weight:bold; direction:ltr; text-align:right;">
                    <?php 
                        $bal = $emp['ledger_balance'];
                        $color = $bal >= 0 ? 'green' : 'red';
                        $sign = $bal >= 0 ? '+' : '';
                    ?>
                    <span style="color:<?= $color ?>">
                        <?= $sign . number_format($bal, 2) ?>
                        <br>
                        <small style="font-size:0.8em; color:#666">
                            <?= $bal >= 0 ? 'له (مستحق)' : 'عليه (مديونية)' ?>
                        </small>
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-info" onclick='showHistory(<?= $emp['id'] ?>, "<?= htmlspecialchars($emp['name']) ?>")'>📜 التفاصيل</button>
                    <button class="btn btn-sm btn-outline" onclick='editEmployee(<?= json_encode($emp) ?>)'>تعديل</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteEmployee(<?= $emp['id'] ?>)">حذف</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- History Modal -->
<div class="modal-overlay" id="historyModal">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header">
            <h3>سجل حركة الموظف: <span id="historyEmpName"></span></h3>
            <button class="modal-close" onclick="closeModal('historyModal')">&times;</button>
        </div>
        <div class="modal-body">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>العملية</th>
                        <th>المبلغ</th>
                        <th>التفاصيل</th>
                    </tr>
                </thead>
                <tbody id="historyBody">
                    <tr><td colspan="4" class="text-center">جاري التحميل...</td></tr>
                </tbody>
            </table>
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
        .then(res => {
            if(res.success) {
                const tbody = document.getElementById('historyBody');
                if(res.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">لا توجد حركات مسجلة</td></tr>';
                    return;
                }
                
                tbody.innerHTML = res.data.map(t => {
                    let color = 'black';
                    let sign = '';
                    if(t.type === 'accrual' || t.type === 'salary') {
                        // Accrual = earned, Salary = received payment → both positive for employee
                        color = 'green'; sign = '+';
                    } else {
                        // Advance = deduction (سلفة)
                        color = 'red'; sign = '-';
                    }
                    
                    return `
                        <tr>
                            <td>${t.date}</td>
                            <td><span class="badge ${t.type === 'accrual' ? 'badge-success' : t.type === 'salary' ? 'badge-primary' : 'badge-warning'}">${t.type_label}</span></td>
                            <td style="color:${color};font-weight:bold;direction:ltr;text-align:right;">${sign}${parseFloat(t.amount).toFixed(2)}</td>
                            <td>${t.description || '-'}</td>
                        </tr>
                    `;
                }).join('');
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
    const id = document.getElementById('empId').value;
    const name = document.getElementById('empName').value;
    const phone = document.getElementById('empPhone').value;
    const salary = document.getElementById('empSalary').value;
    
    if(!name) {
        showToast('الاسم مطلوب', 'error');
        return;
    }
    
    const url = id ? '?page=employees&action=update' : '?page=employees&action=store';
    
    apiRequest(url, {
        method: 'POST',
        body: { id: id, name: name, phone: phone, base_salary: salary }
    }).then(res => {
        if(res.success) {
            showToast(res.message);
            closeModal('employeeModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(res.message, 'error');
        }
    });
}

function deleteEmployee(id) {
    if(confirm('هل أنت متأكد من حذف هذا الموظف؟')) {
        apiRequest('?page=employees&action=delete&id=' + id)
            .then(res => {
                if(res.success) {
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
