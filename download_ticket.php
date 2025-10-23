<?php
require_once 'config/auth.php';

// Only allow logged in users
$auth->requireLogin();
$auth->requireRole('user');

$ticketId = $_GET['id'] ?? 0;

if (!$ticketId) {
    $_SESSION['error_message'] = 'Geçersiz bilet ID.';
    header('Location: my_tickets.php');
    exit;
}

// Get ticket details
$ticket = $db->fetchOne("
    SELECT t.*, tr.departure_city, tr.arrival_city, tr.departure_date, tr.departure_time, 
           tr.arrival_date, tr.arrival_time, f.name as firm_name, f.contact_phone, f.contact_email,
           u.first_name, u.last_name, u.email
    FROM tickets t
    JOIN trips tr ON t.trip_id = tr.id
    JOIN firms f ON tr.firm_id = f.id
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ? AND t.user_id = ? AND t.status = 'active'
", [$ticketId, $_SESSION['user_id']]);

if (!$ticket) {
    $_SESSION['error_message'] = 'Bilet bulunamadı.';
    header('Location: my_tickets.php');
    exit;
}

// Simple PDF generation using HTML to PDF conversion
// For production, you would use a proper PDF library like TCPDF or FPDF

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bilet - ' . htmlspecialchars($ticket['firm_name']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 20px; }
        .company-name { font-size: 24px; font-weight: bold; color: #007bff; }
        .ticket-info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .route { display: flex; justify-content: space-between; align-items: center; margin: 20px 0; }
        .departure, .arrival { text-align: center; }
        .departure h3, .arrival h3 { margin: 0; color: #007bff; }
        .departure p, .arrival p { margin: 5px 0; }
        .arrow { font-size: 24px; color: #007bff; }
        .details { display: flex; justify-content: space-between; margin-top: 20px; }
        .detail-item { text-align: center; }
        .detail-label { font-weight: bold; color: #666; }
        .detail-value { font-size: 18px; margin-top: 5px; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
        .barcode { text-align: center; margin: 20px 0; }
        .barcode-text { font-family: monospace; font-size: 16px; letter-spacing: 2px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">' . htmlspecialchars($ticket['firm_name']) . '</div>
        <div>Bilet Satın Alma Platformu</div>
    </div>
    
    <div class="ticket-info">
        <h2>OTOBÜS BİLETİ</h2>
        <div class="route">
            <div class="departure">
                <h3>' . htmlspecialchars($ticket['departure_city']) . '</h3>
                <p>' . date('d.m.Y', strtotime($ticket['departure_date'])) . '</p>
                <p><strong>' . date('H:i', strtotime($ticket['departure_time'])) . '</strong></p>
            </div>
            <div class="arrow">→</div>
            <div class="arrival">
                <h3>' . htmlspecialchars($ticket['arrival_city']) . '</h3>
                <p>' . date('d.m.Y', strtotime($ticket['arrival_date'])) . '</p>
                <p><strong>' . date('H:i', strtotime($ticket['arrival_time'])) . '</strong></p>
            </div>
        </div>
        
        <div class="details">
            <div class="detail-item">
                <div class="detail-label">Yolcu</div>
                <div class="detail-value">' . htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) . '</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Koltuk No</div>
                <div class="detail-value">' . $ticket['seat_number'] . '</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Bilet No</div>
                <div class="detail-value">#' . str_pad($ticket['id'], 6, '0', STR_PAD_LEFT) . '</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Fiyat</div>
                <div class="detail-value">' . number_format($ticket['final_price'], 2) . ' ₺</div>
            </div>
        </div>
        
        ' . ($ticket['coupon_code'] ? '<div style="text-align: center; margin-top: 15px;"><strong>Kupon:</strong> ' . htmlspecialchars($ticket['coupon_code']) . '</div>' : '') . '
    </div>
    
    <div class="barcode">
        <div class="barcode-text">||||| ' . str_pad($ticket['id'], 6, '0', STR_PAD_LEFT) . ' |||||</div>
    </div>
    
    <div class="footer">
        <p>Bu bilet ' . date('d.m.Y H:i', strtotime($ticket['purchase_date'])) . ' tarihinde satın alınmıştır.</p>
        <p>İletişim: ' . htmlspecialchars($ticket['contact_phone']) . ' | ' . htmlspecialchars($ticket['contact_email']) . '</p>
        <p><strong>Önemli:</strong> Kalkış saatinden 30 dakika önce terminalde bulunmanız önerilir.</p>
    </div>
</body>
</html>';

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="bilet_' . $ticketId . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// For a simple implementation, we'll output HTML that can be printed as PDF
// In production, you would use a proper PDF library
echo $html;
?>
