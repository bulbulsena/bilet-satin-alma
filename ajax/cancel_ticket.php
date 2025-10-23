<?php
// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

require_once __DIR__ . '/../config/auth.php';

// Get database connection
global $db;

// Check authentication
if (!$auth->isLoggedIn()) {
    echo json_encode(['error' => 'Giriş yapmanız gerekiyor']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = $_POST['ticket_id'] ?? '';
    
    if (!$ticketId) {
        echo json_encode(['success' => false, 'message' => 'Bilet ID gerekli.']);
        exit;
    }
    
    // Get ticket details
    $ticket = $db->fetchOne("
        SELECT t.*, tr.departure_time 
        FROM tickets t 
        JOIN trips tr ON t.trip_id = tr.id 
        WHERE t.id = ? AND t.user_id = ?
    ", [$ticketId, $_SESSION['user_id']]);
    
    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'Bilet bulunamadı.']);
        exit;
    }
    
    // Check if ticket is already cancelled
    if ($ticket['status'] === 'cancelled') {
        echo json_encode(['success' => false, 'message' => 'Bu bilet zaten iptal edilmiş.']);
        exit;
    }
    
    // Check if trip has already departed
    if (strtotime($ticket['departure_time']) <= time()) {
        echo json_encode(['success' => false, 'message' => 'Kalkış zamanı geçmiş biletler iptal edilemez.']);
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        // Update ticket status
        $db->query("UPDATE tickets SET status = 'cancelled' WHERE id = ?", [$ticketId]);
        
        // Remove seat reservations
        $db->query("DELETE FROM booked_seats WHERE ticket_id = ?", [$ticketId]);
        
        $db->commit();
        
        echo json_encode(['success' => true, 'message' => 'Bilet başarıyla iptal edildi.']);
        
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['success' => false, 'message' => 'Bilet iptal etme sırasında hata oluştu: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
}
?>