<!-- Sales List View -->
<div class="page-header">
    <div>
        <h1>سجل المبيعات</h1>
        <p class="subtitle">مبيعات اليوم: <?= $todaySummary['count'] ?? 0 ?> فاتورة | الإجمالي: <?= number_format($todaySummary['total'] ?? 0, 2) ?></p>
    </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
<div class="card" style="padding:12px 20px;background:var(--success-bg);border-color:var(--success);">
    <span class="text-success fw-bold">تم حذف الفاتورة بنجاح وإعادة الكميات للمخزون.</span>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card" style="padding:14px 20px;">
    <form style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;" method="GET">
        <input type="hidden" name="page" value="sales">
        <label class="text-muted">من:</label>
        <input type="date" name="date_from" class="form-control" style="width:160px;" value="<?= htmlspecialchars($dateFrom) ?>">
        <label class="text-muted">إلى:</label>
        <input type="date" name="date_to" class="form-control" style="width:160px;" value="<?= htmlspecialchars($dateTo) ?>">
        <button type="submit" class="btn btn-outline">بحث</button>
        <a href="?page=sales" class="btn btn-ghost">اليوم</a>
        <span style="border-right:1px solid var(--border-color);height:24px;margin:0 4px;"></span>
        <label class="text-muted">رقم الفاتورة:</label>
        <input type="text" id="invoiceSearch" class="form-control" style="width:180px;" placeholder="INV-083126-001">
        <button type="button" class="btn btn-outline" onclick="searchInvoice()">بحث</button>
    </form>
</div>

<script>
function searchInvoice() {
    var q = document.getElementById('invoiceSearch').value.trim();
    if (!q) return;
    window.location.href = '?page=sales&action=view_by_invoice&invoice=' + encodeURIComponent(q);
}
</script>

<div class="card">
    <?php if (!empty($sales)): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>رقم الفاتورة</th>
                    <th>التاريخ</th>
                    <th>الخصم</th>
                    <th>الإجمالي</th>
                    <th>الدفع</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $s): ?>
                <tr>
                    <td class="text-muted"><?= $s['id'] ?></td>
                    <td class="fw-bold"><?= $s['invoice_number'] ?? ('#' . $s['id']) ?>
                        <?php if (($s['status'] ?? '') === 'voided'): ?>
                            <span class="badge badge-danger" style="font-size:10px;margin-right:4px;">ملغاة</span>
                        <?php elseif (($s['type'] ?? 'sale') === 'return'): ?>
                            <span class="badge badge-warning" style="font-size:10px;margin-right:4px;">مرتجع</span>
                        <?php endif; ?>
                    </td>
                    <td class="fs-sm"><?= $s['datetime'] ?></td>
                    <td><?= $s['discount'] > 0 ? number_format($s['discount'], 2) : '—' ?></td>
                    <td class="fw-bold text-accent"><?= number_format($s['total'], 2) ?></td>
                    <td><span class="badge badge-success"><?= $s['payment_method'] === 'cash' ? 'نقدي' : $s['payment_method'] ?></span></td>
                    <td>
                        <div class="btn-group">
                            <a href="?page=sales&action=view&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline" title="عرض">📄</a>
                            <?php if (($s['status'] ?? '') !== 'voided' && ($s['type'] ?? 'sale') === 'sale'): ?>
                            <a href="?page=sales&action=edit_form&id=<?= $s['id'] ?>" class="btn btn-sm btn-primary" title="تعديل">✏️</a>
                            <?php endif; ?>
                            <a href="?page=sales&action=delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف الفاتورة؟')">🗑️</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <p>لا توجد مبيعات في هذه الفترة</p>
    </div>
    <?php endif; ?>
</div>
