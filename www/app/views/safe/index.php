<?php require_once 'app/views/layout/header.php'; ?>

<div class="page-header">
    <div>
        <h1>الخزنة</h1>
        <p class="subtitle">إدارة الحركات المالية</p>
    </div>
    <div class="btn-group">
        <button class="btn btn-outline" onclick="openReport()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            تقرير حركة
        </button>
        <button class="btn btn-primary" onclick="openTransactionModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            إضافة عملية
        </button>
    </div>
</div>

<!-- Balance Card -->
<div class="card" style="background:linear-gradient(135deg, #0f2140 0%, #152d54 100%);border:none;text-align:center;padding:30px;">
    <div style="color:rgba(255,255,255,0.7);font-size:14px;margin-bottom:8px;">الرصيد الحالي</div>
    <div style="color:#5ee2db;font-size:36px;font-weight:800;direction:ltr;"><?= number_format($balance, 2) ?></div>
    <div style="color:rgba(255,255,255,0.5);font-size:13px;margin-top:4px;">جنيه مصري</div>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-header">
        <h3>حركة الخزينة — <?= date('F Y', strtotime($month)) ?></h3>
        <div>
            <form method="GET" style="display:flex;gap:8px;">
                <input type="hidden" name="page" value="safe">
                <input type="month" name="month" class="form-control" value="<?= $month ?>" onchange="this.form.submit()" style="width:160px;">
                <select name="type" class="form-control" onchange="this.form.submit()" style="width:150px;">
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
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>التاريخ</th>
                    <th>النوع</th>
                    <th>المبلغ</th>
                    <th>الوصف</th>
                    <th>الموظف</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                <tr>
                    <td class="text-muted"><?= $t['id'] ?></td>
                    <td><?= date('d/m/Y', strtotime($t['date'])) ?></td>
                    <td>
                        <?php
                            switch($t['type']) {
                                case 'expense': echo '<span class="badge badge-warning">مصروف</span>'; break;
                                case 'withdrawal': echo '<span class="badge badge-danger">سحب شخصي</span>'; break;
                                case 'advance': echo '<span class="badge badge-info">سلفة</span>'; break;
                                case 'salary': echo '<span class="badge badge-success">راتب</span>'; break;
                                case 'deposit': echo '<span class="badge badge-teal">إيداع</span>'; break;
                                case 'sale': echo '<span class="badge badge-success">مبيعات</span>'; break;
                                case 'purchase': echo '<span class="badge badge-warning">مشتريات</span>'; break;
                                default: echo '<span class="badge">' . $t['type'] . '</span>';
                            }
                        ?>
                    </td>
                    <td style="font-weight:bold; <?= in_array($t['type'], ['deposit', 'opening', 'sale']) ? 'color:var(--success)' : 'color:var(--danger)' ?>">
                        <?= number_format($t['amount'], 2) ?>
                    </td>
                    <td class="text-muted fs-sm"><?= htmlspecialchars($t['description']) ?></td>
                    <td><?= htmlspecialchars($t['employee_name'] ?? '-') ?></td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="deleteTransaction(<?= $t['id'] ?>)">🗑️</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
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
                        <option value="expense">مصروفات تشغيل</option>
                        <option value="withdrawal">سحب شخصي (للمالك)</option>
                        <option value="advance">سلفة موظف</option>
                        <option value="salary">سداد راتب شهري</option>
                        <option value="deposit">إيداع في الخزنة</option>
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
    var type = document.getElementById('transType').value;
    var empGroup = document.getElementById('employeeSelectGroup');
    if (type === 'advance' || type === 'salary') {
        empGroup.style.display = 'block';
    } else {
        empGroup.style.display = 'none';
        document.getElementById('transEmployee').value = '';
    }
}

function saveTransaction() {
    var type = document.getElementById('transType').value;
    var amount = document.getElementById('transAmount').value;
    var date = document.getElementById('transDate').value;
    var desc = document.getElementById('transDesc').value;
    var empId = document.getElementById('transEmployee').value;
    
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
    }).then(function(res) {
        if (res.success) {
            showToast(res.message);
            closeModal('transactionModal');
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            showToast(res.message, 'error');
        }
    });
}

function deleteTransaction(id) {
    if (confirm('هل أنت متأكد من حذف هذه العملية؟')) {
        apiRequest('?page=safe&action=delete&id=' + id)
            .then(function(res) {
                if (res.success) showToast(res.message); 
                else showToast(res.message, 'error');
                setTimeout(function() { location.reload(); }, 1000);
            });
    }
}

function openReport() {
    // Placeholder for report functionality
    showToast('قريباً', 'warning');
}
</script>

<?php require_once 'app/views/layout/footer.php'; ?>
