<!-- Categories Management -->
<div class="page-header">
    <div>
        <h1>إدارة الأقسام</h1>
        <p class="subtitle">إضافة وتعديل وحذف أقسام المنتجات</p>
    </div>
    <button class="btn btn-primary" onclick="openCategoryModal()">+ إضافة قسم</button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>اسم القسم</th>
                    <th>عدد المنتجات</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= $cat['id'] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($cat['name']) ?></td>
                    <td><?= $cat['product_count'] ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline" onclick='editCategory(<?= $cat['id'] ?>, "<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>")'>✏️ تعديل</button>
                        <?php if ($cat['product_count'] == 0): ?>
                        <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?= $cat['id'] ?>)">🗑️ حذف</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Category Modal -->
<div class="modal-overlay" id="categoryModal">
    <div class="modal" style="min-width:400px;">
        <div class="modal-header">
            <h3 id="categoryModalTitle">إضافة قسم</h3>
            <button class="modal-close" onclick="closeModal('categoryModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="categoryId" value="">
            <div class="form-group">
                <label>اسم القسم *</label>
                <input type="text" class="form-control" id="categoryName" placeholder="أدخل اسم القسم" required>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('categoryModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="saveCategory()">حفظ</button>
        </div>
    </div>
</div>

<script>
function openCategoryModal() {
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryName').value = '';
    document.getElementById('categoryModalTitle').textContent = 'إضافة قسم';
    openModal('categoryModal');
    setTimeout(function() { document.getElementById('categoryName').focus(); }, 200);
}

function editCategory(id, name) {
    document.getElementById('categoryId').value = id;
    document.getElementById('categoryName').value = name;
    document.getElementById('categoryModalTitle').textContent = 'تعديل القسم';
    openModal('categoryModal');
    setTimeout(function() { document.getElementById('categoryName').focus(); }, 200);
}

function saveCategory() {
    var id = document.getElementById('categoryId').value;
    var name = document.getElementById('categoryName').value.trim();
    
    if (!name) {
        showToast('اسم القسم مطلوب', 'error');
        return;
    }
    
    var url = id ? '?page=categories&action=update' : '?page=categories&action=store';
    var body = { name: name };
    if (id) body.id = parseInt(id);
    
    apiRequest(url, { method: 'POST', body: body })
        .then(function(res) {
            if (res.success) {
                showToast(res.message);
                closeModal('categoryModal');
                setTimeout(function() { location.reload(); }, 500);
            } else {
                showToast(res.message, 'error');
            }
        });
}

function deleteCategory(id) {
    if (confirm('هل أنت متأكد من حذف هذا القسم؟')) {
        apiRequest('?page=categories&action=delete&id=' + id)
            .then(function(res) {
                if (res.success) {
                    showToast(res.message);
                    setTimeout(function() { location.reload(); }, 500);
                } else {
                    showToast(res.message, 'error');
                }
            });
    }
}
</script>
