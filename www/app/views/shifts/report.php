<!-- Shift Report (X/Z) View -->
<?php
$type = $report['type'];
$shift = $report['shift'];
$generatedAt = $report['generated_at'];
?>

<div class="page-header">
    <div>
        <h1>📊 تقرير <?= $type ?> — وردية #<?= $shift['id'] ?></h1>
        <p class="subtitle"><?= $type === 'X' ? 'تقرير مرحلي (الوردية مفتوحة)' : 'تقرير نهائي (الوردية مغلقة)' ?></p>
    </div>
    <div class="btn-group">
        <button class="btn btn-outline" onclick="window.print()">🖨️ طباعة</button>
        <a href="?page=shifts" class="btn btn-outline">← العودة</a>
    </div>
</div>

<div class="card" id="printableReport">
    <div style="text-align:center;padding:20px 0;border-bottom:2px solid var(--border-color);margin-bottom:20px;">
        <h2 style="margin-bottom:4px;">تقرير <?= $type ?></h2>
        <p class="text-muted">وردية #<?= $shift['id'] ?> — <?= $generatedAt ?></p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h4><?= number_format($shift['opening_cash'], 2) ?></h4>
                <p>العهدة الافتتاحية</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h4 class="text-success"><?= number_format($report['totals']['sales'], 2) ?></h4>
                <p>إجمالي المبيعات</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h4><?= $report['totals']['sale_count'] ?></h4>
                <p>عدد الفواتير</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h4 class="text-danger"><?= number_format($report['totals']['returns'], 2) ?></h4>
                <p>المرتجعات</p>
            </div>
        </div>
    </div>

    <?php if ($type === 'X'): ?>
    <div style="border-top:2px solid var(--border-color);padding-top:20px;margin-top:10px;">
        <h4 style="margin-bottom:12px;">ملخص نقدي</h4>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h4><?= number_format($report['expected_cash'], 2) ?></h4>
                    <p>النقد المتوقع في الدرج</p>
                </div>
            </div>
        </div>
        <p class="text-muted fs-sm" style="margin-top:8px;">العهدة (<?= number_format($shift['opening_cash'], 2) ?>) + المبيعات النقدية - المصروفات النقدية</p>
    </div>
    <?php endif; ?>

    <?php if ($type === 'Z' && isset($shift['closing_cash'])): ?>
    <div style="border-top:2px solid var(--border-color);padding-top:20px;margin-top:10px;">
        <h4 style="margin-bottom:12px;">ملخص الإغلاق</h4>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h4><?= number_format($shift['expected_cash'], 2) ?></h4>
                    <p>النقد المتوقع</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h4><?= number_format($shift['closing_cash'], 2) ?></h4>
                    <p>النقد الفعلي</p>
                </div>
            </div>
            <div class="stat-card" style="border:2px solid <?= ($shift['cash_difference'] ?? 0) >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                <div class="stat-info">
                    <h4 class="<?= ($shift['cash_difference'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($shift['cash_difference'] ?? 0, 2) ?></h4>
                    <p><strong><?= ($shift['cash_difference'] ?? 0) >= 0 ? 'زيادة' : 'عجز' ?></strong></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div style="border-top:1px dashed var(--border-color);padding-top:16px;margin-top:20px;font-size:12px;color:var(--text-muted);text-align:center;">
        <p>الكاشير: <?= htmlspecialchars($_SESSION['user_name'] ?? '—') ?></p>
        <p>الفتح: <?= $shift['opened_at'] ?> | الإغلاق: <?= $shift['closed_at'] ?? '—' ?></p>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden !important; }
    #printableReport, #printableReport * { visibility: visible !important; }
    #printableReport { position: absolute; top: 0; right: 0; left: 0; width: 100%; margin: 0; padding: 20px; }
    .sidebar, .page-header, .no-print, nav, header, footer { display: none !important; }
}
</style>
