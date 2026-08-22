<!-- Supplier Profile View -->
<div class="page-header">
    <div>
        <h1>👤 ملف المورد: <?= htmlspecialchars($supplier['name']) ?></h1>
    </div>
    <div class="btn-group">
        <a href="?page=suppliers&action=edit&id=<?= $supplier['id'] ?>" class="btn btn-outline">✏️ تعديل</a>
        <a href="?page=suppliers" class="btn btn-ghost">← العودة</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:300px 1fr;gap:16px;">
    <!-- Info -->
    <div class="card">
        <div class="card-header"><h3>معلومات المورد</h3></div>
        <table>
            <tr><td class="text-muted">الاسم</td><td class="fw-bold"><?= htmlspecialchars($supplier['name']) ?></td></tr>
            <tr><td class="text-muted">الهاتف</td><td><?= $supplier['phone'] ?: '—' ?></td></tr>
            <tr><td class="text-muted">العنوان</td><td><?= $supplier['address'] ? htmlspecialchars($supplier['address']) : '—' ?></td></tr>
            <tr><td class="text-muted">ملاحظات</td><td><?= $supplier['notes'] ? htmlspecialchars($supplier['notes']) : '—' ?></td></tr>
        </table>
    </div>

    <!-- Purchase History -->
    <div class="card">
        <div class="card-header">
            <h3>🧾 فواتير الشراء (<?= count($purchases) ?>)</h3>
            <a href="?page=purchases&action=create" class="btn btn-sm btn-primary">➕ فاتورة جديدة</a>
        </div>
        <?php if (!empty($purchases)): ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>التاريخ</th>
                        <th>رقم الفاتورة</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= $p['date'] ?></td>
                        <td><?= $p['invoice_number'] ?: '—' ?></td>
                        <td class="fw-bold text-accent"><?= number_format($p['total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><p>لا توجد فواتير شراء</p></div>
        <?php endif; ?>
    </div>
</div>
