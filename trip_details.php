<?php
$pageTitle = "Sefer Detayları";
require_once 'config/auth.php';

// Only allow logged in users
$auth->requireLogin();

$tripId = $_GET['id'] ?? '';
if (!$tripId) {
    $_SESSION['error_message'] = 'Sefer bulunamadı.';
    header('Location: index.php');
    exit;
}

// Get trip details
$trip = $db->fetchOne("
    SELECT t.*, bc.name as company_name, bc.logo_path
    FROM trips t 
    JOIN bus_companies bc ON t.company_id = bc.id 
    WHERE t.id = ?
", [$tripId]);

if (!$trip) {
    $_SESSION['error_message'] = 'Sefer bulunamadı.';
    header('Location: index.php');
    exit;
}

// Get reserved seats for this trip
$reservedSeats = $db->fetchAll("
    SELECT seat_number FROM booked_seats 
    WHERE trip_id = ?
", [$tripId]);

$reservedSeatNumbers = array_column($reservedSeats, 'seat_number');

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header">
                <h4><i class="fas fa-bus"></i> Sefer Detayları</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted">Firma</h6>
                                <h5><?php echo htmlspecialchars($trip['company_name']); ?></h5>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Güzergah</h6>
                                <h5><?php echo htmlspecialchars($trip['departure_city']); ?> → <?php echo htmlspecialchars($trip['destination_city']); ?></h5>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted">Kalkış</h6>
                                <h5><?php echo date('d.m.Y H:i', strtotime($trip['departure_time'])); ?></h5>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Varış</h6>
                                <h5><?php echo date('d.m.Y H:i', strtotime($trip['arrival_time'])); ?></h5>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted">Fiyat</h6>
                                <h4 class="price-display"><?php echo number_format($trip['price'], 2); ?> ₺</h4>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Kapasite</h6>
                                <h5><?php echo $trip['capacity']; ?> koltuk</h5>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="text-center">
                            <?php if ($trip['logo_path']): ?>
                                <img src="<?php echo htmlspecialchars($trip['logo_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($trip['company_name']); ?>" 
                                     class="img-fluid mb-3" style="max-height: 100px;">
                            <?php else: ?>
                                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ticket Purchase Form -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-ticket-alt"></i> Bilet Satın Al</h5>
            </div>
            <div class="card-body">
                <form id="ticketForm">
                    <input type="hidden" id="trip_id" value="<?php echo $trip['id']; ?>">
                    
                    <!-- Passenger Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="mb-3">Yolcu Bilgileri</h6>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="passenger_name" class="form-label">Ad Soyad</label>
                            <input type="text" class="form-control" id="passenger_name" name="passenger_name" 
                                   value="<?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="passenger_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="passenger_email" name="passenger_email" 
                                   value="<?php echo htmlspecialchars($_SESSION['user']['email'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="passenger_phone" class="form-label">Telefon</label>
                            <input type="tel" class="form-control" id="passenger_phone" name="passenger_phone" required>
                        </div>
                    </div>
                    
                    <!-- Seat Selection -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="mb-3">Koltuk Seçimi</h6>
                            <div class="seat-grid" id="seatGrid">
                                <!-- Seats will be loaded here -->
                            </div>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <span class="seat available me-3"></span> Müsait
                                    <span class="seat occupied me-3"></span> Dolu
                                    <span class="seat selected me-3"></span> Seçili
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Coupon Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="mb-3">İndirim Kuponu (İsteğe Bağlı)</h6>
                        </div>
                        <div class="col-md-8 mb-3">
                            <input type="text" class="form-control" id="coupon_code" name="coupon_code" 
                                   placeholder="Kupon kodunu girin">
                        </div>
                        <div class="col-md-4 mb-3">
                            <button type="button" class="btn btn-outline-primary w-100" onclick="validateCoupon()">
                                <i class="fas fa-tag"></i> Kuponu Uygula
                            </button>
                        </div>
                        <div class="col-12" id="couponResult" style="display: none;">
                            <!-- Coupon validation result will be shown here -->
                        </div>
                    </div>
                    
                    <!-- Price Summary -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="mb-3">Fiyat Özeti</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between">
                                                <span>Seçilen Koltuk Sayısı:</span>
                                                <span id="selectedSeatsCount">0</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Birim Fiyat:</span>
                                                <span><?php echo number_format($trip['price'], 2); ?> ₺</span>
                                            </div>
                                            <div class="d-flex justify-content-between" id="subtotalRow">
                                                <span>Ara Toplam:</span>
                                                <span id="subtotal">0.00 ₺</span>
                                            </div>
                                            <div class="d-flex justify-content-between" id="discountRow" style="display: none;">
                                                <span>İndirim:</span>
                                                <span id="discountAmount" class="text-success">-0.00 ₺</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between">
                                                <strong>Toplam:</strong>
                                                <strong id="totalPrice" class="price-display">0.00 ₺</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Purchase Button -->
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="button" class="btn btn-primary btn-lg" onclick="purchaseTicket()" id="purchaseBtn" disabled>
                                <i class="fas fa-credit-card"></i> Bilet Satın Al
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let selectedSeats = [];
let appliedCoupon = null;
let basePrice = <?php echo $trip['price']; ?>;
let reservedSeats = <?php echo json_encode($reservedSeatNumbers); ?>;

// Initialize seat grid
function initializeSeats() {
    const seatGrid = document.getElementById('seatGrid');
    const capacity = <?php echo $trip['capacity']; ?>;
    
    seatGrid.innerHTML = '';
    
    for (let i = 1; i <= capacity; i++) {
        const seat = document.createElement('div');
        seat.className = 'seat';
        seat.textContent = i;
        seat.dataset.seatNumber = i;
        
        if (reservedSeats.includes(i)) {
            seat.classList.add('occupied');
        } else {
            seat.classList.add('available');
            seat.onclick = () => toggleSeat(i);
        }
        
        seatGrid.appendChild(seat);
    }
}

// Toggle seat selection
function toggleSeat(seatNumber) {
    const seat = document.querySelector(`[data-seat-number="${seatNumber}"]`);
    const index = selectedSeats.indexOf(seatNumber);
    
    if (index > -1) {
        // Deselect seat
        selectedSeats.splice(index, 1);
        seat.classList.remove('selected');
        seat.classList.add('available');
    } else {
        // Select seat
        selectedSeats.push(seatNumber);
        seat.classList.remove('available');
        seat.classList.add('selected');
    }
    
    updatePriceSummary();
}

// Update price summary
function updatePriceSummary() {
    const count = selectedSeats.length;
    const subtotal = count * basePrice;
    
    document.getElementById('selectedSeatsCount').textContent = count;
    document.getElementById('subtotal').textContent = subtotal.toFixed(2) + ' ₺';
    
    let total = subtotal;
    let discount = 0;
    
    if (appliedCoupon && count > 0) {
        if (appliedCoupon.discount_type === 'percentage') {
            discount = (subtotal * appliedCoupon.discount_value) / 100;
        } else {
            discount = appliedCoupon.discount_value;
        }
        
        total = Math.max(0, subtotal - discount);
        
        document.getElementById('discountRow').style.display = 'flex';
        document.getElementById('discountAmount').textContent = '-' + discount.toFixed(2) + ' ₺';
    } else {
        document.getElementById('discountRow').style.display = 'none';
    }
    
    document.getElementById('totalPrice').textContent = total.toFixed(2) + ' ₺';
    
    // Enable/disable purchase button
    document.getElementById('purchaseBtn').disabled = count === 0;
}

// Validate coupon
function validateCoupon() {
    const couponCode = document.getElementById('coupon_code').value.trim();
    const resultDiv = document.getElementById('couponResult');
    
    if (!couponCode) {
        resultDiv.innerHTML = '<div class="alert alert-warning">Lütfen kupon kodunu girin.</div>';
        resultDiv.style.display = 'block';
        return;
    }
    
    fetch('ajax/validate_coupon.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'coupon_code=' + encodeURIComponent(couponCode)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            appliedCoupon = data.coupon;
            resultDiv.innerHTML = '<div class="alert alert-success">Kupon geçerli! İndirim uygulandı.</div>';
            updatePriceSummary();
        } else {
            appliedCoupon = null;
            resultDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            updatePriceSummary();
        }
        resultDiv.style.display = 'block';
    })
    .catch(error => {
        console.error('Error:', error);
        resultDiv.innerHTML = '<div class="alert alert-danger">Kupon doğrulama sırasında hata oluştu.</div>';
        resultDiv.style.display = 'block';
    });
}

// Purchase ticket
function purchaseTicket() {
    if (selectedSeats.length === 0) {
        alert('Lütfen en az bir koltuk seçin.');
        return;
    }
    
    const formData = new FormData();
    formData.append('trip_id', document.getElementById('trip_id').value);
    formData.append('passenger_name', document.getElementById('passenger_name').value);
    formData.append('passenger_email', document.getElementById('passenger_email').value);
    formData.append('passenger_phone', document.getElementById('passenger_phone').value);
    formData.append('selected_seats', JSON.stringify(selectedSeats));
    
    if (appliedCoupon) {
        formData.append('coupon_id', appliedCoupon.id);
    }
    
    fetch('ajax/purchase_ticket.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Bilet başarıyla satın alındı!');
            window.location.href = 'my_tickets.php';
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bilet satın alma sırasında hata oluştu.');
    });
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializeSeats();
    updatePriceSummary();
});
</script>

<?php require_once 'includes/footer.php'; ?>