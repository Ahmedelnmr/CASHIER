<!-- Reports Dashboard -->
<div class="page-header">
    <div>
        <h1>التقارير</h1>
        <p class="subtitle">تقارير المبيعات والمخزون والأرباح</p>
    </div>
</div>

<!-- Report Type Tabs -->
<div class="card" style="padding:12px 16px;">
    <div class="btn-group">
        <button class="btn btn-primary" onclick="loadReport('daily')">يومي</button>
        <button class="btn btn-outline" onclick="loadReport('weekly')">أسبوعي</button>
        <button class="btn btn-outline" onclick="loadReport('monthly')">شهري</button>
        <button class="btn btn-outline" onclick="loadReport('topProducts')">الأكثر مبيعاً</button>
        <button class="btn btn-outline" onclick="loadReport('leastProducts')">الأقل مبيعاً</button>
        <button class="btn btn-outline" onclick="loadReport('lowStock')">المخزون المنخفض</button>
        <button class="btn btn-outline" onclick="loadReport('suppliers')">المشتريات حسب المورد</button>
        <button class="btn btn-outline" onclick="loadReport('financial')">الأرباح والمصروفات</button>
        <button class="btn btn-outline" onclick="loadReport('category')">المبيعات حسب القسم</button>
        <button class="btn btn-outline" onclick="loadReport('profit')">هامش الربح</button>
        <button class="btn btn-outline" onclick="loadReport('deadStock')">المخزون الراكد</button>
        <button class="btn btn-outline" onclick="loadReport('cashier')">أداء الكاشير</button>
        <button class="btn btn-outline" onclick="loadReport('debtors')">العملاء المدينون</button>
    </div>
</div>

<!-- Date Range Picker -->
<div class="card" id="dateRangeCard" style="padding:14px 20px;display:none;">
    <div style="display:flex;align-items:center;gap:12px;">
        <label class="text-muted">من:</label>
        <input type="date" id="reportDateFrom" class="form-control" style="width:160px;">
        <label class="text-muted">إلى:</label>
        <input type="date" id="reportDateTo" class="form-control" style="width:160px;">
        <button class="btn btn-primary btn-sm" onclick="loadRangeReport()">عرض</button>
        <button class="btn btn-outline btn-sm" onclick="exportReport()">📥 تصدير CSV</button>
    </div>
</div>

<!-- Report Content Area -->
<div class="card" id="reportContent">
    <div style="padding:40px; text-align:center;">
        <div class="spinner"></div>
        <p style="margin-top:10px; font-weight:bold; color:var(--text-muted);">جاري تحميل التقرير...</p>
    </div>
</div>

<script>
var currentReport = '';

function loadRangeReport() {
    var from = document.getElementById('reportDateFrom').value;
    var to = document.getElementById('reportDateTo').value;
    if (!from || !to) {
        alert('الرجاء اختيار الفترة الزمنية');
        return;
    }
    if (currentReport === 'financial') {
        loadReport('financial');
    }
}

function loadReport(type) {
    currentReport = type;
    var content = document.getElementById('reportContent');
    var dateCard = document.getElementById('dateRangeCard');
    content.innerHTML = '<div style="padding:40px;text-align:center;"><div class="spinner"></div><p style="margin-top:10px;font-weight:bold;color:var(--text-muted);">جاري التحميل...</p></div>';

    // Update active button
    var buttons = document.querySelectorAll('.btn-group .btn');
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].className = buttons[i].className.replace('btn-primary', 'btn-outline');
    }
    var reportTypes = ['daily', 'weekly', 'monthly', 'topProducts', 'leastProducts', 'lowStock', 'suppliers', 'financial', 'category', 'profit', 'deadStock', 'cashier', 'debtors'];
    var idx = reportTypes.indexOf(type);
    if (idx >= 0 && buttons[idx]) {
        buttons[idx].className = buttons[idx].className.replace('btn-outline', 'btn-primary');
    }

    var url = '';
    switch (type) {
        case 'daily':
            dateCard.style.display = 'none';
            url = '?page=reports&action=daily&date=' + new Date().toISOString().split('T')[0];
            apiRequest(url).then(function(res) {
                if (res.success) renderDailyReport(res.data);
                else showError(res.message);
            });
            break;

        case 'weekly':
            dateCard.style.display = 'none';
            var weekFrom = new Date(Date.now() - 7 * 86400000).toISOString().split('T')[0];
            var weekTo = new Date().toISOString().split('T')[0];
            url = '?page=reports&action=range&from=' + weekFrom + '&to=' + weekTo;
            apiRequest(url).then(function(res) {
                if (res.success) renderRangeReport(res.data, 'التقرير الأسبوعي');
                else showError(res.message);
            });
            break;

        case 'monthly':
            dateCard.style.display = 'none';
            var monthFrom = new Date().toISOString().slice(0, 8) + '01';
            var monthTo = new Date().toISOString().split('T')[0];
            url = '?page=reports&action=range&from=' + monthFrom + '&to=' + monthTo;
            apiRequest(url).then(function(res) {
                if (res.success) renderRangeReport(res.data, 'التقرير الشهري');
                else showError(res.message);
            });
            break;

        case 'topProducts':
            dateCard.style.display = 'none';
            apiRequest('?page=reports&action=topProducts').then(function(res) {
                if (res.success) renderProductReport(res.data, 'الأكثر مبيعاً');
                else showError(res.message);
            });
            break;

        case 'leastProducts':
            dateCard.style.display = 'none';
            apiRequest('?page=reports&action=leastProducts').then(function(res) {
                if (res.success) renderLeastReport(res.data);
                else showError(res.message);
            });
            break;

        case 'lowStock':
            dateCard.style.display = 'none';
            apiRequest('?page=reports&action=lowStock').then(function(res) {
                if (res.success) renderLowStockReport(res.data);
                else showError(res.message);
            });
            break;

        case 'suppliers':
            dateCard.style.display = 'none';
            apiRequest('?page=reports&action=supplierPurchases').then(function(res) {
                if (res.success) renderSupplierReport(res.data);
                else showError(res.message);
            });
            break;

        case 'financial':
            dateCard.style.display = 'flex';
            var finFrom = document.getElementById('reportDateFrom').value || (new Date().toISOString().slice(0, 8) + '01');
            var finTo = document.getElementById('reportDateTo').value || new Date().toISOString().split('T')[0];
            if (!document.getElementById('reportDateFrom').value) {
                document.getElementById('reportDateFrom').value = finFrom;
                document.getElementById('reportDateTo').value = finTo;
            }
            url = '?page=reports&action=financial&from=' + finFrom + '&to=' + finTo;
            apiRequest(url).then(function(res) {
                if (res.success) renderFinancialReport(res.data);
                else showError(res.message);
            });
            break;

        case 'category':
            dateCard.style.display = 'flex';
            var catFrom = document.getElementById('reportDateFrom').value || '';
            var catTo = document.getElementById('reportDateTo').value || '';
            url = '?page=reports&action=salesByCategory' + (catFrom ? '&from=' + catFrom : '') + (catTo ? '&to=' + catTo : '');
            apiRequest(url).then(function(res) {
                if (res.success) renderCategoryReport(res.data);
                else showError(res.message);
            });
            break;

        case 'profit':
            dateCard.style.display = 'flex';
            var profFrom = document.getElementById('reportDateFrom').value || '';
            var profTo = document.getElementById('reportDateTo').value || '';
            url = '?page=reports&action=productProfit' + (profFrom ? '&from=' + profFrom : '') + (profTo ? '&to=' + profTo : '');
            apiRequest(url).then(function(res) {
                if (res.success) renderProfitReport(res.data);
                else showError(res.message);
            });
            break;

        case 'deadStock':
            dateCard.style.display = 'none';
            apiRequest('?page=reports&action=deadStock&days=30').then(function(res) {
                if (res.success) renderDeadStockReport(res.data);
                else showError(res.message);
            });
            break;

        case 'cashier':
            dateCard.style.display = 'flex';
            var cashFrom = document.getElementById('reportDateFrom').value || '';
            var cashTo = document.getElementById('reportDateTo').value || '';
            url = '?page=reports&action=cashierPerformance' + (cashFrom ? '&from=' + cashFrom : '') + (cashTo ? '&to=' + cashTo : '');
            apiRequest(url).then(function(res) {
                if (res.success) renderCashierReport(res.data);
                else showError(res.message);
            });
            break;

        case 'debtors':
            dateCard.style.display = 'none';
            apiRequest('?page=reports&action=debtors').then(function(res) {
                if (res.success) renderDebtorsReport(res.data);
                else showError(res.message);
            });
            break;
    }
}

function showError(msg) {
    document.getElementById('reportContent').innerHTML =
        '<div class="empty-state"><p style="color:var(--danger);font-weight:bold;">حدث خطأ</p><p class="text-muted">' + (msg || 'حاول مرة أخرى') + '</p></div>';
}

function renderDailyReport(data) {
    var c = document.getElementById('reportContent');
    var s = data.summary;
    var rows = '';
    for (var i = 0; i < data.sales.length; i++) {
        var sale = data.sales[i];
        rows += '<tr><td>#' + (sale.sale_number || sale.id) + '</td><td class="fs-sm">' + sale.datetime + '</td><td>' + sale.item_count + '</td><td><span class="badge badge-success">نقدي</span></td><td class="fw-bold text-accent">' + parseFloat(sale.total).toFixed(2) + '</td></tr>';
    }
    c.innerHTML =
        '<div class="card-header"><h3>تقرير يومي — ' + data.date + '</h3></div>' +
        '<div class="stats-grid" style="margin-bottom:16px;">' +
        '<div class="stat-card"><div class="stat-icon teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></div><div class="stat-info"><h4>' + parseFloat(s.total).toFixed(2) + '</h4><p>إجمالي المبيعات</p></div></div>' +
        '<div class="stat-card"><div class="stat-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg></div><div class="stat-info"><h4>' + s.count + '</h4><p>عدد الفواتير</p></div></div>' +
        '<div class="stat-card"><div class="stat-icon orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5"></rect><line x1="12" y1="22" x2="12" y2="7"></line></svg></div><div class="stat-info"><h4>' + parseFloat(s.discount || 0).toFixed(2) + '</h4><p>الخصومات</p></div></div>' +
        '</div>' +
        (data.sales.length ? '<div class="table-wrapper"><table><thead><tr><th>#</th><th>الوقت</th><th>الأصناف</th><th>الدفع</th><th>الإجمالي</th></tr></thead><tbody>' + rows + '</tbody></table></div>' : '<div class="empty-state"><p>لا توجد مبيعات</p></div>');
}

function renderRangeReport(data, title) {
    var c = document.getElementById('reportContent');
    var t = data.totals;
    var rows = '';
    for (var i = 0; i < data.daily.length; i++) {
        var d = data.daily[i];
        rows += '<tr><td class="fw-bold">' + d.sale_date + '</td><td>' + d.count + '</td><td class="fw-bold text-accent">' + parseFloat(d.total).toFixed(2) + '</td></tr>';
    }
    c.innerHTML =
        '<div class="card-header"><h3>' + title + '</h3></div>' +
        '<div class="stats-grid" style="margin-bottom:16px;">' +
        '<div class="stat-card"><div class="stat-icon teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></div><div class="stat-info"><h4>' + parseFloat(t.total).toFixed(2) + '</h4><p>إجمالي المبيعات</p></div></div>' +
        '<div class="stat-card"><div class="stat-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg></div><div class="stat-info"><h4>' + t.count + '</h4><p>عدد الفواتير</p></div></div>' +
        '</div>' +
        (data.daily.length ? '<div class="table-wrapper"><table><thead><tr><th>التاريخ</th><th>الفواتير</th><th>الإجمالي</th></tr></thead><tbody>' + rows + '</tbody></table></div>' : '<div class="empty-state"><p>لا توجد بيانات</p></div>');
}

function renderProductReport(data, title) {
    var c = document.getElementById('reportContent');
    var rows = '';
    for (var i = 0; i < data.length; i++) {
        var p = data[i];
        rows += '<tr><td>' + (i + 1) + '</td><td class="fw-bold">' + p.product_name + '</td><td>' + parseFloat(p.total_qty).toFixed(2) + '</td><td>' + p.sale_count + '</td><td class="fw-bold text-accent">' + parseFloat(p.total_revenue).toFixed(2) + '</td></tr>';
    }
    c.innerHTML =
        '<div class="card-header"><h3>' + title + '</h3></div>' +
        (data.length ? '<div class="table-wrapper"><table><thead><tr><th>#</th><th>المنتج</th><th>الكمية</th><th>الفواتير</th><th>الإيرادات</th></tr></thead><tbody>' + rows + '</tbody></table></div>' : '<div class="empty-state"><p>لا توجد بيانات</p></div>');
}

function renderLeastReport(data) {
    var c = document.getElementById('reportContent');
    var rows = '';
    for (var i = 0; i < data.length; i++) {
        var p = data[i];
        rows += '<tr><td>' + (i + 1) + '</td><td class="fw-bold">' + p.name + '</td><td>' + parseFloat(p.stock_quantity).toFixed(2) + '</td><td class="text-warning">' + parseFloat(p.total_sold).toFixed(2) + '</td></tr>';
    }
    c.innerHTML =
        '<div class="card-header"><h3>الأقل مبيعاً</h3></div>' +
        (data.length ? '<div class="table-wrapper"><table><thead><tr><th>#</th><th>المنتج</th><th>المخزون</th><th>المبيعات</th></tr></thead><tbody>' + rows + '</tbody></table></div>' : '<div class="empty-state"><p>لا توجد بيانات</p></div>');
}

function renderLowStockReport(data) {
    var c = document.getElementById('reportContent');
    var rows = '';
    for (var i = 0; i < data.length; i++) {
        var p = data[i];
        rows += '<tr><td class="fw-bold">' + p.name + '</td><td><span class="badge badge-info">' + p.type + '</span></td><td><span class="badge badge-danger">' + p.stock_quantity + '</span></td><td>' + p.min_stock + '</td><td class="text-muted">' + (p.category || '—') + '</td></tr>';
    }
    c.innerHTML =
        '<div class="card-header"><h3>المخزون المنخفض (' + data.length + ')</h3></div>' +
        (data.length ? '<div class="table-wrapper"><table><thead><tr><th>المنتج</th><th>النوع</th><th>المخزون</th><th>الحد الأدنى</th><th>القسم</th></tr></thead><tbody>' + rows + '</tbody></table></div>' : '<div class="empty-state"><p>جميع المنتجات في مستوى آمن</p></div>');
}

function renderSupplierReport(data) {
    var c = document.getElementById('reportContent');
    var rows = '';
    for (var i = 0; i < data.length; i++) {
        var s = data[i];
        rows += '<tr><td class="fw-bold">' + s.name + '</td><td>' + s.invoice_count + '</td><td class="fw-bold text-accent">' + parseFloat(s.total).toFixed(2) + '</td></tr>';
    }
    c.innerHTML =
        '<div class="card-header"><h3>المشتريات حسب المورد</h3></div>' +
        (data.length ? '<div class="table-wrapper"><table><thead><tr><th>المورد</th><th>الفواتير</th><th>الإجمالي</th></tr></thead><tbody>' + rows + '</tbody></table></div>' : '<div class="empty-state"><p>لا توجد بيانات</p></div>');
}

function renderFinancialReport(data) {
    var c = document.getElementById('reportContent');
    var t = data.totals;
    var profitClass = t.profit >= 0 ? 'text-success' : 'text-danger';
    var profitBorder = t.profit >= 0 ? 'var(--success)' : 'var(--danger)';

    var dailyRows = '';
    for (var i = 0; i < data.daily.length; i++) {
        var d = data.daily[i];
        var pc = d.profit >= 0 ? 'text-success' : 'text-danger';
        var salAdv = (parseFloat(d.salaries || 0) + parseFloat(d.advances || 0)).toFixed(2);
        dailyRows += '<tr><td class="fw-bold">' + d.date + '</td><td class="text-success">' + parseFloat(d.sales).toFixed(2) + '</td><td class="text-danger">' + parseFloat(d.returns || 0).toFixed(2) + '</td><td class="text-success fw-bold">' + parseFloat(d.income).toFixed(2) + '</td><td class="text-danger">' + parseFloat(d.purchases || 0).toFixed(2) + '</td><td class="text-danger">' + salAdv + '</td><td class="text-danger">' + parseFloat(d.expenses || 0).toFixed(2) + '</td><td class="' + pc + ' fw-bold">' + parseFloat(d.profit).toFixed(2) + '</td><td class="text-muted">' + parseFloat(d.withdrawals || 0).toFixed(2) + '</td></tr>';
    }

    c.innerHTML =
        '<div class="card-header"><h3>تقرير الأرباح والمصروفات</h3></div>' +

        '<h4 style="margin:16px 0 10px;color:var(--success);font-weight:700;">الإيرادات</h4>' +
        '<div class="stats-grid" style="margin-bottom:16px;">' +
        '<div class="stat-card"><div class="stat-info"><h4 class="text-success">' + parseFloat(t.sales).toFixed(2) + '</h4><p>المبيعات</p></div></div>' +
        '<div class="stat-card"><div class="stat-info"><h4 class="text-danger">- ' + parseFloat(t.returns).toFixed(2) + '</h4><p>المرتجعات</p></div></div>' +
        (t.deposits > 0 ? '<div class="stat-card"><div class="stat-info"><h4 class="text-success">' + parseFloat(t.deposits).toFixed(2) + '</h4><p>إيداعات</p></div></div>' : '') +
        '<div class="stat-card" style="border:2px solid var(--success);"><div class="stat-info"><h4 class="text-success">' + parseFloat(t.income).toFixed(2) + '</h4><p><strong>صافي الإيرادات</strong></p></div></div>' +
        '</div>' +

        '<h4 style="margin:16px 0 10px;color:var(--danger);font-weight:700;">المصروفات التشغيلية</h4>' +
        '<div class="stats-grid" style="margin-bottom:16px;">' +
        '<div class="stat-card"><div class="stat-info"><h5 class="text-danger">' + parseFloat(t.purchases).toFixed(2) + '</h5><p>تكلفة المشتريات</p></div></div>' +
        '<div class="stat-card"><div class="stat-info"><h5 class="text-danger">' + parseFloat(t.salaries).toFixed(2) + '</h5><p>رواتب</p></div></div>' +
        '<div class="stat-card"><div class="stat-info"><h5 class="text-danger">' + parseFloat(t.advances).toFixed(2) + '</h5><p>سلف</p></div></div>' +
        '<div class="stat-card"><div class="stat-info"><h5 class="text-danger">' + parseFloat(t.expenses).toFixed(2) + '</h5><p>مصروفات تشغيل</p></div></div>' +
        '<div class="stat-card" style="border:2px solid var(--danger);"><div class="stat-info"><h4 class="text-danger">' + parseFloat(t.op_expenses).toFixed(2) + '</h4><p><strong>إجمالي المصروفات</strong></p></div></div>' +
        '</div>' +

        '<div class="stat-card" style="border:2px solid ' + profitBorder + ';margin-bottom:20px;">' +
        '<div class="stat-info"><h3 class="' + profitClass + '" style="font-size:28px;">' + parseFloat(t.profit).toFixed(2) + '</h3><p><strong>صافي الربح</strong></p></div>' +
        '</div>' +

        (t.withdrawals > 0 ? '<div class="stat-card" style="border:1px dashed var(--border-color);margin-bottom:20px;opacity:0.7;"><div class="stat-info"><h5 class="text-muted">' + parseFloat(t.withdrawals).toFixed(2) + '</h5><p class="text-muted">سحب شخصي (ليس مصروفاً)</p></div></div>' : '') +

        (data.daily.length ? '<h4 style="margin:16px 0 10px;font-weight:700;">التفاصيل اليومية</h4><div class="table-wrapper"><table><thead><tr><th>التاريخ</th><th>المبيعات</th><th>المرتجعات</th><th>صافي الدخل</th><th>المشتريات</th><th>رواتب/سلف</th><th>مصروفات</th><th>صافي الربح</th><th>سحب شخصي</th></tr></thead><tbody>' + dailyRows + '</tbody></table></div>' : '<div class="empty-state"><p>لا توجد بيانات</p></div>');
}

// Load daily report by default (deferred until app_core.js is loaded)
document.addEventListener('DOMContentLoaded', function() {
    try {
        loadReport('daily');
    } catch (e) {
        document.getElementById('reportContent').innerHTML = '<div class="empty-state"><p style="color:var(--danger);">خطأ: ' + e.message + '</p></div>';
    }
});

function exportReport() {
    var from = document.getElementById('reportDateFrom').value || '';
    var to = document.getElementById('reportDateTo').value || '';
    var url = '?page=reports&action=export&type=' + currentReport;
    if (from) url += '&from=' + from;
    if (to) url += '&to=' + to;
    window.open(url, '_blank');
}

function renderCategoryReport(data) {
    var c = document.getElementById('reportContent');
    var rows = '';
    var totalRevenue = 0;
    for (var i = 0; i < data.length; i++) {
        totalRevenue += parseFloat(data[i].total_revenue);
    }
    for (var i = 0; i < data.length; i++) {
        var d = data[i];
        var pct = totalRevenue > 0 ? ((parseFloat(d.total_revenue) / totalRevenue) * 100).toFixed(1) : '0.0';
        rows += '<tr><td class="fw-bold">' + escapeHtml(d.category) + '</td><td>' + d.sale_count + '</td><td>' + parseFloat(d.total_qty).toFixed(2) + '</td><td class="fw-bold text-accent">' + parseFloat(d.total_revenue).toFixed(2) + '</td><td>' + pct + '%</td></tr>';
    }
    c.innerHTML =
        '<div class="card-header"><h3>المبيعات حسب القسم</h3><button class="btn btn-sm btn-outline" onclick="exportReport()">📥 تصدير</button></div>' +
        (data.length ? '<div class="table-wrapper"><table><thead><tr><th>القسم</th><th>الفواتير</th><th>الكمية</th><th>الإيرادات</th><th>النسبة</th></tr></thead><tbody>' + rows + '</tbody></table></div>' : '<div class="empty-state"><p>لا توجد بيانات</p></div>');
}

function renderProfitReport(data) {
    var c = document.getElementById('reportContent');
    var rows = '';
    for (var i = 0; i < data.length; i++) {
        var p = data[i];
        var profitClass = parseFloat(p.profit) >= 0 ? 'text-success' : 'text-danger';
        rows += '<tr><td>' + (i + 1) + '</td><td class="fw-bold">' + escapeHtml(p.product_name) + '</td><td>' + parseFloat(p.total_qty).toFixed(2) + '</td><td>' + parseFloat(p.total_revenue).toFixed(2) + '</td><td>' + parseFloat(p.total_cost).toFixed(2) + '</td><td class="' + profitClass + ' fw-bold">' + parseFloat(p.profit).toFixed(2) + '</td><td>' + parseFloat(p.margin_pct).toFixed(1) + '%</td></tr>';
    }
    c.innerHTML =
        '<div class="card-header"><h3>هامش الربح حسب المنتج</h3><button class="btn btn-sm btn-outline" onclick="exportReport()">📥 تصدير</button></div>' +
        (data.length ? '<div class="table-wrapper"><table><thead><tr><th>#</th><th>المنتج</th><th>الكمية</th><th>الإيراد</th><th>التكلفة</th><th>الربح</th><th>الهامش</th></tr></thead><tbody>' + rows + '</tbody></table></div>' : '<div class="empty-state"><p>لا توجد بيانات</p></div>');
}

function renderDeadStockReport(data) {
    var c = document.getElementById('reportContent');
    var rows = '';
    var totalValue = 0;
    for (var i = 0; i < data.length; i++) {
        totalValue += parseFloat(data[i].stock_value);
    }
    for (var i = 0; i < data.length; i++) {
        var p = data[i];
        rows += '<tr><td>' + (i + 1) + '</td><td class="fw-bold">' + escapeHtml(p.name) + '</td><td>' + (p.barcode || '—') + '</td><td>' + parseFloat(p.stock_quantity).toFixed(2) + '</td><td>' + parseFloat(p.purchase_price).toFixed(2) + '</td><td class="fw-bold text-warning">' + parseFloat(p.stock_value).toFixed(2) + '</td><td class="text-muted">' + (p.last_sale_date || 'لم يُبَع') + '</td></tr>';
    }
    c.innerHTML =
        '<div class="card-header"><h3>المخزون الراكد (آخر 30 يوم)</h3><button class="btn btn-sm btn-outline" onclick="exportReport()">📥 تصدير</button></div>' +
        '<div class="stats-grid" style="margin-bottom:16px;"><div class="stat-card"><div class="stat-info"><h4>' + data.length + '</h4><p>منتجات راكدة</p></div></div><div class="stat-card"><div class="stat-info"><h4 class="text-warning">' + totalValue.toFixed(2) + '</h4><p>قيمة المخزون الراكد</p></div></div></div>' +
        (data.length ? '<div class="table-wrapper"><table><thead><tr><th>#</th><th>المنتج</th><th>الباركود</th><th>المخزون</th><th>سعر التكلفة</th><th>القيمة</th><th>آخر بيع</th></tr></thead><tbody>' + rows + '</tbody></table></div>' : '<div class="empty-state"><p>لا يوجد مخزون راكد</p></div>');
}

function renderCashierReport(data) {
    var c = document.getElementById('reportContent');
    var rows = '';
    for (var i = 0; i < data.length; i++) {
        var d = data[i];
        rows += '<tr><td>' + (i + 1) + '</td><td class="fw-bold">' + escapeHtml(d.cashier_name) + '</td><td>' + d.sale_count + '</td><td class="fw-bold text-accent">' + parseFloat(d.total_sales).toFixed(2) + '</td><td>' + parseFloat(d.total_discounts).toFixed(2) + '</td><td>' + parseFloat(d.avg_sale).toFixed(2) + '</td></tr>';
    }
    c.innerHTML =
        '<div class="card-header"><h3>أداء الكاشير</h3></div>' +
        (data.length ? '<div class="table-wrapper"><table><thead><tr><th>#</th><th>الكاشير</th><th>الفواتير</th><th>إجمالي المبيعات</th><th>الخصومات</th><th>متوسط الفاتورة</th></tr></thead><tbody>' + rows + '</tbody></table></div>' : '<div class="empty-state"><p>لا توجد بيانات</p></div>');
}

function renderDebtorsReport(data) {
    var c = document.getElementById('reportContent');
    var rows = '';
    var totalDebt = 0;
    for (var i = 0; i < data.length; i++) {
        totalDebt += parseFloat(data[i].balance);
    }
    for (var i = 0; i < data.length; i++) {
        var d = data[i];
        rows += '<tr><td>' + (i + 1) + '</td><td class="fw-bold"><a href="?page=customers&action=profile&id=' + d.id + '">' + escapeHtml(d.name) + '</a></td><td>' + (d.phone || '—') + '</td><td class="fw-bold text-warning">' + parseFloat(d.balance).toFixed(2) + '</td><td>' + (d.credit_limit > 0 ? parseFloat(d.credit_limit).toFixed(2) : 'بلا حد') + '</td></tr>';
    }
    c.innerHTML =
        '<div class="card-header"><h3>العملاء المدينون</h3><button class="btn btn-sm btn-outline" onclick="exportReport()">📥 تصدير</button></div>' +
        '<div class="stats-grid" style="margin-bottom:16px;"><div class="stat-card"><div class="stat-info"><h4>' + data.length + '</h4><p>عميل مدين</p></div></div><div class="stat-card"><div class="stat-info"><h4 class="text-warning">' + totalDebt.toFixed(2) + '</h4><p>إجمالي الديون</p></div></div></div>' +
        (data.length ? '<div class="table-wrapper"><table><thead><tr><th>#</th><th>العميل</th><th>الهاتف</th><th>الرصيد</th><th>الحد الائتماني</th></tr></thead><tbody>' + rows + '</tbody></table></div>' : '<div class="empty-state"><p>لا يوجد عملاء مدينون</p></div>');
}
</script>
