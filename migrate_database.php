<?php
// Database migration script
require_once 'config/database.php';

try {
    echo "Veritabanı migration başlatılıyor...\n";
    
    // Check if is_active column exists in users table
    $columns = $db->fetchAll("PRAGMA table_info(users)");
    $hasIsActive = false;
    $hasBalance = false;
    $hasUpdatedAt = false;
    
    foreach ($columns as $column) {
        if ($column['name'] === 'is_active') {
            $hasIsActive = true;
        }
        if ($column['name'] === 'balance') {
            $hasBalance = true;
        }
        if ($column['name'] === 'updated_at') {
            $hasUpdatedAt = true;
        }
    }
    
    // Add is_active column if it doesn't exist
    if (!$hasIsActive) {
        echo "is_active sütunu ekleniyor...\n";
        $db->query("ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT 1");
    }
    
    // Add updated_at column if it doesn't exist
    if (!$hasUpdatedAt) {
        echo "updated_at sütunu ekleniyor...\n";
        $db->query("ALTER TABLE users ADD COLUMN updated_at DATETIME");
    }
    
    // Update balance column type if needed
    if ($hasBalance) {
        echo "balance sütunu güncelleniyor...\n";
        // SQLite doesn't support ALTER COLUMN, so we need to recreate the table
        $db->query("BEGIN TRANSACTION");
        
        // Create new table with correct structure
        $db->query("CREATE TABLE users_new (
            id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
            full_name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            role TEXT NOT NULL DEFAULT 'user' CHECK (role IN ('admin', 'company_admin', 'user')),
            password TEXT NOT NULL,
            company_id TEXT,
            balance DECIMAL(10,2) DEFAULT 0.00,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES bus_companies(id)
        )");
        
        // Copy data from old table
        $db->query("INSERT INTO users_new (id, full_name, email, role, password, company_id, balance, is_active, created_at, updated_at)
                    SELECT id, full_name, email, role, password, company_id, 
                           CASE WHEN balance IS NULL THEN 0.00 ELSE balance END,
                           1, created_at, CURRENT_TIMESTAMP
                    FROM users");
        
        // Drop old table and rename new one
        $db->query("DROP TABLE users");
        $db->query("ALTER TABLE users_new RENAME TO users");
        
        $db->query("COMMIT");
    }
    
    // Update bus_companies table
    $busColumns = $db->fetchAll("PRAGMA table_info(bus_companies)");
    $hasContactEmail = false;
    $hasContactPhone = false;
    $hasAddress = false;
    $hasIsActiveBus = false;
    $hasUpdatedAtBus = false;
    
    foreach ($busColumns as $column) {
        if ($column['name'] === 'contact_email') $hasContactEmail = true;
        if ($column['name'] === 'contact_phone') $hasContactPhone = true;
        if ($column['name'] === 'address') $hasAddress = true;
        if ($column['name'] === 'is_active') $hasIsActiveBus = true;
        if ($column['name'] === 'updated_at') $hasUpdatedAtBus = true;
    }
    
    if (!$hasContactEmail) {
        echo "bus_companies tablosuna contact_email sütunu ekleniyor...\n";
        $db->query("ALTER TABLE bus_companies ADD COLUMN contact_email TEXT");
    }
    
    if (!$hasContactPhone) {
        echo "bus_companies tablosuna contact_phone sütunu ekleniyor...\n";
        $db->query("ALTER TABLE bus_companies ADD COLUMN contact_phone TEXT");
    }
    
    if (!$hasAddress) {
        echo "bus_companies tablosuna address sütunu ekleniyor...\n";
        $db->query("ALTER TABLE bus_companies ADD COLUMN address TEXT");
    }
    
    if (!$hasIsActiveBus) {
        echo "bus_companies tablosuna is_active sütunu ekleniyor...\n";
        $db->query("ALTER TABLE bus_companies ADD COLUMN is_active BOOLEAN DEFAULT 1");
    }
    
    if (!$hasUpdatedAtBus) {
        echo "bus_companies tablosuna updated_at sütunu ekleniyor...\n";
        $db->query("ALTER TABLE bus_companies ADD COLUMN updated_at DATETIME");
    }
    
    echo "Migration tamamlandı!\n";
    echo "Veritabanı başarıyla güncellendi.\n";
    
} catch (Exception $e) {
    echo "Migration hatası: " . $e->getMessage() . "\n";
    if (isset($db)) {
        $db->query("ROLLBACK");
    }
}
?>
