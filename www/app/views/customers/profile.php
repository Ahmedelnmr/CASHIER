<!-- Customer Profile View -->
<?php
$toastSuccess = $_SESSION['toast_success'] ?? '';
$toastError = $_SESSION['toast_error'] ?? '';
unset($_SESSION['toast_success'], $_SESSION['toast_error']);
?>

<div class="page-header">
    <div>
        <h1>📄 ملف العميل — <?= htmlspecialchars($customer['name']) ?></h1>
        <p class="subtitle"><?= htmlspecialchars($customer['phone'] ?: '') ?> | <?= htmlspecialchars($customer['address'] ?: '') ?></p>
    </div>
    <div class="btn-group">
        <button class="btn btn-success" onclick="openModal('paymentModal')">💰 تسجيل تسديد</button>
        <button class="btn btn-outline" onclick="openModal('editCustomerModal')">✏️ تعديل</button>
        <a href="?page=customers" class="btn btn-outline">← العودة</a>
    </div>
</div>

<?php if ($toastSuccess): ?>
<script>showToast('<?= htmlspecialchars($toastSuccess) ?>', 'success');</script>
<?php endif; ?>
<?php if ($toastError): ?>
<script>showToast('<?= htmlspecialchars($toastError) ?>', 'error');</script>
<?php endif; ?>

<!-- Customer Stats -->
<div class="stats-grid">
    <div class="stat-card" style="border:2px solid <?= $customer['balance'] > 0 ? 'var(--warning)' : 'var(--success)' ?>;">
        <div class="stat-info">
            <h4 class="<?= $customer['balance'] > 0 ? 'text-warning' : 'text-success' ?>"><?= number_format($customer['balance'], 2) ?></h4>
            <p>الرصيد الحالي (آجل)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h4><?= $customer['credit_limit'] > 0 ? number_format($customer['credit_limit'], 2) : 'بلا حد' ?></h4>
            <p>الحد الائتماني</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h4><?= $customer['created_at'] ?></h4>
            <p>تاريخ التسجيل</p>
        </div>
    </div>
</div>

<!-- Transaction History -->
<div class="card">
    <div class="card-header">
        <h3>📋 حركة الحساب</h3>
    </div>
    <?php if (!empty($transactions)): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>التاريخ</th>
                    <th>النوع</th>
                    <th>المبلغ</ال>
                    <th>الرصيد بعد</th>
                    <th>الوصف</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><?= $t['id'] ?></td>
                    <td class="fs-sm"><?= $t['created_at'] ?></td>
                    <td>
                        <?php
                        $typeLabels = ['sale' => 'بيع آجل', 'payment' => 'تسديد', 'credit' => 'دائن', 'adjustment' => 'تعديل'];
                        $typeBadges = ['sale' => 'badge-warning', 'payment' => 'badge-success', 'credit' => 'badge-info', 'adjustment' => 'badge-purple'];
                        ?>
                        <span class="badge <?= $typeBadges[$t['type']] ?? 'badge-info' ?>"><?= $typeLabels[$t['type']] ?? $t['type'] ?></span>
                    </td>
                    <td class="fw-bold <?= $t['type'] === 'payment' ? 'text-success' : 'text-danger' ?>">
                        <?= $t['type'] === 'payment' ? '-' : '+' ?><?= number_format($t['amount'], 2) ?>
                    </td>
                    <td><?= number_format($t['balance_after'], 2) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($t['description'] ?: '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <p>لا توجد حركات</p>
    </div>
    <?php endif; ?>
</div>

<!-- Payment Modal -->
<div class="modal-overlay" id="paymentModal">
    <div class="modal">
        <div class="modal-header">
            <h3>💰 تسجيل تسديد — <?= htmlspecialchars($customer['name']) ?></h3>
            <button class="modal-close" onclick="closeModal('paymentModal')">&times;</button>
        </div>
        <form method="POST" action="?page=customers&action=payment">
            <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label>المبلغ *</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>وصف</label>
                    <input type="text" name="description" class="form-control" placeholder="تسديد نقدي / تحويل بنكي ...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('paymentModal')">إلغاء</button>
                <button type="submit" class="btn btn-success">تسجيل التسديد</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal-overlay" id="editCustomerModal">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ تعديل بيانات العميل</h3>
            <button class="modal-close" onclick="closeModal('editCustomerModal')">&times;</button>
        </div>
        <form method="POST" action="?page=customers&action=update">
            <input type="hidden" name="id" value="<?= $customer['id'] ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label>الاسم *</label>
                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($customer['name']) ?>">
                </div>
                <div class="form-group">
                    <label>الهاتف</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone']) ?>">
                </div>
                <div class="form-group">
                    <label>العنوان</label>
                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($customer['address']) ?>">
                </div>
                <div class="form-group">
                    <label>الحد الائتماني</label>
                    <input type="number" name="credit_limit" class="form-control" step="0.01" min="0" value="<?= $customer['credit_limit'] ?>">
                </div>
                <div class="form-group">
                    <label>ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($customer['notes']) ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editCustomerModal')">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
</div>
