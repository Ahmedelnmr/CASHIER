<?php
/**
 * Settings Service - System Configuration
 */

class SettingsService {
    private $db;
    private $cache = [];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get a setting value by key
     */
    public function get($key, $default = '') {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        $value = $row ? $row['setting_value'] : $default;
        $this->cache[$key] = $value;
        return $value;
    }

    /**
     * Get all settings as key-value array
     */
    public function getAll() {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Set a setting value
     */
    public function set($key, $value) {
        $stmt = $this->db->prepare(
            "INSERT OR REPLACE INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, datetime('now', 'localtime'))"
        );
        $stmt->execute([$key, $value]);
        $this->cache[$key] = $value;
        return true;
    }

    /**
     * Set multiple settings at once
     */
    public function setMultiple($settings) {
        $stmt = $this->db->prepare(
            "INSERT OR REPLACE INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, datetime('now', 'localtime'))"
        );
        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value]);
            $this->cache[$key] = $value;
        }
        return true;
    }

    /**
     * Get store info for receipts
     */
    public function getStoreInfo() {
        return [
            'name' => $this->get('store_name', 'تاجر'),
            'phone' => $this->get('store_phone', ''),
            'address' => $this->get('store_address', ''),
            'receipt_header' => $this->get('receipt_header', ''),
            'receipt_footer' => $this->get('receipt_footer', ''),
            'currency_symbol' => $this->get('currency_symbol', 'ج.م'),
            'currency_name' => $this->get('currency_name', 'جنيه مصري'),
        ];
    }
}
