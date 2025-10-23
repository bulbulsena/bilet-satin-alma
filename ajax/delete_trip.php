<?php
require_once __DIR__ . '/../config/auth.php';

// Get database connection
global $db;

// Only allow admin or company admin users
$auth->requireLogin();
$auth->requireAdminOrCompanyAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $tripId = $input['trip_id'] ?? '';
    
    if (!$tripId) {
        echo json_encode(['success' => false, 'message' => 'Sefer ID gerekli.']);
        exit;
    }
    
    // Check if user can manage this trip
    if (!$auth->canManageTrip($tripId)) {
        echo json_encode(['success' => false, 'message' => 'Bu seferi silme yetkiniz yok.']);
        exit;
    }
    
    // Check if trip has any tickets
    $ticketCount = $db->fetchOne("SELECT COUNT(*) as count FROM tickets WHERE trip_id = ?", [$tripId])['count'];
    if ($ticketCount > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu sefere ait biletler bulunduğu için silinemez.']);
        exit;
    }
    
    try {
        $db->execute("DELETE FROM trips WHERE id = ?", [$tripId]);
        echo json_encode(['success' => true, 'message' => 'Sefer başarıyla silindi.']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Sefer silme sırasında hata oluştu: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
}
?>
