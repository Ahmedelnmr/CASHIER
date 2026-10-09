<?php
class KeygenCLI {
    private const SECRET_SALT = 'TAJER-SECRET-2026-XK9#mN@qw!';

    public static function run(string $hid, $type) {
        $hid = strtoupper(trim($hid));
        if (empty($hid)) {
            echo "خطأ: كود الجهاز فارغ!\n";
            return;
        }

        if ($type === 'lifetime' || $type === '0') {
            $prefix = 'LIFE';
            $payload = $hid . '|LIFETIME|' . self::SECRET_SALT;
            $typeLabel = 'دائم مدى الحياة';
        } elseif (is_numeric($type)) {
            $days = intval($type);
            $prefix = sprintf('%03dD', $days);
            $payload = $hid . '|' . $days . 'D|' . self::SECRET_SALT;
            $typeLabel = "{$days} يوم";
        } else {
            $prefix = 'LIFE';
            $payload = $hid . '|LIFETIME|' . self::SECRET_SALT;
            $typeLabel = 'دائم مدى الحياة';
        }

        $hash = strtoupper(hash('sha256', $payload));
        $key = "{$prefix}-" . substr($hash, 0, 32);

        echo "========================================\n";
        echo "✅ تم توليد كود التفعيل بنجاح!\n";
        echo "🖥️ كود الجهاز: {$hid}\n";
        echo "📌 النوع: {$typeLabel}\n";
        echo "🔑 كود التفعيل:\n{$key}\n";
        echo "========================================\n";

        // نسخ للحافظة في ويندوز
        @shell_exec('echo ' . trim($key) . '| clip');
        echo "(تم نسخ كود التفعيل تلقائياً إلى الحافظة Clipboard!)\n";
    }
}
