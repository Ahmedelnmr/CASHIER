<?php
/**
 * POS Main Screen - Standalone (no sidebar layout)
 */
// Start session for cart restore
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
        <div style="display:flex;align-items:center;gap:8px;">
            <button class="btn btn-outline btn-sm" onclick="openDiscount()">خصم</button>
            <button class="btn btn-outline btn-sm" onclick="openManualPrice()">يدوي</button>
            <button class="btn btn-outline btn-sm" onclick="openWeightInput()">وزن</button>
            <button class="btn btn-danger btn-sm" onclick="clearCart()">تفريغ</button>
            <a href="?page=login&action=logout" class="btn btn-ghost btn-sm" style="color:#ef4444;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </a>
        </div>
    </div>

    <!-- Input Section -->
    <div class="pos-input-section">
        <div class="pos-input-wrapper">
            <div style="flex: 1;">
                <div class="pos-input-group">
                    <span class="pos-input-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="1" width="18" height="22" rx="2" ry="2"></rect><line x1="7" y1="6" x2="7" y2="6"></line><line x1="12" y1="6" x2="12" y2="6"></line><line x1="17" y1="6" x2="17" y2="6"></line></svg>
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
                           placeholder="ابحث عن منتج بالاسم..." 
                           oninput="searchProducts(this.value)">
                    <div class="pos-search-results" id="productSearchResults"></div>
                </div>
            </div>
        </div>
        <div class="pos-shortcuts-hint">
            <span>F2: باركود</span>
            <span>F3: تفاصيل</span>
            <span>F5: إتمام</span>
            <span>F8: تبديل</span>
            <span>↑↓: تنقل</span>
        </div>
    </div>

    <!-- Main Cart Area -->
    <div class="pos-main-cart">
        <div class="pos-cart-container">
            <table class="pos-cart-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>المنتج</th>
                        <th style="width:140px">نوع الوحدة</th>
                        <th style="width:140px">الكمية</th>
                        <th style="width:100px">السعر</th>
                        <th style="width:110px">المجموع</th>
                        <th style="width:50px"></th>
                    </tr>
                </thead>
                <tbody id="cartBody">
                    <tr id="emptyCart">
                        <td colspan="7">
                            <div class="empty-cart-state">
                                <div class="icon">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#8899aa" stroke-width="1.5"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                                </div>
                                <p>السلة فارغة — ابدأ بمسح باركود أو البحث عن منتج</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bottom Totals Bar -->
    <div class="pos-bottombar">
        <div class="pos-bottombar-content">
            <div class="pos-totals">
                <div class="total-row">
                    <div class="total-row-label">المجموع الفرعي</div>
                    <div class="total-row-value" id="subtotalDisplay">0.00</div>
                </div>
                <div class="total-row">
                    <div class="total-row-label">الخصم</div>
                    <div class="total-row-value" id="discountDisplay">0.00</div>
                </div>
                <div class="total-row total-final">
                    <div class="total-row-label">الإجمالي</div>
                    <div class="total-row-value" id="totalDisplay">0.00</div>
                </div>
            </div>
            <button id="checkoutBtn" onclick="processCheckout()" disabled>
                إتمام البيع (F5)
            </button>
        </div>
    </div>
</div>

<!-- Removed Scanner Modal -->

<!-- Manual Price Modal -->
<div class="modal-overlay" id="manualPriceModal">
    <div class="modal">
        <div class="modal-header">
            <h3>سعر يدوي</h3>
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
            <h3>إدخال الوزن</h3>
            <button class="modal-close" onclick="closeModal('weightModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>المنتج</label>
                <input type="text" class="form-control" id="weightProductSearch" 
                       placeholder="ابحث عن المنتج بالاسم أو PLU..." 
                       oninput="searchWeightProducts(this.value)">
                <div id="weightProductResults" style="margin-top:8px;"></div>
                <input type="hidden" id="weightProductId">
                <div id="weightProductName" class="mt-1 fw-bold" style="color:var(--accent);"></div>
            </div>
            <div class="form-group">
                <label>الوزن (كجم)</label>
                <input type="number" class="form-control" id="weightValue" step="0.001" min="0.001" placeholder="0.000">
                
                <!-- Quick Price Options -->
                <div style="margin-top:10px;">
                    <label style="font-size:12px; color:#666; display:block; margin-bottom:5px;">اختر بالسعر (ج.م):</label>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <button class="btn btn-outline btn-sm" onclick="setWeightByPrice(5)">5</button>
                        <button class="btn btn-outline btn-sm" onclick="setWeightByPrice(10)">10</button>
                        <button class="btn btn-outline btn-sm" onclick="setWeightByPrice(15)">15</button>
                        <button class="btn btn-outline btn-sm" onclick="setWeightByPrice(20)">20</button>
                    </div>
                    <div id="weightEquivalent" style="font-size:13px; color:#2c3e50; margin-top:8px; font-weight:bold; min-height:20px;"></div>
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
    <div class="modal">
        <div class="modal-header">
            <h3>خصم</h3>
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
            <h3>Receipt</h3>
            <button class="modal-close" onclick="closeModal('receiptModal')">&times;</button>
        </div>
        <div class="modal-body" id="receiptContent">
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('receiptModal')">Close</button>
            <button class="btn btn-primary" onclick="printReceipt()">Print</button>
        </div>
    </div>
</div>


<script src="public/js/pos_core_v2.js?v=<?= time() ?>"></script>
<script src="public/js/pos_shortcuts.js?v=<?= time() ?>"></script>
<script>
// Store info from settings (used by showReceipt)
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
