<?php require_once 'app/views/layout/header.php'; ?>

<div class="page-header">
    <div>
        <h1>الخزنة</h1>
        <p class="subtitle">إدارة الحركات المالية</p>
    </div>
    <div class="btn-group">
        <input type="date" id="reportFrom" class="form-control" style="width:140px;" value="<?= date('Y-m-01') ?>">
        <input type="date" id="reportTo" class="form-control" style="width:140px;" value="<?= date('Y-m-d') ?>">
        <button class="btn btn-outline" onclick="openReport()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            تقرير حركة
        </button>
        <button class="btn btn-outline" onclick="openReconcileModal()">📋 مطابقة نقدية</button>
        <button class="btn btn-primary" onclick="openTransactionModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            إضافة عملية
        </button>
    </div>
</div>

<!-- Balance Card -->
<div class="card" style="background:linear-gradient(135deg, <?= $balance < 0 ? '#8b0000 0%, #5c0000 100%' : '#0f2140 0%, #152d54 100%' ?>);border:none;text-align:center;padding:30px;">
    <div style="color:rgba(255,255,255,0.7);font-size:14px;margin-bottom:8px;">الرصيد الحالي</div>
    <div style="color:<?= $balance < 0 ? '#ff6b6b' : '#5ee2db' ?>;font-size:36px;font-weight:800;direction:ltr;"><?= number_format($balance, 2) ?></div>
    <div style="color:rgba(255,255,255,0.5);font-size:13px;margin-top:4px;">جنيه مصري</div>
    <?php if ($balance < 0): ?>
    <div style="color:#ff6b6b;font-size:12px;margin-top:8px;font-weight:600;">⚠️ رصيد سالب</div>
    <?php endif; ?>
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
                    <option value="funding" <?= $type == 'funding' ? 'selected' : '' ?>>تمويل الخزنة</option>
                    <option value="debt_settlement" <?= $type == 'debt_settlement' ? 'selected' : '' ?>>تسوية مديونية</option>
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
                <tr id="txn-<?= $t['id'] ?>" <?= $t['status'] === 'reversed' ? 'style="opacity:0.6;"' : '' ?>>
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
                                case 'funding': echo '<span class="badge badge-teal">تمويل الخزنة</span>'; break;
                                case 'debt_settlement': echo '<span class="badge badge-info">تسوية مديونية</span>'; break;
                                case 'bonus': echo '<span class="badge badge-success">مكافأة</span>'; break;
                                case 'other_payment': echo '<span class="badge">دفع آخر</span>'; break;
                                default: echo '<span class="badge">' . $t['type'] . '</span>';
                            }
                            if ($t['transaction_type'] === 'reversal') {
                                echo ' <span class="badge badge-info" style="font-size:10px;">عكس</span>';
                            }
                        ?>
                    </td>
                    <td style="font-weight:bold; <?= in_array($t['type'], ['deposit', 'opening', 'sale', 'debt_settlement', 'funding']) ? 'color:var(--success)' : 'color:var(--danger)' ?>">
                        <?= number_format($t['amount'], 2) ?>
                    </td>
                    <td class="text-muted fs-sm"><?= htmlspecialchars($t['description']) ?></td>
                    <td><?= htmlspecialchars($t['employee_name'] ?? '-') ?></td>
                    <td>
                        <?php if ($t['status'] === 'reversed'): ?>
                            <span class="badge badge-secondary">معكوس</span>
                        <?php elseif ($t['transaction_type'] === 'reversal'): ?>
                            <span class="badge badge-info">عكس العملية</span>
                            <?php if ($t['related_transaction_id']): ?>
                                <a href="#txn-<?= $t['related_transaction_id'] ?>" class="btn btn-sm btn-outline" title="العملية الأصلية">#<?= $t['related_transaction_id'] ?></a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn btn-sm btn-warning" onclick="openReverseModal(<?= $t['id'] ?>, '<?= htmlspecialchars($t['type']) ?>', <?= $t['amount'] ?>, '<?= htmlspecialchars($t['description'] ?? '') ?>')" title="عكس العملية">↩ عكس</button>
                        <?php endif; ?>
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
                        <option value="funding">تمويل الخزنة (من المالك)</option>
                        <option value="debt_settlement">تسوية مديونية موظف</option>
                        <option value="bonus">مكافأة موظف</option>
                        <option value="other_payment">دفع آخر</option>
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

<!-- Cash Movement Report Modal -->
<div class="modal-overlay" id="reportModal">
    <div class="modal" style="min-width:700px;max-width:900px;">
        <div class="modal-header">
            <h3>📊 تقرير حركة النقد</h3>
            <button class="modal-close" onclick="closeModal('reportModal')">&times;</button>
        </div>
        <div class="modal-body" id="reportContent">
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('reportModal')">إغلاق</button>
        </div>
    </div>
</div>

<!-- Reverse Transaction Modal -->
<div class="modal-overlay" id="reverseModal">
    <div class="modal" style="min-width:450px;">
        <div class="modal-header">
            <h3>🔄 عكس العملية المالية</h3>
            <button class="modal-close" onclick="closeModal('reverseModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="reverseDetails" style="background:var(--bg-secondary);padding:12px;border-radius:8px;margin-bottom:16px;"></div>
            <div class="form-group">
                <label>سبب العكس *</label>
                <textarea class="form-control" id="reverseReason" rows="3" placeholder="أدخل سبب عكس هذه العملية..." required></textarea>
            </div>
            <div style="background:var(--warning-bg,#fff3cd);padding:10px;border-radius:6px;font-size:13px;color:#856404;">
                ⚠️ سيتم الاحتفاظ بالعملية الأصلية وستظهر مرتبطة بالعملية المعكوسة في سجل الحركات.
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('reverseModal')">إلغاء</button>
            <button class="btn btn-warning" onclick="confirmReverse()">تأكيد العكس</button>
        </div>
    </div>
</div>

<!-- Cash Reconciliation Modal -->
<div class="modal-overlay" id="reconcileModal">
    <div class="modal" style="min-width:450px;">
        <div class="modal-header">
            <h3>📋 مطابقة نقدية</h3>
            <button class="modal-close" onclick="closeModal('reconcileModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="background:var(--bg-secondary);padding:12px;border-radius:8px;margin-bottom:16px;">
                <p><strong>الرصيد المتوقع:</strong> <span id="expectedBalance" style="direction:ltr;display:inline-block;"><?= number_format($balance, 2) ?></span> جنيه</p>
            </div>
            <div class="form-group">
                <label>المبلغ الفعلي (العد الفعلي) *</label>
                <input type="number" class="form-control" id="actualCount" step="0.01" required>
            </div>
            <div id="reconcileDiff" style="padding:10px;border-radius:6px;font-size:14px;font-weight:600;display:none;margin-bottom:12px;"></div>
            <div class="form-group">
                <label>ملاحظات</label>
                <textarea class="form-control" id="reconcileNotes" rows="2" placeholder="سبب العجز/الزيادة إن وجد..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('reconcileModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="confirmReconcile()">تسجيل المطابقة</button>
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
    if (type === 'advance' || type === 'salary' || type === 'salary_payment' || type === 'debt_settlement' || type === 'bonus') {
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

var reverseTransactionId = 0;

function openReverseModal(id, type, amount, description) {
    reverseTransactionId = id;
    document.getElementById('reverseDetails').innerHTML = 
        '<p><strong>رقم العملية:</strong> #' + id + '</p>' +
        '<p><strong>النوع:</strong> ' + type + '</p>' +
        '<p><strong>المبلغ:</strong> ' + parseFloat(amount).toFixed(2) + ' جنيه</p>' +
        '<p><strong>الوصف:</strong> ' + (description || '-') + '</p>';
    document.getElementById('reverseReason').value = '';
    openModal('reverseModal');
}

function confirmReverse() {
    var reason = document.getElementById('reverseReason').value.trim();
    if (!reason) {
        showToast('يجب إدخال سبب العكس', 'error');
        return;
    }
    
    apiRequest('?page=safe&action=reverse', {
        method: 'POST',
        body: { id: reverseTransactionId, reason: reason }
    }).then(function(res) {
        if (res.success) {
            showToast(res.message);
            closeModal('reverseModal');
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            showToast(res.message, 'error');
        }
    });
}

function openReport() {
    var from = document.getElementById('reportFrom').value || new Date().toISOString().slice(0, 8) + '01';
    var to = document.getElementById('reportTo').value || new Date().toISOString().split('T')[0];
    
    apiRequest('?page=safe&action=report&from=' + from + '&to=' + to)
        .then(function(res) {
            if (res.success) {
                renderCashReport(res.data);
            } else {
                showToast(res.message, 'error');
            }
        });
}

function renderCashReport(data) {
    var modal = document.getElementById('reportModal');
    var content = document.getElementById('reportContent');
    
    var html = '<div style="margin-bottom:16px;">';
    html += '<p><strong>من:</strong> ' + data.from + ' <strong>إلى:</strong> ' + data.to + '</p>';
    html += '<p><strong>الرصيد الافتتاحي:</strong> ' + parseFloat(data.opening_balance).toFixed(2) + ' جنيه</p>';
    html += '<p><strong>الرصيد الختامي:</strong> ' + parseFloat(data.closing_balance).toFixed(2) + ' جنيه</p>';
    html += '</div>';
    
    // Type totals
    html += '<div style="margin-bottom:16px;"><h4>ملخص حسب النوع</h4><ul>';
    for (var type in data.type_totals) {
        html += '<li>' + type + ': ' + parseFloat(data.type_totals[type]).toFixed(2) + ' جنيه</li>';
    }
    html += '</ul></div>';
    
    // Transactions table
    html += '<div class="table-wrapper"><table><thead><tr><th>#</th><th>التاريخ</th><th>النوع</th><th>المبلغ</th><th>الوصف</th><th>الموظف</th><th>الحالة</th></tr></thead><tbody>';
    for (var i = 0; i < data.transactions.length; i++) {
        var t = data.transactions[i];
        var statusBadge = t.status === 'reversed' ? '<span class="badge badge-secondary">معكوس</span>' : 
                         t.transaction_type === 'reversal' ? '<span class="badge badge-info">عكس</span>' : 
                         '<span class="badge badge-success">نشط</span>';
        html += '<tr><td>' + t.id + '</td><td>' + t.date + '</td><td>' + t.type + '</td><td>' + parseFloat(t.amount).toFixed(2) + '</td><td>' + (t.description || '-') + '</td><td>' + (t.employee_name || '-') + '</td><td>' + statusBadge + '</td></tr>';
    }
    html += '</tbody></table></div>';
    
    content.innerHTML = html;
    openModal('reportModal');
}

function openReconcileModal() {
    document.getElementById('actualCount').value = '';
    document.getElementById('reconcileNotes').value = '';
    var diffDiv = document.getElementById('reconcileDiff');
    diffDiv.style.display = 'none';
    openModal('reconcileModal');
    
    // Update expected balance
    apiRequest('?page=safe&action=report&from=' + new Date().toISOString().slice(0,8) + '01&to=' + new Date().toISOString().split('T')[0])
        .then(function(res) {
            if (res.success) {
                document.getElementById('expectedBalance').textContent = parseFloat(res.data.closing_balance).toFixed(2);
            }
        });
}

document.addEventListener('DOMContentLoaded', function() {
    var actualInput = document.getElementById('actualCount');
    if (actualInput) {
        actualInput.addEventListener('input', function() {
            var expected = parseFloat(document.getElementById('expectedBalance').textContent) || 0;
            var actual = parseFloat(this.value) || 0;
            var diff = actual - expected;
            var diffDiv = document.getElementById('reconcileDiff');
            
            if (this.value === '') {
                diffDiv.style.display = 'none';
                return;
            }
            
            diffDiv.style.display = 'block';
            if (Math.abs(diff) < 0.01) {
                diffDiv.style.background = 'var(--success-bg, #d4edda)';
                diffDiv.style.color = '#155724';
                diffDiv.textContent = '✓ متوازن - لا يوجد فرق';
            } else if (diff < 0) {
                diffDiv.style.background = 'var(--danger-bg, #f8d7da)';
                diffDiv.style.color = '#721c24';
                diffDiv.textContent = '⚠️ عجز: ' + Math.abs(diff).toFixed(2) + ' جنيه';
            } else {
                diffDiv.style.background = 'var(--warning-bg, #fff3cd)';
                diffDiv.style.color = '#856404';
                diffDiv.textContent = '⚠️ زيادة: ' + diff.toFixed(2) + ' جنيه';
            }
        });
    }
});

function confirmReconcile() {
    var actual = parseFloat(document.getElementById('actualCount').value);
    if (isNaN(actual) || actual < 0) {
        showToast('أدخل المبلغ الفعلي', 'error');
        return;
    }
    
    apiRequest('?page=safe&action=reconcile', {
        method: 'POST',
        body: { actual_count: actual, notes: document.getElementById('reconcileNotes').value }
    }).then(function(res) {
        if (res.success) {
            showToast(res.message);
            closeModal('reconcileModal');
        } else {
            showToast(res.message, 'error');
        }
    });
}
</script>

<?php require_once 'app/views/layout/footer.php'; ?>
