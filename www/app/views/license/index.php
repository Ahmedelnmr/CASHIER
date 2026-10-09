<?php
/**
 * License Activation Page
 */
$licenseError   = $_SESSION['license_error'] ?? '';
$licenseSuccess = $_SESSION['license_success'] ?? '';
unset($_SESSION['license_error'], $_SESSION['license_success']);

$hardwareId = $licenseInfo['hardware_id'] ?? 'جاري التحميل...';
$status     = $licenseInfo;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفعيل الترخيص — تاجر</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #0a1628 0%, #152d54 50%, #0f2140 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            direction: rtl;
            padding: 20px;
        }

        .license-wrapper {
            width: 100%;
            max-width: 560px;
        }

        .license-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        /* Header */
        .license-header {
            background: linear-gradient(135deg, #0f2140 0%, #0ea5a0 100%);
            padding: 32px 30px 24px;
            text-align: center;
            color: #fff;
        }

        .lock-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255,255,255,0.25);
            font-size: 34px;
        }

        .license-header h1 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .license-header p {
            font-size: 13px;
            opacity: 0.8;
            line-height: 1.5;
        }

        /* Body */
        .license-body {
            padding: 30px;
        }

        /* Hardware ID Box */
        .hid-box {
            background: #f0f9ff;
            border: 2px dashed #0ea5a0;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 24px;
            text-align: center;
        }

        .hid-label {
            font-size: 12px;
            font-weight: 700;
            color: #0d8a85;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .hid-value {
            font-size: 24px;
            font-weight: 900;
            color: #0a1628;
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
            margin-bottom: 10px;
            user-select: all;
        }

        .hid-tip {
            font-size: 12px;
            color: #64748b;
            line-height: 1.5;
        }

        .btn-copy {
            margin-top: 10px;
            padding: 6px 16px;
            background: #0ea5a0;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.2s;
        }

        .btn-copy:hover { background: #0d8a85; }

        /* Status indicator */
        .status-banner {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13.5px;
            font-weight: 600;
        }

        .status-banner.invalid {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .status-banner.valid {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #86efac;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-dot.red { background: #ef4444; }
        .status-dot.green { background: #10b981; animation: pulse 2s infinite; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* Alert messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13.5px;
            font-weight: 600;
        }
        .alert-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #86efac;
        }

        /* Form */
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #1a2332;
            margin-bottom: 7px;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Courier New', monospace;
            color: #0a1628;
            font-weight: 700;
            letter-spacing: 1px;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #0ea5a0;
            box-shadow: 0 0 0 3px rgba(14, 165, 160, 0.12);
        }

        .btn-activate {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0ea5a0, #0d8a85);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(14, 165, 160, 0.35);
        }

        .btn-activate:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(14, 165, 160, 0.45);
        }

        .btn-activate:active {
            transform: translateY(1px);
        }

        /* Steps guide */
        .steps-box {
            margin-top: 24px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 20px;
        }

        .steps-title {
            font-size: 13px;
            font-weight: 800;
            color: #0a1628;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 12.5px;
            color: #475569;
            line-height: 1.5;
        }

        .step-num {
            width: 22px;
            height: 22px;
            background: #0ea5a0;
            color: #fff;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Footer */
        .license-footer {
            padding: 16px 30px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

<div class="license-wrapper">
    <div class="license-card">

        <!-- Header -->
        <div class="license-header">
            <div class="lock-icon">🔐</div>
            <h1>تفعيل الترخيص</h1>
            <p>يرجى تفعيل البرنامج بكود التفعيل الخاص بجهازك<br>للحصول على الكود تواصل مع مطوّر النظام</p>
        </div>

        <!-- Body -->
        <div class="license-body">

            <!-- Error / Success Alerts -->
            <?php if ($licenseError): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($licenseError) ?></div>
            <?php endif; ?>
            <?php if ($licenseSuccess): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($licenseSuccess) ?></div>
            <?php endif; ?>

            <!-- Current License Status -->
            <?php if ($status['valid']): ?>
            <div class="status-banner valid">
                <span class="status-dot green"></span>
                <span>البرنامج مفعّل ✅ — نوع الترخيص: <strong><?= $status['expiry'] === 'LIFETIME' ? 'دائم ♾️' : 'محدد حتى ' . htmlspecialchars($status['expiry']) ?></strong></span>
            </div>
            <a href="?page=dashboard" class="btn-activate" style="display:block; text-align:center; text-decoration:none; margin-bottom:18px; background:linear-gradient(135deg, #10b981, #059669);">
                🚀 الدخول إلى لوحة التحكم
            </a>
            <details style="margin-bottom: 20px; font-size: 13px; color: #64748b;">
                <summary style="cursor: pointer; font-weight: 700; color: #0ea5a0; margin-bottom: 10px;">🔄 هل ترغب في تجديد أو تغيير كود التفعيل؟ اضغط هنا</summary>
            <?php else: ?>
            <div class="status-banner invalid">
                <span class="status-dot red"></span>
                <span><?= htmlspecialchars($status['message'] ?? 'البرنامج غير مفعّل على هذا الجهاز') ?></span>
            </div>
            <?php endif; ?>

            <!-- Hardware ID Box -->
            <div class="hid-box">
                <div class="hid-label">
                    🖥️ كود الجهاز الخاص بك (Hardware ID)
                </div>
                <div class="hid-value" id="hidValue"><?= htmlspecialchars($hardwareId) ?></div>
                <div class="hid-tip">أرسل هذا الكود للمطوّر ليعطيك كود التفعيل المخصص لهذا الجهاز فقط</div>
                <div style="display: flex; gap: 8px; justify-content: center; margin-top: 12px; flex-wrap: wrap;">
                    <button type="button" class="btn-copy" onclick="copyHid()">📋 نسخ الكود</button>
                    <a href="https://wa.me/?text=<?= urlencode('مرحباً، كود تفعيل جهازي لبرنامج تاجر هو: ' . $hardwareId) ?>" target="_blank" class="btn-copy" style="background:#25D366; text-decoration:none; display:inline-flex; align-items:center; gap:5px;">
                        💬 إرسال عبر واتساب
                    </a>
                </div>
            </div>

            <!-- Activation Form -->
            <form method="POST" action="?page=license&action=activate">
                <div class="form-group">
                    <label for="activation_code">🔑 كود التفعيل (Activation Code)</label>
                    <input type="text" 
                           id="activation_code" 
                           name="activation_code" 
                           class="form-control" 
                           placeholder="مثال: LIFE-677BCC9C... أو 365D-E754..."
                           autocomplete="off"
                           value="<?= htmlspecialchars($_POST['activation_code'] ?? '') ?>"
                           required>
                </div>
                <button type="submit" class="btn-activate">🚀 تفعيل البرنامج</button>
            </form>
            <?php if ($status['valid']): ?>
            </details>
            <?php endif; ?>

            <!-- How it works -->
            <div class="steps-box">
                <div class="steps-title">📋 طريقة التفعيل</div>
                <div class="step-item">
                    <span class="step-num">1</span>
                    <span>انسخ كود الجهاز أعلاه (Hardware ID) وأرسله للمطوّر</span>
                </div>
                <div class="step-item">
                    <span class="step-num">2</span>
                    <span>سيرسل لك المطوّر كود التفعيل الخاص بجهازك (64 حرف)</span>
                </div>
                <div class="step-item">
                    <span class="step-num">3</span>
                    <span>الصق كود التفعيل في الحقل أعلاه واضغط "تفعيل البرنامج"</span>
                </div>
                <div class="step-item">
                    <span class="step-num">4</span>
                    <span>الترخيص مرتبط بهذا الجهاز فقط ولا يعمل على أي جهاز آخر 🔒</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="license-footer">
            تاجر — نظام نقاط البيع والمخزون &copy; <?= date('Y') ?>
        </div>

    </div>
</div>

<script>
function copyHid() {
    var hid = document.getElementById('hidValue').innerText;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(hid).then(function() {
            var btn = document.querySelector('.btn-copy');
            btn.textContent = '✅ تم النسخ!';
            btn.style.background = '#10b981';
            setTimeout(function() {
                btn.textContent = '📋 نسخ الكود';
                btn.style.background = '#0ea5a0';
            }, 2000);
        });
    } else {
        // Fallback
        var ta = document.createElement('textarea');
        ta.value = hid;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        alert('تم النسخ: ' + hid);
    }
}
</script>

</body>
</html>
