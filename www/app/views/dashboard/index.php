<!-- Dashboard View -->
<div class="page-header">
    <div>
        <h1>لوحة التحكم</h1>
        <p class="subtitle">نظرة عامة على أداء النظام — <?= date('Y/m/d') ?></p>
    </div>
    <div class="btn-group">
        <a href="?page=pos" class="btn btn-success">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            نقطة البيع
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon teal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
        <div class="stat-info">
            <h4><?= number_format($summary['today_sales_total'] ?? 0, 2) ?></h4>
            <p>مبيعات اليوم</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
        </div>
        <div class="stat-info">
            <h4><?= $summary['today_sales_count'] ?? 0 ?></h4>
            <p>عدد الفواتير</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
        </div>
        <div class="stat-info">
            <h4><?= $summary['product_count'] ?? 0 ?></h4>
            <p>إجمالي المنتجات</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
        </div>
        <div class="stat-info">
            <h4><?= number_format($summary['month_total'] ?? 0, 2) ?></h4>
            <p>مبيعات الشهر</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        </div>
        <div class="stat-info">
            <h4><?= $summary['low_stock_count'] ?? 0 ?></h4>
            <p>مخزون منخفض</p>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h3>إجراءات سريعة</h3>
    </div>
    <div class="btn-group">
        <a href="?page=pos" class="btn btn-success btn-lg">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            نقطة البيع
        </a>
        <a href="?page=products&action=create" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            إضافة منتج
        </a>
        <a href="?page=purchases&action=create" class="btn btn-outline">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            فاتورة شراء
        </a>
        <a href="?page=inventory" class="btn btn-outline">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
            المخزون
        </a>
        <a href="?page=reports" class="btn btn-outline">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
            التقارير
        </a>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Low Stock Alerts -->
    <div class="card">
        <div class="card-header">
            <h3>تنبيهات المخزون المنخفض</h3>
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
                        <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                        <td><span class="badge badge-danger"><?= $p['stock_quantity'] ?></span></td>
                        <td><?= $p['min_stock'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <p>جميع المنتجات في المستوى الآمن</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Sales -->
    <div class="card">
        <div class="card-header">
            <h3>آخر المبيعات</h3>
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
                        <td class="fw-bold"><?= $s['invoice_number'] ?? ('#' . $s['id']) ?></td>
                        <td class="fs-sm text-muted"><?= $s['datetime'] ?></td>
                        <td class="fw-bold text-accent"><?= number_format($s['total'], 2) ?></td>
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
