<!-- Settings View -->
<div class="page-header">
    <div>
        <h1>إعدادات النظام</h1>
        <p class="subtitle">تكوين معلومات المتجر والإيصال</p>
    </div>
</div>

<?php if ($toastSuccess): ?>
<script>showToast('<?= htmlspecialchars($toastSuccess) ?>', 'success');</script>
<?php endif; ?>
<?php if ($toastError): ?>
<script>showToast('<?= htmlspecialchars($toastError) ?>', 'error');</script>
<?php endif; ?>

<form method="POST" action="?page=settings&action=save">
    <div class="card">
        <div class="card-header">
            <h3>معلومات المتجر</h3>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>اسم المتجر *</label>
                <input type="text" name="store_name" class="form-control" required value="<?= htmlspecialchars($settings['store_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>رقم الهاتف</label>
                <input type="text" name="store_phone" class="form-control" value="<?= htmlspecialchars($settings['store_phone'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>العنوان</label>
            <input type="text" name="store_address" class="form-control" value="<?= htmlspecialchars($settings['store_address'] ?? '') ?>">
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>إعدادات الإيصال</h3>
        </div>
        <div class="form-group">
            <label>نص أعلى الإيصال</label>
            <textarea name="receipt_header" class="form-control" rows="2" placeholder="نص اختياري يظهر في أعلى الإيصال"><?= htmlspecialchars($settings['receipt_header'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>نص أسفل الإيصال</label>
            <textarea name="receipt_footer" class="form-control" rows="2" placeholder="نص يظهر في أسفل الإيصال"><?= htmlspecialchars($settings['receipt_footer'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>إعدادات العملة</h3>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>رمز العملة</label>
                <input type="text" name="currency_symbol" class="form-control" value="<?= htmlspecialchars($settings['currency_symbol'] ?? 'ج.م') ?>">
            </div>
            <div class="form-group">
                <label>اسم العملة</label>
                <input type="text" name="currency_name" class="form-control" value="<?= htmlspecialchars($settings['currency_name'] ?? 'جنيه مصري') ?>">
            </div>
        </div>
    </div>

    <div class="btn-group mt-2">
        <button type="submit" class="btn btn-primary btn-lg">حفظ الإعدادات</button>
    </div>
</form>
