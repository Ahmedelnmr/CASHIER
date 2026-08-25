<!-- Sale Receipt View -->
<div class="page-header no-print">
    <div>
        <h1>🧾 إيصال بيع #<?= $sale['sale_number'] ?? $sale['id'] ?></h1>
    </div>
    <div class="btn-group">
        <button class="btn btn-primary" onclick="window.print()">🖨️ طباعة</button>
        <a href="?page=sales" class="btn btn-outline">← العودة</a>
    </div>
</div>

<div style="max-width:400px;margin:0 auto;">
    <div class="receipt" id="printableReceipt">
        
        <!-- ===== HEADER with Logo ===== -->
        <div style="border-bottom:2px solid #333;padding-bottom:12px;margin-bottom:10px;text-align:center;">
            <img src="public/images/logo.png" alt="تاجر" style="width:50px;height:50px;margin-bottom:6px;">
            <div style="font-size:22px;font-weight:bold;color:#2c3e50;margin-bottom:4px;">تاجر</div>
            <div style="font-size:11px;color:#666;line-height:1.8;">
                📞 01096107040<br>
                📍 القاهرة - المرج - ش الشهيد محمد عبدالفتاح الحجار المتفرع من ش مؤسسة الزكاة
            </div>
        </div>

        <!-- ===== INVOICE INFO ===== -->
        <div style="display:flex;justify-content:space-between;font-size:11px;color:#888;margin-bottom:8px;">
            <span>فاتورة رقم: <strong style="color:#333;"><?= $sale['sale_number'] ?? $sale['id'] ?></strong></span>
            <span><?= $sale['datetime'] ?></span>
        </div>

        <!-- ===== ITEMS TABLE ===== -->
        <table style="width:100%;font-size:12px;border-collapse:collapse;margin:8px 0;">
            <thead>
                <tr style="background:#f8f9fa;border-top:2px solid #333;border-bottom:2px solid #333;">
                    <th style="text-align:right;padding:6px 2px;font-size:11px;">الصنف</th>
                    <th style="text-align:center;padding:6px 2px;font-size:11px;">الكمية</th>
                    <th style="text-align:center;padding:6px 2px;font-size:11px;">السعر</th>
                    <th style="text-align:left;padding:6px 2px;font-size:11px;">المجموع</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sale['items'] as $item): ?>
                <tr>
                    <td style="text-align:right;padding:4px 2px;border-bottom:1px dotted #eee;"><?= htmlspecialchars($item['product_name']) ?></td>
                    <td style="text-align:center;padding:4px 2px;border-bottom:1px dotted #eee;"><?= $item['quantity'] ?></td>
                    <td style="text-align:center;padding:4px 2px;border-bottom:1px dotted #eee;"><?= number_format($item['price'], 2) ?></td>
                    <td style="text-align:left;padding:4px 2px;border-bottom:1px dotted #eee;font-weight:bold;"><?= number_format($item['subtotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ===== TOTALS ===== -->
        <div style="border-top:2px solid #333;padding-top:8px;margin-top:5px;font-size:13px;">
            <div style="display:flex;justify-content:space-between;padding:3px 0;">
                <span>عدد الأصناف:</span>
                <span><?= count($sale['items']) ?></span>
            </div>
            <?php if ($sale['discount'] > 0): ?>
            <div style="display:flex;justify-content:space-between;padding:3px 0;">
                <span>المجموع الفرعي:</span>
                <span><?= number_format($sale['subtotal'] ?? ($sale['total'] + $sale['discount']), 2) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:3px 0;color:#e74c3c;">
                <span>الخصم:</span>
                <span>- <?= number_format($sale['discount'], 2) ?></span>
            </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;padding:8px 0;margin-top:5px;border-top:2px dashed #333;font-size:20px;font-weight:bold;color:#2c3e50;">
                <span>الإجمالي:</span>
                <span><?= number_format($sale['total'], 2) ?> ج.م</span>
            </div>
        </div>

        <!-- ===== FOOTER ===== -->
        <div style="border-top:1px dashed #ccc;margin-top:10px;padding-top:10px;font-size:11px;color:#999;text-align:center;">
            <div>شكراً لتسوقكم معنا 🙏</div>
            <div style="margin-top:3px;">نتمنى لكم يوماً سعيداً</div>
        </div>
    </div>
</div>
