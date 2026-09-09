<style>
/* =========================================================
   PURCHASE INVOICE — Modern ERP / POS Design
   Navy & Teal Theme | Chrome 57 & Desktop Compatible
   ========================================================= */

.purchase-invoice-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding-bottom: 24px;
}

/* Hide Spin Buttons */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
    -webkit-appearance: none; 
    margin: 0; 
}
input[type=number] { -moz-appearance: textfield; }

/* ---- Page Header ---- */
.purchase-header-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    background: #ffffff;
    padding: 16px 22px;
    border-radius: var(--radius, 12px);
    border: 1px solid var(--border-color, #e2e8f0);
    box-shadow: 0 1px 3px rgba(10, 22, 40, 0.05);
}

.purchase-header-info {
    display: flex;
    align-items: center;
    gap: 14px;
}

.purchase-header-icon {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, var(--navy-800, #0f2140), var(--navy-700, #152d54));
    color: var(--teal-300, #5ee2db);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(15, 33, 64, 0.15);
    flex-shrink: 0;
}

.purchase-header-title h1 {
    font-size: 20px;
    font-weight: 800;
    color: var(--navy-900, #0a1628);
    margin: 0 0 2px 0;
    line-height: 1.2;
}

.purchase-header-title .subtitle {
    font-size: 12.5px;
    color: var(--text-muted, #8899aa);
    margin: 0;
}

/* ---- Metadata Card ---- */
.purchase-meta-grid {
    background: #ffffff;
    border-radius: var(--radius, 12px);
    border: 1px solid var(--border-color, #e2e8f0);
    padding: 16px 20px;
    display: grid;
    grid-template-columns: 2fr 1.5fr 1.5fr 2fr;
    gap: 16px;
    align-items: center;
    box-shadow: 0 1px 3px rgba(10, 22, 40, 0.04);
}

@media (max-width: 992px) {
    .purchase-meta-grid {
        grid-template-columns: 1fr 1fr;
    }
}

.meta-field-box {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.meta-field-label {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--navy-800, #0f2140);
    display: flex;
    align-items: center;
    gap: 6px;
}

.meta-field-label .badge-auto {
    font-size: 10px;
    font-weight: 600;
    background: #e2e8f0;
    color: #475569;
    padding: 1px 6px;
    border-radius: 4px;
}

.meta-field-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.meta-field-icon {
    position: absolute;
    right: 12px;
    color: #94a3b8;
    pointer-events: none;
    display: flex;
    align-items: center;
}

.meta-input-styled {
    width: 100%;
    height: 42px;
    padding: 8px 36px 8px 12px;
    background: #ffffff;
    border: 1.5px solid var(--border-color, #e2e8f0);
    border-radius: 8px;
    font-size: 13.5px;
    font-family: inherit;
    color: var(--text-primary, #1a2332);
    transition: all 0.2s ease;
    outline: none;
}

.meta-input-styled:focus {
    border-color: var(--teal-500, #0ea5a0);
    box-shadow: 0 0 0 3px rgba(14, 165, 160, 0.12);
}

.meta-input-readonly {
    background: #f8fafc;
    color: #64748b;
    cursor: default;
}

/* Scanner Live Status Badge */
.scanner-live-card {
    background: linear-gradient(135deg, #f0fdfa, #f8fafc);
    border: 1.5px dashed var(--teal-300, #5ee2db);
    border-radius: 10px;
    padding: 10px 14px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 4px;
}

.scanner-live-top {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 700;
    color: var(--teal-600, #0d8a85);
}

.pulse-indicator {
    width: 8px;
    height: 8px;
    background: var(--teal-500, #0ea5a0);
    border-radius: 50%;
    box-shadow: 0 0 0 3px rgba(14, 165, 160, 0.25);
    animation: scannerPulse 2s infinite;
}

@keyframes scannerPulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(14, 165, 160, 0.6); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(14, 165, 160, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(14, 165, 160, 0); }
}

.scanner-status-tip {
    font-size: 11px;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ---- Hero Search & Barcode Bar ---- */
.purchase-search-card {
    background: #ffffff;
    border-radius: var(--radius, 12px);
    border: 1.5px solid var(--border-color, #e2e8f0);
    padding: 8px;
    position: relative;
    box-shadow: 0 2px 8px rgba(10, 22, 40, 0.05);
    transition: all 0.2s ease;
}

.purchase-search-card:focus-within {
    border-color: var(--teal-500, #0ea5a0);
    box-shadow: 0 0 0 4px rgba(14, 165, 160, 0.12);
}

.search-input-box {
    display: flex;
    align-items: center;
    background: #ffffff;
    position: relative;
}

.search-icon-badge {
    padding: 0 14px;
    color: var(--teal-600, #0d8a85);
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-search-input {
    flex: 1;
    height: 44px;
    border: none;
    outline: none;
    background: transparent;
    font-size: 14.5px;
    font-family: inherit;
    color: var(--navy-900, #0a1628);
    font-weight: 600;
}

.hero-search-input::placeholder {
    color: #94a3b8;
    font-weight: 400;
}

.search-shortcut-badge {
    padding-left: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.kbd-tag {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 5px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.05);
}

.search-debug-tip {
    font-size: 12px;
    padding: 2px 14px;
    min-height: 18px;
    font-weight: 600;
}

/* Dropdown Search Results */
.search-results {
    position: absolute !important;
    top: calc(100% + 4px) !important;
    left: 0 !important;
    right: 0 !important;
    background: #ffffff !important;
    border: 1.5px solid var(--teal-400, #14c8c1) !important;
    border-radius: 10px !important;
    box-shadow: 0 12px 30px rgba(10, 22, 40, 0.15) !important;
    z-index: 9999 !important;
    max-height: 340px;
    overflow-y: auto;
    display: none;
}

.search-results.active { 
    display: block !important; 
}

.search-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px !important;
    border-bottom: 1px solid #f1f5f9 !important;
    cursor: pointer;
    background: #ffffff;
    transition: background 0.15s ease;
}

.search-item:hover { 
    background: #f0fdfa !important; 
}

.search-item-info {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.search-item-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--navy-900, #0a1628);
}

.search-item-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11.5px;
}

.search-barcode-pill {
    background: #f1f5f9;
    color: #475569;
    padding: 1px 6px;
    border-radius: 4px;
    font-family: monospace;
    font-weight: 600;
}

.search-plu-pill {
    background: #e0f2fe;
    color: #0369a1;
    padding: 1px 6px;
    border-radius: 4px;
    font-weight: 600;
}

.search-item-prices {
    text-align: left;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
}

.search-item-sale {
    font-size: 14.5px;
    font-weight: 800;
    color: var(--teal-600, #0d8a85);
}

.search-item-sale small {
    font-size: 10.5px;
    color: #64748b;
    font-weight: 400;
}

.search-item-cost {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
}

.search-item-cost small {
    font-size: 10px;
    color: #94a3b8;
}

/* ---- Items Table Container ---- */
.purchase-table-card {
    background: #ffffff;
    border-radius: var(--radius, 12px);
    border: 1px solid var(--border-color, #e2e8f0);
    box-shadow: 0 1px 3px rgba(10, 22, 40, 0.04);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-height: 280px;
}

.table-card-top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.table-card-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.table-card-title h3 {
    font-size: 14px;
    font-weight: 800;
    color: var(--navy-900, #0a1628);
    margin: 0;
}

.items-badge-count {
    background: var(--teal-100, #d4faf8);
    color: var(--teal-600, #0d8a85);
    font-size: 11.5px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
}

.table-legend-wrap {
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 11.5px;
    color: #64748b;
}

.legend-chip {
    display: flex;
    align-items: center;
    gap: 5px;
}

.legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}
.legend-dot.bonus { background: #10b981; }
.legend-dot.return { background: #ef4444; }

.table-scroll-wrap {
    overflow-x: auto;
    overflow-y: auto;
    max-height: calc(100vh - 430px);
    min-height: 220px;
}

/* Custom Table Design */
.purchase-items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    text-align: right;
}

.purchase-items-table thead {
    background: var(--navy-800, #0f2140) !important;
    position: sticky;
    top: 0;
    z-index: 20;
}

.purchase-items-table th {
    background: var(--navy-800, #0f2140) !important;
    color: #ffffff !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    padding: 12px 10px !important;
    border: none !important;
    white-space: nowrap;
    letter-spacing: 0.2px;
}

.purchase-items-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #f1f5f9;
    color: var(--text-primary, #1a2332);
    vertical-align: middle;
}

.purchase-items-table tbody tr {
    transition: background-color 0.15s ease;
}

.purchase-items-table tbody tr:hover {
    background-color: #f8fafc;
}

/* Row Indicators */
.row-bonus {
    background-color: #f0fdf4 !important;
    border-right: 4px solid #10b981 !important;
}

.row-return {
    background-color: #fef2f2 !important;
    border-right: 4px solid #ef4444 !important;
}

.badge-flag {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    margin-right: 6px;
}

.badge-flag.bonus {
    background: #d1fae5;
    color: #065f46;
}

.badge-flag.return {
    background: #fee2e2;
    color: #991b1b;
}

/* Table Controls */
.cell-index {
    color: #94a3b8;
    font-weight: 700;
    text-align: center;
    width: 32px;
}

.cell-product-name {
    font-weight: 700;
    color: var(--navy-900, #0a1628);
}

.table-unit-select {
    width: 100%;
    min-width: 125px;
    height: 34px;
    padding: 4px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #ffffff;
    font-size: 12.5px;
    font-family: inherit;
    font-weight: 600;
    color: var(--navy-800, #0f2140);
    outline: none;
    transition: border 0.2s;
}

.table-unit-select:focus {
    border-color: var(--teal-500, #0ea5a0);
}

.table-num-input {
    height: 34px;
    padding: 4px 8px;
    text-align: center;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #ffffff;
    font-size: 13px;
    font-weight: 700;
    font-family: inherit;
    color: var(--navy-900, #0a1628);
    outline: none;
    transition: all 0.2s;
}

.table-num-input:focus {
    border-color: var(--teal-500, #0ea5a0);
    box-shadow: 0 0 0 2px rgba(14, 165, 160, 0.15);
}

.table-num-input.qty { width: 75px; }
.table-num-input.price { width: 95px; }
.table-num-input.readonly-cost {
    background: #f1f5f9;
    color: #94a3b8;
    text-decoration: line-through;
    cursor: not-allowed;
}

/* Checkbox Toggle Buttons */
.checkbox-toggle-cell {
    display: flex;
    justify-content: center;
    align-items: center;
}

.custom-checkbox-wrap {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
}

.custom-checkbox-wrap input[type=checkbox] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--teal-500, #0ea5a0);
}

.custom-checkbox-wrap.return-check input[type=checkbox] {
    accent-color: #ef4444;
}

.btn-remove-row {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: transparent;
    border: 1px solid transparent;
    color: #94a3b8;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    transition: all 0.15s ease;
}

.btn-remove-row:hover {
    background: #fee2e2;
    color: #ef4444;
    border-color: #fca5a5;
}

/* Empty State Table */
.empty-table-state {
    padding: 48px 20px;
    text-align: center;
    color: #64748b;
}

.empty-state-icon-box {
    width: 60px;
    height: 60px;
    background: #f1f5f9;
    border-radius: 50%;
    margin: 0 auto 12px auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
}

.empty-state-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--navy-800, #0f2140);
    margin-bottom: 4px;
}

.empty-state-subtitle {
    font-size: 12.5px;
    color: #94a3b8;
    margin: 0;
}

/* ---- Bottom Summary & Checkout Bar ---- */
.purchase-summary-bar {
    background: #ffffff;
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: var(--radius, 12px);
    padding: 16px 20px;
    box-shadow: 0 4px 20px rgba(10, 22, 40, 0.08);
    display: grid;
    grid-template-columns: 2.2fr 2fr 1.8fr 1.6fr;
    gap: 18px;
    align-items: center;
    position: sticky;
    bottom: 0;
    z-index: 50;
}

@media (max-width: 1100px) {
    .purchase-summary-bar {
        grid-template-columns: 1fr 1fr;
    }
}

/* Breakdown Block */
.summary-breakdown-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 16px;
    gap: 12px;
}

.breakdown-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    flex: 1;
}

.breakdown-label {
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 2px;
}

.breakdown-val {
    font-size: 16px;
    font-weight: 800;
    color: var(--navy-900, #0a1628);
}
.breakdown-val.buy { color: #10b981; }
.breakdown-val.ret { color: #ef4444; }

.breakdown-divider {
    width: 1px;
    height: 32px;
    background: #cbd5e1;
}

/* Discount Block */
.summary-discount-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 14px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.discount-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11.5px;
    font-weight: 700;
    color: var(--navy-800, #0f2140);
}

.discount-deducted-badge {
    background: #fee2e2;
    color: #b91c1c;
    padding: 1px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
}

.discount-inputs-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.discount-select {
    width: 110px;
    height: 32px;
    padding: 2px 6px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
    background: #ffffff;
    outline: none;
}

.discount-input-field {
    flex: 1;
    height: 32px;
    padding: 2px 8px;
    text-align: center;
    font-size: 13px;
    font-weight: 800;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #ffffff;
    outline: none;
}

/* Final Net Total Block */
.summary-total-card {
    background: linear-gradient(135deg, var(--navy-900, #0a1628), var(--navy-800, #0f2140));
    color: #ffffff;
    border-radius: 10px;
    padding: 10px 16px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(10, 22, 40, 0.2);
}

.total-header-text {
    font-size: 11px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 2px;
}

.total-number-row {
    display: flex;
    align-items: baseline;
    gap: 6px;
}

.total-amount-display {
    font-size: 26px;
    font-weight: 900;
    color: var(--teal-300, #5ee2db);
    line-height: 1;
}

.total-currency-text {
    font-size: 12px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.8);
}

/* Save Action Button */
.btn-save-purchase {
    height: 52px;
    background: linear-gradient(135deg, var(--teal-500, #0ea5a0), var(--teal-600, #0d8a85));
    border: none;
    border-radius: 10px;
    color: #ffffff;
    font-size: 16px;
    font-weight: 800;
    font-family: inherit;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 14px rgba(14, 165, 160, 0.35);
    transition: all 0.2s ease;
    width: 100%;
}

.btn-save-purchase:hover {
    background: linear-gradient(135deg, var(--teal-400, #14c8c1), var(--teal-500, #0ea5a0));
    box-shadow: 0 6px 18px rgba(14, 165, 160, 0.45);
    transform: translateY(-1px);
}

.btn-save-purchase:active {
    transform: translateY(1px);
    box-shadow: 0 2px 8px rgba(14, 165, 160, 0.3);
}
</style>

<div class="purchase-invoice-container">

    <!-- Header Section -->
    <div class="purchase-header-bar">
        <div class="purchase-header-info">
            <div class="purchase-header-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </div>
            <div class="purchase-header-title">
                <h1><?= !empty($invoice) ? '✏️ تعديل فاتورة شراء #' . $invoice['id'] : '📦 فاتورة شراء جديدة' ?></h1>
                <p class="subtitle">إدخال بضائع الموردين وتحديث المخزون وأسعار الشراء والبيع</p>
            </div>
        </div>
        <div class="purchase-header-actions">
            <a href="?page=purchases" class="btn btn-outline" style="border-radius:8px; font-weight:700;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                العودة للمشتريات
            </a>
        </div>
    </div>

    <!-- Metadata Section -->
    <div class="purchase-meta-grid">
        <!-- Supplier Select -->
        <div class="meta-field-box">
            <label for="supplierId" class="meta-field-label">
                🏢 المورد <span class="text-danger" style="color:#ef4444;">*</span>
            </label>
            <div class="meta-field-input-wrap">
                <select id="supplierId" class="meta-input-styled" onchange="checkLastInvoice()">
                    <option value="">-- اختر المورد --</option>
                    <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Invoice Number -->
        <div class="meta-field-box">
            <label for="invoiceNumber" class="meta-field-label">
                🏷️ رقم الفاتورة
                <span class="badge-auto">تلقائي</span>
            </label>
            <div class="meta-field-input-wrap">
                <input type="text" id="invoiceNumber" class="meta-input-styled meta-input-readonly" placeholder="سيتم إنشاؤه تلقائياً" readonly>
            </div>
        </div>

        <!-- Invoice Date -->
        <div class="meta-field-box">
            <label for="invoiceDate" class="meta-field-label">
                📅 تاريخ الفاتورة
            </label>
            <div class="meta-field-input-wrap">
                <input type="date" id="invoiceDate" class="meta-input-styled" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <!-- Live Scanner Status -->
        <div class="scanner-live-card">
            <div class="scanner-live-top">
                <span class="pulse-indicator"></span>
                <span>القارئ متصل وجاهز</span>
            </div>
            <div id="scannerStatus" class="scanner-status-tip">امسح الباركود مباشرة في أي وقت</div>
        </div>
    </div>

    <!-- Hero Search & Barcode Bar -->
    <div class="purchase-search-card">
        <div class="search-input-box">
            <div class="search-icon-badge">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </div>
            <input type="text" id="manualSearch" class="hero-search-input" 
                   placeholder="امسح الباركود بجهاز المسح أو ابحث بالاسم أو الكود... (اضغط Enter للإضافة المباشرة)" 
                   autocomplete="off" 
                   oninput="handleSearchInput(this.value, event)" 
                   onkeydown="if(event.key==='Enter'){event.preventDefault();scanFromInput(this);}" 
                   onblur="hideSearchResults()">
            <div class="search-shortcut-badge">
                <span class="kbd-tag">Enter ↵</span>
            </div>
        </div>
        <div id="searchDebug" class="search-debug-tip"></div>
        <div id="searchResults" class="search-results"></div>
    </div>

    <!-- Items Table Card -->
    <div class="purchase-table-card">
        <div class="table-card-top-bar">
            <div class="table-card-title">
                <h3>📦 جدول أصناف الفاتورة</h3>
                <span class="items-badge-count" id="tableItemsCount">0 أصناف</span>
            </div>
            <div class="table-legend-wrap">
                <div class="legend-chip">
                    <span class="legend-dot bonus"></span>
                    <span>هدية (بونص)</span>
                </div>
                <div class="legend-chip">
                    <span class="legend-dot return"></span>
                    <span>مرتجع للمورد</span>
                </div>
            </div>
        </div>

        <div class="table-scroll-wrap">
            <table class="purchase-items-table">
                <thead>
                    <tr>
                        <th style="width: 3%; text-align: center;">#</th>
                        <th style="width: 25%;">المنتج</th>
                        <th style="width: 15%;">الوحدة</th>
                        <th style="width: 9%; text-align: center;">الكمية</th>
                        <th style="width: 6%; text-align: center;">هدية</th>
                        <th style="width: 6%; text-align: center;">مرتجع</th>
                        <th style="width: 11%; text-align: center;">سعر الشراء</th>
                        <th style="width: 11%; text-align: center;">سعر البيع</th>
                        <th style="width: 10%; text-align: center;">المجموع</th>
                        <th style="width: 4%; text-align: center;"></th>
                    </tr>
                </thead>
                <tbody id="invoiceItems">
                    <!-- Items rendered here by renderItems() -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sticky Financial Summary Bar -->
    <div class="purchase-summary-bar">

        <!-- Metrics Breakdown -->
        <div class="summary-breakdown-card">
            <div class="breakdown-item">
                <span class="breakdown-label">إجمالي الشراء</span>
                <span class="breakdown-val buy" id="purchaseSubtotal">0.00</span>
            </div>
            <div class="breakdown-divider"></div>
            <div class="breakdown-item">
                <span class="breakdown-label">إجمالي المرتجع</span>
                <span class="breakdown-val ret" id="returnSubtotal">0.00</span>
            </div>
            <div class="breakdown-divider"></div>
            <div class="breakdown-item">
                <span class="breakdown-label">المجموع الفرعي</span>
                <span class="breakdown-val" id="subtotalAmount">0.00</span>
            </div>
        </div>

        <!-- Discount Settings -->
        <div class="summary-discount-card">
            <div class="discount-card-top">
                <span id="discountLabel">🏷️ الخصم المطبق:</span>
                <span class="discount-deducted-badge">
                    مخصوم: <strong id="actualDiscount">0.00</strong> ج.م
                </span>
            </div>
            <div class="discount-inputs-row">
                <select id="discountType" class="discount-select" onchange="updateTotals()">
                    <option value="fixed">مبلغ ثابت (ج.م)</option>
                    <option value="percent">نسبة مئوية (%)</option>
                </select>
                <input type="number" id="invoiceDiscount" class="discount-input-field" value="0" min="0" step="0.01" onchange="updateTotals()" oninput="updateTotals()">
            </div>
        </div>

        <!-- Net Payable Amount -->
        <div class="summary-total-card">
            <span class="total-header-text">الصافي النهائي المستحق</span>
            <div class="total-number-row">
                <span class="total-amount-display" id="totalAmount">0.00</span>
                <span class="total-currency-text">ج.م</span>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="summary-action-card">
            <button type="button" class="btn-save-purchase" onclick="saveInvoice()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>حفظ الفاتورة</span>
            </button>
        </div>

    </div>

</div>

<script>
// ==========================================
// UTILITIES (ES5 Compatible)
// ==========================================
function debounce(func, wait) {
    var timeout;
    return function() {
        var context = this;
        var args = arguments;
        var later = function() {
            timeout = null;
            func.apply(context, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function apiRequest(url, options) {
    options = options || {};
    var defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    if (options.body && typeof options.body === 'object') {
        options.body = JSON.stringify(options.body);
    }

    var fetchOptions = Object.assign({}, defaultOptions, options);
    
    return fetch(url, fetchOptions)
        .then(function(response) { 
            return response.json(); 
        })
        .catch(function(error) {
            console.error('API Error:', error);
            return { success: false, message: 'خطأ في الاتصال: ' + error };
        });
}

// ==========================================
// MAIN INVOICE LOGIC
// ==========================================
var invoiceItems = [];

document.addEventListener('DOMContentLoaded', function() {
    renderItems();
    
    // Auto-focus search on load
    var manualSearch = document.getElementById('manualSearch');
    if (manualSearch) manualSearch.focus();
});

// Scanner Input Handler - Auto-add product from search field
function scanFromInput(input) {
    var code = input.value.trim();
    if (code.length > 0) {
        var results = document.getElementById('searchResults');
        if (results) results.classList.remove('active');
        var debugEl = document.getElementById('searchDebug');
        if (debugEl) debugEl.innerText = "";
        
        findAndAddProduct(code);
        
        input.value = '';
        input.focus();
    }
}

// Hardware Barcode Scanner Listener
var scanBuffer = '';
var lastKeyTime = 0;
var SCAN_TIMING_THRESHOLD = 100;

document.addEventListener('keydown', function(e) {
    if (e.keyCode === 123) { e.preventDefault(); return false; }
    if (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74 || e.keyCode === 67)) {
        e.preventDefault(); return false;
    }

    var target = e.target;
    if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT') {
        return;
    }

    var currentTime = Date.now();
    if (currentTime - lastKeyTime > SCAN_TIMING_THRESHOLD) {
        scanBuffer = '';
    }
    lastKeyTime = currentTime;

    if (e.ctrlKey || e.altKey || e.metaKey) return;

    if (e.key === 'Enter') {
        if (scanBuffer.length > 0) {
            e.preventDefault();
            console.log('Scanner Input Detected:', scanBuffer);
            findAndAddProduct(scanBuffer);
            scanBuffer = '';
        }
    } else if (e.key.length === 1) {
        scanBuffer += e.key;
    }
});

// ==========================================
// MANUAL SEARCH & AUTOCOMPLETE
// ==========================================
var searchTimeout;
var selectedSearchIndex = -1;

function handleSearchInput(value, event) {
    var resultsBox = document.getElementById('searchResults');
    
    if(event) {
        if(event.key === 'ArrowDown') {
            event.preventDefault();
            navigateSearch(1);
            return;
        }
        if(event.key === 'ArrowUp') {
            event.preventDefault();
            navigateSearch(-1);
            return;
        }
        if(event.key === 'Enter') {
            event.preventDefault();
            if(selectedSearchIndex >= 0) {
                var items = resultsBox.querySelectorAll('.search-item');
                if(items[selectedSearchIndex]) items[selectedSearchIndex].click();
            } else if(value.trim().length > 0) {
                handleManualSearch(value); 
            }
            return;
        }
    }

    var debugEl = document.getElementById('searchDebug');
    if(debugEl) {
        if(value && value.trim().length > 0) {
            debugEl.innerText = "⏳ جاري البحث عن: " + value + "...";
            debugEl.style.color = "var(--teal-600, #0d8a85)";
        } else {
            debugEl.innerText = "";
        }
    }

    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        if(value && value.trim().length > 0) {
            handleManualSearch(value);
        } else {
            document.getElementById('searchResults').classList.remove('active');
            if(debugEl) debugEl.innerText = "";
        }
    }, 280);
}

function navigateSearch(direction) {
    var resultsBox = document.getElementById('searchResults');
    var items = resultsBox.querySelectorAll('.search-item');
    if(items.length === 0) return;
    
    if(selectedSearchIndex >= 0 && items[selectedSearchIndex]) {
        items[selectedSearchIndex].style.background = '#ffffff';
    }
    
    selectedSearchIndex += direction;
    
    if(selectedSearchIndex >= items.length) selectedSearchIndex = 0;
    if(selectedSearchIndex < 0) selectedSearchIndex = items.length - 1;
    
    var current = items[selectedSearchIndex];
    if(current) {
        current.style.background = '#f0fdfa';
        current.scrollIntoView({block: 'nearest'});
    }
}

function hideSearchResults() {
    setTimeout(function() {
        var results = document.getElementById('searchResults');
        if(results) results.classList.remove('active');
        selectedSearchIndex = -1;
    }, 350);
}

function handleManualSearch(q) {
    var resultsBox = document.getElementById('searchResults');
    var debugEl = document.getElementById('searchDebug');
    if(debugEl) debugEl.innerText = "";
    
    q = q.trim();
    
    if (q.length < 2) {
        resultsBox.innerHTML = '';
        resultsBox.classList.remove('active');
        return;
    }
    
    resultsBox.innerHTML = '<div style="padding:14px; text-align:center; color:#64748b; font-weight:600;">⏳ جاري البحث في المنتجات...</div>';
    resultsBox.classList.add('active');
    
    apiRequest('?page=pos&action=search&q=' + encodeURIComponent(q) + '&_=' + Date.now())
        .then(function(res) {
            if (res.success && res.data) {
                if(res.data.length > 0) {
                    var html = res.data.map(function(p) {
                        var json = JSON.stringify(p).replace(/'/g, "&#39;");
                        var salePriceDisplay = parseFloat(p.sale_price_unit || 0).toFixed(2);
                        var costPriceDisplay = parseFloat(p.purchase_price || 0).toFixed(2);
                        
                        return '<div class="search-item" onclick=\'selectSearchProduct(' + json + ')\'>' +
                               '<div class="search-item-info">' + 
                                   '<div class="search-item-name">' + p.name + '</div>' +
                                   '<div class="search-item-meta">' +
                                       '<span class="search-barcode-pill">🏷️ ' + (p.barcode || 'بدون باركود') + '</span>' +
                                       (p.plu_code ? '<span class="search-plu-pill">PLU: ' + p.plu_code + '</span>' : '') +
                                   '</div>' +
                               '</div>' +
                               '<div class="search-item-prices">' +
                                   '<div class="search-item-sale">' + salePriceDisplay + ' ج.م <small>سعر البيع</small></div>' +
                                   '<div class="search-item-cost">' + costPriceDisplay + ' ج.م <small>سعر التكلفة</small></div>' +
                               '</div>' +
                               '</div>';
                    }).join('');
                    
                    resultsBox.innerHTML = html;
                    resultsBox.classList.add('active');
                } else {
                    resultsBox.innerHTML = '<div style="padding:14px; text-align:center; color:#94a3b8;">لا توجد منتجات مطابقة لهذا البحث</div>';
                    resultsBox.classList.add('active');
                }
            }
        })
        .catch(function(err) {
            console.error(err);
        });
}

function selectSearchProduct(product) {
    addItem(product);
    var searchInput = document.getElementById('manualSearch');
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
    }
    var resultsBox = document.getElementById('searchResults');
    if (resultsBox) {
        resultsBox.innerHTML = '';
        resultsBox.classList.remove('active');
    }
}

function findAndAddProduct(query) {
    if (!query) return;
    
    apiRequest('?page=pos&action=findProduct&q=' + encodeURIComponent(query))
        .then(function(res) {
            if (res.success && res.data) {
                addItem(res.data);
            } else {
                alert('المنتج غير موجود: ' + query);
            }
        })
        .catch(function(e) {
            alert("خطأ في البحث: " + e.message);
        });
}

// ==========================================
// INVOICE ITEMS STATE & ACTIONS
// ==========================================
function addItem(product, qty) {
    qty = qty || 1;
    try {
        if (!product || !product.id) {
            alert("بيانات المنتج غير صحيحة");
            return;
        }

        var hasPack = (parseFloat(product.pack_unit_quantity) || 0) > 1;
        var defaultMode = hasPack ? 'pack' : 'unit';
        
        var unitName = 'قطعة';
        if (product.type === 'weight') unitName = 'كيلو';
        
        var packName = product.pack_type || 'عبوة';
        if (product.type === 'weight' && !product.pack_type) packName = 'شوال';
        
        var currentUnitName = defaultMode === 'pack' ? packName : unitName;

        invoiceItems.push({
            product_id: product.id,
            product_name: product.name,
            unit_mode: defaultMode, 
            quantity: parseFloat(qty) || 1,
            
            db_pack_cost: parseFloat(product.pack_purchase_price) || 0,
            db_unit_cost: parseFloat(product.purchase_price) || 0,
            db_pack_price: parseFloat(product.pack_sale_price) || 0,
            db_unit_price: parseFloat(product.sale_price_unit) || 0,
            pack_qty: parseFloat(product.pack_unit_quantity) || 1,
            
            unit_name: unitName,
            pack_name: packName,
            current_unit_name: currentUnitName,
            
            is_bonus: false,
            is_return: false,
            original_price: defaultMode === 'pack' ? (parseFloat(product.pack_purchase_price) || 0) : (parseFloat(product.purchase_price) || 0),
            
            purchase_price: defaultMode === 'pack' ? (parseFloat(product.pack_purchase_price) || 0) : (parseFloat(product.purchase_price) || 0),
            sale_price: defaultMode === 'pack' ? (parseFloat(product.pack_sale_price) || 0) : (parseFloat(product.sale_price_unit) || 0)
        });
        
        // Play beep sound if available
        try {
            var audio = new Audio("public/audio/beep.mp3");
            audio.play().catch(function(e){});
        } catch(audioErr) {}
        
        renderItems();

        var status = document.getElementById('scannerStatus');
        if(status) {
            status.innerHTML = "✅ تم إضافة: <strong>" + product.name + "</strong>";
            status.style.color = "var(--teal-600, #0d8a85)";
            setTimeout(function() {
                status.innerText = "امسح الباركود مباشرة في أي وقت";
                status.style.color = "#64748b";
            }, 3000);
        }

    } catch(e) {
        console.error("Error in addItem:", e);
        alert("خطأ في إضافة المنتج: " + e.message);
    }
}

function renderItems() {
    var tbody = document.getElementById('invoiceItems');
    if (!tbody) return;

    // Update count badge in header
    var countEl = document.getElementById('tableItemsCount');
    if (countEl) {
        countEl.textContent = invoiceItems.length + (invoiceItems.length === 1 ? ' صنف' : ' أصناف');
    }

    if (invoiceItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="empty-table-state">' +
            '<div class="empty-state-icon-box">📦</div>' +
            '<div class="empty-state-title">لا توجد أصناف في الفاتورة بعد</div>' +
            '<p class="empty-state-subtitle">امسح باركود المنتج بجهاز المسح أو ابحث من الشريط أعلاه لإضافة المنتجات</p>' +
        '</td></tr>';
        updateTotals();
        return;
    }
    
    tbody.innerHTML = invoiceItems.map(function(item, i) {
        var subtotal = item.quantity * item.purchase_price;
        
        // Build unit dropdown options
        var unitLabel = item.unit_name + ' (' + item.db_unit_cost.toFixed(2) + ' ج.م)';
        var unitSelected = item.unit_mode === 'unit' ? 'selected' : '';
        var unitOptions = '<option value="unit" ' + unitSelected + '>' + unitLabel + '</option>';
        
        if (item.pack_qty > 1) {
            var packLabel = item.pack_name + ' (' + item.pack_qty + ' ' + item.unit_name + ' - ' + item.db_pack_cost.toFixed(2) + ' ج.م)';
            var packSelected = item.unit_mode === 'pack' ? 'selected' : '';
            unitOptions += '<option value="pack" ' + packSelected + '>' + packLabel + '</option>';
        }

        var rowClass = '';
        var priceInputClass = 'table-num-input price';
        var bonusBadge = '';
        var returnBadge = '';
        
        if (item.is_return) {
            rowClass = 'row-return';
            returnBadge = '<span class="badge-flag return">↩ مرتجع</span>';
        } else if (item.is_bonus) {
            rowClass = 'row-bonus';
            priceInputClass += ' readonly-cost';
            bonusBadge = '<span class="badge-flag bonus">🎁 هدية</span>';
        }

        var displaySubtotal = item.is_return ? -subtotal : (item.is_bonus ? 0 : subtotal);
        var subtotalStyle = item.is_return ? 'color:#ef4444; font-weight:800;' : (item.is_bonus ? 'color:#10b981; font-weight:800;' : 'color:var(--navy-900, #0a1628); font-weight:800;');

        return '<tr class="' + rowClass + '">' +
                '<td class="cell-index">' + (i + 1) + '</td>' +
                '<td class="cell-product-name">' + 
                    item.product_name + 
                    bonusBadge +
                    returnBadge +
                '</td>' +
                '<td>' +
                    '<select class="table-unit-select" onchange="switchUnitMode(' + i + ', this.value)">' +
                        unitOptions +
                    '</select>' +
                '</td>' +
                '<td style="text-align:center;">' +
                    '<input type="number" class="table-num-input qty" value="' + item.quantity + '" step="0.01" min="0.01" onchange="updateItemProp(' + i + ', \'quantity\', this.value)">' +
                '</td>' +
                '<td style="text-align:center;">' +
                    '<div class="checkbox-toggle-cell">' +
                        '<label class="custom-checkbox-wrap" title="منتج هدية مجاني (تكلفة 0)">' +
                            '<input type="checkbox" ' + (item.is_bonus ? 'checked' : '') + ' ' + (item.is_return ? 'disabled' : '') + ' onchange="toggleBonus(' + i + ', this.checked)">' +
                        '</label>' +
                    '</div>' +
                '</td>' +
                '<td style="text-align:center;">' +
                    '<div class="checkbox-toggle-cell">' +
                        '<label class="custom-checkbox-wrap return-check" title="منتج مرتجع للمورد">' +
                            '<input type="checkbox" ' + (item.is_return ? 'checked' : '') + ' ' + (item.is_bonus ? 'disabled' : '') + ' onchange="toggleReturn(' + i + ', this.checked)">' +
                        '</label>' +
                    '</div>' +
                '</td>' +
                '<td style="text-align:center;">' +
                    '<input type="number" class="' + priceInputClass + '" value="' + item.purchase_price + '" step="0.01" min="0" onchange="updateItemProp(' + i + ', \'purchase_price\', this.value)" ' + (item.is_bonus ? 'readonly' : '') + '>' +
                '</td>' +
                '<td style="text-align:center;">' +
                    '<input type="number" class="table-num-input price" value="' + item.sale_price + '" step="0.01" min="0" onchange="updateItemProp(' + i + ', \'sale_price\', this.value)">' +
                '</td>' +
                '<td style="text-align:center; ' + subtotalStyle + '">' + displaySubtotal.toFixed(2) + '</td>' +
                '<td style="text-align:center;">' +
                    '<button type="button" class="btn-remove-row" onclick="removeItem(' + i + ')" title="حذف الصنف">&times;</button>' +
                '</td>' +
            '</tr>';
    }).join('');
    
    updateTotals();
}

function switchUnitMode(index, mode) {
    var item = invoiceItems[index];
    if (mode === 'pack') {
        item.unit_mode = 'pack';
        item.original_price = item.db_pack_cost;
        if (!item.is_bonus) item.purchase_price = item.db_pack_cost;
        item.sale_price = item.db_pack_price;
    } else {
        item.unit_mode = 'unit';
        item.original_price = item.db_unit_cost;
        if (!item.is_bonus) item.purchase_price = item.db_unit_cost;
        item.sale_price = item.db_unit_price;
    }
    renderItems();
}

function toggleBonus(index, isBonus) {
    var item = invoiceItems[index];
    item.is_bonus = isBonus;
    if (isBonus) {
        item.is_return = false;
        item.purchase_price = 0;
    } else {
        item.purchase_price = item.original_price;
    }
    renderItems();
}

function toggleReturn(index, isReturn) {
    var item = invoiceItems[index];
    item.is_return = isReturn;
    if (isReturn) {
        item.is_bonus = false;
        if (item.purchase_price === 0) {
            item.purchase_price = item.original_price;
        }
    }
    renderItems();
}

function updateItemProp(index, prop, value) {
    var item = invoiceItems[index];
    if (prop === 'current_unit_name') {
        item.current_unit_name = value;
        renderItems();
        return;
    }
    
    var val = parseFloat(value) || 0;
    if (prop === 'purchase_price' && !item.is_bonus) {
        item.purchase_price = val;
    } else if (prop === 'quantity') {
        item.quantity = val;
    } else if (prop === 'sale_price') {
        item.sale_price = val;
    } else {
        item[prop] = val;
    }
    renderItems();
}

function removeItem(index) {
    if (confirm('هل أنت متأكد من حذف هذا الصنف من الفاتورة؟')) {
        invoiceItems.splice(index, 1);
        renderItems();
    }
}

// ==========================================
// TOTALS & FINANCIAL CALCULATION
// ==========================================
function updateTotals() {
    var purchaseTotal = 0;
    var returnTotal = 0;
    
    invoiceItems.forEach(function(item) {
        var itemSubtotal = item.quantity * item.purchase_price;
        if (item.is_bonus) {
            // Free bonus doesn't add to cost
        } else if (item.is_return) {
            returnTotal += itemSubtotal;
        } else {
            purchaseTotal += itemSubtotal;
        }
    });
    
    var netSubtotal = purchaseTotal - returnTotal;
    
    var discountInput = parseFloat(document.getElementById('invoiceDiscount').value) || 0;
    var discountType = document.getElementById('discountType').value;
    
    var discountValue = 0;
    var discountLabelText = '🏷️ الخصم المطبق:';
    
    if(discountType === 'percent') {
        discountValue = (netSubtotal * discountInput) / 100;
        discountLabelText = '🏷️ خصم (' + discountInput + '%):';
    } else {
        discountValue = discountInput;
        discountLabelText = '🏷️ خصم (مبلغ ثابت):';
    }
    
    if(discountValue > netSubtotal) discountValue = netSubtotal;
    if(discountValue < 0) discountValue = 0;
    
    var finalTotal = netSubtotal - discountValue;
    
    var purchaseEl = document.getElementById('purchaseSubtotal');
    var returnEl = document.getElementById('returnSubtotal');
    if(purchaseEl) purchaseEl.textContent = purchaseTotal.toFixed(2);
    if(returnEl) returnEl.textContent = returnTotal > 0 ? '-' + returnTotal.toFixed(2) : '0.00';
    
    var totalEl = document.getElementById('totalAmount');
    if (totalEl) {
        totalEl.textContent = finalTotal.toFixed(2);
        totalEl.style.color = finalTotal < 0 ? '#ef4444' : 'var(--teal-300, #5ee2db)';
    }
    
    var subtotalEl = document.getElementById('subtotalAmount');
    if (subtotalEl) subtotalEl.textContent = netSubtotal.toFixed(2);
    
    var actualDiscountEl = document.getElementById('actualDiscount');
    var discountLabelEl = document.getElementById('discountLabel');
    if(actualDiscountEl) actualDiscountEl.textContent = discountValue.toFixed(2);
    if(discountLabelEl) discountLabelEl.textContent = discountLabelText;
}

// ==========================================
// SUPPLIER LAST INVOICE & AUTO NUMBERING
// ==========================================
function checkLastInvoice() {
    var supplierId = document.getElementById('supplierId').value;
    if (!supplierId) return;

    fetchNextInvoiceNumber(supplierId);
    
    if (invoiceItems.length > 0) return;

    if (confirm('هل تريد استيراد أصناف آخر فاتورة مسجلة لهذا المورد؟')) {
        apiRequest('?page=purchases&action=getLastInvoice&supplier_id=' + supplierId)
            .then(function(res) {
                if (res.success && res.data && res.data.length > 0) {
                    invoiceItems = res.data.map(function(i) {
                        return {
                            product_id: i.product_id,
                            product_name: i.product_name,
                            unit_mode: (i.pack_unit_quantity > 1 && i.purchase_price == i.current_pack_cost) ? 'pack' : 'unit', 
                            quantity: parseFloat(i.quantity),
                            
                            db_pack_cost: parseFloat(i.current_pack_cost) || 0,
                            db_unit_cost: parseFloat(i.current_unit_cost) || 0,
                            db_pack_price: parseFloat(i.current_pack_price) || 0,
                            db_unit_price: parseFloat(i.current_unit_price) || 0,
                            pack_qty: parseFloat(i.pack_unit_quantity) || 1,
                            pack_type: i.pack_type || 'عبوة',
                            
                            purchase_price: parseFloat(i.purchase_price),
                            sale_price: 0
                        };
                    });
                    
                    invoiceItems.forEach(function(i) {
                        i.sale_price = i.unit_mode === 'pack' ? i.db_pack_price : i.db_unit_price;
                    });
                    
                    renderItems();
                }
            })
            .catch(function(e) {
                console.error(e);
            });
    }
}

function fetchNextInvoiceNumber(supplierId) {
    apiRequest('?page=purchases&action=next_invoice_number&supplier_id=' + supplierId)
        .then(function(res) {
            if (res.success && res.next_number) {
                document.getElementById('invoiceNumber').value = res.next_number;
            }
        })
        .catch(function(e) {
            console.error(e);
        });
}

// ==========================================
// SAVE INVOICE
// ==========================================
function saveInvoice() {
    if (invoiceItems.length === 0) {
        alert('يرجى إضافة أصناف إلى الفاتورة أولاً');
        return;
    }
    
    var supplierId = document.getElementById('supplierId').value;
    if (!supplierId) {
        alert('يرجى اختيار المورد');
        document.getElementById('supplierId').focus();
        return;
    }
    
    if (!confirm('هل أنت متأكد من حفظ واعتماد الفاتورة؟ سيتم تحديث المخزون وأسعار التكلفة فوراً.')) return;
    
    var discountValue = document.getElementById('actualDiscount').textContent;
    
    var payload = {
        supplier_id: supplierId,
        invoice_number: document.getElementById('invoiceNumber').value,
        date: document.getElementById('invoiceDate').value,
        items: invoiceItems,
        discount: parseFloat(discountValue) || 0
    };
    
    apiRequest('?page=purchases&action=save', {
        method: 'POST',
        body: payload
    })
    .then(function(res) {
        if (res.success) {
            alert('تم حفظ الفاتورة بنجاح ✅');
            window.location.href = '?page=purchases';
        } else {
            alert('خطأ أثناء الحفظ: ' + res.message);
        }
    })
    .catch(function(e) {
        console.error(e);
        alert('حدث خطأ في الاتصال بالخادم');
    });
}

<?php if (!empty($invoice)): ?>
// ==========================================
// EDIT MODE INITIALIZATION
// ==========================================
(function() {
    var editInvoiceId = <?= $invoice['id'] ?>;
    
    var supplierSelect = document.getElementById('supplierId');
    if (supplierSelect) {
        supplierSelect.value = '<?= $invoice['supplier_id'] ?>';
    }
    
    var invoiceNumberEl = document.getElementById('invoiceNumber');
    if (invoiceNumberEl) {
        invoiceNumberEl.value = '<?= htmlspecialchars($invoice['invoice_number'] ?: '') ?>';
    }
    
    var invoiceDateEl = document.getElementById('invoiceDate');
    if (invoiceDateEl) {
        invoiceDateEl.value = '<?= $invoice['date'] ?>';
    }

    var editItems = <?= json_encode($invoice['items']) ?>;
    editItems.forEach(function(item) {
        var isReturn = parseFloat(item.subtotal) < 0;
        var isBonus = parseFloat(item.subtotal) == 0 && parseFloat(item.purchase_price) == 0;
        
        invoiceItems.push({
            product_id: item.product_id,
            product_name: item.product_name,
            unit_mode: 'unit',
            unit_name: 'قطعة',
            pack_name: 'عبوة',
            pack_type: 'عبوة',
            db_purchase_price: parseFloat(item.purchase_price),
            db_pack_purchase_price: 0,
            db_unit_price: 0,
            db_pack_price: 0,
            db_pack_qty: 0,
            db_unit_cost: parseFloat(item.purchase_price) || 0,
            db_pack_cost: 0,
            pack_qty: 1,
            quantity: parseFloat(item.quantity),
            purchase_price: parseFloat(item.purchase_price),
            sale_price: 0,
            is_bonus: isBonus,
            is_return: isReturn,
            original_price: parseFloat(item.purchase_price)
        });
    });
    
    renderItems();

    var originalSave = saveInvoice;
    saveInvoice = function() {
        if (invoiceItems.length === 0) {
            alert('لا توجد أصناف في الفاتورة');
            return;
        }
        
        var supplierId = document.getElementById('supplierId').value;
        if (!supplierId) {
            alert('اختر المورد');
            return;
        }
        
        if (!confirm('هل أنت متأكد من حفظ التعديلات؟ سيتم تحديث المخزون والأسعار.')) return;
        
        var discountValue = document.getElementById('actualDiscount').textContent;
        var payload = {
            id: editInvoiceId,
            supplier_id: supplierId,
            invoice_number: document.getElementById('invoiceNumber').value,
            date: document.getElementById('invoiceDate').value,
            items: invoiceItems,
            discount: parseFloat(discountValue) || 0
        };
        
        apiRequest('?page=purchases&action=save', {
            method: 'POST',
            body: payload
        })
        .then(function(res) {
            if (res.success) {
                alert('تم تعديل الفاتورة بنجاح ✅');
                window.location.href = '?page=purchases';
            } else {
                alert('خطأ: ' + res.message);
            }
        })
        .catch(function(e) {
            console.error(e);
            alert('حدث خطأ في الاتصال');
        });
    };
})();
<?php endif; ?>
</script>
