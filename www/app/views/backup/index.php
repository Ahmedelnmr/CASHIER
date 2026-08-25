<!-- Backup & Restore View -->
<div class="page-header">
    <div>
        <h1>النسخ الاحتياطي</h1>
        <p class="subtitle">إنشاء واستعادة نسخ احتياطية من قاعدة البيانات</p>
    </div>
    <a href="?page=backup&action=create" class="btn btn-primary" onclick="return confirm('هل تريد إنشاء نسخة احتياطية الآن؟')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        إنشاء نسخة احتياطية
    </a>
</div>

<?php if ($toastSuccess): ?>
<script>showToast('<?= htmlspecialchars($toastSuccess) ?>', 'success');</script>
<?php endif; ?>
<?php if ($toastError): ?>
<script>showToast('<?= htmlspecialchars($toastError) ?>', 'error');</script>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>استعادة نسخة احتياطية</h3>
    </div>
    <form method="POST" action="?page=backup&action=restore" enctype="multipart/form-data">
        <div class="form-group">
            <label>اختر ملف النسخة الاحتياطية (.db)</label>
            <input type="file" name="backup_file" class="form-control" accept=".db" required>
            <small class="text-muted">سيتم إنشاء نسخة احتياطية تلقائياً قبل الاستعادة</small>
        </div>
        <button type="submit" class="btn btn-warning" onclick="return confirm('سيتم استبدال قاعدة البيانات الحالية. هل أنت متأكد؟')">استعادة</button>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3>النسخ الاحتياطية المتاحة</h3>
    </div>
    <?php if (!empty($backups)): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>اسم الملف</th>
                    <th>الحجم</th>
                    <th>التاريخ</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backups as $backup): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($backup['name']) ?></td>
                    <td><?= number_format($backup['size'] / 1024, 1) ?> KB</td>
                    <td class="text-muted"><?= $backup['date'] ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="?page=backup&action=download&file=<?= urlencode($backup['name']) ?>" class="btn btn-sm btn-outline">تحميل</a>
                            <a href="?page=backup&action=delete&file=<?= urlencode($backup['name']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذه النسخة؟')">حذف</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <p>لا توجد نسخ احتياطية بعد</p>
    </div>
    <?php endif; ?>
</div>
