<?php
/**
 * POS Main Screen - Split Layout
 * Left: Product Grid + Search + Categories
 * Right: Cart + Customer + Checkout
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$restoreCart = [];
if (isset($_SESSION['pos_cart_restore'])) {
    $restoreCart = $_SESSION['pos_cart_restore'];
    unset($_SESSION['pos_cart_restore']);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نقطة البيع - POS</title>
    <link rel="stylesheet" href="public/css/pos_simple.css?v=<?= time() ?>">
    <link rel="stylesheet" href="public/css/pos_fixes.css?v=<?= time() ?>">
    <link rel="stylesheet" href="public/css/main.css?v=<?= time() ?>">
</head>
<body>
<div class="toast-container" id="toastContainer"></div>

<div class="pos-fullscreen">
    <!-- Top Navigation Bar -->
    <div class="pos-topbar">
        <div style="display:flex;align-items:center;gap:16px;">
            <a href="?page=dashboard" class="btn btn-ghost btn-sm">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                الرئيسية
            </a>
            <h2>نقطة البيع</h2>
        </div>
        <div class="pos-topbar-actions">
            <button class="btn btn-outline btn-sm" onclick="openDiscount()">💲 خصم</button>
            <button class="btn btn-outline btn-sm" onclick="openManualPrice()">✏️ يدوي</button>
            <button class="btn btn-outline btn-sm" onclick="openWeightInput()">⚖️ وزن</button>
            <button class="btn btn-outline btn-sm" onclick="openCustomerModal()">👤 عميل (F9)</button>
            <button class="btn btn-danger btn-sm" onclick="clearCart()">🗑️ تفريغ</button>
            <a href="?page=shifts" class="btn btn-ghost btn-sm" title="الورديات">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </a>
            <a href="?page=login&action=logout" class="btn btn-ghost btn-sm" style="color:#ef4444;" title="تسجيل الخروج">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </a>
        </div>
    </div>

    <!-- Shift Warning Banner -->
    <div class="shift-warning" id="shiftWarning" style="display:none;">
        <span>⚠️ لا توجد وردية مفتوحة. يجب <a href="?page=shifts" style="color:#fff;text-decoration:underline;font-weight:700;">فتح وردية</a> قبل البيع.</span>
    </div>

    <!-- Main Split Layout -->
    <div class="pos-split-layout">

        <!-- LEFT PANEL: Products -->
        <div class="pos-products-panel">
            <!-- Search -->
            <div class="pos-product-search-bar">
                <div class="pos-input-group">
                    <span class="pos-input-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="1" width="18" height="22" rx="2" ry="2"></rect></svg>
                    </span>
                    <input type="text" id="barcodeInput" class="pos-barcode-input" 
                           placeholder="امسح الباركود أو أدخل الكود (F2)..." 
                           autocomplete="off" autofocus>
                    <div class="pos-search-results" id="searchResults"></div>
                </div>
                <div class="pos-input-group">
                    <span class="pos-input-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input type="text" id="productSearch" class="pos-barcode-input" 
                           placeholder="ابحث عن منتج بالاسم...">
                </div>
            </div>

            <!-- Category Filter -->
            <div class="pos-category-bar">
                <button class="category-btn active" data-category="" onclick="filterByCategory('')">الكل</button>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                        <button class="category-btn" data-category="<?= $cat['id'] ?>" onclick="filterByCategory('<?= $cat['id'] ?>')"><?= htmlspecialchars($cat['name']) ?></button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Product Grid -->
            <div class="pos-product-grid-container">
                <div class="product-grid" id="productGrid">
                    <div class="product-grid-loading">جاري تحميل المنتجات...</div>
                </div>
                <button class="load-more-btn" id="loadMoreBtn" style="display:none;" onclick="loadMoreProducts()">تحميل المزيد ↓</button>
            </div>
        </div>

        <!-- RIGHT PANEL: Cart -->
        <div class="pos-cart-panel">
            <!-- Cart Header -->
            <div class="cart-panel-header">
                <h3>🛒 السلة <span class="cart-count" id="cartItemCount">0</span></h3>
            </div>

            <!-- Customer Section -->
            <div class="cart-customer-section">
                <div class="customer-display" id="selectedCustomerDisplay">
                    <span class="no-customer">عميل نقدي (بدون حساب)</span>
                </div>
            </div>

            <!-- Cart Table -->
            <div class="cart-scroll-area">
                <table class="pos-cart-table">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th style="width:90px">الوحدة</th>
                            <th style="width:120px">الكمية</th>
                            <th style="width:70px">السعر</th>
                            <th style="width:80px">المجموع</th>
                            <th style="width:35px"></th>
                        </tr>
                    </thead>
                    <tbody id="cartBody">
                        <tr id="emptyCart">
                            <td colspan="6">
                                <div class="empty-cart-state">
                                    <div class="icon">🛒</div>
                                    <p>السلة فارغة</p>
                                    <p class="sub">امسح باركود أو اختر منتج من القائمة</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Totals & Checkout -->
            <div class="cart-totals-section">
                <div class="totals-grid">
                    <div class="total-line">
                        <span>المجموع الفرعي</span>
                        <span id="subtotalDisplay">0.00</span>
                    </div>
                    <div class="total-line discount-line">
                        <span>الخصم</span>
                        <span id="discountDisplay">0.00</span>
                    </div>
                    <div class="total-line total-final-line">
                        <span>الإجمالي</span>
                        <span id="totalDisplay">0.00</span>
                    </div>
                </div>
                <button id="checkoutBtn" class="checkout-btn" onclick="openPaymentModal()" disabled>
                    💰 إتمام البيع (F5)
                </button>
            </div>

            <!-- Shortcuts -->
            <div class="pos-shortcuts-hint">
                <span>F2 باركود</span>
                <span>F5 دفع</span>
                <span>F8 وحدة</span>
                <span>F9 عميل</span>
                <span>Del حذف</span>
                <span>↑↓ تنقل</span>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODALS ===== -->

<!-- Payment Modal -->
<div class="modal-overlay" id="paymentModal">
    <div class="modal" style="min-width:450px;max-width:500px;">
        <div class="modal-header">
            <h3>💰 الدفع وإتمام البيع</h3>
            <button class="modal-close" onclick="closeModal('paymentModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="payment-summary">
                <div class="payment-summary-row"><span>المجموع الفرعي:</span><span id="paymentSubtotal">0.00</span></div>
                <div class="payment-summary-row"><span>الخصم:</span><span id="paymentDiscount">0.00</span></div>
                <div class="payment-summary-row payment-total-row"><span>الإجمالي:</span><span id="paymentTotal">0.00</span></div>
            </div>

            <div class="form-group" style="margin-top:16px;">
                <label>طريقة الدفع</label>
                <div class="payment-methods">
                    <label class="payment-method-option">
                        <input type="radio" name="paymentMethod" value="cash" checked onchange="onPaymentMethodChange('cash')">
                        <span>💵 نقدي</span>
                    </label>
                    <label class="payment-method-option">
                        <input type="radio" name="paymentMethod" value="credit" onchange="onPaymentMethodChange('credit')">
                        <span>📋 آجل</span>
                    </label>
                </div>
            </div>

            <!-- Cash Section -->
            <div id="cashPaymentSection">
                <div class="form-group">
                    <label>المبلغ المدفوع</label>
                    <input type="number" class="form-control" id="amountPaidInput" step="0.01" min="0" oninput="calculateChange()" style="font-size:20px;font-weight:700;text-align:center;">
                </div>
                <div class="change-display">
                    <span>الباقي:</span>
                    <span id="changeDisplay" style="font-size:28px;font-weight:800;color:#10b981;">0.00</span>
                </div>
            </div>

            <!-- Credit Section -->
            <div id="creditPaymentSection" style="display:none;">
                <div id="creditWarning" class="credit-warning" style="display:none;">
                    ⚠️ يجب اختيار عميل أولاً للبيع الآجل
                </div>
                <div class="credit-info">
                    <div>العميل: <strong id="creditCustomerName">-</strong></div>
                    <div>الرصيد الحالي: <strong id="creditCustomerBalance">0.00</strong></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('paymentModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="confirmPayment()" style="padding:12px 30px;font-size:15px;">✅ تأكيد البيع</button>
        </div>
    </div>
</div>

<!-- Customer Selection Modal -->
<div class="modal-overlay" id="customerModal">
    <div class="modal">
        <div class="modal-header">
            <h3>👤 اختيار العميل</h3>
            <button class="modal-close" onclick="closeModal('customerModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <input type="text" class="form-control" id="customerSearchInput" 
                       placeholder="ابحث بالاسم أو رقم الهاتف..." 
                       oninput="searchCustomersForPOS(this.value)">
            </div>
            <div id="customerSearchResults" style="max-height:250px;overflow-y:auto;"></div>
            <div style="margin-top:12px;text-align:center;">
                <button class="btn btn-outline btn-sm" onclick="closeModal('customerModal');openQuickCustomerModal();">+ إضافة عميل جديد</button>
                <button class="btn btn-ghost btn-sm" onclick="clearCustomer();closeModal('customerModal');">عميل نقدي</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Customer Create Modal -->
<div class="modal-overlay" id="quickCustomerModal">
    <div class="modal" style="min-width:350px;">
        <div class="modal-header">
            <h3>➕ إضافة عميل جديد</h3>
            <button class="modal-close" onclick="closeModal('quickCustomerModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>الاسم *</label>
                <input type="text" class="form-control" id="newCustomerName" placeholder="اسم العميل">
            </div>
            <div class="form-group">
                <label>الهاتف</label>
                <input type="text" class="form-control" id="newCustomerPhone" placeholder="رقم الهاتف">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('quickCustomerModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="saveQuickCustomer()">حفظ واختيار</button>
        </div>
    </div>
</div>

<!-- Manual Price Modal -->
<div class="modal-overlay" id="manualPriceModal">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ سعر يدوي</h3>
            <button class="modal-close" onclick="closeModal('manualPriceModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>اسم المنتج</label>
                <input type="text" class="form-control" id="manualName" placeholder="أدخل اسم المنتج">
            </div>
            <div class="form-group">
                <label>السعر</label>
                <input type="number" class="form-control" id="manualPriceValue" step="0.01" min="0" placeholder="0.00">
            </div>
            <div class="form-group">
                <label>الكمية</label>
                <input type="number" class="form-control" id="manualQty" value="1" min="0.01" step="0.01">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('manualPriceModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="addManualPrice()">إضافة للسلة</button>
        </div>
    </div>
</div>

<!-- Weight Input Modal -->
<div class="modal-overlay" id="weightModal">
    <div class="modal">
        <div class="modal-header">
            <h3>⚖️ إدخال الوزن</h3>
            <button class="modal-close" onclick="closeModal('weightModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>المنتج</label>
                <input type="text" class="form-control" id="weightProductSearch" 
                       placeholder="ابحث عن المنتج..." 
                       oninput="searchWeightProducts(this.value)">
                <div id="weightProductResults" style="margin-top:8px;"></div>
                <input type="hidden" id="weightProductId">
            </div>
            <div class="form-group">
                <label>الوزن (كجم)</label>
                <input type="number" class="form-control" id="weightValue" step="0.001" min="0.001" placeholder="0.000">
                <div style="margin-top:10px;">
                    <label style="font-size:12px;color:#666;display:block;margin-bottom:5px;">اختر بالسعر (ج.م):</label>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button class="btn btn-outline btn-sm" onclick="setWeightByPrice(5)">5</button>
                        <button class="btn btn-outline btn-sm" onclick="setWeightByPrice(10)">10</button>
                        <button class="btn btn-outline btn-sm" onclick="setWeightByPrice(15)">15</button>
                        <button class="btn btn-outline btn-sm" onclick="setWeightByPrice(20)">20</button>
                    </div>
                    <div id="weightEquivalent" style="font-size:13px;color:#2c3e50;margin-top:8px;font-weight:bold;min-height:20px;"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('weightModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="addWeightItem()">إضافة للسلة</button>
        </div>
    </div>
</div>

<!-- Discount Modal -->
<div class="modal-overlay" id="discountModal">
    <div class="modal" style="min-width:350px;">
        <div class="modal-header">
            <h3>💲 خصم</h3>
            <button class="modal-close" onclick="closeModal('discountModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>قيمة الخصم</label>
                <input type="number" class="form-control" id="discountValue" step="0.01" min="0" placeholder="0.00">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('discountModal')">إلغاء</button>
            <button class="btn btn-primary" onclick="applyDiscount()">تطبيق</button>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal-overlay" id="receiptModal">
    <div class="modal" style="max-width:380px;">
        <div class="modal-header">
            <h3>🧾 الفاتورة</h3>
            <button class="modal-close" onclick="closeModal('receiptModal')">&times;</button>
        </div>
        <div class="modal-body" id="receiptContent"></div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('receiptModal')">إغلاق</button>
            <button class="btn btn-primary" onclick="printReceipt()">🖨️ طباعة</button>
        </div>
    </div>
</div>


<script src="public/js/pos_core_v2.js?v=<?= time() ?>"></script>
<script src="public/js/pos_shortcuts.js?v=<?= time() ?>"></script>
<script>
// Store info from settings
window.STORE_INFO = {
    name: <?= json_encode($storeInfo['name'] ?? 'تاجر') ?>,
    phone: <?= json_encode($storeInfo['phone'] ?? '') ?>,
    address: <?= json_encode($storeInfo['address'] ?? '') ?>,
    receiptHeader: <?= json_encode($storeInfo['receipt_header'] ?? '') ?>,
    receiptFooter: <?= json_encode($storeInfo['receipt_footer'] ?? 'شكراً لتسوقكم معنا - نتمنى لكم يوماً سعيداً') ?>,
    currency: <?= json_encode($storeInfo['currency_symbol'] ?? 'ج.م') ?>
};

<?php if (!empty($restoreCart)): ?>
    window.addEventListener('load', function() {
        try {
            window.cart = <?= json_encode($restoreCart) ?>;
            if(window.cart && window.cart.length > 0) {
                renderCart();
                setTimeout(function() { showToast('✅ تم استرجاع الفاتورة للتعديل'); }, 500);
            }
        } catch(e) { console.error(e); }
    });
<?php endif; ?>
</script>
</body>
</html>
