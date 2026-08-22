<!-- Sales List View -->
<div class="page-header">
    <div>
        <h1>💵 سجل المبيعات</h1>
        <p class="subtitle">مبيعات اليوم: <?= $todaySummary['count'] ?? 0 ?> فاتورة | الإجمالي: <?= number_format($todaySummary['total'] ?? 0, 2) ?></p>
    </div>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
    <div class="alert alert-success" style="padding:10px; background:#dcfce7; color:#166534; margin:10px 0; border-radius:4px;">✅ تم حذف الفاتورة بنجاح وإعادة الكميات للمخزون.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger" style="padding:10px; background:#fee2e2; color:#991b1b; margin:10px 0; border-radius:4px;">❌ <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card" style="padding:14px 20px;">
    <form class="d-flex align-center gap-2" method="GET">
        <input type="hidden" name="page" value="sales">
        <label class="text-muted">من:</label>
        <input type="date" name="date_from" class="form-control" style="width:160px;" value="<?= htmlspecialchars($dateFrom) ?>">
        <label class="text-muted">إلى:</label>
        <input type="date" name="date_to" class="form-control" style="width:160px;" value="<?= htmlspecialchars($dateTo) ?>">
        <button type="submit" class="btn btn-outline">بحث</button>
        <a href="?page=sales" class="btn btn-ghost">اليوم</a>
    </form>
</div>

<div class="card">
    <?php if (!empty($sales)): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>رقم الفاتورة</th>
                    <th>التاريخ والوقت</th>
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
                    <td class="fw-bold">#<?= $s['sale_number'] ?? $s['id'] ?>
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
                        <a href="?page=sales&action=view&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline" title="عرض">📄</a>
                        <?php if (($s['status'] ?? '') !== 'voided' && ($s['type'] ?? 'sale') === 'sale'): ?>
                        <a href="?page=sales&action=edit_form&id=<?= $s['id'] ?>" class="btn btn-sm btn-primary" title="تعديل">✏️</a>
                        <?php endif; ?>
                        <a href="?page=sales&action=delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف الفاتورة؟')">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="icon">💵</div>
        <p>لا توجد مبيعات في هذه الفترة</p>
    </div>
    <?php endif; ?>
</div>
