<?php require_once 'app/views/layout/header.php'; ?>

<div class="page-header">
    <h1>💰 الخزنة (المالية)</h1>
    <div style="display:flex; gap:10px;">
        <button class="btn btn-outline" onclick="openReport()">📅 تقرير حركة</button>
        <button class="btn btn-primary" onclick="openTransactionModal()">+ إضافة عملية</button>
    </div>
</div>

<!-- Balance Card -->
<div class="row">
    <div class="col-md-4">
        <div class="card bg-dark text-white text-center p-4">
            <h3>الرصيد الحالي</h3>
            <h1 class="display-4 text-success"><?= number_format($balance, 2) ?></h1>
            <small>جنية مصري</small>
        </div>
    </div>
</div>

<br>

<!-- Transactions Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3>حركة الخزينة لشهر <?= date('F Y', strtotime($month)) ?></h3>
        <div>
            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="page" value="safe">
                <input type="month" name="month" class="form-control" value="<?= $month ?>" onchange="this.form.submit()">
                <select name="type" class="form-control" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    <option value="expense" <?= $type == 'expense' ? 'selected' : '' ?>>مصروفات</option>
                    <option value="withdrawal" <?= $type == 'withdrawal' ? 'selected' : '' ?>>سحب شخصي</option>
                    <option value="advance" <?= $type == 'advance' ? 'selected' : '' ?>>سلف موظفين</option>
                    <option value="deposit" <?= $type == 'deposit' ? 'selected' : '' ?>>إيداع</option>
                    <option value="sale" <?= $type == 'sale' ? 'selected' : '' ?>>مبيعات</option>
                    <option value="purchase" <?= $type == 'purchase' ? 'selected' : '' ?>>مشتريات</option>
                </select>
            </form>
        </div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>التاريخ</th>
                <th>النوع</th>
                <th>المبلغ</th>
                <th>الوصف / الملاحظات</th>
                <th>الموظف</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $t): ?>
            <tr>
                <td><?= $t['id'] ?></td>
                <td><?= date('d/m/Y', strtotime($t['date'])) ?></td>
                <td>
                    <?php
                        switch($t['type']) {
                            case 'expense': echo '<span class="badge badge-warning">مصروف</span>'; break;
                            case 'withdrawal': echo '<span class="badge badge-danger">سحب شخصي</span>'; break;
                            case 'advance': echo '<span class="badge badge-info">سلفة</span>'; break;
                            case 'salary': echo '<span class="badge badge-success">راتب</span>'; break;
                            case 'deposit': echo '<span class="badge badge-primary">إيداع</span>'; break;
                            case 'sale': echo '<span class="badge badge-success" style="background:#27ae60">مبيعات</span>'; break;
                            case 'purchase': echo '<span class="badge badge-warning" style="background:#d35400">مشتريات</span>'; break;
                            default: echo $t['type'];
                        }
                    ?>
                </td>
                <td style="font-weight:bold; <?= in_array($t['type'], ['deposit', 'opening', 'sale']) ? 'color:green' : 'color:red' ?>">
                    <?= number_format($t['amount'], 2) ?>
                </td>
                <td><?= htmlspecialchars($t['description']) ?></td>
                <td><?= htmlspecialchars($t['employee_name'] ?? '-') ?></td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="deleteTransaction(<?= $t['id'] ?>)">🗑️</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Transaction Modal -->
<div class="modal-overlay" id="transactionModal">
    <div class="modal">
        <div class="modal-header">
            <h3>إضافة عملية مالية</h3>
            <button class="modal-close" onclick="closeModal('transactionModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="transactionForm">
                <div class="form-group">
                    <label>نوع العملية *</label>
                    <select class="form-control" id="transType" onchange="toggleEmployeeSelect()" required>
                        <option value="expense">🛒 مصروفات تشغيل (كهرباء، صيانة...)</option>
                        <option value="withdrawal">👤 سحب شخصي (للمالك)</option>
                        <option value="advance">💸 سلفة موظف</option>
                        <option value="salary">💰 سداد راتب شهري</option>
                        <option value="deposit">💰 إيداع في الخزنة</option>
                    </select>
                </div>
                
                <div class="form-group" id="employeeSelectGroup" style="display:none;">
                    <label>الموظف *</label>
                    <select class="form-control" id="transEmployee">
                        <option value="">اختر الموظف</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>المبلغ *</label>
                    <input type="number" class="form-control" id="transAmount" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label>التاريخ</label>
                    <input type="date" class="form-control" id="transDate" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label>الوصف / ملاحظات</label>
                    <textarea class="form-control" id="transDesc" rows="2"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('transactionModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="saveTransaction()">حفظ العملية</button>
        </div>
    </div>
</div>

<script>
function openTransactionModal() {
    document.getElementById('transactionForm').reset();
    toggleEmployeeSelect();
    openModal('transactionModal');
}

function toggleEmployeeSelect() {
    const type = document.getElementById('transType').value;
    const empGroup = document.getElementById('employeeSelectGroup');
    if (type === 'advance' || type === 'salary') {
        empGroup.style.display = 'block';
    } else {
        empGroup.style.display = 'none';
        document.getElementById('transEmployee').value = '';
    }
}

function saveTransaction() {
    const type = document.getElementById('transType').value;
    const amount = document.getElementById('transAmount').value;
    const date = document.getElementById('transDate').value;
    const desc = document.getElementById('transDesc').value;
    const empId = document.getElementById('transEmployee').value;
    
    if (!amount || amount <= 0) {
        showToast('أدخل مبلغ صحيح', 'error');
        return;
    }
    
    if ((type === 'advance' || type === 'salary') && !empId) {
        showToast('يجب اختيار الموظف', 'error');
        return;
    }
    
    apiRequest('?page=safe&action=store', {
        method: 'POST',
        body: { type: type, amount: amount, date: date, description: desc, employee_id: empId }
    }).then(res => {
        if(res.success) {
            showToast(res.message);
            closeModal('transactionModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(res.message, 'error');
        }
    });
}

function deleteTransaction(id) {
    if(confirm('هل أنت متأكد من حذف هذه العملية؟ سيتم تعديل الرصيد.')) {
        apiRequest('?page=safe&action=delete&id=' + id)
            .then(res => {
                if(res.success) showToast(res.message); 
                else showToast(res.message, 'error');
                setTimeout(() => location.reload(), 1000);
            });
    }
}
</script>

<?php require_once 'app/views/layout/footer.php'; ?>
