<?php
/**
 * Layout - Header & Sidebar
 */
$currentPage = $_GET['page'] ?? 'dashboard';
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $currentUser = [
        'name' => $_SESSION['user_name'] ?? 'مستخدم',
        'role' => $_SESSION['user_role'] ?? 'cashier',
    ];
}
$roleLabels = ['admin' => 'مدير النظام', 'manager' => 'مشرف', 'cashier' => 'كاشير'];

// SVG Icon helper
function navIcon($svg) {
    return '<span class="icon"><svg viewBox="0 0 24 24">' . $svg . '</svg></span>';
}

$icons = [
    'dashboard' => navIcon('<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>'),
    'pos' => navIcon('<circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>'),
    'products' => navIcon('<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>'),
    'suppliers' => navIcon('<rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle>'),
    'purchases' => navIcon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>'),
    'inventory' => navIcon('<path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>'),
    'sales' => navIcon('<line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>'),
    'reports' => navIcon('<line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line>'),
    'employees' => navIcon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>'),
    'users' => navIcon('<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>'),
    'settings' => navIcon('<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>'),
    'backup' => navIcon('<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line>'),
    'safe' => navIcon('<rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="M12 8v8"></path><path d="M8 12h8"></path>'),
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تاجر — نظام نقاط البيع</title>
    <link rel="stylesheet" href="public/css/main.css?v=<?= time() ?>">
</head>
<body>
<div class="toast-container" id="toastContainer"></div>
<div class="app-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="public/images/logo.png" alt="تاجر" onerror="this.style.display='none'">
            <h2>تاجر</h2>
            <span>نظام نقاط البيع والمخزون</span>
        </div>
        <nav class="sidebar-nav">
            <a href="?page=dashboard" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <?= $icons['dashboard'] ?> لوحة التحكم
            </a>
            <a href="?page=pos" class="nav-item <?= $currentPage === 'pos' ? 'active' : '' ?>">
                <?= $icons['pos'] ?> نقطة البيع
            </a>
            <a href="?page=shifts" class="nav-item <?= $currentPage === 'shifts' ? 'active' : '' ?>">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></span> الورديات
            </a>
            <a href="?page=customers" class="nav-item <?= $currentPage === 'customers' ? 'active' : '' ?>">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></span> العملاء
            </a>
            <a href="?page=products" class="nav-item <?= $currentPage === 'products' ? 'active' : '' ?>">
                <?= $icons['products'] ?> المنتجات
            </a>
            <a href="?page=barcode" class="nav-item <?= $currentPage === 'barcode' ? 'active' : '' ?>">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2v20M10 2v20M14 2v20M18 2v20M2 2h20M2 22h20"></path></svg></span> الباركود
            </a>
            <?php
            $userRole = $currentUser['role'] ?? 'cashier';
            $roleLevel = ['cashier' => 1, 'manager' => 2, 'admin' => 3];
            $level = $roleLevel[$userRole] ?? 0;
            ?>
            <?php if ($level >= 2): ?>
            <a href="?page=suppliers" class="nav-item <?= $currentPage === 'suppliers' ? 'active' : '' ?>">
                <?= $icons['suppliers'] ?> الموردين
            </a>
            <a href="?page=purchases" class="nav-item <?= $currentPage === 'purchases' ? 'active' : '' ?>">
                <?= $icons['purchases'] ?> المشتريات
            </a>
            <a href="?page=inventory" class="nav-item <?= $currentPage === 'inventory' ? 'active' : '' ?>">
                <?= $icons['inventory'] ?> المخزون
            </a>
            <?php endif; ?>
            <a href="?page=sales" class="nav-item <?= $currentPage === 'sales' ? 'active' : '' ?>">
                <?= $icons['sales'] ?> المبيعات
            </a>
            <?php if ($level >= 2): ?>
            <a href="?page=reports" class="nav-item <?= $currentPage === 'reports' ? 'active' : '' ?>">
                <?= $icons['reports'] ?> التقارير
            </a>
            <?php endif; ?>
            <?php if ($level >= 3): ?>
            <a href="?page=employees" class="nav-item <?= $currentPage === 'employees' ? 'active' : '' ?>">
                <?= $icons['employees'] ?> الموظفين
            </a>
            <a href="?page=users" class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>">
                <?= $icons['users'] ?> المستخدمين
            </a>
            <a href="?page=settings" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                <?= $icons['settings'] ?> الإعدادات
            </a>
            <a href="?page=backup" class="nav-item <?= $currentPage === 'backup' ? 'active' : '' ?>">
                <?= $icons['backup'] ?> النسخ الاحتياطي
            </a>
            <a href="?page=safe" class="nav-item <?= $currentPage === 'safe' ? 'active' : '' ?>">
                <?= $icons['safe'] ?> الخزنة
            </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <?php if ($currentUser): ?>
            <div class="user-card">
                <div class="user-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                <div class="user-role"><?= $roleLabels[$currentUser['role']] ?? $currentUser['role'] ?></div>
            </div>
            <a href="?page=login&action=logout" class="logout-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                تسجيل الخروج
            </a>
            <?php endif; ?>
            <div class="copyright">تاجر &copy; <?= date('Y') ?></div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
