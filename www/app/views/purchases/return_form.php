<div class="card">
    <div class="card-header">
        <h3>تسجيل مرتجع مشتريات (فاتورة #<?= $invoice['invoice_number'] ?>)</h3>
    </div>
    <div class="card-body">
        <form action="?page=purchases&action=save_return" method="post" id="returnForm">
            <input type="hidden" name="original_invoice_id" value="<?= $invoice['id'] ?>">
            <input type="hidden" name="items_json" id="items_json">

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>سعر الشراء</th>
                            <th>الكمية الأصلية</th>
                            <th>الكمية المرتجعة</th>
                            <th>الإجمالي المسترد</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoice['items'] as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td><?= number_format($item['purchase_price'], 2) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td>
                                    <input type="number" class="form-control return-qty" 
                                           data-product-id="<?= $item['product_id'] ?>"
                                           data-product-name="<?= htmlspecialchars($item['product_name']) ?>"
                                           data-price="<?= $item['purchase_price'] ?>"
                                           data-max="<?= $item['quantity'] ?>"
                                           min="0" max="<?= $item['quantity'] ?>" value="0" step="0.001">
                                </td>
                                <td class="line-total">0.00</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-right">إجمالي المرتجع:</th>
                            <th id="grandTotal">0.00</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="form-group text-center">
                <button type="button" onclick="submitReturn()" class="btn btn-danger btn-lg">حفظ المرتجع للمورد</button>
                <a href="?page=purchases" class="btn btn-secondary btn-lg">إلغاء</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.return-qty').forEach(input => {
        input.addEventListener('input', function() {
            let max = parseFloat(this.dataset.max);
            let val = parseFloat(this.value) || 0;
            
            if (val > max) {
                val = max;
                this.value = max;
            }
            if (val < 0) {
                val = 0;
                this.value = 0;
            }

            let price = parseFloat(this.dataset.price);
            let total = val * price;
            
            this.closest('tr').querySelector('.line-total').textContent = total.toFixed(2);
            calculateGrandTotal();
        });
    });

    function calculateGrandTotal() {
        let total = 0;
        document.querySelectorAll('.line-total').forEach(el => {
            total += parseFloat(el.textContent);
        });
        document.getElementById('grandTotal').textContent = total.toFixed(2);
    }

    function submitReturn() {
        let items = [];
        let hasItems = false;
        
        document.querySelectorAll('.return-qty').forEach(input => {
            let qty = parseFloat(input.value) || 0;
            if (qty > 0) {
                items.push({
                    product_id: input.dataset.productId,
                    product_name: input.dataset.productName,
                    price: parseFloat(input.dataset.price),
                    quantity: qty
                });
                hasItems = true;
            }
        });

        if (!hasItems) {
            alert('يجب اختيار صنف واحد على الأقل للإرجاع');
            return;
        }

        if (confirm('هل أنت متأكد من تسجيل هذا المرتجع للمورد؟ (سيتم خصم المخزون واسترداد القيمة)')) {
            document.getElementById('items_json').value = JSON.stringify(items);
            document.getElementById('returnForm').submit();
        }
    }
</script>
