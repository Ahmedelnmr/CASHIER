<!-- Suppliers List View -->
<div class="page-header">
    <div>
        <h1>🏭 الموردين</h1>
        <p class="subtitle">إجمالي الموردين: <?= count($suppliers) ?></p>
    </div>
    <a href="?page=suppliers&action=create" class="btn btn-primary">➕ إضافة مورد</a>
</div>

<div class="card" style="padding:14px 20px;">
    <form class="d-flex align-center gap-2" method="GET">
        <input type="hidden" name="page" value="suppliers">
        <div class="search-box" style="flex:1;">
            <input type="text" name="search" class="form-control" placeholder="🔍 بحث بالاسم أو الهاتف..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-outline">بحث</button>
    </form>
</div>

<div class="card">
    <?php if (!empty($suppliers)): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>العنوان</th>
                    <th>ملاحظات</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td class="text-muted"><?= $s['id'] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= $s['phone'] ?: '—' ?></td>
                    <td class="text-muted fs-sm"><?= $s['address'] ? htmlspecialchars($s['address']) : '—' ?></td>
                    <td class="text-muted fs-sm"><?= $s['notes'] ? htmlspecialchars($s['notes']) : '—' ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="?page=suppliers&action=profile&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline">👤 ملف</a>
                            <a href="?page=suppliers&action=edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-ghost">✏️</a>
                            <button class="btn btn-sm btn-ghost text-danger" onclick="deleteItem('?page=suppliers&action=delete&id=<?= $s['id'] ?>', 'حذف <?= htmlspecialchars($s['name']) ?>؟')">🗑️</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="icon">🏭</div>
        <p>لا يوجد موردين</p>
    </div>
    <?php endif; ?>
</div>
