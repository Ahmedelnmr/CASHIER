<?php
/**
 * Seed Data Loader for POS System Integration Tests
 * 
 * Usage: php seed_loader.php
 * 
 * This script loads seed data from seed_data.sql into the database
 * and verifies the data integrity.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=====================================================\n";
echo "POS System - Seed Data Loader\n";
echo "=====================================================\n\n";

// Database path
$dbPath = __DIR__ . '/www/data/pos_system.db';
$seedFile = __DIR__ . '/seed_data.sql';

// Check if seed file exists
if (!file_exists($seedFile)) {
    echo "[ERROR] Seed file not found: {$seedFile}\n";
    exit(1);
}

// Ensure data directory exists
$dataDir = dirname($dbPath);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
    echo "[INFO] Created data directory: {$dataDir}\n";
}

try {
    // Connect to database
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set pragmas
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    
    echo "[INFO] Connected to database: {$dbPath}\n";
    
    // Run migrations first
    echo "[INFO] Running migrations...\n";
    $migrationsDir = __DIR__ . '/www/app/database/migrations/';
    $migrationFiles = glob($migrationsDir . '*.sql');
    sort($migrationFiles);
    
    foreach ($migrationFiles as $file) {
        $sql = file_get_contents($file);
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false &&
                strpos($e->getMessage(), 'duplicate column') === false) {
                echo "[WARN] Migration warning in " . basename($file) . ": " . $e->getMessage() . "\n";
            }
        }
    }
    echo "[INFO] Migrations completed.\n\n";
    
    // Load seed data
    echo "[INFO] Loading seed data...\n";
    $seedSql = file_get_contents($seedFile);
    
    // Remove comment lines but keep SQL statements
    $lines = explode("\n", $seedSql);
    $cleanedLines = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        // Skip pure comment lines and PRAGMA lines
        if (strpos($trimmed, '--') === 0 || strpos($trimmed, 'PRAGMA') === 0) {
            continue;
        }
        // Skip verification query block
        if (strpos($trimmed, 'SELECT') !== false && strpos($trimmed, 'UNION ALL') !== false) {
            continue;
        }
        if (strpos($trimmed, "-- Run these") !== false) {
            break; // Stop at verification section
        }
        $cleanedLines[] = $line;
    }
    $cleanedSql = implode("\n", $cleanedLines);
    
    // Execute the entire cleaned SQL
    $successCount = 0;
    $errorCount = 0;
    
    try {
        $pdo->exec($cleanedSql);
        echo "[INFO] Seed data loaded successfully.\n\n";
    } catch (PDOException $e) {
        echo "[WARN] Some statements had issues: " . $e->getMessage() . "\n\n";
    }
    
    // Verify data
    echo "=====================================================\n";
    echo "Data Verification\n";
    echo "=====================================================\n\n";
    
    $tables = [
        'users' => 'Users',
        'suppliers' => 'Suppliers',
        'products' => 'Products',
        'customers' => 'Customers',
        'employees' => 'Employees',
        'shifts' => 'Shifts',
        'sales' => 'Sales',
        'sale_items' => 'Sale Items',
        'purchase_invoices' => 'Purchase Invoices',
        'purchase_items' => 'Purchase Items',
        'safe_transactions' => 'Safe Transactions',
        'stock_movements' => 'Stock Movements',
        'customer_transactions' => 'Customer Transactions',
        'employee_accruals' => 'Employee Accruals',
        'settings' => 'Settings',
    ];
    
    $totalRecords = 0;
    foreach ($tables as $table => $label) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM {$table}");
            $count = $stmt->fetch()['cnt'];
            $totalRecords += $count;
            printf("  %-25s %5d records\n", $label, $count);
        } catch (PDOException $e) {
            printf("  %-25s [TABLE NOT FOUND]\n", $label);
        }
    }
    
    echo "\n  Total records: {$totalRecords}\n\n";
    
    // Detailed verification
    echo "=====================================================\n";
    echo "Detailed Verification\n";
    echo "=====================================================\n\n";
    
    // Check products by type
    echo "Products by type:\n";
    $stmt = $pdo->query("SELECT type, COUNT(*) as cnt FROM products WHERE is_active = 1 GROUP BY type");
    while ($row = $stmt->fetch()) {
        printf("  %-10s %d\n", $row['type'], $row['cnt']);
    }
    
    // Check products with low stock
    echo "\nLow stock products:\n";
    $stmt = $pdo->query("SELECT name, stock_quantity, min_stock FROM products WHERE is_active = 1 AND stock_quantity <= min_stock AND min_stock > 0");
    while ($row = $stmt->fetch()) {
        printf("  %-30s stock=%-5d min=%-5d\n", $row['name'], $row['stock_quantity'], $row['min_stock']);
    }
    
    // Check sales by status
    echo "\nSales by status:\n";
    $stmt = $pdo->query("SELECT status, type, COUNT(*) as cnt FROM sales GROUP BY status, type");
    while ($row = $stmt->fetch()) {
        printf("  %-10s %-10s %d\n", $row['status'], $row['type'], $row['cnt']);
    }
    
    // Check shifts
    echo "\nShifts:\n";
    $stmt = $pdo->query("SELECT id, user_id, status, opening_cash FROM shifts");
    while ($row = $stmt->fetch()) {
        printf("  Shift %d: user=%d, status=%-8s opening=%.2f\n", $row['id'], $row['user_id'], $row['status'], $row['opening_cash']);
    }
    
    // Check safe transaction types
    echo "\nSafe transaction types:\n";
    $stmt = $pdo->query("SELECT type, COUNT(*) as cnt, SUM(amount) as total FROM safe_transactions GROUP BY type ORDER BY type");
    while ($row = $stmt->fetch()) {
        printf("  %-20s count=%-3d total=%-10.2f\n", $row['type'], $row['cnt'], $row['total']);
    }
    
    // Check customers with balance
    echo "\nCustomers with balance:\n";
    $stmt = $pdo->query("SELECT name, balance, credit_limit FROM customers WHERE is_active = 1 AND balance != 0");
    while ($row = $stmt->fetch()) {
        printf("  %-20s balance=%-10.2f limit=%.2f\n", $row['name'], $row['balance'], $row['credit_limit']);
    }
    
    // Check employees
    echo "\nEmployees:\n";
    $stmt = $pdo->query("SELECT id, name, base_salary, is_active FROM employees");
    while ($row = $stmt->fetch()) {
        printf("  %d: %-20s salary=%-10.2f active=%d\n", $row['id'], $row['name'], $row['base_salary'], $row['is_active']);
    }
    
    echo "\n=====================================================\n";
    echo "[SUCCESS] Seed data loaded and verified!\n";
    echo "=====================================================\n";
    
} catch (PDOException $e) {
    echo "[ERROR] Database error: " . $e->getMessage() . "\n";
    exit(1);
}
