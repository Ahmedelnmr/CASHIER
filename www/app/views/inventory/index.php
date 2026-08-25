<!-- Inventory View -->
<div class="page-header">
    <div>
        <h1>إدارة المخزون</h1>
        <p class="subtitle">متابعة وتعديل المخزون</p>
    </div>
</div>

<!-- Summary Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
        </div>
        <div class="stat-info">
            <h4><?= $summary['total_products'] ?? 0 ?></h4>
            <p>إجمالي المنتجات</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
        <div class="stat-info">
            <h4><?= number_format($summary['total_value'] ?? 0, 2) ?></h4>
            <p>قيمة المخزون</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        </div>
        <div class="stat-info">
            <h4><?= $summary['low_stock_count'] ?? 0 ?></h4>
            <p>منتجات منخفضة</p>
        </div>
    </div>
</div>

<?php if (!empty($lowStock)): ?>
<!-- Low Stock Alerts -->
<div class="card">
    <div class="card-header">
        <h3>تنبيهات المخزون المنخفض (<?= count($lowStock) ?>)</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>النوع</th>
                    <th>المخزون الحالي</th>
                    <th>الحد الأدنى</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lowStock as $p): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                    <td><span class="badge badge-info"><?= $p['type'] ?></span></td>
                    <td><span class="badge badge-danger"><?= $p['stock_quantity'] ?></span></td>
                    <td><?= $p['min_stock'] ?></td>
                    <td>
                        <?php if ($p['stock_quantity'] <= 0): ?>
                            <span class="badge badge-danger">نفد</span>
                        <?php else: ?>
                            <span class="badge badge-warning">منخفض</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- All Products Stock -->
<div class="card">
    <div class="card-header">
        <h3>مخزون المنتجات</h3>
    </div>
    
    <div style="margin-bottom:12px;">
        <form style="display:flex;align-items:center;gap:8px;" method="GET">
            <input type="hidden" name="page" value="inventory">
            <input type="text" name="search" class="form-control" style="max-width:300px;" placeholder="بحث..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <button type="submit" class="btn btn-outline btn-sm">بحث</button>
        </form>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>النوع</th>
                    <th>المخزون</th>
                    <th>الحد الأدنى</th>
                    <th>القيمة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                    <td><span class="badge badge-info"><?= $p['type'] ?></span></td>
                    <td>
                        <?php
                        $stockQty = $p['stock_quantity'];
                        $packQty = $p['pack_unit_quantity'] ?? 1;
                        $badgeClass = ($p['min_stock'] > 0 && $stockQty <= $p['min_stock']) ? 'badge-danger' : 'badge-success';
                        
                        if ($packQty > 1) {
                            $packs = floor($stockQty / $packQty);
                            $units = $stockQty % $packQty;
                            $packName = $p['pack_type'] ?? 'عبوة';
                            $unitName = $p['type'] === 'weight' ? 'كيلو' : 'قطعة';
                            $display = "<strong>{$packs}</strong> {$packName}";
                            if ($units > 0) {
                                $display .= " + <strong>{$units}</strong> {$unitName}";
                            }
                        } else {
                            $display = "<strong>{$stockQty}</strong>";
                        }
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= $display ?></span>
                    </td>
                    <td><?= $p['min_stock'] ?></td>
                    <td class="text-muted"><?= number_format($p['stock_quantity'] * $p['purchase_price'], 2) ?></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline" onclick="openAdjust(<?= $p['id'] ?>)">تعديل</button>
                            <button class="btn btn-sm btn-ghost" onclick="viewMoves(<?= $p['id'] ?>)">الحركات</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Adjust Stock Modal -->
<div class="modal-overlay" id="adjustModal">
    <div class="modal">
        <div class="modal-header">
            <h3>تعديل المخزون</h3>
            <button class="modal-close" onclick="closeModal('adjustModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p class="fw-bold mb-2" id="adjustProductName"></p>
            <div class="form-group">
                <label>الكمية الحالية</label>
                <input type="text" class="form-control" id="adjustCurrentQty" readonly>
            </div>
            <div class="form-group">
                <label>الكمية الجديدة</label>
                <input type="number" class="form-control" id="adjustNewQty" step="0.01">
            </div>
            <div class="form-group">
                <label>السبب</label>
                <input type="text" class="form-control" id="adjustReason" placeholder="سبب التعديل">
            </div>
            <input type="hidden" id="adjustProductId">
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('adjustModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="saveAdjustment()">حفظ</button>
        </div>
    </div>
</div>

<!-- Movements Modal -->
<div class="modal-overlay" id="movementsModal">
    <div class="modal" style="max-width:700px;">
        <div class="modal-header">
            <h3>حركات المخزون</h3>
            <button class="modal-close" onclick="closeModal('movementsModal')">&times;</button>
        </div>
        <div class="modal-body" id="movementsContent">
            <div class="spinner"></div>
        </div>
    </div>
</div>

<script>
var productMap = {
    <?php foreach ($products as $p): ?>
    <?= $p['id'] ?>: { id: <?= $p['id'] ?>, name: "<?= addslashes($p['name']) ?>", qty: <?= $p['stock_quantity'] ?> },
    <?php endforeach; ?>
};

function openAdjust(id) {
    var p = productMap[id];
    if (!p) return;
    document.getElementById('adjustProductId').value = p.id;
    document.getElementById('adjustProductName').textContent = p.name;
    document.getElementById('adjustCurrentQty').value = p.qty;
    document.getElementById('adjustNewQty').value = p.qty;
    openModal('adjustModal');
}

function viewMoves(id) {
    openModal('movementsModal');
    var content = document.getElementById('movementsContent');
    content.innerHTML = '<div class="spinner"></div>';

    apiRequest('?page=inventory&action=movements&product_id=' + id).then(function(res) {
        if (res.success && res.data && res.data.length > 0) {
            var typeLabels = { purchase: 'شراء', sale: 'بيع', adjustment: 'تعديل' };
            var rows = '';
            for (var i = 0; i < res.data.length; i++) {
                var m = res.data[i];
                var colorClass = m.quantity >= 0 ? 'text-success' : 'text-danger';
                var sign = m.quantity >= 0 ? '+' : '';
                rows += '<tr><td class="fs-sm">' + m.created_at + '</td><td>' + (typeLabels[m.type] || m.type) + '</td><td class="' + colorClass + ' fw-bold">' + sign + m.quantity + '</td><td class="text-muted fs-sm">' + (m.notes || '—') + '</td></tr>';
            }
            content.innerHTML = '<div class="table-wrapper"><table><thead><tr><th>التاريخ</th><th>النوع</th><th>الكمية</th><th>ملاحظات</th></tr></thead><tbody>' + rows + '</tbody></table></div>';
        } else {
            content.innerHTML = '<div class="empty-state"><p>لا توجد حركات</p></div>';
        }
    });
}

function saveAdjustment() {
    var productId = document.getElementById('adjustProductId').value;
    var newQty = parseFloat(document.getElementById('adjustNewQty').value);
    var reason = document.getElementById('adjustReason').value;

    apiRequest('?page=inventory&action=adjust', {
        method: 'POST',
        body: { product_id: productId, new_quantity: newQty, reason: reason }
    }).then(function(res) {
        if (res.success) {
            showToast('تم تعديل المخزون');
            closeModal('adjustModal');
            setTimeout(function() { location.reload(); }, 500);
        } else {
            showToast(res.message || 'خطأ', 'error');
        }
    });
}
</script>
