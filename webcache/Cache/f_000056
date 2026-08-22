/**
 * POS.js - Point of Sale Frontend Logic
 */

// Cart state
let cart = [];
let discount = 0;
let selectedWeightProduct = null;

// ==========================================
// INITIALIZATION
// ==========================================
document.addEventListener('DOMContentLoaded', function () {
    try {
        const barcodeInput = document.getElementById('barcodeInput');

        // Auto-focus barcode input
        if (barcodeInput) barcodeInput.focus();

        // Barcode input handler - submit on Enter
        if (barcodeInput) {
            barcodeInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const code = this.value.trim();
                    if (code) {
                        addByCode(code);
                        this.value = '';
                        hideSearchResults();
                    }
                }
            });

            // Live search on barcode input
            barcodeInput.addEventListener('input', debounce(function () {
                const q = this.value.trim();
                if (q.length >= 2) {
                    liveSearchBarcode(q);
                } else {
                    hideSearchResults();
                }
            }, 250));
        }

        // Click outside to close search results
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.pos-input-area')) {
                hideSearchResults();
            }
        });

        // Load all products initially
        loadProducts('');

        // Keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            if (e.key === 'F5') {
                e.preventDefault();
                processCheckout();
            }
            if (e.key === 'F2') {
                e.preventDefault();
                if (barcodeInput) {
                    barcodeInput.focus();
                    barcodeInput.select();
                }
            }
            if (e.key === 'F3') {
                e.preventDefault();
                const ps = document.getElementById('productSearch');
                if (ps) ps.focus();
            }
            if (e.key === 'F4') {
                e.preventDefault();
                openManualPrice();
            }
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
                if (barcodeInput) barcodeInput.focus();
            }
        });
    } catch (err) {
        alert('JS Error: ' + err.message);
        console.error(err);
    }
});

// ==========================================
// PRODUCT LOOKUP
// ==========================================
async function addByCode(code) {
    const res = await apiRequest(`?page=pos&action=findProduct&q=${encodeURIComponent(code)}`);
    if (res.success && res.data) {
        addToCart(res.data, 1, 'unit');
    } else {
        showToast(res.message || 'المنتج غير موجود: ' + code, 'error');
    }
}

async function liveSearchBarcode(query) {
    const res = await apiRequest(`?page=pos&action=search&q=${encodeURIComponent(query)}`);
    if (res.success && res.data && res.data.length > 0) {
        showSearchResults(res.data);
    } else {
        hideSearchResults();
    }
}

function showSearchResults(products) {
    const container = document.getElementById('searchResults');
    container.innerHTML = products.map(p => `
        <div class="pos-search-item" onclick="selectSearchProduct(${p.id})">
            <div>
                <div class="name">${escapeHtml(p.name)}</div>
                <div class="meta">${p.barcode || ''} ${p.plu_code ? '| PLU: ' + p.plu_code : ''}</div>
            </div>
            <div class="price">${parseFloat(p.sale_price_unit).toFixed(2)}</div>
        </div>
    `).join('');
    container.classList.add('active');
}

function hideSearchResults() {
    document.getElementById('searchResults').classList.remove('active');
}

async function selectSearchProduct(productId) {
    const res = await apiRequest(`?page=pos&action=findProduct&q=${productId}`);
    if (res.success && res.data) {
        addToCart(res.data, 1, res.data.type === 'weight' ? 'weight' : 'unit');
        document.getElementById('barcodeInput').value = '';
        hideSearchResults();
    }
}

// ==========================================
// PRODUCT GRID SEARCH
// ==========================================
async function searchProducts(query) {
    loadProducts(query);
}

async function loadProducts(query) {
    const res = await apiRequest(`?page=pos&action=search&q=${encodeURIComponent(query || '')}`);
    const grid = document.getElementById('productsGrid');

    if (res.success && res.data && res.data.length > 0) {
        grid.innerHTML = res.data.map(p => `
            <div class="pos-product-card" onclick="selectSearchProduct(${p.id})">
                <div class="p-name" title="${escapeHtml(p.name)}">${escapeHtml(p.name)}</div>
                <div class="p-price">${parseFloat(p.sale_price_unit).toFixed(2)}</div>
                <div class="p-stock">المخزون: ${parseFloat(p.stock_quantity).toFixed(p.type === 'weight' ? 3 : 0)}</div>
            </div>
        `).join('');
    } else {
        grid.innerHTML = `
            <div class="empty-state" style="grid-column:1/-1;">
                <div class="icon">📦</div>
                <p>${query ? 'لا توجد نتائج' : 'ابحث عن منتج'}</p>
            </div>`;
    }
}

// ==========================================
// CART MANAGEMENT
// ==========================================
function addToCart(product, quantity = 1, saleMode = 'unit') {
    let price;
    switch (saleMode) {
        case 'pack':
            price = parseFloat(product.sale_price_pack) || parseFloat(product.sale_price_unit);
            break;
        case 'weight':
            price = parseFloat(product.sale_price_unit);
            break;
        default:
            price = parseFloat(product.sale_price_unit);
    }

    // Check if product already in cart (same product and mode)
    const existingIndex = cart.findIndex(item =>
        item.product_id === product.id && item.sale_mode === saleMode
    );

    if (existingIndex >= 0 && saleMode !== 'custom') {
        cart[existingIndex].quantity += quantity;
        cart[existingIndex].subtotal = cart[existingIndex].quantity * cart[existingIndex].price;
    } else {
        cart.push({
            product_id: product.id,
            product_name: product.name,
            quantity: quantity,
            price: price,
            sale_mode: saleMode,
            subtotal: quantity * price
        });
    }

    renderCart();
    showToast('✅ ' + product.name);
    document.getElementById('barcodeInput').focus();
}

function addManualItem(name, price, quantity) {
    cart.push({
        product_id: null,
        product_name: name || 'منتج يدوي',
        quantity: quantity,
        price: parseFloat(price),
        sale_mode: 'custom',
        subtotal: quantity * parseFloat(price)
    });
    renderCart();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

function updateCartQty(index, newQty) {
    if (newQty <= 0) {
        removeFromCart(index);
        return;
    }
    cart[index].quantity = parseFloat(newQty);
    cart[index].subtotal = cart[index].quantity * cart[index].price;
    renderCart();
}

function changeQty(index, delta) {
    const newQty = cart[index].quantity + delta;
    updateCartQty(index, newQty);
}

function clearCart() {
    cart = [];
    discount = 0;
    renderCart();
}

// ==========================================
// RENDER CART
// ==========================================
function renderCart() {
    const tbody = document.getElementById('cartBody');

    if (cart.length === 0) {
        tbody.innerHTML = `
            <tr id="emptyCart">
                <td colspan="6" class="text-center text-muted" style="padding:40px;">
                    السلة فارغة - امسح باركود أو ابحث عن منتج
                </td>
            </tr>`;
        updateTotals();
        return;
    }

    tbody.innerHTML = cart.map((item, i) => `
        <tr>
            <td class="text-muted">${i + 1}</td>
            <td>
                <div class="fw-bold" style="font-size:12.5px;">${escapeHtml(item.product_name)}</div>
                ${item.sale_mode !== 'unit' ? `<span class="badge badge-info" style="margin-top:2px;">${getModeLabel(item.sale_mode)}</span>` : ''}
            </td>
            <td>
                <div class="qty-control">
                    <button class="qty-btn" onclick="changeQty(${i}, -1)">−</button>
                    <input type="number" class="qty-value" value="${item.quantity}" min="0.01" step="${item.sale_mode === 'weight' ? '0.001' : '1'}"
                           onchange="updateCartQty(${i}, parseFloat(this.value))">
                    <button class="qty-btn" onclick="changeQty(${i}, 1)">+</button>
                </div>
            </td>
            <td class="text-muted">${item.price.toFixed(2)}</td>
            <td class="fw-bold">${item.subtotal.toFixed(2)}</td>
            <td><button class="remove-btn" onclick="removeFromCart(${i})">✕</button></td>
        </tr>
    `).join('');

    updateTotals();
}

function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const total = subtotal - discount;

    document.getElementById('subtotalDisplay').textContent = subtotal.toFixed(2);
    document.getElementById('discountDisplay').textContent = discount.toFixed(2);
    document.getElementById('totalDisplay').textContent = total.toFixed(2);
    document.getElementById('checkoutBtn').disabled = cart.length === 0;
}

function getModeLabel(mode) {
    const labels = { 'unit': 'وحدة', 'pack': 'عبوة', 'weight': 'وزن', 'custom': 'يدوي' };
    return labels[mode] || mode;
}

// ==========================================
// CHECKOUT
// ==========================================
async function processCheckout() {
    if (cart.length === 0) return;

    const checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.disabled = true;
    checkoutBtn.textContent = '⏳ جاري المعالجة...';

    const payload = {
        items: cart,
        discount: discount,
        payment_method: 'cash',
        notes: ''
    };

    const res = await apiRequest('?page=pos&action=checkout', {
        method: 'POST',
        body: payload
    });

    if (res.success) {
        showToast('✅ تم البيع بنجاح!');
        showReceipt(res.data.sale);
        cart = [];
        discount = 0;
        renderCart();
    } else {
        showToast(res.message || 'خطأ في عملية البيع', 'error');
    }

    checkoutBtn.disabled = cart.length === 0;
    checkoutBtn.textContent = '✅ إتمام البيع (F5)';
}

// ==========================================
// RECEIPT
// ==========================================
function showReceipt(sale) {
    if (!sale) return;

    const content = document.getElementById('receiptContent');
    content.innerHTML = `
        <div class="receipt">
            <h2>إيصال بيع</h2>
            <div style="text-align:center;font-size:11px;color:#666;">
                فاتورة رقم: ${sale.sale_number || sale.id}<br>
                ${sale.datetime}
            </div>
            <div class="line"></div>
            <table>
                <thead>
                    <tr><th style="text-align:right">المنتج</th><th>الكمية</th><th>السعر</th><th>المجموع</th></tr>
                </thead>
                <tbody>
                    ${(sale.items || []).map(item => `
                        <tr>
                            <td>${escapeHtml(item.product_name)}</td>
                            <td style="text-align:center">${item.quantity}</td>
                            <td style="text-align:center">${parseFloat(item.price).toFixed(2)}</td>
                            <td style="text-align:left">${parseFloat(item.subtotal).toFixed(2)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            <div class="line"></div>
            ${parseFloat(sale.discount) > 0 ? `<div style="display:flex;justify-content:space-between;"><span>الخصم:</span><span>${parseFloat(sale.discount).toFixed(2)}</span></div>` : ''}
            <div class="total-line">
                الإجمالي: ${parseFloat(sale.total).toFixed(2)}
            </div>
            <div class="line"></div>
            <div style="text-align:center;font-size:10px;color:#888;margin-top:8px;">
                شكراً لتسوقكم 🙏
            </div>
        </div>
    `;
    openModal('receiptModal');
}

function printReceipt() {
    const content = document.getElementById('receiptContent').innerHTML;
    const win = window.open('', '_blank', 'width=350,height=600');
    win.document.write(`
        <html dir="rtl"><head><title>إيصال</title>
        <style>
            body { font-family: 'Courier New', monospace; font-size: 12px; padding: 10px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 3px 2px; font-size: 11px; }
            .line { border-bottom: 1px dashed #000; margin: 8px 0; }
            .total-line { font-size: 16px; font-weight: bold; text-align: center; margin-top: 10px; }
            h2 { text-align: center; font-size: 16px; }
        </style></head><body>${content}</body></html>
    `);
    win.document.close();
    win.print();
}

// ==========================================
// MODALS
// ==========================================
function openManualPrice() {
    document.getElementById('manualName').value = '';
    document.getElementById('manualPriceValue').value = '';
    document.getElementById('manualQty').value = '1';
    openModal('manualPriceModal');
    setTimeout(() => document.getElementById('manualName').focus(), 200);
}

function addManualPrice() {
    const name = document.getElementById('manualName').value.trim() || 'منتج يدوي';
    const price = parseFloat(document.getElementById('manualPriceValue').value);
    const qty = parseFloat(document.getElementById('manualQty').value) || 1;

    if (!price || price <= 0) {
        showToast('أدخل سعر صحيح', 'error');
        return;
    }

    addManualItem(name, price, qty);
    closeModal('manualPriceModal');
}

function openWeightInput() {
    document.getElementById('weightProductSearch').value = '';
    document.getElementById('weightProductId').value = '';
    document.getElementById('weightProductName').textContent = '';
    document.getElementById('weightValue').value = '';
    document.getElementById('weightProductResults').innerHTML = '';
    selectedWeightProduct = null;
    openModal('weightModal');
    setTimeout(() => document.getElementById('weightProductSearch').focus(), 200);
}

async function searchWeightProducts(query) {
    if (query.length < 1) {
        document.getElementById('weightProductResults').innerHTML = '';
        return;
    }
    const res = await apiRequest(`?page=pos&action=search&q=${encodeURIComponent(query)}`);
    if (res.success && res.data) {
        const container = document.getElementById('weightProductResults');
        container.innerHTML = res.data.map(p => `
            <div class="pos-search-item" style="border:1px solid var(--border-color);border-radius:6px;margin-bottom:4px;" 
                 onclick="selectWeightProduct(${p.id}, '${escapeHtml(p.name)}', ${p.sale_price_unit})">
                <span class="name">${escapeHtml(p.name)}</span>
                <span class="price">${parseFloat(p.sale_price_unit).toFixed(2)}/كجم</span>
            </div>
        `).join('');
    }
}

function selectWeightProduct(id, name, price) {
    selectedWeightProduct = { id, name, price };
    document.getElementById('weightProductId').value = id;
    document.getElementById('weightProductName').textContent = '✅ ' + name + ' - ' + price.toFixed(2) + '/كجم';
    document.getElementById('weightProductResults').innerHTML = '';
    document.getElementById('weightProductSearch').value = name;
    document.getElementById('weightValue').focus();
}

function addWeightItem() {
    if (!selectedWeightProduct) {
        showToast('اختر منتج أولاً', 'error');
        return;
    }
    const weight = parseFloat(document.getElementById('weightValue').value);
    if (!weight || weight <= 0) {
        showToast('أدخل الوزن', 'error');
        return;
    }

    addToCart({
        id: selectedWeightProduct.id,
        name: selectedWeightProduct.name,
        sale_price_unit: selectedWeightProduct.price
    }, weight, 'weight');

    closeModal('weightModal');
}

function openSearch() {
    document.getElementById('productSearch').focus();
    document.getElementById('productSearch').select();
}

function openDiscount() {
    document.getElementById('discountValue').value = discount || '';
    openModal('discountModal');
    setTimeout(() => document.getElementById('discountValue').focus(), 200);
}

function applyDiscount() {
    discount = parseFloat(document.getElementById('discountValue').value) || 0;
    updateTotals();
    closeModal('discountModal');
    showToast('تم تطبيق الخصم: ' + discount.toFixed(2));
}

// ==========================================
// UTILITIES
// ==========================================
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}
