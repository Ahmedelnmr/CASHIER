<!-- Suppliers List View -->
<div class="page-header">
    <div>
        <h1>الموردين</h1>
        <p class="subtitle">إجمالي الموردين: <?= count($suppliers) ?></p>
    </div>
    <a href="?page=suppliers&action=create" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        إضافة مورد
    </a>
</div>

<div class="card" style="padding:14px 20px;">
    <form style="display:flex;align-items:center;gap:12px;" method="GET">
        <input type="hidden" name="page" value="suppliers">
        <div class="search-box" style="flex:1;">
            <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو الهاتف..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
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
                    <td>
                        <div class="btn-group">
                            <a href="?page=suppliers&action=profile&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline">ملف</a>
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
        <p>لا يوجد موردين</p>
    </div>
    <?php endif; ?>
</div>
