<?php
// Simple database migration script
require_once 'config/database.php';

try {
    echo "Basit migration başlatılıyor...\n";
    
    // Check if is_active column exists
    $columns = $db->fetchAll("PRAGMA table_info(users)");
    $hasIsActive = false;
    
    foreach ($columns as $column) {
        if ($column['name'] === 'is_active') {
            $hasIsActive = true;
            break;
        }
    }
    
    // Add is_active column if it doesn't exist
    if (!$hasIsActive) {
        echo "is_active sütunu ekleniyor...\n";
        $db->query("ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT 1");
        echo "is_active sütunu başarıyla eklendi.\n";
    } else {
        echo "is_active sütunu zaten mevcut.\n";
    }
    
    // Update existing users to have is_active = 1
    $db->query("UPDATE users SET is_active = 1 WHERE is_active IS NULL");
    
    echo "Migration tamamlandı!\n";
    
} catch (Exception $e) {
    echo "Migration hatası: " . $e->getMessage() . "\n";
}
?>
