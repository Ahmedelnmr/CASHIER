/**
 * POS.js - Point of Sale Frontend Logic
 * Version: Fixed Scope (Global Access)
 * Last Updated: 2026-02-17 04:30 - Scanner F12 Fix v3
 */

(function () {

    // ==========================================
    // 1. UTILITIES
    // ==========================================

    // Debounce Utility (Attached to window)
    window.debounce = function (func, wait) {
        var timeout;
        return function () {
            var context = this;
            var args = arguments;
            var later = function () {
                clearTimeout(timeout);
                func.apply(context, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    // API Request Helper
    window.apiRequest = async function (url, options = {}) {
        try {
            var defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };

            if (options.body && typeof options.body === 'object') {
                options.body = JSON.stringify(options.body);
            }

            var response = await fetch(url, Object.assign({}, defaultOptions, options));
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, message: 'خطأ في الاتصال' };
        }
    };

    // Toast Notifications
    window.showToast = function (message, type = 'success') {
        var container = document.getElementById('toastContainer');
        if (!container) return;

        var toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.textContent = message;

        // Inline styles for reliability
        toast.style.background = type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#3b82f6');
        toast.style.color = 'white';
        toast.style.padding = '12px 20px';
        toast.style.borderRadius = '6px';
        toast.style.marginBottom = '10px';
        toast.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
        toast.style.transition = 'all 0.3s ease';

        container.appendChild(toast);

        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(function () { if (toast.parentNode) toast.remove(); }, 300);
        }, 3000);
    };

    // Modal Controls
    window.openModal = function (modalId) {
        var modal = document.getElementById(modalId);
        if (modal) modal.classList.add('active');
    };

    window.closeModal = function (modalId) {
        var modal = document.getElementById(modalId);
        if (modal) modal.classList.remove('active');
    };

    window.escapeHtml = function (text) {
        if (!text) return '';
        return text.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    };

    // ==========================================
    // 2. STATE & INITIALIZATION
    // ==========================================
    window.cart = [];
    window.discount = 0;
    window.selectedWeightProduct = null;
    window.selectedCartIndex = -1;

    // Load initial data
    document.addEventListener('DOMContentLoaded', function () {
        try {
            console.log('POS Initializing...');
            var barcodeInput = document.getElementById('barcodeInput');
            if (barcodeInput) barcodeInput.focus();

            // Barcode Listener
            if (barcodeInput) {
                barcodeInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        var code = this.value.trim();
                        if (code) {
                            addByCode(code);
                            this.value = '';
                            hideSearchResults();
                        }
                    }
                });

                // Live search for barcode input
                barcodeInput.addEventListener('input', debounce(function () {
                    var q = this.value.trim();
                    if (q.length >= 2) {
                        liveSearchBarcode(q);
                    } else {
                        hideSearchResults();
                    }
                }, 250));
            }

            // Global Click Listener
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.pos-input-section')) {
                    hideSearchResults('searchResults');
                    hideSearchResults('productSearchResults');
                }
            });

            // Global Scanner Listener (Buffer rapid keystrokes)
            var posScanBuffer = '';
            var lastKeyTime = 0;
            const SCAN_TIMING_THRESHOLD = 100; // ms

            document.addEventListener('keydown', function (e) {
                // SCANNER SAFETY: Block F12 and DevTools shortcuts first
                if (e.keyCode === 123) { // F12
                    e.preventDefault();
                    return false;
                }
                if (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74 || e.keyCode === 67)) {
                    // Ctrl+Shift+I/J/C
                    e.preventDefault();
                    return false;
                }

                // If user is typing in an input, ignore (unless it's the body or some non-input element)
                const target = e.target;
                if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA') {
                    return;
                }

                const currentTime = Date.now();

                // Reset buffer if too slow (manual typing detected)
                if (currentTime - lastKeyTime > SCAN_TIMING_THRESHOLD) {
                    posScanBuffer = '';
                }

                lastKeyTime = currentTime;

                // Ignore modifier keys
                if (e.ctrlKey || e.altKey || e.metaKey) return;

                if (e.key === 'Enter') {
                    // If buffer has content, treat as scan
                    if (posScanBuffer.length > 0) {
                        e.preventDefault();
                        console.log('Scanner Input Detected:', posScanBuffer);
                        addByCode(posScanBuffer);
                        posScanBuffer = '';
                    }
                } else if (e.key.length === 1) {
                    // Append printable characters
                    posScanBuffer += e.key;
                }
            });

            // Keyboard Shortcuts
            document.addEventListener('keydown', handleShortcuts);

        } catch (err) {
            alert('POS Init Error: ' + err.message);
            console.error('POS Init Error:', err);
        }
    });

    window.handleShortcuts = function (e) {
        var barcodeInput = document.getElementById('barcodeInput');

        // F2: Focus barcode
        if (e.key === 'F2') {
            e.preventDefault();
            if (barcodeInput) {
                barcodeInput.focus();
                barcodeInput.select();
            }
        }

        // F3: Show Details
        if (e.key === 'F3') {
            e.preventDefault();
            if (typeof showSelectedProductDetails === 'function') showSelectedProductDetails();
        }

        // F5: Checkout
        if (e.key === 'F5') {
            e.preventDefault();
            processCheckout();
        }

        // F8: Switch Unit
        if (e.key === 'F8') {
            e.preventDefault();
            if (typeof toggleSelectedUnitType === 'function') toggleSelectedUnitType();
        }

        // Escape: Close
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(function (m) { m.classList.remove('active'); });
            if (barcodeInput) barcodeInput.focus();
            hideSearchResults('searchResults');
            hideSearchResults('productSearchResults');
        }

        // Arrows: Navigation
        if (e.key === 'ArrowDown' && cart.length > 0) {
            e.preventDefault();
            selectedCartIndex = Math.min(selectedCartIndex + 1, cart.length - 1);
            renderCart();
        }
        if (e.key === 'ArrowUp' && cart.length > 0) {
            e.preventDefault();
            selectedCartIndex = Math.max(selectedCartIndex - 1, 0);
            renderCart();
        }
    };

    // ==========================================
    // 3. LOGIC FUNCTIONS
    // ==========================================

    window.addByCode = async function (code) {
        code = code ? code.trim() : '';
        if (!code) return;

        var res = await apiRequest('?page=pos&action=findProduct&q=' + encodeURIComponent(code));
        if (res.success && res.data) {
            addToCart(res.data, 1, 'unit');
        } else {
            // Show brackets to detect spaces
            showToast(res.message || 'المنتج غير موجود: [' + code + ']', 'error');

            // Play error sound
            let audio = new Audio("public/audio/error.mp3"); // Ensure this exists or just beep
            audio.play().catch(e => { });
        }
    };

    window.liveSearchBarcode = async function (query) {
        var res = await apiRequest('?page=pos&action=search&q=' + encodeURIComponent(query));
        if (res.success && res.data && res.data.length > 0) {
            showSearchResults(res.data, 'searchResults');
        } else {
            hideSearchResults('searchResults');
        }
    };

    window.searchProducts = debounce(async function (query) {
        var container = document.getElementById('productSearchResults');
        if (!query || query.length < 2) {
            hideSearchResults('productSearchResults');
            return;
        }

        if (container) {
            container.innerHTML = '<div style="padding:10px;text-align:center;color:#666">جاري البحث...</div>';
            container.classList.add('active');
        }

        try {
            var res = await apiRequest('?page=pos&action=search&q=' + encodeURIComponent(query));
            if (res.success && res.data && res.data.length > 0) {
                showSearchResults(res.data, 'productSearchResults');
            } else {
                if (container) {
                    container.innerHTML = '<div style="padding:10px;text-align:center;color:#666">لا توجد نتائج</div>';
                }
            }
        } catch (e) {
            console.error(e);
            hideSearchResults('productSearchResults');
        }
    }, 300);

    window.showSearchResults = function (products, containerId) {
        var container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = products.map(function (p) {
            return '<div class="pos-search-item" onclick="selectSearchProduct(' + p.id + ')">' +
                '<div>' +
                '<div class="name">' + escapeHtml(p.name) + '</div>' +
                '<div class="meta">' + (p.barcode || '') + (p.plu_code ? ' | PLU: ' + p.plu_code : '') + '</div>' +
                '</div>' +
                '<div class="price">' + parseFloat(p.sale_price_unit).toFixed(2) + '</div>' +
                '</div>';
        }).join('');
        container.classList.add('active');
    };

    window.hideSearchResults = function (containerId) {
        // Default to 'searchResults' if no arg provided, but check if arg is undefined
        var id = containerId || 'searchResults';
        var container = document.getElementById(id);
        if (container) container.classList.remove('active');
    };

    window.selectSearchProduct = async function (productId) {
        try {
            var res = await apiRequest('?page=pos&action=findProduct&q=' + productId);
            if (res.success && res.data) {
                addToCart(res.data, 1, res.data.type === 'weight' ? 'weight' : 'unit');

                var barcodeInput = document.getElementById('barcodeInput');
                if (barcodeInput) barcodeInput.value = '';

                var productSearch = document.getElementById('productSearch');
                if (productSearch) productSearch.value = '';

                hideSearchResults('searchResults');
                hideSearchResults('productSearchResults');
            }
        } catch (e) {
            console.error(e);
            showToast('حدث خطأ أثناء اختيار المنتج', 'error');
        }
    };

    window.addToCart = function (product, quantity = 1, saleMode = 'unit', unitType = 'قطعة') {
        var price;
        var actualQty = quantity;

        switch (saleMode) {
            case 'pack':
                price = parseFloat(product.pack_sale_price) || parseFloat(product.sale_price_unit);
                if (product.pack_unit_quantity) {
                    actualQty = quantity * parseFloat(product.pack_unit_quantity);
                }
                break;
            case 'weight':
                price = parseFloat(product.sale_price_unit);
                actualQty = quantity;
                break;
            default: // unit
                price = parseFloat(product.sale_price_unit);
                actualQty = quantity;
        }

        var existingIndex = cart.findIndex(function (item) {
            return item.product_id === product.id && item.sale_mode === saleMode && item.unit_type === unitType;
        });

        if (existingIndex >= 0 && saleMode !== 'custom') {
            cart[existingIndex].quantity += quantity;
            cart[existingIndex].actual_quantity = (cart[existingIndex].actual_quantity || cart[existingIndex].quantity) + actualQty;
            cart[existingIndex].subtotal = cart[existingIndex].quantity * cart[existingIndex].price;
        } else {
            cart.push({
                product_id: product.id,
                product_name: product.name,
                quantity: quantity,
                actual_quantity: actualQty,
                unit_type: unitType,
                price: price,
                sale_mode: saleMode,
                subtotal: quantity * price,
                product_unit_price: product.sale_price_unit,
                product_pack_price: product.pack_sale_price,
                product_pack_type: product.pack_type,
                product_pack_quantity: product.pack_unit_quantity
            });
        }

        renderCart();
        showToast('✅ ' + product.name);
    };

    window.removeFromCart = function (index) {
        cart.splice(index, 1);
        renderCart();
    };

    window.updateCartQty = function (index, newQty) {
        if (newQty <= 0) {
            removeFromCart(index);
            return;
        }
        cart[index].quantity = parseFloat(newQty);
        if (cart[index].sale_mode === 'pack' && cart[index].product_pack_quantity) {
            cart[index].actual_quantity = cart[index].quantity * parseFloat(cart[index].product_pack_quantity);
        } else {
            cart[index].actual_quantity = cart[index].quantity;
        }
        cart[index].subtotal = cart[index].quantity * cart[index].price;
        renderCart();
    };

    window.changeQty = function (index, delta) {
        if (!cart[index]) return;
        var newQty = cart[index].quantity + delta;
        updateCartQty(index, newQty);
    };

    window.clearCart = function () {
        if (!confirm('هل أنت متأكد من تفريغ السلة؟')) return;
        cart = [];
        discount = 0;
        renderCart();
    };

    window.changeUnitType = function (index, newUnitType) {
        if (!cart[index]) return;
        var item = cart[index];
        item.unit_type = newUnitType;
        if (newUnitType === 'قطعة') {
            item.sale_mode = 'unit';
            item.price = parseFloat(item.product_unit_price) || item.price;
            item.actual_quantity = item.quantity;
        } else {
            item.sale_mode = 'pack';
            item.price = parseFloat(item.product_pack_price) || item.price;
            if (item.product_pack_quantity) {
                item.actual_quantity = item.quantity * parseFloat(item.product_pack_quantity);
            }
        }
        item.subtotal = item.quantity * item.price;
        renderCart();
    };

    window.renderCart = function () {
        var tbody = document.getElementById('cartBody');
        if (!tbody) return;

        if (cart.length === 0) {
            tbody.innerHTML =
                '<tr id="emptyCart">' +
                '<td colspan="7">' +
                '<div class="empty-cart-state">' +
                '<div class="icon">🛒</div>' +
                '<p>السلة فارغة - ابدأ بمسح باركود أو البحث عن منتج</p>' +
                '</div>' +
                '</td>' +
                '</tr>';
            selectedCartIndex = -1;
            updateTotals();
            return;
        }

        tbody.innerHTML = cart.map(function (item, i) {
            var unitOptions = '<option value="قطعة">قطعة</option>';
            if (item.product_pack_type && item.product_pack_quantity) {
                unitOptions += '<option value="' + escapeHtml(item.product_pack_type) + '">' + escapeHtml(item.product_pack_type) + '</option>';
            }
            var selectedUnit = item.unit_type || 'قطعة';
            // Simple replace for selection
            unitOptions = unitOptions.split('value="' + selectedUnit + '"').join('value="' + selectedUnit + '" selected');

            var isSelected = i === selectedCartIndex ? 'selected' : '';
            var modeLabel = getModeLabel(item.sale_mode);
            var badge = item.sale_mode !== 'unit' ? '<span class="badge badge-info">' + modeLabel + '</span>' : '';

            return '<tr class="' + isSelected + '" onclick="selectCartRow(' + i + ')">' +
                '<td class="text-muted">' + (i + 1) + '</td>' +
                '<td>' +
                '<div class="product-name-cell">' +
                '<div class="name">' + escapeHtml(item.product_name) + '</div>' +
                badge +
                '</div>' +
                '</td>' +
                '<td>' +
                '<select class="unit-type-select" onchange="changeUnitType(' + i + ', this.value)" onclick="event.stopPropagation()">' +
                unitOptions +
                '</select>' +
                '</td>' +
                '<td>' +
                '<div class="qty-control">' +
                '<button class="qty-btn" onclick="event.stopPropagation();changeQty(' + i + ', -1)">−</button>' +
                '<input type="number" class="qty-value" value="' + item.quantity + '" min="0.01" step="' + (item.sale_mode === 'weight' ? '0.001' : '1') + '"' +
                ' onchange="event.stopPropagation();updateCartQty(' + i + ', parseFloat(this.value))" onclick="event.stopPropagation()">' +
                '<button class="qty-btn" onclick="event.stopPropagation();changeQty(' + i + ', 1)">+</button>' +
                '</div>' +
                '</td>' +
                '<td class="text-muted">' + item.price.toFixed(2) + '</td>' +
                '<td class="fw-bold">' + item.subtotal.toFixed(2) + '</td>' +
                '<td><button class="remove-btn" onclick="event.stopPropagation();removeFromCart(' + i + ')">✕</button></td>' +
                '</tr>';
        }).join('');

        if (selectedCartIndex === -1 && cart.length > 0) selectedCartIndex = 0;
        updateTotals();
    };

    window.selectCartRow = function (index) {
        selectedCartIndex = index;
        renderCart();
    };

    window.updateTotals = function () {
        var subtotal = cart.reduce(function (sum, item) { return sum + item.subtotal; }, 0);
        var total = subtotal - discount;
        setText('subtotalDisplay', subtotal.toFixed(2));
        setText('discountDisplay', discount.toFixed(2));
        setText('totalDisplay', total.toFixed(2));
        var checkoutBtn = document.getElementById('checkoutBtn');
        if (checkoutBtn) checkoutBtn.disabled = cart.length === 0;
    };

    window.setText = function (id, text) {
        var el = document.getElementById(id);
        if (el) el.textContent = text;
    };

    window.getModeLabel = function (mode) {
        var labels = { 'unit': 'وحدة', 'pack': 'عبوة', 'weight': 'وزن', 'custom': 'يدوي' };
        return labels[mode] || mode;
    };

    // Modal Actions
    window.openDiscount = function () {
        var el = document.getElementById('discountValue');
        if (el) el.value = discount || '';
        openModal('discountModal');
        setTimeout(function () { if (el) el.focus(); }, 200);
    };

    window.applyDiscount = function () {
        var el = document.getElementById('discountValue');
        discount = parseFloat(el ? el.value : 0) || 0;
        updateTotals();
        closeModal('discountModal');
        showToast('تم تطبيق الخصم: ' + discount.toFixed(2));
    };

    window.openManualPrice = function () {
        setVal('manualName', '');
        setVal('manualPriceValue', '');
        setVal('manualQty', '1');
        openModal('manualPriceModal');
        setTimeout(function () { document.getElementById('manualName').focus(); }, 200);
    };

    window.addManualPrice = function () {
        var name = document.getElementById('manualName').value.trim() || 'منتج يدوي';
        var price = parseFloat(document.getElementById('manualPriceValue').value);
        var qty = parseFloat(document.getElementById('manualQty').value) || 1;
        if (!price || price <= 0) {
            showToast('أدخل سعر صحيح', 'error');
            return;
        }
        cart.push({
            product_id: null,
            product_name: name,
            quantity: qty,
            price: price,
            sale_mode: 'custom',
            unit_type: 'قطعة',
            subtotal: qty * price,
            actual_quantity: qty
        });
        renderCart();
        closeModal('manualPriceModal');
    };

    window.openWeightInput = function () {
        setVal('weightProductSearch', '');
        setVal('weightProductId', '');
        setVal('weightValue', '');
        document.getElementById('weightProductResults').innerHTML = '';
        selectedWeightProduct = null;
        openModal('weightModal');
        setTimeout(function () { document.getElementById('weightProductSearch').focus(); }, 200);
    };

    window.searchWeightProducts = async function (query) {
        if (query.length < 1) {
            document.getElementById('weightProductResults').innerHTML = '';
            return;
        }
        var res = await apiRequest('?page=pos&action=search&q=' + encodeURIComponent(query));
        if (res.success && res.data) {
            var container = document.getElementById('weightProductResults');
            container.innerHTML = res.data.map(function (p) {
                return '<div class="pos-search-item" style="border:1px solid #eee; margin-bottom:5px; padding:8px; display:flex; justify-content:space-between; cursor:pointer;"' +
                    ' onclick="selectWeightProduct(' + p.id + ', \'' + escapeHtml(p.name) + '\', ' + p.sale_price_unit + ')">' +
                    '<span class="name">' + escapeHtml(p.name) + '</span>' +
                    '<span class="price">' + parseFloat(p.sale_price_unit).toFixed(2) + '/كجم</span>' +
                    '</div>';
            }).join('');
        }
    };

    window.selectWeightProduct = function (id, name, price) {
        selectedWeightProduct = { id: id, name: name, price: price };
        setVal('weightProductId', id);
        setVal('weightProductSearch', name);
        document.getElementById('weightProductResults').innerHTML = '';
        document.getElementById('weightValue').focus();
        // Clear feedback
        var feedback = document.getElementById('weightEquivalent');
        if (feedback) feedback.textContent = '';
    };

    window.setWeightByPrice = function (targetPrice) {
        if (!selectedWeightProduct) {
            showToast('اختر منتج أولاً', 'error');
            return;
        }

        var pricePerKg = parseFloat(selectedWeightProduct.price);
        if (pricePerKg <= 0) {
            showToast('سعر المنتج غير صحيح', 'error');
            return;
        }

        // Calculate Weight
        var weight = targetPrice / pricePerKg;

        // Round to 3 decimal places (grams)
        var roundedWeight = weight.toFixed(3);

        // Update Input
        var input = document.getElementById('weightValue');
        if (input) {
            input.value = roundedWeight;
            input.focus();
        }

        // Visual Feedback
        var feedback = document.getElementById('weightEquivalent');
        if (feedback) {
            feedback.textContent = '⚖️ ' + targetPrice + ' ج.م = ' + roundedWeight + ' كجم';
        }
    };

    window.addWeightItem = function () {
        if (!selectedWeightProduct) {
            showToast('اختر منتج أولاً', 'error');
            return;
        }
        var weight = parseFloat(document.getElementById('weightValue').value);
        if (!weight || weight <= 0) {
            showToast('أدخل الوزن', 'error');
            return;
        }
        addToCart({
            id: selectedWeightProduct.id,
            name: selectedWeightProduct.name,
            sale_price_unit: selectedWeightProduct.price
        }, weight, 'weight', 'كجم');
        closeModal('weightModal');
    };

    window.setVal = function (id, val) {
        var el = document.getElementById(id);
        if (el) el.value = val;
    };

    window.processCheckout = async function () {
        if (cart.length === 0) return;
        var checkoutBtn = document.getElementById('checkoutBtn');
        if (checkoutBtn) {
            checkoutBtn.disabled = true;
            checkoutBtn.textContent = '⏳ جاري المعالجة...';
        }
        var payload = {
            items: cart,
            discount: discount,
            payment_method: 'cash',
            notes: ''
        };
        var res = await apiRequest('?page=pos&action=checkout', {
            method: 'POST',
            body: payload
        });
        if (res.success) {
            // Debug: Check if sale data exists
            if (!res.data || !res.data.sale) {
                alert('Debug Error: Sale data missing in response\n' + JSON.stringify(res));
            }
            showToast('✅ تم البيع بنجاح!');
            if (typeof showReceipt === 'function') {
                try {
                    showReceipt(res.data.sale);
                } catch (e) {
                    alert('JS Error in showReceipt: ' + e.message);
                }
            }
            // cart = [];
            // discount = 0;
            // renderCart();
        } else {
            showToast(res.message || 'خطأ في عملية البيع', 'error');
        }
        if (checkoutBtn) {
            checkoutBtn.disabled = cart.length === 0;
            checkoutBtn.textContent = '✅ إتمام البيع (F5)';
        }
    };

    window.showReceipt = function (sale) {
        var content = document.getElementById('receiptContent');
        if (!content) return;

        // ========== STORE INFO (Change these values) ==========
        var storeName = 'عطارة الحكمة';
        var storePhone = '01096107040';
        var storeAddress = 'القاهرة - المرج - ش الشهيد محمد عبدالفتاح الحجار المتفرع من ش مؤسسة الزكاة';
        // ======================================================

        var itemsHtml = (sale.items || []).map(function (item, i) {
            var qty = parseFloat(item.quantity);
            var price = parseFloat(item.price);
            var subtotal = parseFloat(item.subtotal);
            return '<tr>' +
                '<td style="text-align:right;padding:4px 2px;border-bottom:1px dotted #eee;">' + item.product_name + '</td>' +
                '<td style="text-align:center;padding:4px 2px;border-bottom:1px dotted #eee;">' + qty + '</td>' +
                '<td style="text-align:center;padding:4px 2px;border-bottom:1px dotted #eee;">' + price.toFixed(2) + '</td>' +
                '<td style="text-align:left;padding:4px 2px;border-bottom:1px dotted #eee;font-weight:bold;">' + subtotal.toFixed(2) + '</td>' +
                '</tr>';
        }).join('');

        var subtotal = parseFloat(sale.total) + (parseFloat(sale.discount) || 0);
        var discountVal = parseFloat(sale.discount) || 0;
        var total = parseFloat(sale.total);
        var itemCount = (sale.items || []).length;

        // Discount row (only if discount exists)
        var discountRow = '';
        if (discountVal > 0) {
            discountRow =
                '<div style="display:flex;justify-content:space-between;padding:3px 0;color:#e74c3c;">' +
                '<span>الخصم:</span>' +
                '<span>- ' + discountVal.toFixed(2) + '</span>' +
                '</div>';
        }

        content.innerHTML =
            '<div style="font-family:Arial,sans-serif;max-width:300px;margin:0 auto;direction:rtl;text-align:center;padding:10px;">' +

            // ===== HEADER =====
            '<div style="border-bottom:2px solid #333;padding-bottom:12px;margin-bottom:10px;">' +
            '<img src="public/images/logo.png" alt="' + storeName + '" style="width:50px;height:50px;margin-bottom:6px;">' +
            '<div style="font-size:22px;font-weight:bold;color:#2c3e50;margin-bottom:4px;">' + storeName + '</div>' +
            '<div style="font-size:12px;color:#666;line-height:1.8;">' +
            '📞 ' + storePhone + '<br>' +
            '📍 ' + storeAddress +
            '</div>' +
            '</div>' +

            // ===== INVOICE INFO =====
            '<div style="display:flex;justify-content:space-between;font-size:11px;color:#888;margin-bottom:8px;direction:rtl;">' +
            '<span>فاتورة رقم: <strong style="color:#333;">' + (sale.sale_number || sale.id) + '</strong></span>' +
            '<span>' + (sale.datetime || '') + '</span>' +
            '</div>' +

            // ===== ITEMS TABLE =====
            '<table style="width:100%;font-size:12px;border-collapse:collapse;margin:8px 0;">' +
            '<thead>' +
            '<tr style="background:#f8f9fa;border-top:2px solid #333;border-bottom:2px solid #333;">' +
            '<th style="text-align:right;padding:6px 2px;font-size:11px;">الصنف</th>' +
            '<th style="text-align:center;padding:6px 2px;font-size:11px;">الكمية</th>' +
            '<th style="text-align:center;padding:6px 2px;font-size:11px;">السعر</th>' +
            '<th style="text-align:left;padding:6px 2px;font-size:11px;">المجموع</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>' + itemsHtml + '</tbody>' +
            '</table>' +

            // ===== TOTALS =====
            '<div style="border-top:2px solid #333;padding-top:8px;margin-top:5px;font-size:13px;">' +
            '<div style="display:flex;justify-content:space-between;padding:3px 0;">' +
            '<span>عدد الأصناف:</span>' +
            '<span>' + itemCount + '</span>' +
            '</div>' +
            (discountVal > 0 ?
                '<div style="display:flex;justify-content:space-between;padding:3px 0;">' +
                '<span>المجموع الفرعي:</span>' +
                '<span>' + subtotal.toFixed(2) + '</span>' +
                '</div>' : '') +
            discountRow +
            '<div style="display:flex;justify-content:space-between;padding:8px 0;margin-top:5px;border-top:2px dashed #333;font-size:20px;font-weight:bold;color:#2c3e50;">' +
            '<span>الإجمالي:</span>' +
            '<span>' + total.toFixed(2) + ' ج.م</span>' +
            '</div>' +
            '</div>' +

            // ===== FOOTER =====
            '<div style="border-top:1px dashed #ccc;margin-top:10px;padding-top:10px;font-size:11px;color:#999;">' +
            '<div>شكراً لتسوقكم معنا 🙏</div>' +
            '<div style="margin-top:3px;">نتمنى لكم يوماً سعيداً</div>' +
            '</div>' +

            '</div>';

        openModal('receiptModal');
    };

    window.printReceipt = function () {
        // طباعة مباشرة بدون نافذة منبثقة — kiosk-printing يطبع تلقائي
        window.print();
    };

})();

// Force Modal Logic to bypass CSS issues - Nuclear Option
window.openModal = function (id) {

    var m = document.getElementById(id);
    if (m) {

        try {
            document.body.appendChild(m); // Move to body to avoid nesting/z-index traps
        } catch (e) { }

        m.classList.add('active');
        m.style.setProperty('display', 'flex', 'important');
        m.style.setProperty('z-index', '2147483647', 'important'); // Max int
        m.style.setProperty('opacity', '1', 'important');
        m.style.setProperty('visibility', 'visible', 'important');
        m.style.setProperty('position', 'fixed', 'important');
        m.style.top = '0';
        m.style.left = '0';
        m.style.width = '100%';
        m.style.height = '100%';
        m.style.backgroundColor = 'rgba(0,0,0,0.5)'; // Ensure overlay background

    } else {

    }
};

window.closeModal = function (id) {
    var m = document.getElementById(id);
    if (m) {
        m.classList.remove('active');
        m.style.display = 'none';
    }
};
