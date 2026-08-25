<!-- Barcode Management View -->
<div class="page-header">
    <div>
        <h1>🏷️ إدارة الباركود</h1>
        <p class="subtitle">توليد وطباعة باركودات المنتجات</p>
    </div>
    <button class="btn btn-primary" onclick="printSelectedLabels()">🖨️ طباعة التسميات المحددة</button>
</div>

<!-- Search -->
<div class="card" style="padding:14px 20px;">
    <div style="display:flex;gap:12px;">
        <input type="text" id="barcodeSearch" class="form-control" placeholder="بحث عن منتج بالاسم أو الباركود..." style="max-width:400px;" oninput="filterProducts(this.value)">
        <button class="btn btn-outline" onclick="selectAll()">تحديد الكل</button>
        <button class="btn btn-ghost" onclick="deselectAll()">إلغاء التحديد</button>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table id="productsTable">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" id="selectAllCb" onchange="toggleAll(this)"></th>
                    <th>#</th>
                    <th>المنتج</th>
                    <th>الباركود</th>
                    <th>السعر</th>
                    <th>معاينة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr class="product-row" data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>" data-barcode="<?= htmlspecialchars($p['barcode'] ?? '') ?>">
                    <td><input type="checkbox" class="product-cb" value="<?= $p['id'] ?>"></td>
                    <td><?= $p['id'] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                    <td>
                        <?php if ($p['barcode']): ?>
                        <code><?= htmlspecialchars($p['barcode']) ?></code>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= number_format($p['sale_price_unit'], 2) ?></td>
                    <td>
                        <?php if ($p['barcode']): ?>
                        <img src="?page=barcode&action=image&data=<?= urlencode($p['barcode']) ?>&w=1&h=30" alt="barcode" style="height:30px;">
                        <?php else: ?>
                        <span class="text-muted">لا يوجد</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <?php if (!$p['barcode']): ?>
                            <a href="?page=barcode&action=generate&id=<?= $p['id'] ?>" class="btn btn-sm btn-success" title="توليد باركود">🏷️ توليد</a>
                            <?php endif; ?>
                            <?php if ($p['barcode']): ?>
                            <a href="?page=barcode&action=labels&ids=<?= $p['id'] ?>" class="btn btn-sm btn-outline" target="_blank" title="طباعة تسمية">🖨️</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterProducts(query) {
    query = query.toLowerCase();
    var rows = document.querySelectorAll('.product-row');
    for (var i = 0; i < rows.length; i++) {
        var name = rows[i].getAttribute('data-name');
        var barcode = rows[i].getAttribute('data-barcode');
        rows[i].style.display = (name.indexOf(query) !== -1 || barcode.indexOf(query) !== -1) ? '' : 'none';
    }
}

function toggleAll(cb) {
    var checkboxes = document.querySelectorAll('.product-cb');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = cb.checked;
    }
}

function selectAll() {
    var checkboxes = document.querySelectorAll('.product-cb');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = true;
    }
}

function deselectAll() {
    var checkboxes = document.querySelectorAll('.product-cb');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = false;
    }
}

function printSelectedLabels() {
    var ids = [];
    var checkboxes = document.querySelectorAll('.product-cb:checked');
    for (var i = 0; i < checkboxes.length; i++) {
        ids.push(checkboxes[i].value);
    }
    if (ids.length === 0) {
        alert('حدد منتجات أولاً');
        return;
    }
    window.open('?page=barcode&action=labels&ids=' + ids.join(','), '_blank');
}
</script>
