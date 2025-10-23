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

if (!$auth->isCompanyAdmin()) {
    echo json_encode(['error' => 'Bu işlem için firma admin yetkisi gerekiyor']);
    exit;
}

// Get company ID
$company = $auth->getCompany();
if (!$company) {
    echo json_encode(['error' => 'Firma bilgisi bulunamadı']);
    exit;
}

// Get trips for this company
$trips = $db->fetchAll("
    SELECT t.*, 
           COUNT(tk.id) as ticket_count,
           SUM(tk.total_price) as revenue
    FROM trips t
    LEFT JOIN tickets tk ON t.id = tk.trip_id
    WHERE t.company_id = ?
    GROUP BY t.id
    ORDER BY t.departure_time DESC
", [$company['id']]);

echo json_encode($trips);
?>
