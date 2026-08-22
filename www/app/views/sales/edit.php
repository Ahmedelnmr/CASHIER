<?php
/**
 * Edit Sale View
 * Allows modifying items, quantities, and prices dynamically.
 */
?>
<style>
/* Hide Browser Default Spinners */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
input[type=number] {
  -moz-appearance: textfield;
}
/* Search Results Styling */
.search-results {
    position: absolute;
    top: 100%; left: 0; right: 0;
    background: #fff; border: 1px solid #ddd;
    border-radius: 0 0 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.2);
    z-index: 9999; max-height: 300px; overflow-y: auto; display: none;
}
.search-results.active { display: block; }
.search-item { padding: 12px 10px; border-bottom: 1px solid #eee; cursor: pointer; background: #fff; }
.search-item:hover { background: #f0f4f8; }
.search-item strong { display: block; color: #000; }
.search-item small { color: #666; font-size: 11px; }

.summary-card { background:#fff; border:1px solid #ddd; box-shadow:0 -2px 10px rgba(0,0,0,0.05); color:#333; display: flex; flex-direction: row; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 15px; padding: 15px; border-radius: 8px; margin-top: 20px;}
</style>

<div class="page-header">
    <div>
        <h1>📝 تعديل فاتورة #<?= $sale['sale_number'] ?></h1>
        <p class="subtitle"><?= $sale['datetime'] ?></p>
    </div>
    <div>
        <a href="?page=sales" class="btn btn-outline">إلغاء وعودة</a>
    </div>
</div>

<div class="card" style="padding:20px; min-height: 500px; display: flex; flex-direction: column;">

    <?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger" style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
        ❌ <?= htmlspecialchars($_GET['error']) ?>
    </div>
    <?php endif; ?>

    <!-- Product Search -->
    <div class="form-group mb-3" style="position:relative;">
        <label>إضافة منتج</label>
        <input type="text" id="productSearch" class="form-control" placeholder="امسح الباركود أو اكتب اسم المنتج للبحث..." autocomplete="off" oninput="handleSearchInput(this.value)" onkeydown="if(event.key==='Enter'){event.preventDefault(); addProductFromSearch();}" onblur="setTimeout(()=>hideSearchResults(), 200)">
        <div id="searchResults" class="search-results"></div>
    </div>

    <!-- Explicit form action to route correctly -->
    <form action="index.php?page=sales&action=update" method="POST" id="editForm" style="flex: 1; display: flex; flex-direction: column;" onsubmit="return false;">
        <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">
        <input type="hidden" name="items_json" id="itemsJson">

        <div class="table-wrapper" style="flex: 1; overflow: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th width="120">النوع</th>
                        <th width="120">الكمية</th>
                        <th width="120">السعر</th>
                        <th width="120">الإجمالي</th>
                        <th width="50"></th>
                    </tr>
                </thead>
                <tbody id="itemsTableBody">
                    <!-- Items will be injected here via JS -->
                </tbody>
            </table>
        </div>
        
        <div class="form-group mt-3">
            <label>سبب التعديل *</label>
            <input type="text" name="reason" id="editReason" class="form-control" placeholder="مثال: خطأ في الكمية، استبدال منتج..." required>
        </div>

        <div class="summary-card">
            <div style="display:flex; gap: 20px; align-items: center; flex: 1;">
                <div style="display: flex; flex-direction: column; text-align: center;">
                    <div style="color:#666; font-size: 12px;">المجموع الفرعي</div>
                    <div style="font-size: 18px; font-weight:bold;" id="subtotalDisplay">0.00</div>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; background:#f8f9fa; padding:8px 15px; border-radius:6px; border: 1px solid #eee;">
                <span style="font-size:12px; color:#666;">الخصم:</span>
                <input type="number" name="discount" id="discountInput" class="form-control" style="width: 100px; height:32px; text-align:center; font-weight:bold;" value="<?= $sale['discount'] ?>" onchange="updateTotals()">
            </div>
            
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="display: flex; flex-direction: column; text-align: left;">
                    <div style="font-size: 12px; color:#666;">الإجمالي النهائي</div>
                    <div class="total-amount" id="totalDisplay" style="color:#27ae60; font-size:24px; font-weight: bold;">0.00</div>
                    <input type="hidden" name="total" id="totalInput">
                </div>
                
                <button type="button" class="btn btn-primary" onclick="prepareSubmit()" style="background:#2c3e50; border:none; height:45px; padding: 0 25px; font-size:16px; border-radius: 6px;">
                    💾 حفظ التعديلات
                </button>
            </div>
        </div>
    </form>
</div>

<script>
// Initial Items from PHP
var items = <?= json_encode($sale['items']) ?>;
var productsList = [];

// Build Products List for local autocomplete
<?php foreach ($products as $p): ?>
productsList.push({
    id: "<?= $p['id'] ?>",
    name: <?= json_encode($p['name']) ?>,
    barcode: "<?= htmlspecialchars($p['barcode'] ?? '') ?>",
    barcode2: "<?= htmlspecialchars($p['barcode2'] ?? '') ?>",
    barcode3: "<?= htmlspecialchars($p['barcode3'] ?? '') ?>",
    plu_code: "<?= htmlspecialchars($p['plu_code'] ?? '') ?>",
    price_unit: <?= $p['sale_price_unit'] ?>,
    price_pack: <?= $p['pack_sale_price'] ?? 0 ?>,
    pack_qty: <?= $p['pack_unit_quantity'] ?? 1 ?>,
    type: "<?= $p['type'] ?>"
});
<?php endforeach; ?>

function renderItems() {
    var tbody = document.getElementById('itemsTableBody');
    tbody.innerHTML = '';
    var subtotal = 0;

    items.forEach(function(item, index) {
        var rowTotal = item.quantity * item.price; // Use stored price
        item.subtotal = rowTotal; // IMPORTANT: Update subtotal in object
        subtotal += rowTotal;

        var tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.product_name}</td>
            <td>
                <select class="form-control form-control-sm" onchange="updateItemUnit(${index}, this.value)">
                    <option value="قطعة" ${item.unit_type === 'قطعة' ? 'selected' : ''}>قطعة</option>
                    <option value="pack" ${item.unit_type !== 'قطعة' ? 'selected' : ''}>عبوة/طرد</option>
                </select>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm no-spinner" value="${item.quantity}" min="0.1" step="0.1" onchange="updateItemQty(${index}, this.value)">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm no-spinner" value="${item.price}" min="0" step="0.01" onchange="updateItemPrice(${index}, this.value)">
            </td>
            <td class="fw-bold">${rowTotal.toFixed(2)}</td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${index})">×</button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById('subtotalDisplay').textContent = subtotal.toFixed(2);
    updateTotals();
}

function updateTotals() {
    var subtotal = 0;
    items.forEach(function(i) { subtotal += (i.quantity * i.price); });
    
    var discount = parseFloat(document.getElementById('discountInput').value) || 0;
    var total = subtotal - discount;

    document.getElementById('totalDisplay').textContent = total.toFixed(2);
    document.getElementById('totalInput').value = total;
}

function updateItemQty(index, val) {
    items[index].quantity = parseFloat(val) || 0;
    items[index].subtotal = items[index].quantity * items[index].price;
    renderItems();
}

function updateItemPrice(index, val) {
    items[index].price = parseFloat(val) || 0;
    items[index].subtotal = items[index].quantity * items[index].price;
    renderItems();
}

function updateItemUnit(index, unit) {
    items[index].unit_type = unit;
    
    var p = productsList.find(pr => pr.name === items[index].product_name || pr.id == items[index].product_id);
    if (p) {
        if (unit === 'قطعة') {
            items[index].price = p.price_unit;
            items[index].sale_mode = 'unit';
        } else {
            items[index].price = p.price_pack;
            items[index].sale_mode = 'pack';
        }
    }
    items[index].subtotal = items[index].quantity * items[index].price;
    renderItems();
}

function removeItem(index) {
    if(confirm('حذف الصنف؟')) {
        items.splice(index, 1);
        renderItems();
    }
}

// Custom Autocomplete Search
function handleSearchInput(val) {
    var resultsBox = document.getElementById('searchResults');
    val = val.trim().toLowerCase();
    
    if (val.length === 0) {
        resultsBox.classList.remove('active');
        return;
    }
    
    var matches = productsList.filter(function(p) {
        return p.name.toLowerCase().includes(val) ||
               p.barcode === val ||
               p.barcode2 === val ||
               p.barcode3 === val ||
               p.plu_code === val;
    }).slice(0, 10);
    
    if (matches.length === 0) {
        resultsBox.innerHTML = '<div style="padding:10px; color:#999; text-align:center;">لا توجد نتائج</div>';
        resultsBox.classList.add('active');
        return;
    }
    
    var html = '';
    matches.forEach(function(p) {
        var pJson = JSON.stringify(p).replace(/'/g, "&#39;").replace(/"/g, '&quot;');
        html += `<div class="search-item" onclick="addSelectedProduct(${pJson})">
                    <strong>${p.name}</strong>
                    <small>السعر: ${p.price_unit} | باركود: ${p.barcode || p.plu_code || 'بدون'}</small>
                 </div>`;
    });
    
    resultsBox.innerHTML = html;
    resultsBox.classList.add('active');
}

function hideSearchResults() {
    document.getElementById('searchResults').classList.remove('active');
}

function addProductFromSearch() {
    var val = document.getElementById('productSearch').value.trim().toLowerCase();
    if (!val) return;
    
    // Exact barcode match
    var exactMatch = productsList.find(function(p) {
        return p.barcode === val || p.barcode2 === val || p.barcode3 === val || p.plu_code === val;
    });
    
    if (exactMatch) {
        addSelectedProduct(exactMatch);
    } else {
        // Fallback to searching names
        var matches = productsList.filter(function(p) {
            return p.name.toLowerCase().includes(val);
        });
        if (matches.length === 1) {
            addSelectedProduct(matches[0]);
        } else {
            alert('المنتج غير موجود أو يوجد أكثر من نتيجة. برجاء اختياره من القائمة.');
        }
    }
}

function addSelectedProduct(p) {
    items.push({
        product_id: p.id,
        product_name: p.name,
        quantity: 1,
        unit_type: 'قطعة',
        price: p.price_unit,
        sale_mode: 'unit',
        subtotal: p.price_unit
    });
    document.getElementById('productSearch').value = '';
    hideSearchResults();
    renderItems();
}

function prepareSubmit() {
    if (items.length === 0) {
        alert('لا يمكن حفظ فاتورة فارغة');
        return;
    }
    
    var reason = document.getElementById('editReason').value.trim();
    if (!reason) {
        alert('الرجاء كتابة سبب التعديل');
        document.getElementById('editReason').focus();
        return;
    }
    
    document.getElementById('itemsJson').value = JSON.stringify(items);
    document.getElementById('editForm').submit();
}

// Initial Render
renderItems();
</script>
