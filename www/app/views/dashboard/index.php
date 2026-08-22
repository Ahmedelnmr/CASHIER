<!-- Dashboard View -->
<div class="page-header">
    <div>
        <h1>📊 لوحة التحكم</h1>
        <p class="subtitle">نظرة عامة على أداء النظام</p>
    </div>
    <div>
        <span class="text-muted"><?= date('Y/m/d - H:i') ?></span>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green">💵</div>
        <div class="stat-info">
            <h4><?= formatCurrency($summary['today_sales_total'] ?? 0) ?></h4>
            <p>مبيعات اليوم</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">🧾</div>
        <div class="stat-info">
            <h4><?= $summary['today_sales_count'] ?? 0 ?></h4>
            <p>عدد الفواتير اليوم</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">📦</div>
        <div class="stat-info">
            <h4><?= $summary['product_count'] ?? 0 ?></h4>
            <p>إجمالي المنتجات</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">📈</div>
        <div class="stat-info">
            <h4><?= formatCurrency($summary['month_total'] ?? 0) ?></h4>
            <p>مبيعات الشهر</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">⚠️</div>
        <div class="stat-info">
            <h4><?= $summary['low_stock_count'] ?? 0 ?></h4>
            <p>منتجات منخفضة المخزون</p>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h3>⚡ إجراءات سريعة</h3>
    </div>
    <div class="btn-group">
        <a href="?page=pos" class="btn btn-success btn-lg">🛒 نقطة البيع</a>
        <a href="?page=products&action=create" class="btn btn-primary">➕ إضافة منتج</a>
        <a href="?page=purchases&action=create" class="btn btn-outline">🧾 فاتورة شراء</a>
        <a href="?page=inventory" class="btn btn-outline">📋 المخزون</a>
        <a href="?page=reports" class="btn btn-outline">📈 التقارير</a>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Low Stock Alerts -->
    <div class="card">
        <div class="card-header">
            <h3>⚠️ تنبيهات المخزون المنخفض</h3>
            <a href="?page=inventory" class="btn btn-sm btn-ghost">عرض الكل</a>
        </div>
        <?php if (!empty($lowStock)): ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>المخزون</th>
                        <th>الحد الأدنى</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($lowStock, 0, 5) as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><span class="badge badge-danger"><?= $p['stock_quantity'] ?></span></td>
                        <td><?= $p['min_stock'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <p>✅ جميع المنتجات في المستوى الآمن</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Sales -->
    <div class="card">
        <div class="card-header">
            <h3>💵 آخر المبيعات</h3>
            <a href="?page=sales" class="btn btn-sm btn-ghost">عرض الكل</a>
        </div>
        <?php if (!empty($recentSales)): ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>التاريخ</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentSales as $s): ?>
                    <tr>
                        <td><?= $s['sale_number'] ?? $s['id'] ?></td>
                        <td class="fs-sm"><?= $s['datetime'] ?></td>
                        <td class="fw-bold text-accent"><?= formatCurrency($s['total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <p>لا توجد مبيعات بعد</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
function formatCurrency($val) {
    return number_format((float)$val, 2);
}
?>
