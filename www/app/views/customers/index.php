<!-- Customers List View -->
<?php
$toastSuccess = $_SESSION['toast_success'] ?? '';
$toastError = $_SESSION['toast_error'] ?? '';
unset($_SESSION['toast_success'], $_SESSION['toast_error']);
?>

<div class="page-header">
    <div>
        <h1>👥 إدارة العملاء</h1>
        <p class="subtitle">قاعدة بيانات العملاء والمبيعات الآجلة</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addCustomerModal')">➕ إضافة عميل</button>
</div>

<?php if ($toastSuccess): ?>
<script>showToast('<?= htmlspecialchars($toastSuccess) ?>', 'success');</script>
<?php endif; ?>
<?php if ($toastError): ?>
<script>showToast('<?= htmlspecialchars($toastError) ?>', 'error');</script>
<?php endif; ?>

<!-- Search -->
<div class="card" style="padding:14px 20px;">
    <form style="display:flex;gap:12px;" method="GET">
        <input type="hidden" name="page" value="customers">
        <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو رقم الهاتف..." value="<?= htmlspecialchars($search) ?>" style="max-width:350px;">
        <button type="submit" class="btn btn-outline">بحث</button>
        <?php if ($search): ?>
        <a href="?page=customers" class="btn btn-ghost">الكل</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <?php if (!empty($customers)): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>الرصيد (آجل)</th>
                    <th>الحد الائتماني</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                <tr>
                    <td><?= $c['id'] ?></td>
                    <td class="fw-bold"><a href="?page=customers&action=profile&id=<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></a></td>
                    <td><?= htmlspecialchars($c['phone'] ?: '—') ?></td>
                    <td>
                        <?php if ($c['balance'] > 0): ?>
                        <span class="badge badge-warning"><?= number_format($c['balance'], 2) ?></span>
                        <?php else: ?>
                        <span class="text-muted">0.00</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $c['credit_limit'] > 0 ? number_format($c['credit_limit'], 2) : '—' ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="?page=customers&action=profile&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline">📄</a>
                            <a href="?page=customers&action=delete&id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد؟')">🗑️</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <p>لا يوجد عملاء<?= $search ? ' مطابقون للبحث' : '' ?></p>
    </div>
    <?php endif; ?>
</div>

<!-- Add Customer Modal -->
<div class="modal-overlay" id="addCustomerModal">
    <div class="modal">
        <div class="modal-header">
            <h3>إضافة عميل جديد</h3>
            <button class="modal-close" onclick="closeModal('addCustomerModal')">&times;</button>
        </div>
        <form method="POST" action="?page=customers&action=store">
            <div class="modal-body">
                <div class="form-group">
                    <label>اسم العميل *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>رقم الهاتف</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="form-group">
                    <label>العنوان</label>
                    <input type="text" name="address" class="form-control">
                </div>
                <div class="form-group">
                    <label>الحد الائتماني</label>
                    <input type="number" name="credit_limit" class="form-control" step="0.01" min="0" value="0">
                    <small class="text-muted">0 = بلا حد</small>
                </div>
                <div class="form-group">
                    <label>ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addCustomerModal')">إلغاء</button>
                <button type="submit" class="btn btn-primary">إضافة</button>
            </div>
        </form>
    </div>
</div>
