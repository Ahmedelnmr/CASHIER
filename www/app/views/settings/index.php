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

<!-- License Info Card -->
<div class="card" style="margin-top: 24px; border: 1px solid <?= !empty($licenseInfo['valid']) ? '#86efac' : '#fca5a5' ?>;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; display: flex; align-items: center; gap: 8px;">
            🔐 ترخيص النظام (Hardware ID Locking)
        </h3>
        <span class="badge" style="background: <?= !empty($licenseInfo['valid']) ? '#10b981' : '#ef4444' ?>; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
            <?= !empty($licenseInfo['valid']) ? 'مفعّل ✅' : 'غير مفعّل ❌' ?>
        </span>
    </div>
    <div style="padding: 16px 20px;">
        <div class="form-row">
            <div class="form-group">
                <label>كود هذا الجهاز (Hardware ID):</label>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <input type="text" class="form-control" style="font-family: monospace; font-weight: bold; letter-spacing: 1px;" readonly value="<?= htmlspecialchars($licenseInfo['hardware_id'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>نوع وصلاحية الترخيص:</label>
                <input type="text" class="form-control" readonly value="<?= !empty($licenseInfo['valid']) ? htmlspecialchars($licenseInfo['expiry_label'] ?? 'مفعّل') : 'غير مفعّل' ?>">
            </div>
        </div>
        <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
            <a href="?page=license" class="btn btn-secondary" style="text-decoration: none;">
                ⚙️ إدارة وتجديد الترخيص
            </a>
        </div>
    </div>
</div>
