<?php
/**
 * Invoice Number Generator
 *
 * Generates professional invoice numbers in the format: INV-MMDDYY-SSS
 * - INV: fixed prefix (uppercase)
 * - MM: 2-digit month
 * - DD: 2-digit day
 * - YY: 2-digit year
 * - SSS: 3-digit daily sequence (resets each calendar day)
 *
 * Concurrency safety:
 *   The generate() method must be called within an active database transaction.
 *   SQLite serializes all writes, so the MAX()+1 query inside a transaction
 *   is safe against concurrent generation for the same date.
 */

class InvoiceNumberGenerator {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Generate the next invoice number for the given business date.
     *
     * @param string $businessDate Date in Y-m-d format (e.g. '2026-08-31')
     * @return string Invoice number (e.g. 'INV-083126-001')
     * @throws Exception if generation fails
     */
    public function generate($businessDate = null) {
        if ($businessDate === null) {
            $businessDate = date('Y-m-d');
        }

        // Validate date
        $ts = strtotime($businessDate);
        if ($ts === false) {
            throw new Exception("Invalid business date: $businessDate");
        }

        // Build the date prefix: INV-MMDDYY-
        $prefix = 'INV-'
            . date('m', $ts)
            . date('d', $ts)
            . substr(date('Y', $ts), 2)
            . '-';

        // Find the next sequence for this date prefix
        // This query is safe within a transaction because SQLite serializes writes
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(CAST(SUBSTR(invoice_number, :prefixLen + 1) AS INTEGER)), 0) + 1
             FROM sales
             WHERE invoice_number LIKE :pattern"
        );
        $stmt->execute([
            ':prefixLen' => strlen($prefix),
            ':pattern' => $prefix . '%',
        ]);
        $nextSeq = intval($stmt->fetchColumn());

        // Format sequence as 3-digit (minimum), grows if needed
        $seqStr = str_pad($nextSeq, 3, '0', STR_PAD_LEFT);

        return $prefix . $seqStr;
    }

    /**
     * Validate that a string matches the INV-MMDDYY-SSS format.
     *
     * @param string $invoiceNumber
     * @return bool
     */
    public static function isValidFormat($invoiceNumber) {
        return (bool) preg_match('/^INV-\d{6}-\d{3,}$/', $invoiceNumber);
    }

    /**
     * Extract the business date from an invoice number.
     *
     * @param string $invoiceNumber e.g. 'INV-083126-001'
     * @return string|false Date in Y-m-d format, or false if invalid
     */
    public static function extractDate($invoiceNumber) {
        if (!preg_match('/^INV-(\d{2})(\d{2})(\d{2})-\d+$/', $invoiceNumber, $m)) {
            return false;
        }
        $month = $m[1];
        $day = $m[2];
        $year = '20' . $m[3]; // Assumes 20xx century
        $dateStr = "$year-$month-$day";
        // Validate it's a real date
        return checkdate(intval($month), intval($day), intval($year)) ? $dateStr : false;
    }
}
