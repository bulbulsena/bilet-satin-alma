<?php
// Database initialization script
require_once 'config/database.php';

try {
    // Read and execute schema
    $schema = file_get_contents('database/schema.sql');
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $db->query($statement);
        }
    }
    
    echo "Veritabanı başarıyla oluşturuldu!\n";
    echo "Admin kullanıcısı: admin / password\n";
    echo "Firma Admin kullanıcıları:\n";
    echo "- metro_admin / password (Metro Turizm)\n";
    echo "- ulusoy_admin / password (Ulusoy)\n";
    
} catch (Exception $e) {
    echo "Veritabanı oluşturma hatası: " . $e->getMessage() . "\n";
}
?>
