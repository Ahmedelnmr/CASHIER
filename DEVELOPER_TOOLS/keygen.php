<?php
/**
 * ============================================================
 * أداة توليد تراخيص برنامج تاجر (خاصة بالمطوّر فقط)
 * ⚠️ لا ترسل هذا المجلد أو هذا الملف لأي عميل نهائياً!
 * ============================================================
 */

// القيمة السرية المطابقة تماماً لـ LicenseService
$SECRET_SALT = 'TAJER-SECRET-2026-XK9#mN@qw!';

// تحميل ملف license.dat مباشرة من المتصفح
if (isset($_GET['download']) && !empty($_GET['hid']) && !empty($_GET['code'])) {
    $dHid = strtoupper(trim($_GET['hid']));
    $dCode = strtoupper(trim($_GET['code']));
    $dExpiry = trim($_GET['expiry'] ?? 'LIFETIME');
    $licenseData = [
        'hid'        => $dHid,
        'code'       => $dCode,
        'expiry'     => $dExpiry,
        'activated'  => date('Y-m-d H:i:s'),
        'checksum'   => hash('sha256', $dHid . $dExpiry . $SECRET_SALT)
    ];
    $encoded = base64_encode(json_encode($licenseData, JSON_UNESCAPED_UNICODE));
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="license.dat"');
    header('Content-Length: ' . strlen($encoded));
    echo $encoded;
    exit;
}

// حفظ ملف license.dat في مجلد البرنامج مباشرة (لتفعيل جهازك فوراً)
if (isset($_POST['save_direct']) && !empty($_POST['hid']) && !empty($_POST['code'])) {
    $dHid = strtoupper(trim($_POST['hid']));
    $dCode = strtoupper(trim($_POST['code']));
    $dExpiry = trim($_POST['expiry'] ?? 'LIFETIME');
    $licenseData = [
        'hid'        => $dHid,
        'code'       => $dCode,
        'expiry'     => $dExpiry,
        'activated'  => date('Y-m-d H:i:s'),
        'checksum'   => hash('sha256', $dHid . $dExpiry . $SECRET_SALT)
    ];
    $encoded = base64_encode(json_encode($licenseData, JSON_UNESCAPED_UNICODE));
    $rootTarget = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'license.dat';
    $saved = @file_put_contents($rootTarget, $encoded);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => ($saved !== false), 'path' => $rootTarget]);
    exit;
}

function generateCode($hardwareId, $type = 'lifetime', $secret = '') {
    $hardwareId = strtoupper(trim($hardwareId));
    if (empty($hardwareId)) return '';

    // 1. دائم
    if ($type === 'lifetime' || $type === '0') {
        $payload = $hardwareId . '|LIFETIME|' . $secret;
        $hash = strtoupper(hash('sha256', $payload));
        return "LIFE-" . substr($hash, 0, 32);
    }

    // 2. مدة بالدقائق (للاختبار الفوري: 5m, 10m...)
    if (preg_match('/^(\d+)m$/i', strval($type), $m)) {
        $mins = intval($m[1]);
        $prefix = sprintf('%03dM', $mins);
        $payload = $hardwareId . '|' . $mins . 'M|' . $secret;
        $hash = strtoupper(hash('sha256', $payload));
        return "{$prefix}-" . substr($hash, 0, 32);
    }

    // 3. وقت وتاريخ محدد بالدقيقة: YYYY-MM-DD HH:MM
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})[T\s](\d{2}):(\d{2})(:(\d{2}))?$/', strval($type), $m)) {
        $dtClean = $m[1] . $m[2] . $m[3] . $m[4] . $m[5];
        $payload = $hardwareId . '|' . $dtClean . '|' . $secret;
        $hash = strtoupper(hash('sha256', $payload));
        return "{$dtClean}-" . substr($hash, 0, 32);
    }

    // 4. تاريخ فقط (يوم): YYYY-MM-DD
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', strval($type), $m)) {
        $dateClean = $m[1] . $m[2] . $m[3];
        $payload = $hardwareId . '|' . $dateClean . '|' . $secret;
        $hash = strtoupper(hash('sha256', $payload));
        return "{$dateClean}-" . substr($hash, 0, 32);
    }

    // 5. عدد أيام: رقم صحيح
    if (is_numeric($type) && intval($type) > 0) {
        $days = intval($type);
        $prefix = sprintf('%03dD', $days);
        $payload = $hardwareId . '|' . $days . 'D|' . $secret;
        $hash = strtoupper(hash('sha256', $payload));
        return "{$prefix}-" . substr($hash, 0, 32);
    }

    $payload = $hardwareId . '|LIFETIME|' . $secret;
    return "LIFE-" . substr(strtoupper(hash('sha256', $payload)), 0, 32);
}

$generatedKey = '';
$clientName = '';
$hidInput = '';
$licType = 'lifetime';
$customMinutes = 5;
$customDatetime = date('Y-m-d\TH:i', strtotime('+10 minutes'));
$customDays = '365';
$customDate = date('Y-m-d', strtotime('+1 year'));
$historyFile = __DIR__ . '/licenses_log.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hidInput = strtoupper(trim($_POST['hardware_id'] ?? ''));
    $clientName = trim($_POST['client_name'] ?? '');
    $licType = $_POST['lic_type'] ?? 'lifetime';
    $customMinutes = intval($_POST['custom_minutes'] ?? 5);
    $customDatetime = trim($_POST['custom_datetime'] ?? date('Y-m-d\TH:i', strtotime('+10 minutes')));
    $customDays = intval($_POST['custom_days'] ?? 365);
    $customDate = trim($_POST['custom_date'] ?? date('Y-m-d', strtotime('+1 year')));

    if ($hidInput) {
        $expiryVal = 'LIFETIME';
        if ($licType === 'lifetime') {
            $generatedKey = generateCode($hidInput, 'lifetime', $SECRET_SALT);
            $typeLabel = 'دائم مدى الحياة ♾️';
            $expiryVal = 'LIFETIME';
        } elseif ($licType === 'minutes') {
            $generatedKey = generateCode($hidInput, "{$customMinutes}m", $SECRET_SALT);
            $typeLabel = "مدة تجريبية {$customMinutes} دقيقة من وقت التفعيل ⏱️";
            $expiryVal = date('Y-m-d H:i:00', strtotime("+{$customMinutes} minutes"));
        } elseif ($licType === 'datetime') {
            $dtFormatted = str_replace('T', ' ', $customDatetime);
            $generatedKey = generateCode($hidInput, $dtFormatted, $SECRET_SALT);
            $typeLabel = "ينتهي في دقيقة محددة: {$dtFormatted} 🕒";
            $expiryVal = $dtFormatted . ':00';
        } elseif ($licType === 'days') {
            $generatedKey = generateCode($hidInput, $customDays, $SECRET_SALT);
            $typeLabel = "صالح لمدة {$customDays} يوم";
            $expiryVal = date('Y-m-d', strtotime("+{$customDays} days"));
        } elseif ($licType === 'fixed_date') {
            $generatedKey = generateCode($hidInput, $customDate, $SECRET_SALT);
            $typeLabel = "صالح حتى {$customDate} (نهاية اليوم)";
            $expiryVal = $customDate;
        }

        // حفظ في سجل التراخيص الصادرة
        $history = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];
        if (!is_array($history)) $history = [];
        array_unshift($history, [
            'date'        => date('Y-m-d H:i'),
            'client_name' => $clientName ?: 'بدون اسم',
            'hardware_id' => $hidInput,
            'key'         => $generatedKey,
            'type'        => $typeLabel
        ]);
        // الاحتفاظ بآخر 100 ترخيص
        $history = array_slice($history, 0, 100);
        file_put_contents($historyFile, json_encode($history, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}

$history = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];
if (!is_array($history)) $history = [];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مولّد تراخيص برنامج تاجر — خاص بالمطوّر</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', sans-serif;
            background: #0b1329;
            color: #e2e8f0;
            min-height: 100vh;
            padding: 30px 15px;
            direction: rtl;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .header h1 {
            font-size: 26px;
            color: #38bdf8;
            margin-bottom: 6px;
        }
        .header p {
            color: #94a3b8;
            font-size: 14px;
        }
        .badge-dev {
            display: inline-block;
            background: #ef4444;
            color: #fff;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media(max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
        }
        .card {
            background: #1e293b;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #334155;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .card h2 {
            font-size: 18px;
            margin-bottom: 18px;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
            color: #cbd5e1;
        }
        .form-control {
            width: 100%;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid #475569;
            background: #0f172a;
            color: #f8fafc;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #38bdf8;
        }
        .mono {
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
            font-weight: bold;
        }
        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 16px;
        }
        .radio-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: #0f172a;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid #334155;
            transition: all 0.2s;
        }
        .radio-label:hover {
            border-color: #38bdf8;
        }
        .radio-label input {
            accent-color: #38bdf8;
        }
        .btn-gen {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.4);
        }
        .btn-gen:hover {
            background: linear-gradient(135deg, #0369a1, #075985);
            transform: translateY(-1px);
        }
        .result-box {
            background: #0f172a;
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 18px;
            margin-top: 20px;
        }
        .result-title {
            color: #10b981;
            font-weight: 800;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .result-key {
            background: #1e293b;
            padding: 12px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            color: #38bdf8;
            font-weight: 900;
            font-size: 15px;
            word-break: break-all;
            user-select: all;
            margin-bottom: 12px;
            border: 1px dashed #38bdf8;
        }
        .btn-copy-box {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-sm {
            padding: 8px 14px;
            border-radius: 6px;
            border: none;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }
        .btn-copy { background: #38bdf8; color: #0f172a; }
        .btn-wa { background: #25D366; color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .table-wrap {
            overflow-x: auto;
            max-height: 480px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }
        th, td {
            padding: 10px 12px;
            text-align: right;
            border-bottom: 1px solid #334155;
        }
        th {
            background: #0f172a;
            color: #94a3b8;
            font-weight: 700;
            position: sticky;
            top: 0;
        }
        tr:hover td {
            background: rgba(255,255,255,0.02);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <span class="badge-dev">⚠️ سري للغاية للمطوّر فقط</span>
        <h1>🔑 مولّد تراخيص برنامج تاجر (Hardware ID Locking)</h1>
        <p>قم بإدخال كود الجهاز المستلم من العميل لاشتقاق كود التفعيل الخاص بجهازه</p>
    </div>

    <div class="grid">
        <!-- Form -->
        <div class="card">
            <h2>⚙️ إنشاء كود جديد</h2>
            <form method="POST">
                <div class="form-group">
                    <label>اسم العميل / المحل (اختياري للأرشفة):</label>
                    <input type="text" name="client_name" class="form-control" placeholder="مثال: سوبر ماركت الأمل" value="<?= htmlspecialchars($clientName) ?>">
                </div>

                <div class="form-group">
                    <label>كود الجهاز (Hardware ID):</label>
                    <input type="text" name="hardware_id" class="form-control mono" placeholder="XXXX-XXXX-XXXX-XXXX" value="<?= htmlspecialchars($hidInput) ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label>نوع الترخيص:</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="lic_type" value="lifetime" <?= $licType === 'lifetime' ? 'checked' : '' ?> onchange="toggleType()">
                            <span>♾️ دائم مدى الحياة (Lifetime)</span>
                        </label>
                        
                        <!-- Minutes Option -->
                        <label class="radio-label">
                            <input type="radio" name="lic_type" value="minutes" <?= $licType === 'minutes' ? 'checked' : '' ?> onchange="toggleType()">
                            <span>⏱️ تجربة سريعة بعدد الدقائق (للاختبار الفوري)</span>
                        </label>
                        <div id="minutesBox" style="display: <?= $licType === 'minutes' ? 'block' : 'none' ?>; padding-right: 25px;">
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="number" id="customMinutesInput" name="custom_minutes" class="form-control" style="width: 140px;" value="<?= htmlspecialchars($customMinutes) ?>" min="1" max="1440" placeholder="الدقائق">
                                <span style="font-size: 13px; color: #94a3b8;">دقيقة من لحظة التفعيل</span>
                            </div>
                            <div style="display: flex; gap: 6px; margin-top: 6px;">
                                <button type="button" class="btn-sm btn-copy" style="background:#334155; color:#38bdf8;" onclick="setMinutes(2)">2 دقيقة</button>
                                <button type="button" class="btn-sm btn-copy" style="background:#334155; color:#38bdf8;" onclick="setMinutes(5)">5 دقائق</button>
                                <button type="button" class="btn-sm btn-copy" style="background:#334155; color:#38bdf8;" onclick="setMinutes(15)">15 دقيقة</button>
                                <button type="button" class="btn-sm btn-copy" style="background:#334155; color:#38bdf8;" onclick="setMinutes(60)">ساعة واحدة</button>
                            </div>
                        </div>

                        <!-- Exact DateTime Option -->
                        <label class="radio-label">
                            <input type="radio" name="lic_type" value="datetime" <?= $licType === 'datetime' ? 'checked' : '' ?> onchange="toggleType()">
                            <span>🕒 وقت وتاريخ محدد بالدقيقة (Exact Date & Time)</span>
                        </label>
                        <div id="datetimeBox" style="display: <?= $licType === 'datetime' ? 'block' : 'none' ?>; padding-right: 25px;">
                            <input type="datetime-local" name="custom_datetime" class="form-control" value="<?= htmlspecialchars($customDatetime) ?>" step="60">
                            <span style="font-size: 12px; color: #94a3b8; display: block; margin-top: 4px;">اختر اليوم والساعة والدقيقة بالضبط لينتهي الترخيص عندها</span>
                        </div>

                        <!-- Days Option -->
                        <label class="radio-label">
                            <input type="radio" name="lic_type" value="days" <?= $licType === 'days' ? 'checked' : '' ?> onchange="toggleType()">
                            <span>⏳ محدد بعدد أيام (مثلاً 365 يوم لسنة، أو 30 يوم تجريبي)</span>
                        </label>
                        <div id="daysBox" style="display: <?= $licType === 'days' ? 'block' : 'none' ?>; padding-right: 25px;">
                            <input type="number" name="custom_days" class="form-control" value="<?= htmlspecialchars($customDays) ?>" placeholder="عدد الأيام">
                        </div>

                        <!-- Fixed Date Option -->
                        <label class="radio-label">
                            <input type="radio" name="lic_type" value="fixed_date" <?= $licType === 'fixed_date' ? 'checked' : '' ?> onchange="toggleType()">
                            <span>📅 تاريخ يوم محدد (ينتهي نهاية ذلك اليوم 23:59:59)</span>
                        </label>
                        <div id="dateBox" style="display: <?= $licType === 'fixed_date' ? 'block' : 'none' ?>; padding-right: 25px;">
                            <input type="date" name="custom_date" class="form-control" value="<?= htmlspecialchars($customDate) ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-gen">⚡ توليد كود التفعيل</button>
            </form>

            <?php if ($generatedKey): ?>
            <div class="result-box">
                <div class="result-title">✅ كود التفعيل جاهز:</div>
                <div class="result-key" id="keyText"><?= htmlspecialchars($generatedKey) ?></div>
                
                <?php
                $msg = "مرحباً بك! كود تفعيل برنامج تاجر الخاص بجهازك:\n" .
                       "🖥️ كود الجهاز: {$hidInput}\n" .
                       "🔑 كود التفعيل:\n{$generatedKey}\n" .
                       "📌 نوع الترخيص: {$typeLabel}\n\n" .
                       "قم بلصق الكود في خانة التفعيل بالبرنامج واضغط تفعيل.";
                ?>
                <div class="btn-copy-box" style="flex-wrap: wrap; gap: 8px;">
                    <button type="button" class="btn-sm btn-copy" onclick="copyKey()">📋 نسخ الكود فقط</button>
                    <button type="button" class="btn-sm btn-copy" style="background:#6366f1; color:#fff;" onclick="copyMsg()">📝 نسخ رسالة كاملة للعميل</button>
                    <a href="https://wa.me/?text=<?= urlencode($msg) ?>" target="_blank" class="btn-sm btn-wa">
                        💬 إرسال واتساب
                    </a>
                    <a href="?download=1&hid=<?= urlencode($hidInput) ?>&code=<?= urlencode($generatedKey) ?>&expiry=<?= urlencode($expiryVal) ?>" class="btn-sm btn-copy" style="background:#10b981; color:#fff; text-decoration:none;">
                        💾 تحميل ملف license.dat جاهز
                    </a>
                    <button type="button" class="btn-sm btn-copy" style="background:#0ea5e9; color:#fff;" onclick="saveDirectly('<?= htmlspecialchars($hidInput) ?>', '<?= htmlspecialchars($generatedKey) ?>', '<?= htmlspecialchars($expiryVal) ?>')">
                        📁 حفظ license.dat في البرنامج مباشرة
                    </button>
                </div>
                <textarea id="fullMsg" style="display:none;"><?= htmlspecialchars($msg) ?></textarea>
            </div>
            <?php endif; ?>
        </div>

        <!-- History -->
        <div class="card">
            <h2>📑 سجل التراخيص الصادرة (<?= count($history) ?>)</h2>
            <div class="table-wrap">
                <?php if (empty($history)): ?>
                    <p style="color:#64748b; text-align:center; padding:30px;">لم يتم إنشاء أي تراخيص بعد</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>العميل</th>
                                <th>كود الجهاز</th>
                                <th>النوع</th>
                                <th>الكود</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $item): ?>
                            <tr>
                                <td style="color:#94a3b8; font-size:11px;"><?= htmlspecialchars($item['date']) ?></td>
                                <td><strong><?= htmlspecialchars($item['client_name']) ?></strong></td>
                                <td class="mono" style="font-size:11px;"><?= htmlspecialchars($item['hardware_id']) ?></td>
                                <td><span style="background:#0f172a; padding:2px 6px; border-radius:4px; font-size:11px;"><?= htmlspecialchars($item['type']) ?></span></td>
                                <td>
                                    <button type="button" class="btn-sm btn-copy" style="padding:2px 8px; font-size:11px;" onclick="copyStr('<?= htmlspecialchars($item['key']) ?>')">نسخ</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleType() {
    var radios = document.getElementsByName('lic_type');
    var minutesBox = document.getElementById('minutesBox');
    var datetimeBox = document.getElementById('datetimeBox');
    var daysBox = document.getElementById('daysBox');
    var dateBox = document.getElementById('dateBox');
    
    for (var r of radios) {
        if (r.checked) {
            minutesBox.style.display = (r.value === 'minutes') ? 'block' : 'none';
            datetimeBox.style.display = (r.value === 'datetime') ? 'block' : 'none';
            daysBox.style.display = (r.value === 'days') ? 'block' : 'none';
            dateBox.style.display = (r.value === 'fixed_date') ? 'block' : 'none';
        }
    }
}

function setMinutes(m) {
    document.getElementById('customMinutesInput').value = m;
}

function copyKey() {
    var t = document.getElementById('keyText').innerText;
    navigator.clipboard.writeText(t).then(function() {
        alert('تم نسخ كود التفعيل: ' + t);
    });
}

function copyMsg() {
    var m = document.getElementById('fullMsg').value;
    navigator.clipboard.writeText(m).then(function() {
        alert('تم نسخ الرسالة الكاملة لبيانات التفعيل!');
    });
}

function copyStr(s) {
    navigator.clipboard.writeText(s).then(function() {
        alert('تم النسخ: ' + s);
    });
}

function saveDirectly(hid, code, expiry) {
    if (!confirm('هل تريد تفعيل هذا الجهاز وحفظ ملف license.dat في مجلد البرنامج مباشرة؟')) return;
    var fd = new FormData();
    fd.append('save_direct', '1');
    fd.append('hid', hid);
    fd.append('code', code);
    fd.append('expiry', expiry);
    fetch('keygen.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            alert('✅ تم حفظ ملف license.dat وتفعيل البرنامج بنجاح!\nالمسار: ' + data.path);
        } else {
            alert('❌ فشل حفظ الملف، يرجى التأكد من صلاحيات المجلد.');
        }
    })
    .catch(function(e) {
        alert('حدث خطأ في الاتصال: ' + e);
    });
}
</script>

</body>
</html>
