<!-- Reports Dashboard -->
<div class="page-header">
    <div>
        <h1>📈 التقارير</h1>
        <p class="subtitle">تقارير المبيعات والمخزون</p>
    </div>
</div>

<!-- Report Type Tabs -->
<div class="card" style="padding:12px 16px;">
    <div class="btn-group">
        <button class="btn btn-primary" onclick="loadReport('daily')">📅 يومي</button>
        <button class="btn btn-outline" onclick="loadReport('weekly')">📆 أسبوعي</button>
        <button class="btn btn-outline" onclick="loadReport('monthly')">🗓️ شهري</button>
        <button class="btn btn-outline" onclick="loadReport('topProducts')">🏆 الأكثر مبيعاً</button>
        <button class="btn btn-outline" onclick="loadReport('leastProducts')">📉 الأقل مبيعاً</button>
        <button class="btn btn-outline" onclick="loadReport('lowStock')">⚠️ المخزون المنخفض</button>
        <button class="btn btn-outline" onclick="loadReport('suppliers')">🏭 المشتريات حسب المورد</button>
        <button class="btn btn-outline" onclick="loadReport('financial')">💰 الأرباح والمصروفات</button>
    </div>
</div>

<script>
function loadRangeReport() {
    const from = document.getElementById('reportDateFrom').value;
    const to = document.getElementById('reportDateTo').value;
    
    if (!from || !to) {
        alert('الرجاء اختيار الفترة الزمنية');
        return;
    }
    
    if (currentReport === 'financial') {
        loadReport('financial');
    }
}
</script>

<!-- Date Range Picker (for range reports) -->
<div class="card" id="dateRangeCard" style="padding:14px 20px;display:none;">
    <div class="d-flex align-center gap-2">
        <label class="text-muted">من:</label>
        <input type="date" id="reportDateFrom" class="form-control" style="width:160px;">
        <label class="text-muted">إلى:</label>
        <input type="date" id="reportDateTo" class="form-control" style="width:160px;">
        <button class="btn btn-primary btn-sm" onclick="loadRangeReport()">عرض</button>
    </div>
</div>

<!-- Report Content Area -->
<div class="card" id="reportContent">
    <div style="padding:40px; text-align:center;">
        <div class="spinner"></div>
        <p style="margin-top:10px; font-weight:bold;">جاري تحميل التقرير...</p>
    </div>
</div>

<script>
// API Request Helper (inlined)
// API Request Helper (inlined)
async function apiRequest(url, options = {}) {
    try {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };


        const finalOptions = Object.assign({}, defaultOptions, options);
        if (options.body && typeof options.body === 'object') {
            finalOptions.body = JSON.stringify(options.body);
        }

        const response = await fetch(url, finalOptions);
        
        if (!response.ok) {
            throw new Error('HTTP Error ' + response.status);
        }

        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'خطأ في الاتصال: ' + error.message };
    }
}

let currentReport = '';

async function loadReport(type) {
    currentReport = type;
    const content = document.getElementById('reportContent');
    const dateCard = document.getElementById('dateRangeCard');
    content.innerHTML = '<div class="spinner"></div>';

    // Update active button (only if event exists)
    document.querySelectorAll('.btn-group .btn').forEach(b => {
        b.className = b.className.replace('btn-primary', 'btn-outline');
    });
    
    // Find and activate the button for this report type
    const buttons = document.querySelectorAll('.btn-group .btn');
    const buttonTexts = ['يومي', 'أسبوعي', 'شهري', 'الأكثر', 'الأقل', 'المخزون', 'المشتريات'];
    const reportTypes = ['daily', 'weekly', 'monthly', 'topProducts', 'leastProducts', 'lowStock', 'suppliers'];
    const index = reportTypes.indexOf(type);
    if (index >= 0 && buttons[index]) {
        buttons[index].className = buttons[index].className.replace('btn-outline', 'btn-primary');
    }

    try {
        let url = '';
        switch (type) {
            case 'daily':
                dateCard.style.display = 'none';
                url = '?page=reports&action=daily&date=' + (new Date().toISOString().split('T')[0]);
                const dailyRes = await apiRequest(url);
                if (dailyRes.success) {
                    renderDailyReport(dailyRes.data);
                } else {
                    throw new Error(dailyRes.message || 'فشل تحميل التقرير');
                }
                break;

            case 'weekly':
                dateCard.style.display = 'none';
                const weekFrom = new Date(Date.now() - 7 * 86400000).toISOString().split('T')[0];
                const weekTo = new Date().toISOString().split('T')[0];
                url = `?page=reports&action=range&from=${weekFrom}&to=${weekTo}`;
                const weeklyRes = await apiRequest(url);
                if (weeklyRes.success) {
                    renderRangeReport(weeklyRes.data, 'التقرير الأسبوعي');
                } else {
                    throw new Error(weeklyRes.message || 'فشل تحميل التقرير');
                }
                break;

            case 'monthly':
                dateCard.style.display = 'none';
                const monthFrom = new Date().toISOString().slice(0, 8) + '01';
                const monthTo = new Date().toISOString().split('T')[0];
                url = `?page=reports&action=range&from=${monthFrom}&to=${monthTo}`;
                const monthlyRes = await apiRequest(url);
                if (monthlyRes.success) {
                    renderRangeReport(monthlyRes.data, 'التقرير الشهري');
                } else {
                    throw new Error(monthlyRes.message || 'فشل تحميل التقرير');
                }
                break;

            case 'topProducts':
                dateCard.style.display = 'none';
                const topRes = await apiRequest('?page=reports&action=topProducts');
                if (topRes.success) {
                    renderProductReport(topRes.data, '🏆 الأكثر مبيعاً');
                } else {
                    throw new Error(topRes.message || 'فشل تحميل التقرير');
                }
                break;

            case 'leastProducts':
                dateCard.style.display = 'none';
                const leastRes = await apiRequest('?page=reports&action=leastProducts');
                if (leastRes.success) {
                    renderLeastReport(leastRes.data);
                } else {
                    throw new Error(leastRes.message || 'فشل تحميل التقرير');
                }
                break;

            case 'lowStock':
                dateCard.style.display = 'none';
                const lowRes = await apiRequest('?page=reports&action=lowStock');
                if (lowRes.success) {
                    renderLowStockReport(lowRes.data);
                } else {
                    throw new Error(lowRes.message || 'فشل تحميل التقرير');
                }
                break;

            case 'suppliers':
                dateCard.style.display = 'none';
                const supRes = await apiRequest('?page=reports&action=supplierPurchases');
                if (supRes.success) {
                    renderSupplierReport(supRes.data);
                } else {
                    throw new Error(supRes.message || 'فشل تحميل التقرير');
                }
                break;

            case 'financial':
                dateCard.style.display = 'flex';
                // Use input values if set, otherwise default to current month
                const finFrom = document.getElementById('reportDateFrom').value || (new Date().toISOString().slice(0, 8) + '01');
                const finTo = document.getElementById('reportDateTo').value || (new Date().toISOString().split('T')[0]);
                
                // Set inputs if empty
                if (!document.getElementById('reportDateFrom').value) {
                    document.getElementById('reportDateFrom').value = finFrom;
                    document.getElementById('reportDateTo').value = finTo;
                }

                url = `?page=reports&action=financial&from=${finFrom}&to=${finTo}`;
                const finRes = await apiRequest(url);
                if (finRes.success) {
                    renderFinancialReport(finRes.data);
                } else {
                    throw new Error(finRes.message || 'فشل تحميل التقرير');
                }
                break;

        }
    } catch (error) {
        console.error('Report Error:', error);
        content.innerHTML = `
            <div class="empty-state">
                <div class="icon" style="font-size:48px;">❌</div>
                <p style="color:#ef4444; font-weight:bold;">حدث خطأ أثناء تحميل التقرير</p>
                <p class="text-muted">${error.message || 'حاول مرة أخرى'}</p>
            </div>
        `;
    }
}

function renderDailyReport(data) {
    const content = document.getElementById('reportContent');
    const s = data.summary;
    content.innerHTML = `
        <div class="card-header"><h3>📅 تقرير يومي - ${data.date}</h3></div>
        <div class="stats-grid" style="margin-bottom:16px;">
            <div class="stat-card"><div class="stat-icon green">💵</div><div class="stat-info"><h4>${parseFloat(s.total).toFixed(2)}</h4><p>إجمالي المبيعات</p></div></div>
            <div class="stat-card"><div class="stat-icon blue">🧾</div><div class="stat-info"><h4>${s.count}</h4><p>عدد الفواتير</p></div></div>
            <div class="stat-card"><div class="stat-icon orange">🏷️</div><div class="stat-info"><h4>${parseFloat(s.discount || 0).toFixed(2)}</h4><p>إجمالي الخصومات</p></div></div>
        </div>
        ${data.sales.length ? `
        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>الوقت</th><th>عدد الأصناف</th><th>الدفع</th><th>الإجمالي</th></tr></thead>
                <tbody>
                    ${data.sales.map(s => `
                        <tr>
                            <td>#${s.sale_number || s.id}</td>
                            <td class="fs-sm">${s.datetime}</td>
                            <td>${s.item_count}</td>
                            <td><span class="badge badge-success">${s.payment_method === 'cash' ? 'نقدي' : s.payment_method}</span></td>
                            <td class="fw-bold text-accent">${parseFloat(s.total).toFixed(2)}</td>
                        </tr>`).join('')}
                </tbody>
            </table>
        </div>` : '<div class="empty-state"><p>لا توجد مبيعات في هذا اليوم</p></div>'}
    `;
}

function renderRangeReport(data, title) {
    const content = document.getElementById('reportContent');
    const t = data.totals;
    content.innerHTML = `
        <div class="card-header"><h3>${title}</h3></div>
        <div class="stats-grid" style="margin-bottom:16px;">
            <div class="stat-card"><div class="stat-icon green">💵</div><div class="stat-info"><h4>${parseFloat(t.total).toFixed(2)}</h4><p>إجمالي المبيعات</p></div></div>
            <div class="stat-card"><div class="stat-icon blue">🧾</div><div class="stat-info"><h4>${t.count}</h4><p>عدد الفواتير</p></div></div>
        </div>
        ${data.daily.length ? `
        <div class="table-wrapper">
            <table>
                <thead><tr><th>التاريخ</th><th>عدد الفواتير</th><th>الإجمالي</th></tr></thead>
                <tbody>
                    ${data.daily.map(d => `
                        <tr>
                            <td class="fw-bold">${d.sale_date}</td>
                            <td>${d.count}</td>
                            <td class="fw-bold text-accent">${parseFloat(d.total).toFixed(2)}</td>
                        </tr>`).join('')}
                </tbody>
            </table>
        </div>` : '<div class="empty-state"><p>لا توجد بيانات</p></div>'}
    `;
}

function renderProductReport(data, title) {
    const content = document.getElementById('reportContent');
    content.innerHTML = `
        <div class="card-header"><h3>${title}</h3></div>
        ${data.length ? `
        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>المنتج</th><th>الكمية المباعة</th><th>عدد الفواتير</th><th>إجمالي الإيرادات</th></tr></thead>
                <tbody>
                    ${data.map((p, i) => `
                        <tr>
                            <td>${i + 1}</td>
                            <td class="fw-bold">${p.product_name}</td>
                            <td>${parseFloat(p.total_qty).toFixed(2)}</td>
                            <td>${p.sale_count}</td>
                            <td class="fw-bold text-accent">${parseFloat(p.total_revenue).toFixed(2)}</td>
                        </tr>`).join('')}
                </tbody>
            </table>
        </div>` : '<div class="empty-state"><p>لا توجد بيانات</p></div>'}
    `;
}

function renderLeastReport(data) {
    const content = document.getElementById('reportContent');
    content.innerHTML = `
        <div class="card-header"><h3>📉 الأقل مبيعاً</h3></div>
        ${data.length ? `
        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>المنتج</th><th>المخزون</th><th>إجمالي المبيعات</th></tr></thead>
                <tbody>
                    ${data.map((p, i) => `
                        <tr>
                            <td>${i + 1}</td>
                            <td class="fw-bold">${p.name}</td>
                            <td>${parseFloat(p.stock_quantity).toFixed(2)}</td>
                            <td class="text-warning">${parseFloat(p.total_sold).toFixed(2)}</td>
                        </tr>`).join('')}
                </tbody>
            </table>
        </div>` : '<div class="empty-state"><p>لا توجد بيانات</p></div>'}
    `;
}

function renderLowStockReport(data) {
    const content = document.getElementById('reportContent');
    content.innerHTML = `
        <div class="card-header"><h3>⚠️ المخزون المنخفض (${data.length})</h3></div>
        ${data.length ? `
        <div class="table-wrapper">
            <table>
                <thead><tr><th>المنتج</th><th>النوع</th><th>المخزون</th><th>الحد الأدنى</th><th>القسم</th></tr></thead>
                <tbody>
                    ${data.map(p => `
                        <tr>
                            <td class="fw-bold">${p.name}</td>
                            <td><span class="badge badge-info">${p.type}</span></td>
                            <td><span class="badge badge-danger">${p.stock_quantity}</span></td>
                            <td>${p.min_stock}</td>
                            <td class="text-muted">${p.category || '—'}</td>
                        </tr>`).join('')}
                </tbody>
            </table>
        </div>` : '<div class="empty-state"><p>✅ جميع المنتجات في مستوى آمن</p></div>'}
    `;
}

function renderSupplierReport(data) {
    const content = document.getElementById('reportContent');
    content.innerHTML = `
        <div class="card-header"><h3>🏭 المشتريات حسب المورد</h3></div>
        ${data.length ? `
        <div class="table-wrapper">
            <table>
                <thead><tr><th>المورد</th><th>عدد الفواتير</th><th>إجمالي المشتريات</th></tr></thead>
                <tbody>
                    ${data.map(s => `
                        <tr>
                            <td class="fw-bold">${s.name}</td>
                            <td>${s.invoice_count}</td>
                            <td class="fw-bold text-accent">${parseFloat(s.total).toFixed(2)}</td>
                        </tr>`).join('')}
                </tbody>
            </table>
        </div>` : '<div class="empty-state"><p>لا توجد بيانات</p></div>'}
    `;
}


// Load daily report by default


function renderFinancialReport(data) {
    const content = document.getElementById('reportContent');
    const t = data.totals;
    const profitClass = t.profit >= 0 ? 'text-success' : 'text-danger';
    
    content.innerHTML = `
        <div class="card-header"><h3>💰 تقرير الأرباح والمصروفات التفصيلي</h3></div>
        
        <h4 style="margin:16px 0 10px; color:#27ae60;">📥 الإيرادات</h4>
        <div class="stats-grid" style="margin-bottom:16px; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));">
            <div class="stat-card">
                <div class="stat-icon green">🧾</div>
                <div class="stat-info"><h4 class="text-success">${parseFloat(t.sales).toFixed(2)}</h4><p>المبيعات</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">↩️</div>
                <div class="stat-info"><h4 class="text-danger">- ${parseFloat(t.returns).toFixed(2)}</h4><p>المرتجعات</p></div>
            </div>
            ${t.deposits > 0 ? `<div class="stat-card">
                <div class="stat-icon green">💵</div>
                <div class="stat-info"><h4 class="text-success">${parseFloat(t.deposits).toFixed(2)}</h4><p>إيداعات يدوية</p></div>
            </div>` : ''}
            <div class="stat-card" style="border: 2px solid #27ae60;">
                <div class="stat-icon green">💵</div>
                <div class="stat-info"><h4 class="text-success" style="font-size:22px;">${parseFloat(t.income).toFixed(2)}</h4><p><strong>صافي الإيرادات</strong></p></div>
            </div>
        </div>

        <h4 style="margin:16px 0 10px; color:#e74c3c;">📤 المصروفات التشغيلية</h4>
        <div class="stats-grid" style="margin-bottom:16px; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));">
            <div class="stat-card">
                <div class="stat-info"><h5 class="text-danger">${parseFloat(t.purchases).toFixed(2)}</h5><p>🛒 تكلفة المشتريات</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><h5 class="text-danger">${parseFloat(t.salaries).toFixed(2)}</h5><p>👤 رواتب موظفين</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><h5 class="text-danger">${parseFloat(t.advances).toFixed(2)}</h5><p>💳 سلف موظفين</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><h5 class="text-danger">${parseFloat(t.expenses).toFixed(2)}</h5><p>🔧 مصروفات تشغيل</p></div>
            </div>
            <div class="stat-card" style="border: 2px solid #e74c3c;">
                <div class="stat-info"><h4 class="text-danger" style="font-size:22px;">${parseFloat(t.op_expenses).toFixed(2)}</h4><p><strong>إجمالي المصروفات</strong></p></div>
            </div>
        </div>

        <div class="stat-card" style="border: 2px solid ${t.profit >= 0 ? '#27ae60' : '#e74c3c'}; margin-bottom:20px;">
            <div class="stat-icon ${t.profit >= 0 ? 'green' : 'orange'}">⚖️</div>
            <div class="stat-info">
                <h3 class="${profitClass}" style="direction:ltr; font-size:28px;">${parseFloat(t.profit).toFixed(2)}</h3>
                <p><strong>صافي الربح (إيرادات - مصروفات تشغيلية)</strong></p>
            </div>
        </div>

        ${t.withdrawals > 0 ? `<div class="stat-card" style="border:1px dashed #aaa; margin-bottom:20px; opacity:0.8;">
            <div class="stat-info"><h5 style="color:#888;">${parseFloat(t.withdrawals).toFixed(2)}</h5>
            <p style="color:#aaa;">🏧 سحب شخصي (ليس مصروفاً تشغيلياً)</p></div>
        </div>` : ''}

        ${data.daily.length ? `
        <h4 style="margin:16px 0 10px;">📊 التفاصيل اليومية</h4>
        <div class="table-wrapper">
            <table>
                <thead><tr>
                    <th>التاريخ</th><th>المبيعات</th><th>المرتجعات</th><th>صافي الدخل</th>
                    <th>المشتريات</th><th>رواتب وسلف</th><th>مصروفات</th><th>صافي الربح</th><th>سحب شخصي</th>
                </tr></thead>
                <tbody>
                    ${data.daily.map(d => {
                        const pc = d.profit >= 0 ? 'text-success' : 'text-danger';
                        const salAdv = (parseFloat(d.salaries || 0) + parseFloat(d.advances || 0)).toFixed(2);
                        return `<tr>
                            <td class="fw-bold">${d.date}</td>
                            <td class="text-success">${parseFloat(d.sales).toFixed(2)}</td>
                            <td class="text-danger">${parseFloat(d.returns || 0).toFixed(2)}</td>
                            <td class="text-success fw-bold">${parseFloat(d.income).toFixed(2)}</td>
                            <td class="text-danger">${parseFloat(d.purchases || 0).toFixed(2)}</td>
                            <td class="text-danger">${salAdv}</td>
                            <td class="text-danger">${parseFloat(d.expenses || 0).toFixed(2)}</td>
                            <td class="${pc}" style="direction:ltr;font-weight:bold">${parseFloat(d.profit).toFixed(2)}</td>
                            <td style="color:#999;">${parseFloat(d.withdrawals || 0).toFixed(2)}</td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
        </div>` : '<div class="empty-state"><p>لا توجد بيانات</p></div>'}
    `;
}

// Load daily report by default - executed immediately at end of body
try {
    loadReport('daily');
} catch(e) {
    console.error('Global Script Error:', e);
    document.getElementById('reportContent').innerHTML = '<div style="color:red; padding:20px; text-align:center;">حدث خطأ غير متوقع: ' + e.message + '</div>';
}
</script>
