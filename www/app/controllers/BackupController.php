<?php
/**
 * Backup Controller - Database Backup & Restore (Admin Only)
 */

class BackupController {
    private $dbPath;
    private $backupDir;

    public function __construct() {
        $auth = new AuthService();
        if (!$auth->hasRole('admin')) {
            header('Location: ?page=dashboard');
            exit;
        }
        $this->dbPath = BASE_PATH . '/data/pos_system.db';
        $this->backupDir = BASE_PATH . '/data/backups/';

        // Ensure backup directory exists
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }

    /**
     * Show backup management page
     */
    public function index() {
        $backups = $this->getBackupList();
        $toastSuccess = $_SESSION['toast_success'] ?? '';
        $toastError = $_SESSION['toast_error'] ?? '';
        unset($_SESSION['toast_success'], $_SESSION['toast_error']);
        include APP_PATH . '/views/layout/header.php';
        include APP_PATH . '/views/backup/index.php';
        include APP_PATH . '/views/layout/footer.php';
    }

    /**
     * Create a new backup
     */
    public function create() {
        $timestamp = date('Y-m-d_H-i-s');
        $backupName = 'backup_' . $timestamp . '.db';
        $backupPath = $this->backupDir . $backupName;

        try {
            // Use SQLite backup API via PDO
            $db = Database::getInstance()->getConnection();
            $db->exec("VACUUM INTO '" . addslashes($backupPath) . "'");

            $_SESSION['toast_success'] = 'تم إنشاء النسخة الاحتياطية: ' . $backupName;
        } catch (Exception $e) {
            // Fallback: file copy
            if (copy($this->dbPath, $backupPath)) {
                $_SESSION['toast_success'] = 'تم إنشاء النسخة الاحتياطية: ' . $backupName;
            } else {
                $_SESSION['toast_error'] = 'فشل إنشاء النسخة الاحتياطية';
            }
        }

        header('Location: ?page=backup');
        exit;
    }

    /**
     * Download a backup file
     */
    public function download() {
        $filename = $_GET['file'] ?? '';

        // Security: only allow .db files from backup directory
        $filename = basename($filename);
        $filepath = $this->backupDir . $filename;

        if (!file_exists($filepath) || pathinfo($filename, PATHINFO_EXTENSION) !== 'db') {
            $_SESSION['toast_error'] = 'ملف النسخة الاحتياطية غير موجود';
            header('Location: ?page=backup');
            exit;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, no-store, must-revalidate');

        readfile($filepath);
        exit;
    }

    /**
     * Restore from uploaded backup
     */
    public function restore() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=backup');
            exit;
        }

        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['toast_error'] = 'يرجى اختيار ملف النسخة الاحتياطية';
            header('Location: ?page=backup');
            exit;
        }

        $uploadedFile = $_FILES['backup_file'];
        $extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);

        if ($extension !== 'db') {
            $_SESSION['toast_error'] = 'ملف غير صالح. يجب أن يكون ملف .db';
            header('Location: ?page=backup');
            exit;
        }

        // Validate it's a valid SQLite file
        $header = file_get_contents($uploadedFile['tmp_name'], false, null, 0, 16);
        if (strpos($header, 'SQLite format 3') === false) {
            $_SESSION['toast_error'] = 'الملف ليس قاعدة بيانات SQLite صالحة';
            header('Location: ?page=backup');
            exit;
        }

        try {
            // Create a safety backup first
            $safetyBackup = $this->backupDir . 'pre_restore_' . date('Y-m-d_H-i-s') . '.db';
            copy($this->dbPath, $safetyBackup);

            // Close existing connection by replacing singleton
            // Restore by copying uploaded file
            if (move_uploaded_file($uploadedFile['tmp_name'], $this->dbPath)) {
                $_SESSION['toast_success'] = 'تم استعادة قاعدة البيانات بنجاح. يرجى إعادة تشغيل التطبيق.';
            } else {
                $_SESSION['toast_error'] = 'فشل استعادة قاعدة البيانات';
            }
        } catch (Exception $e) {
            $_SESSION['toast_error'] = 'خطأ: ' . $e->getMessage();
        }

        header('Location: ?page=backup');
        exit;
    }

    /**
     * Delete a backup file
     */
    public function delete() {
        $filename = $_GET['file'] ?? '';
        $filename = basename($filename);
        $filepath = $this->backupDir . $filename;

        if (file_exists($filepath) && pathinfo($filename, PATHINFO_EXTENSION) === 'db') {
            unlink($filepath);
            $_SESSION['toast_success'] = 'تم حذف النسخة الاحتياطية';
        } else {
            $_SESSION['toast_error'] = 'ملف غير موجود';
        }

        header('Location: ?page=backup');
        exit;
    }

    /**
     * Get list of backup files
     */
    private function getBackupList() {
        $backups = [];
        $files = glob($this->backupDir . '*.db');
        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }
        // Sort by date descending
        usort($backups, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });
        return $backups;
    }
}
