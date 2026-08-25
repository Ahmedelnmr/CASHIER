<!-- Purchases List View -->
<div class="page-header">
    <div>
        <h1>فواتير الشراء</h1>
        <p class="subtitle">إجمالي الفواتير: <?= count($invoices) ?></p>
    </div>
    <a href="?page=purchases&action=create" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        فاتورة شراء جديدة
    </a>
</div>

<!-- Filters -->
<div class="card" style="padding:14px 20px;">
    <form style="display:flex;align-items:center;gap:12px;" method="GET">
        <input type="hidden" name="page" value="purchases">
        <select name="supplier_id" class="form-control" style="width:200px;">
            <option value="">كل الموردين</option>
            <?php foreach ($suppliers as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($_GET['supplier_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_from" class="form-control" style="width:160px;" value="<?= $_GET['date_from'] ?? '' ?>">
        <input type="date" name="date_to" class="form-control" style="width:160px;" value="<?= $_GET['date_to'] ?? '' ?>">
        <button type="submit" class="btn btn-outline">بحث</button>
    </form>
</div>

<div class="card">
    <?php if (!empty($invoices)): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>المورد</th>
                    <th>رقم الفاتورة</th>
                    <th>التاريخ</th>
                    <th>الإجمالي</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td class="text-muted"><?= $inv['id'] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($inv['supplier_name']) ?></td>
                    <td><?= $inv['invoice_number'] ?: '—' ?></td>
                    <td><?= $inv['date'] ?></td>
                    <td class="fw-bold text-accent"><?= number_format($inv['total'], 2) ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="?page=purchases&action=view&id=<?= $inv['id'] ?>" class="btn btn-sm btn-outline" title="عرض">📄</a>
                            <a href="?page=purchases&action=edit_form&id=<?= $inv['id'] ?>" class="btn btn-sm btn-primary" title="تعديل">✏️</a>
                            <a href="?page=purchases&action=delete&id=<?= $inv['id'] ?>" class="btn btn-sm btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف الفاتورة؟')">🗑️</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <p>لا توجد فواتير شراء</p>
    </div>
    <?php endif; ?>
</div>
