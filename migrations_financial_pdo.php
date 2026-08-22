<?php
// Migration for Financial Module (PDO Version)

try {
    $db = new PDO('sqlite:www/data/pos_system.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create Employees Table
    $db->exec("CREATE TABLE IF NOT EXISTS employees (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        phone TEXT,
        base_salary REAL DEFAULT 0,
        settings TEXT, -- JSON for extra settings if needed
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Create Safe Transactions Table
    // type: 'expense', 'withdrawal', 'advance', 'salary', 'deposit', 'opening'
    $db->exec("CREATE TABLE IF NOT EXISTS safe_transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        type TEXT NOT NULL,
        amount REAL NOT NULL,
        date DATE NOT NULL,
        description TEXT,
        employee_id INTEGER NULL,
        category_id INTEGER NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(employee_id) REFERENCES employees(id)
    )");

    echo "Financial tables created successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
