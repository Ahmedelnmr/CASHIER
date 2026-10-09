<?php
/**
 * LicenseService - Hardware ID Locking
 * 
 * يقرأ معرّفات الأجهزة (CPU, Motherboard, Disk) من الويندوز
 * ويولّد Hardware ID فريد ثم يتحقق من ملف الترخيص license.dat
 *
 * الخوارزمية:
 * 1. اقرأ معرّفات الأجهزة عبر wmic
 * 2. ولّد Hardware ID (HID) = أول 16 حرف من SHA256 للمعرّفات مجتمعةً
 * 3. عند التفعيل: احسب activation_code = SHA256(HID + SECRET_SALT)
 * 4. خزّن ملف license.dat مشفر بـ base64 يحتوي HID + activation_code + expiry
 * 5. عند كل تشغيل: تحقق من license.dat أن HID يطابق الجهاز وأن activation_code صحيح
 */

class LicenseService {

    // ---- القيمة السرية للمطوّر — مشفرة داخل النظام ----
    private const SECRET_SALT = 'TAJER-SECRET-2026-XK9#mN@qw!';

    // ============================================
    // 1. قراءة Hardware Identifiers من الجهاز
    // ============================================

    /**
     * مسار ملف الترخيص - يُحفظ خارج www إن أمكن أو داخل مجلد التطبيق
     */
    public static function getLicensePath(): string {
        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        // مجلد البرنامج الرئيسي (خارج www ليبقى محفوظاً حتى لو استُبدل كود www)
        $outside = dirname($base) . DIRECTORY_SEPARATOR . 'license.dat';
        if (is_writable(dirname($outside))) {
            return $outside;
        }
        return $base . DIRECTORY_SEPARATOR . 'license.dat';
    }

    /**
     * اقرأ معرّفات الجهاز وكوّن Hardware ID فريد وثابت
     * يعمل على Windows
     */
    public static function getHardwareId(): string {
        // Cache in session — hardware never changes mid-session, avoid 4 shell_exec calls per request
        if (!empty($_SESSION['_hid_cache'])) {
            return $_SESSION['_hid_cache'];
        }

        $parts = [];

        // 1. CPU ID
        $cpu = self::queryCpuId();
        if ($cpu) $parts[] = 'CPU:' . $cpu;

        // 2. Motherboard Serial
        $mb = self::queryMotherboardSerial();
        if ($mb) $parts[] = 'MB:' . $mb;

        // 3. Disk Serial
        $disk = self::queryDiskSerial();
        if ($disk) $parts[] = 'DISK:' . $disk;

        // 4. Windows MachineGuid (ثابت لكل تثبيت ويندوز وبديل فوري قوي)
        $guid = self::queryMachineGuid();
        if ($guid) $parts[] = 'GUID:' . $guid;

        // 5. Hostname Fallback
        if (empty($parts)) {
            $parts[] = 'HOST:' . php_uname('n');
        }

        $raw = implode('|', $parts);
        $hash = hash('sha256', $raw . self::SECRET_SALT);

        // تنسيق Hardware ID كـ 16 حرف مقروء بـ 4 مجموعات: XXXX-XXXX-XXXX-XXXX
        $hid = strtoupper(
            substr($hash, 0, 4) . '-' .
            substr($hash, 4, 4) . '-' .
            substr($hash, 8, 4) . '-' .
            substr($hash, 12, 4)
        );

        // Save to session cache
        $_SESSION['_hid_cache'] = $hid;

        return $hid;
    }

    /**
     * قراءة معرّف المعالج
     */
    private static function queryCpuId(): string {
        $out = self::runWmic('cpu get ProcessorId /value');
        if (!$out) {
            $out = @shell_exec('powershell -NoProfile -Command "(Get-CimInstance Win32_Processor).ProcessorId" 2>nul');
        }
        return self::cleanValue($out);
    }

    /**
     * قراءة سيريال الماذربورد
     */
    private static function queryMotherboardSerial(): string {
        $out = self::runWmic('baseboard get SerialNumber /value');
        if (!$out) {
            $out = @shell_exec('powershell -NoProfile -Command "(Get-CimInstance Win32_BaseBoard).SerialNumber" 2>nul');
        }
        return self::cleanValue($out);
    }

    /**
     * قراءة سيريال الهارد ديسك
     */
    private static function queryDiskSerial(): string {
        $out = self::runWmic('diskdrive get SerialNumber /value');
        if (!$out) {
            $out = @shell_exec('powershell -NoProfile -Command "(Get-CimInstance Win32_DiskDrive | Select-Object -First 1).SerialNumber" 2>nul');
        }
        return self::cleanValue($out);
    }

    /**
     * قراءة معرّف الويندوز MachineGuid من Registry (يدعم 32-bit و 64-bit)
     */
    private static function queryMachineGuid(): string {
        // محاولة القراءة مع /reg:64 لدعم برامج 32-bit على ويندوز 64-bit
        $out = @shell_exec('reg query "HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Cryptography" /v MachineGuid /reg:64 2>nul');
        if (!$out || strpos($out, 'MachineGuid') === false) {
            $out = @shell_exec('reg query "HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Cryptography" /v MachineGuid 2>nul');
        }
        if ($out && preg_match('/MachineGuid\s+REG_SZ\s+([a-zA-Z0-9\-]+)/i', $out, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    /**
     * تشغيل أمر wmic
     */
    private static function runWmic(string $query): string {
        $output = @shell_exec('wmic ' . $query . ' 2>nul');
        return $output ? trim($output) : '';
    }

    /**
     * تنظيف القيم وإزالة الفراغات والرموز الافتراضية
     */
    private static function cleanValue(?string $raw): string {
        if (!$raw) return '';
        $lines = explode("\n", $raw);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;
            if (strpos($line, '=') !== false) {
                $line = trim(explode('=', $line, 2)[1]);
            }
            // استبعاد النصوص الافتراضية
            $invalidValues = ['None', 'To Be Filled By O.E.M.', 'Default string', 'Not Applicable'];
            if (in_array($line, $invalidValues, true) || strlen($line) < 3) {
                continue;
            }
            return preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $line);
        }
        return '';
    }

    // ============================================
    // 2. توليد كود التفعيل (للمطوّر)
    // ============================================

    /**
     * توليد كود تفعيل مرتبط بـ Hardware ID
     * 
     * $type:
     * - 'lifetime' (أو 0) -> كود دائم بدون انتهاء
     * - '5m', '10m' (أو بادئة دقائق) -> كود بالدقائق للاختبار الفوري
     * - 'YYYY-MM-DD HH:MM' -> تاريخ ووقت محدد بالدقيقة
     * - 'YYYY-MM-DD' -> تاريخ محدد (ينتهي نهاية اليوم 23:59:59)
     * - عدد صحيح > 0 (مثلاً 365, 30) -> كود صالح لعدد محدد من الأيام
     */
    public static function generateActivationCode(string $hardwareId, $type = 'lifetime'): string {
        $hardwareId = strtoupper(trim($hardwareId));

        // 1. ترخيص دائم
        if ($type === 'lifetime' || $type === 0 || $type === '0') {
            $prefix = 'LIFE';
            $payload = $hardwareId . '|LIFETIME|' . self::SECRET_SALT;
            $hash = strtoupper(hash('sha256', $payload));
            return "LIFE-" . substr($hash, 0, 32);
        }

        // 2. مدة بالدقائق (للاختبار الفوري السريع: 5m, 10m...)
        if (preg_match('/^(\d+)m$/i', strval($type), $m)) {
            $mins = intval($m[1]);
            $prefix = sprintf('%03dM', $mins);
            $payload = $hardwareId . '|' . $mins . 'M|' . self::SECRET_SALT;
            $hash = strtoupper(hash('sha256', $payload));
            return "{$prefix}-" . substr($hash, 0, 32);
        }

        // 3. وقت وتاريخ محدد بالدقيقة: YYYY-MM-DD HH:MM أو YYYY-MM-DDTHH:MM
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})[T\s](\d{2}):(\d{2})(:(\d{2}))?$/', strval($type), $m)) {
            $dtClean = $m[1] . $m[2] . $m[3] . $m[4] . $m[5];
            $payload = $hardwareId . '|' . $dtClean . '|' . self::SECRET_SALT;
            $hash = strtoupper(hash('sha256', $payload));
            return "{$dtClean}-" . substr($hash, 0, 32);
        }

        // 4. تاريخ فقط (يوم): YYYY-MM-DD
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', strval($type), $m)) {
            $dateClean = $m[1] . $m[2] . $m[3];
            $payload = $hardwareId . '|' . $dateClean . '|' . self::SECRET_SALT;
            $hash = strtoupper(hash('sha256', $payload));
            return "{$dateClean}-" . substr($hash, 0, 32);
        }

        // 5. عدد أيام: رقم صحيح
        if (is_numeric($type) && intval($type) > 0) {
            $days = intval($type);
            $prefix = sprintf('%03dD', $days);
            $payload = $hardwareId . '|' . $days . 'D|' . self::SECRET_SALT;
            $hash = strtoupper(hash('sha256', $payload));
            return "{$prefix}-" . substr($hash, 0, 32);
        }

        // افتراضي دائم
        $payload = $hardwareId . '|LIFETIME|' . self::SECRET_SALT;
        return "LIFE-" . substr(strtoupper(hash('sha256', $payload)), 0, 32);
    }

    // ============================================
    // 3. تفعيل الترخيص عند العميل
    // ============================================

    /**
     * تفعيل الترخيص باستخدام كود التفعيل
     */
    public static function activate(string $inputCode): array {
        $inputCode = strtoupper(trim($inputCode));
        if (empty($inputCode)) {
            return ['success' => false, 'message' => 'يرجى إدخال كود التفعيل'];
        }

        $hardwareId = self::getHardwareId();
        $expiry = null;
        $isValid = false;

        // حالة 1: كود دائم LIFE-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
        if (strpos($inputCode, 'LIFE-') === 0) {
            $hashPart = substr($inputCode, 5);
            $expected = substr(strtoupper(hash('sha256', $hardwareId . '|LIFETIME|' . self::SECRET_SALT)), 0, strlen($hashPart));
            if ($hashPart === $expected && strlen($hashPart) >= 16) {
                $isValid = true;
                $expiry = 'LIFETIME';
            }
        }
        // حالة 2: كود دقائق للاختبار مثلاً 005M-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX أو 010M-
        elseif (preg_match('/^(\d+)M-(.+)$/', $inputCode, $matches)) {
            $mins = intval($matches[1]);
            $hashPart = $matches[2];
            $expected = substr(strtoupper(hash('sha256', $hardwareId . '|' . $mins . 'M|' . self::SECRET_SALT)), 0, strlen($hashPart));
            if ($hashPart === $expected && strlen($hashPart) >= 16) {
                $isValid = true;
                $expiry = date('Y-m-d H:i:s', strtotime("+{$mins} minutes"));
            }
        }
        // حالة 3: كود تاريخ ووقت محدد بالدقيقة YYYYMMDDHHII- (12 رقم)
        elseif (preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})-(.+)$/', $inputCode, $matches)) {
            $dtClean = $matches[1] . $matches[2] . $matches[3] . $matches[4] . $matches[5];
            $hashPart = $matches[6];
            $expected = substr(strtoupper(hash('sha256', $hardwareId . '|' . $dtClean . '|' . self::SECRET_SALT)), 0, strlen($hashPart));
            if ($hashPart === $expected && strlen($hashPart) >= 16) {
                $isValid = true;
                $expiry = "{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}:{$matches[5]}:00";
            }
        }
        // حالة 4: كود أيام مثلاً 365D-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX أو 030D-
        elseif (preg_match('/^(\d+)D-(.+)$/', $inputCode, $matches)) {
            $days = intval($matches[1]);
            $hashPart = $matches[2];
            $expected = substr(strtoupper(hash('sha256', $hardwareId . '|' . $days . 'D|' . self::SECRET_SALT)), 0, strlen($hashPart));
            if ($hashPart === $expected && strlen($hashPart) >= 16) {
                $isValid = true;
                $expiry = date('Y-m-d', strtotime("+{$days} days"));
            }
        }
        // حالة 5: كود تاريخ محدد باليوم مثلاً 20271231-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX (8 أرقام)
        elseif (preg_match('/^(\d{4})(\d{2})(\d{2})-(.+)$/', $inputCode, $matches)) {
            $dateClean = $matches[1] . $matches[2] . $matches[3];
            $hashPart = $matches[4];
            $expected = substr(strtoupper(hash('sha256', $hardwareId . '|' . $dateClean . '|' . self::SECRET_SALT)), 0, strlen($hashPart));
            if ($hashPart === $expected && strlen($hashPart) >= 16) {
                $isValid = true;
                $expiry = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
            }
        }
        // حالة 6: كود Hash كامل 64 حرف (دائم - توافقية سابقة)
        elseif (strlen($inputCode) === 64) {
            $expectedFull = strtoupper(hash('sha256', $hardwareId . '|LIFETIME|' . self::SECRET_SALT));
            if ($inputCode === $expectedFull) {
                $isValid = true;
                $expiry = 'LIFETIME';
            }
        }

        if (!$isValid || !$expiry) {
            return [
                'success' => false,
                'message' => 'كود التفعيل غير صحيح أو لا يتطابق مع معرّف هذا الجهاز (' . $hardwareId . ')'
            ];
        }

        // تحقق أن التاريخ/الوقت غير منتهي مسبقاً
        if ($expiry !== 'LIFETIME') {
            $expiryTimestamp = (strlen($expiry) === 10) ? strtotime($expiry . ' 23:59:59') : strtotime($expiry);
            if ($expiryTimestamp < time()) {
                return [
                    'success' => false,
                    'message' => 'كود التفعيل منتهي الصلاحية في: ' . $expiry
                ];
            }
        }

        // تكوين بيانات ملف الترخيص
        $licenseData = [
            'hid'        => $hardwareId,
            'code'       => $inputCode,
            'expiry'     => $expiry,
            'activated'  => date('Y-m-d H:i:s'),
            'checksum'   => hash('sha256', $hardwareId . $expiry . self::SECRET_SALT)
        ];

        $encoded = base64_encode(json_encode($licenseData, JSON_UNESCAPED_UNICODE));
        $licenseFile = self::getLicensePath();

        if (@file_put_contents($licenseFile, $encoded) === false) {
            return [
                'success' => false,
                'message' => 'فشل كتابة ملف الترخيص. تأكد من صلاحيات المجلد: ' . dirname($licenseFile)
            ];
        }

        $expiryLabel = ($expiry === 'LIFETIME') ? 'ترخيص دائم ♾️' : 'صالح حتى ' . $expiry;
        return [
            'success' => true,
            'message' => 'تم تفعيل البرنامج بنجاح! (' . $expiryLabel . ')',
            'expiry'  => $expiry
        ];
    }

    // ============================================
    // 4. فحص حالة الترخيص عند كل طلب
    // ============================================

    /**
     * هل البرنامج مفعّل وصالح على هذا الجهاز؟
     */
    public static function isLicensed(): bool {
        $status = self::getLicenseStatus();
        return $status['valid'] === true;
    }

    /**
     * جلب حالة الترخيص بالتفصيل
     */
    public static function getLicenseStatus(): array {
        $licenseFile = self::getLicensePath();

        if (!file_exists($licenseFile)) {
            return [
                'valid'   => false,
                'reason'  => 'not_activated',
                'message' => 'البرنامج غير مفعّل على هذا الجهاز. يرجى إدخال كود التفعيل.'
            ];
        }

        $content = @file_get_contents($licenseFile);
        if (!$content) {
            return [
                'valid'   => false,
                'reason'  => 'empty_file',
                'message' => 'ملف الترخيص فارغ أو غير مقروء.'
            ];
        }

        $decoded = @json_decode(base64_decode($content), true);
        if (!$decoded || !isset($decoded['hid'], $decoded['expiry'], $decoded['checksum'])) {
            return [
                'valid'   => false,
                'reason'  => 'corrupted',
                'message' => 'ملف الترخيص تالف أو غير صالح.'
            ];
        }

        $currentHid = self::getHardwareId();

        // 1. فحص تطابق معرّف الجهاز (Hardware ID Locking)
        if ($decoded['hid'] !== $currentHid) {
            return [
                'valid'        => false,
                'reason'       => 'hardware_mismatch',
                'message'      => 'هذا الترخيص مخصص لجهاز آخر ولا يمكن تشغيله على هذا الجهاز.',
                'licensed_hid' => $decoded['hid'],
                'current_hid'  => $currentHid
            ];
        }

        // 2. فحص البصمة الرقمية للتحقق من عدم التلاعب بملف الترخيص
        $expectedChecksum = hash('sha256', $decoded['hid'] . $decoded['expiry'] . self::SECRET_SALT);
        if ($decoded['checksum'] !== $expectedChecksum) {
            return [
                'valid'   => false,
                'reason'  => 'tampered',
                'message' => 'تم رصد تلاعب في ملف الترخيص!'
            ];
        }

        // 3. فحص تاريخ انتهاء الصلاحية
        if ($decoded['expiry'] !== 'LIFETIME') {
            $expiryStr = $decoded['expiry'];
            // إذا كان تاريخ فقط (10 حروف YYYY-MM-DD)، ينتهي بنهاية ذلك اليوم 23:59:59
            if (strlen($expiryStr) === 10) {
                $expiryStr .= ' 23:59:59';
            }
            $expiryTimestamp = strtotime($expiryStr);
            if ($expiryTimestamp !== false && $expiryTimestamp < time()) {
                return [
                    'valid'   => false,
                    'reason'  => 'expired',
                    'expiry'  => $decoded['expiry'],
                    'message' => 'انتهت صلاحية هذا الترخيص في: ' . $decoded['expiry']
                ];
            }
        }

        return [
            'valid'        => true,
            'hid'          => $decoded['hid'],
            'expiry'       => $decoded['expiry'],
            'activated'    => $decoded['activated'] ?? 'غير معروف',
            'expiry_label' => ($decoded['expiry'] === 'LIFETIME') ? 'ترخيص دائم ♾️' : 'صالح حتى ' . $decoded['expiry'],
            'message'      => 'الترخيص صالح ومفعّل'
        ];
    }

    /**
     * جلب تفاصيل الترخيص للعرض في الواجهات
     */
    public static function getLicenseInfo(): array {
        $status = self::getLicenseStatus();
        $status['hardware_id'] = self::getHardwareId();
        return $status;
    }
}
