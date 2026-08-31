<?php
/**
 * Database Configuration & Connection
 * Singleton PDO connection to SQLite with WAL mode
 */

class Database {
    private static $instance = null;
    private $pdo;
    private $dbPath;

    private function __construct() {
        $this->dbPath = __DIR__ . '/../../data/pos_system.db';
        
        // Ensure data directory exists
        $dataDir = dirname($this->dbPath);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }

        try {
            $this->pdo = new PDO('sqlite:' . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // SQLite Performance & Safety Pragmas
            $this->pdo->exec('PRAGMA journal_mode = WAL');
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            $this->pdo->exec('PRAGMA busy_timeout = 5000');
            $this->pdo->exec('PRAGMA synchronous = NORMAL');
            $this->pdo->exec('PRAGMA cache_size = -8000'); // 8MB cache
            $this->pdo->exec('PRAGMA temp_store = MEMORY');
            
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    /**
     * Run pending database migrations (versioned)
     */
    public function migrate() {
        // Ensure migrations tracking table exists
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename TEXT NOT NULL UNIQUE,
                applied_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
            )"
        );

        $migrationsDir = __DIR__ . '/../database/migrations/';
        $migrationFiles = glob($migrationsDir . '*.sql');
        sort($migrationFiles);

        // Get already applied migrations
        $applied = [];
        $stmt = $this->pdo->query("SELECT filename FROM migrations");
        while ($row = $stmt->fetch()) {
            $applied[$row['filename']] = true;
        }

        foreach ($migrationFiles as $file) {
            $filename = basename($file);
            if (isset($applied[$filename])) {
                continue; // Already applied, skip
            }

            $sql = file_get_contents($file);
            try {
                $this->pdo->exec($sql);
                // Record successful migration
                $insert = $this->pdo->prepare("INSERT INTO migrations (filename) VALUES (?)");
                $insert->execute([$filename]);
            } catch (PDOException $e) {
                $msg = $e->getMessage();
                // "Already exists" / "duplicate column" errors are expected when upgrading from unversioned schema
                $isBenign = strpos($msg, 'already exists') !== false ||
                            strpos($msg, 'UNIQUE constraint') !== false ||
                            strpos($msg, 'duplicate column name') !== false;
                if ($isBenign) {
                    // Mark as applied so we don't retry on every request
                    $insert = $this->pdo->prepare("INSERT OR IGNORE INTO migrations (filename) VALUES (?)");
                    $insert->execute([$filename]);
                } else {
                    error_log('Migration error in ' . $filename . ': ' . $msg);
                }
            }
        }
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollBack() {
        return $this->pdo->rollBack();
    }

    // Prevent cloning
    private function __clone() {}
}
