<?php
$pageTitle = "Biletlerim";
require_once 'config/auth.php';

// Only allow logged in users
$auth->requireLogin();

// Get user's tickets
$tickets = $db->fetchAll("
    SELECT t.*, tr.departure_city, tr.destination_city, tr.departure_time, tr.arrival_time, 
           bc.name as company_name, c.code as coupon_code,
           GROUP_CONCAT(bs.seat_number) as seat_numbers
    FROM tickets t
    JOIN trips tr ON t.trip_id = tr.id
    JOIN bus_companies bc ON tr.company_id = bc.id
    LEFT JOIN coupons c ON t.coupon_id = c.id
    LEFT JOIN booked_seats bs ON t.id = bs.ticket_id
    WHERE t.user_id = ?
    GROUP BY t.id
    ORDER BY t.created_at DESC
", [$_SESSION['user_id']]);

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-ticket-alt"></i> Biletlerim</h4>
            </div>
            <div class="card-body">
                <?php if (empty($tickets)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Henüz bilet satın alımı yapmamışsınız</h5>
                        <p class="text-muted">Bilet satın almak için <a href="index.php">ana sayfaya</a> gidin.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Bilet No</th>
                                    <th>Güzergah</th>
                                    <th>Firma</th>
                                    <th>Tarih</th>
                                    <th>Koltuklar</th>
                                    <th>Fiyat</th>
                                    <th>Kupon</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo str_pad($ticket['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($ticket['departure_city']); ?></strong>
                                            <i class="fas fa-arrow-right mx-2 text-muted"></i>
                                            <strong><?php echo htmlspecialchars($ticket['destination_city']); ?></strong>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('d.m.Y H:i', strtotime($ticket['departure_time'])); ?> - 
                                            <?php echo date('d.m.Y H:i', strtotime($ticket['arrival_time'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-building me-2 text-muted"></i>
                                            <?php echo htmlspecialchars($ticket['company_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo date('d.m.Y', strtotime($ticket['departure_time'])); ?></strong>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('H:i', strtotime($ticket['departure_time'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars($ticket['seat_numbers'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($ticket['total_price'], 2); ?> ₺</strong>
                                    </td>
                                    <td>
                                        <?php if ($ticket['coupon_code']): ?>
                                            <span class="badge bg-success">
                                                <?php echo htmlspecialchars($ticket['coupon_code']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        switch ($ticket['status']) {
                                            case 'active':
                                                $statusClass = 'bg-success';
                                                $statusText = 'Aktif';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'bg-danger';
                                                $statusText = 'İptal';
                                                break;
                                            case 'used':
                                                $statusClass = 'bg-secondary';
                                                $statusText = 'Kullanıldı';
                                                break;
                                            default:
                                                $statusClass = 'bg-warning';
                                                $statusText = 'Bilinmiyor';
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($ticket['status'] === 'active'): ?>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="cancelTicket('<?php echo $ticket['id']; ?>')">
                                                <i class="fas fa-times"></i> İptal Et
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function cancelTicket(ticketId) {
    if (confirm('Bu bileti iptal etmek istediğinizden emin misiniz?')) {
        fetch('ajax/cancel_ticket.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'ticket_id=' + encodeURIComponent(ticketId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Bilet başarıyla iptal edildi.');
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bilet iptal etme sırasında hata oluştu.');
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>