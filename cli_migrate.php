<?php
// CLI Migration Script
date_default_timezone_set('Africa/Cairo');

$dbPath = __DIR__ . '/www/data/pos_system.db';
echo "Connecting to: $dbPath\n";

if (!file_exists($dbPath)) {
    // If not found in simple path, check relative to Desktop if running from root
    $dbPath = __DIR__ . '/data/pos_system.db';
    if (!file_exists($dbPath)) {
        die("Database file not found at: $dbPath\n");
    }
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if column exists
    $stmt = $pdo->query("PRAGMA table_info(purchase_invoices)");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
    
    if (in_array('discount', $columns)) {
        echo "Column 'discount' already exists. Skipping.\n";
    } else {
        echo "Adding 'discount' column...\n";
        $pdo->exec("ALTER TABLE purchase_invoices ADD COLUMN discount REAL NOT NULL DEFAULT 0");
        echo "Success: Column added.\n";
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
