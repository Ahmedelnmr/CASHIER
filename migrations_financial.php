<?php
// Migration for Financial Module
$db = new SQLite3('www/database.sqlite');

// 1. Create Employees Table
$db->exec("CREATE TABLE IF NOT EXISTS employees (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT,
    base_salary REAL DEFAULT 0,
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
