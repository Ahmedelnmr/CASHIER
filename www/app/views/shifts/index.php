<!-- Shift Management View -->
<?php
$toastSuccess = $_SESSION['toast_success'] ?? '';
$toastError = $_SESSION['toast_error'] ?? '';
unset($_SESSION['toast_success'], $_SESSION['toast_error']);
$shiftReport = $_SESSION['shift_report'] ?? null;
unset($_SESSION['shift_report']);
?>

<div class="page-header">
    <div>
        <h1>🔄 إدارة الورديات</h1>
        <p class="subtitle">فتح وإغلاق الورديات وتقارير X/Z</p>
    </div>
</div>

<?php if ($toastSuccess): ?>
<script>showToast('<?= htmlspecialchars($toastSuccess) ?>', 'success');</script>
<?php endif; ?>
<?php if ($toastError): ?>
<script>showToast('<?= htmlspecialchars($toastError) ?>', 'error');</script>
<?php endif; ?>

<?php if ($shiftReport): ?>
<div class="card" style="border:2px solid var(--success);background:var(--success-bg);">
    <div class="card-header"><h3>✅ تقرير إغلاق الوردية #<?= $shiftReport['shift_id'] ?></h3></div>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-info"><h4><?= number_format($shiftReport['opening_cash'], 2) ?></h4><p>العهدة الافتتاحية</p></div></div>
        <div class="stat-card"><div class="stat-info"><h4 class="text-success"><?= number_format($shiftReport['total_sales'], 2) ?></h4><p>إجمالي المبيعات</p></div></div>
        <div class="stat-card"><div class="stat-info"><h4><?= $shiftReport['sale_count'] ?></h4><p>عدد الفواتير</p></div></div>
        <div class="stat-card"><div class="stat-info"><h4><?= number_format($shiftReport['expected_cash'], 2) ?></h4><p>النقد المتوقع</p></div></div>
        <div class="stat-card"><div class="stat-info"><h4><?= number_format($shiftReport['closing_cash'], 2) ?></h4><p>النقد الفعلي</p></div></div>
        <div class="stat-card"><div class="stat-info"><h4 class="<?= $shiftReport['cash_difference'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($shiftReport['cash_difference'], 2) ?></h4><p>الفرق (عجز/زيادة)</p></div></div>
    </div>
</div>
<?php endif; ?>

<!-- Current Shift Status -->
<?php if ($currentShift): ?>
<div class="card" style="border:2px solid var(--accent);">
    <div class="card-header">
        <h3>🟢 الوردية الحالية — #<?= $currentShift['id'] ?></h3>
        <div class="btn-group">
            <a href="?page=shifts&action=xreport&id=<?= $currentShift['id'] ?>" class="btn btn-sm btn-outline">📊 تقرير X</a>
            <button class="btn btn-sm btn-danger" onclick="document.getElementById('closeShiftModal').classList.add('active')">🔒 إغلاق الوردية</button>
        </div>
    </div>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-info"><h4><?= number_format($currentShift['opening_cash'], 2) ?></h4><p>العهدة الافتتاحية</p></div></div>
        <div class="stat-card"><div class="stat-info"><h4><?= $currentShift['opened_at'] ?></h4><p>وقت الفتح</p></div></div>
        <div class="stat-card"><div class="stat-info"><h4><?= $_SESSION['user_name'] ?? 'كاشير' ?></h4><p>الكاشير</p></div></div>
    </div>
</div>

<!-- Close Shift Modal -->
<div class="modal-overlay" id="closeShiftModal">
    <div class="modal">
        <div class="modal-header">
            <h3>🔒 إغلاق الوردية #<?= $currentShift['id'] ?></h3>
            <button class="modal-close" onclick="document.getElementById('closeShiftModal').classList.remove('active')">&times;</button>
        </div>
        <form method="POST" action="?page=shifts&action=close">
            <input type="hidden" name="shift_id" value="<?= $currentShift['id'] ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label>المبلغ النقدي في الدرج *</label>
                    <input type="number" name="closing_cash" class="form-control" step="0.01" min="0" required placeholder="0.00">
                    <small class="text-muted">عدّ النقدي الموجود في الدرج حالياً</small>
                </div>
                <div class="form-group">
                    <label>ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="اختياري"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('closeShiftModal').classList.remove('active')">إلغاء</button>
                <button type="submit" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من إغلاق الوردية؟')">إغلاق الوردية</button>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- No Open Shift - Open New -->
<div class="card" style="border:2px dashed var(--warning);">
    <div class="card-header">
        <h3>⚠️ لا توجد وردية مفتوحة</h3>
    </div>
    <p style="margin-bottom:16px;color:var(--text-secondary);">يجب فتح وردية قبل البدء في البيع</p>
    <form method="POST" action="?page=shifts&action=open" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0;">
            <label>العهدة الافتتاحية (النقدي في الدرج)</label>
            <input type="number" name="opening_cash" class="form-control" step="0.01" min="0" value="0" style="width:180px;">
        </div>
        <div class="form-group" style="margin:0;">
            <label>ملاحظات</label>
            <input type="text" name="notes" class="form-control" placeholder="اختياري" style="width:250px;">
        </div>
        <button type="submit" class="btn btn-success">🔓 فتح الوردية</button>
    </form>
</div>
<?php endif; ?>

<!-- Shift History -->
<div class="card">
    <div class="card-header">
        <h3>📋 سجل الورديات</h3>
    </div>
    <?php if (!empty($shifts)): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الكاشير</th>
                    <th>الوقت</th>
                    <th>العهدة</th>
                    <th>المبيعات</th>
                    <th>الفواتير</th>
                    <th>النقد الفعلي</th>
                    <th>الفرق</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shifts as $s): ?>
                <tr>
                    <td class="fw-bold"><?= $s['id'] ?></td>
                    <td><?= htmlspecialchars($s['user_name'] ?? '—') ?></td>
                    <td class="fs-sm">
                        <?= $s['opened_at'] ?>
                        <?php if ($s['closed_at']): ?>
                        <br><span class="text-muted">→ <?= $s['closed_at'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= number_format($s['opening_cash'], 2) ?></td>
                    <td class="text-success fw-bold"><?= number_format($s['total_sales'], 2) ?></td>
                    <td><?= $s['sale_count'] ?></td>
                    <td><?= $s['closing_cash'] !== null ? number_format($s['closing_cash'], 2) : '—' ?></td>
                    <td class="<?= ($s['cash_difference'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                        <?= $s['cash_difference'] !== null ? number_format($s['cash_difference'], 2) : '—' ?>
                    </td>
                    <td>
                        <?php if ($s['status'] === 'open'): ?>
                        <span class="badge badge-success">مفتوحة</span>
                        <?php else: ?>
                        <span class="badge badge-info">مغلقة</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <?php if ($s['status'] === 'open'): ?>
                            <a href="?page=shifts&action=xreport&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline" title="تقرير X">X</a>
                            <?php else: ?>
                            <a href="?page=shifts&action=zreport&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline" title="تقرير Z">Z</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <p>لا توجد ورديات بعد</p>
    </div>
    <?php endif; ?>
</div>
