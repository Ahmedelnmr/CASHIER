<?php
/**
 * Barcode Labels - Print View
 * Optimized for thermal label printers (58mm / 40mm labels)
 */
require_once APP_PATH . '/helpers/BarcodeGenerator.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة التسميات</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            font-size: 10px;
            direction: rtl;
        }
        @media print {
            .no-print { display: none !important; }
            @page { margin: 2mm; }
        }
        .no-print {
            padding: 15px;
            text-align: center;
            background: #f0f0f0;
            margin-bottom: 10px;
        }
        .no-print button {
            padding: 10px 30px;
            font-size: 14px;
            cursor: pointer;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            margin: 5px;
        }
        .labels-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 2mm;
            justify-content: flex-start;
        }
        .label {
            width: 38mm;
            padding: 2mm;
            border: 0.5px solid #ccc;
            text-align: center;
            page-break-inside: avoid;
        }
        .label-name {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 1mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 34mm;
        }
        .label-barcode {
            margin: 1mm 0;
        }
        .label-barcode img {
            width: 32mm;
            height: 12mm;
        }
        .label-price {
            font-size: 11px;
            font-weight: bold;
        }
        .label-barcode-text {
            font-size: 7px;
            color: #666;
            direction: ltr;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <h3>طباعة التسميات (<?= count($products) ?> منتج)</h3>
        <p>اضغط "طباعة" لطباعة التسميات</p>
        <button onclick="window.print()">🖨️ طباعة</button>
        <button onclick="window.close()">إغلاق</button>
    </div>

    <div class="labels-grid">
        <?php foreach ($products as $p): ?>
        <?php if (empty($p['barcode'])) continue; ?>
        <div class="label">
            <div class="label-name"><?= htmlspecialchars($p['name']) ?></div>
            <div class="label-barcode">
                <img src="?page=barcode&action=image&data=<?= urlencode($p['barcode']) ?>&w=2&h=40" alt="">
            </div>
            <div class="label-barcode-text"><?= htmlspecialchars($p['barcode']) ?></div>
            <div class="label-price"><?= number_format($p['sale_price_unit'], 2) ?> ج.م</div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
