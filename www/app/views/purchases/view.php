<!-- Purchase Invoice Detail View -->
<div class="page-header">
    <div>
        <h1>🧾 فاتورة شراء #<?= $invoice['id'] ?></h1>
    </div>
    <a href="?page=purchases" class="btn btn-outline">← العودة</a>
</div>

<div style="display:grid;grid-template-columns:300px 1fr;gap:16px;">
    <div class="card">
        <div class="card-header"><h3>بيانات الفاتورة</h3></div>
        <table>
            <tr><td class="text-muted">رقم الفاتورة</td><td class="fw-bold"><?= $invoice['invoice_number'] ?: $invoice['id'] ?></td></tr>
            <tr><td class="text-muted">المورد</td><td class="fw-bold"><?= htmlspecialchars($invoice['supplier_name']) ?></td></tr>
            <tr><td class="text-muted">التاريخ</td><td><?= $invoice['date'] ?></td></tr>
            <tr><td class="text-muted">الإجمالي</td><td class="fw-bold text-accent fs-lg"><?= number_format($invoice['total'], 2) ?></td></tr>
            <?php if ($invoice['notes']): ?>
            <tr><td class="text-muted">ملاحظات</td><td><?= htmlspecialchars($invoice['notes']) ?></td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="card">
        <div class="card-header"><h3>📦 الأصناف</h3></div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>المنتج</th>
                        <th>الكمية</th>
                        <th>سعر الشراء</th>
                        <th>المجموع</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoice['items'] as $i => $item): ?>
                    <?php $isReturn = $item['subtotal'] < 0; ?>
                    <tr style="<?= $isReturn ? 'background-color:#ffebee; border-left:4px solid #e74c3c;' : '' ?>">
                        <td><?= $i + 1 ?></td>
                        <td class="fw-bold">
                            <?= htmlspecialchars($item['product_name']) ?>
                            <?php if ($isReturn): ?>
                                <span style="background:#e74c3c; color:white; padding:2px 6px; border-radius:4px; font-size:10px; margin-right:5px;">↩ مرتجع</span>
                            <?php elseif ($item['subtotal'] == 0): ?>
                                <span style="background:#28a745; color:white; padding:2px 6px; border-radius:4px; font-size:10px; margin-right:5px;">🎁 هدية</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= number_format($item['purchase_price'], 2) ?></td>
                        <td class="fw-bold" style="<?= $isReturn ? 'color:#e74c3c;' : '' ?>"><?= number_format($item['subtotal'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
