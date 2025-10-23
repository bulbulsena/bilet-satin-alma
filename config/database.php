<?php
// Database configuration
class Database {
    private $db;
    
    public function __construct() {
        $this->connect();
        $this->initializeIfNeeded();
    }
    
    private function connect() {
        try {
            error_log("Connecting to database...");
            $dbPath = __DIR__ . '/../database/bilet_satin_alma.db';
            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // Enforce foreign keys in SQLite
            $this->db->exec('PRAGMA foreign_keys = ON');
            error_log("Database connection successful");
        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }
    
    private function initializeIfNeeded() {
        try {
            // Debug: Always log that we're checking
            error_log("Checking if database needs initialization...");
            
            // Check if a known table exists (e.g., users)
            $stmt = $this->db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = ?");
            $stmt->execute(['users']);
            $exists = $stmt->fetch();
            
            error_log("Users table exists: " . ($exists ? 'YES' : 'NO'));
            
            if (!$exists) {
                // Debug: Show that we're initializing
                error_log("Initializing database - users table not found");
                
                // Ensure database directory exists when running outside container
                $dbDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database';
                error_log("Database directory: " . $dbDir);
                
                if (!is_dir($dbDir)) {
                    @mkdir($dbDir, 0755, true);
                    error_log("Created database directory");
                }
                
                $schemaPath = $dbDir . DIRECTORY_SEPARATOR . 'schema.sql';
                error_log("Schema path: " . $schemaPath);
                
                if (!file_exists($schemaPath)) {
                    throw new Exception('Şema dosyası bulunamadı: ' . $schemaPath);
                }
                $schemaSql = file_get_contents($schemaPath);
                if ($schemaSql === false) {
                    throw new Exception('Şema dosyası okunamadı: ' . $schemaPath);
                }
                
                error_log("Schema file loaded, executing statements...");
                
                // Split SQL statements and execute them one by one
                $statements = array_filter(array_map('trim', explode(';', $schemaSql)));
                foreach ($statements as $i => $statement) {
                    if (!empty($statement)) {
                        error_log("Executing statement " . ($i + 1) . ": " . substr($statement, 0, 50) . "...");
                        $this->db->exec($statement);
                    }
                }
                
                error_log("Database initialization completed");
            } else {
                error_log("Database already initialized");
            }
        } catch (Exception $e) {
            error_log("Database initialization error: " . $e->getMessage());
            die('Veritabanı başlatma hatası: ' . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->db;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            throw new Exception("Sorgu hatası: " . $e->getMessage());
        }
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->query($sql, $data);
        return $this->db->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach($data as $key => $value) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        return $this->query($sql, $params)->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }
}

// Global database instance
try {
    $db = new Database();
} catch (Exception $e) {
    die('Veritabanı başlatma hatası: ' . $e->getMessage());
}
?>
