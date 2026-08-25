<!-- Products List View -->
<div class="page-header">
    <div>
        <h1>إدارة المنتجات</h1>
        <p class="subtitle">إجمالي المنتجات: <?= count($products) ?></p>
    </div>
    <a href="?page=products&action=create" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        إضافة منتج
    </a>
</div>

<!-- Filters -->
<div class="card" style="padding:14px 20px;">
    <form style="display:flex;align-items:center;gap:12px;" method="GET">
        <input type="hidden" name="page" value="products">
        <div class="search-box" style="flex:1;">
            <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو الباركود..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <select name="category" class="form-control" style="width:180px;">
            <option value="">كل الأقسام</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= ($_GET['category'] ?? '') === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="type" class="form-control" style="width:150px;">
            <option value="">كل الأنواع</option>
            <option value="unit" <?= ($_GET['type'] ?? '') === 'unit' ? 'selected' : '' ?>>وحدة</option>
            <option value="pack" <?= ($_GET['type'] ?? '') === 'pack' ? 'selected' : '' ?>>عبوة</option>
            <option value="weight" <?= ($_GET['type'] ?? '') === 'weight' ? 'selected' : '' ?>>وزن</option>
        </select>
        <button type="submit" class="btn btn-outline">بحث</button>
        <a href="?page=products" class="btn btn-ghost">إعادة</a>
    </form>
</div>

<!-- Products Table -->
<div class="card">
    <?php if (!empty($products)): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>الباركود</th>
                    <th>PLU</th>
                    <th>النوع</th>
                    <th>سعر الشراء</th>
                    <th>سعر البيع</th>
                    <th>المخزون</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td class="text-muted"><?= $p['id'] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                    <td class="text-muted fs-sm"><?= $p['barcode'] ?: '—' ?></td>
                    <td class="text-muted fs-sm"><?= $p['plu_code'] ?: '—' ?></td>
                    <td>
                        <?php
                        $typeLabels = ['unit' => 'وحدة', 'pack' => 'عبوة', 'weight' => 'وزن'];
                        $typeColors = ['unit' => 'info', 'pack' => 'purple', 'weight' => 'warning'];
                        ?>
                        <span class="badge badge-<?= $typeColors[$p['type']] ?? 'info' ?>"><?= $typeLabels[$p['type']] ?? $p['type'] ?></span>
                    </td>
                    <td><?= number_format($p['purchase_price'], 2) ?></td>
                    <td class="fw-bold text-accent"><?= number_format($p['sale_price_unit'], 2) ?></td>
                    <td>
                        <?php if ($p['stock_quantity'] <= $p['min_stock'] && $p['min_stock'] > 0): ?>
                            <span class="badge badge-danger"><?= $p['stock_quantity'] ?></span>
                        <?php else: ?>
                            <span class="badge badge-success"><?= $p['stock_quantity'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="?page=products&action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline">✏️</a>
                            <button class="btn btn-sm btn-ghost text-danger" onclick="deleteItem('?page=products&action=delete&id=<?= $p['id'] ?>', 'حذف <?= htmlspecialchars($p['name']) ?>؟')">🗑️</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <p>لا توجد منتجات بعد</p>
        <a href="?page=products&action=create" class="btn btn-primary mt-2">إضافة منتج جديد</a>
    </div>
    <?php endif; ?>
</div>
