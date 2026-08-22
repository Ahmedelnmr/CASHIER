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
     * Run database migrations
     */
    public function migrate() {
        $migrationsDir = __DIR__ . '/../database/migrations/';
        $migrationFiles = glob($migrationsDir . '*.sql');
        sort($migrationFiles);

        foreach ($migrationFiles as $file) {
            $sql = file_get_contents($file);
            try {
                $this->pdo->exec($sql);
            } catch (PDOException $e) {
                // Skip if tables/columns already exist (common on repeated migrations)
                if (strpos($e->getMessage(), 'already exists') === false &&
                    strpos($e->getMessage(), 'UNIQUE constraint') === false &&
                    strpos($e->getMessage(), 'duplicate column name') === false) {
                    error_log('Migration error in ' . basename($file) . ': ' . $e->getMessage());
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
