<?php
$pageTitle = "Firma Admin Paneli";
require_once 'config/auth.php';

// Only allow company admin users
$auth->requireLogin();
$auth->requireRole('company_admin');

// Get company information
$company = $auth->getCompany();
if (!$company) {
    $_SESSION['error_message'] = 'Firma bilgisi bulunamadı.';
    header('Location: index.php');
    exit;
}

// Get statistics
$stats = [
    'total_trips' => $db->fetchOne("SELECT COUNT(*) as count FROM trips WHERE company_id = ?", [$company['id']])['count'],
    'active_trips' => $db->fetchOne("SELECT COUNT(*) as count FROM trips WHERE company_id = ? AND departure_time > datetime('now')", [$company['id']])['count'],
    'total_tickets' => $db->fetchOne("SELECT COUNT(*) as count FROM tickets t JOIN trips tr ON t.trip_id = tr.id WHERE tr.company_id = ?", [$company['id']])['count'],
    'total_revenue' => $db->fetchOne("SELECT SUM(t.total_price) as total FROM tickets t JOIN trips tr ON t.trip_id = tr.id WHERE tr.company_id = ? AND t.status = 'active'", [$company['id']])['total'] ?? 0
];

// Get recent trips
$recentTrips = $db->fetchAll("
    SELECT t.*, COUNT(tk.id) as ticket_count, SUM(tk.total_price) as revenue
    FROM trips t
    LEFT JOIN tickets tk ON t.id = tk.trip_id
    WHERE t.company_id = ?
    GROUP BY t.id
    ORDER BY t.departure_time DESC
    LIMIT 10
", [$company['id']]);

require_once 'includes/header.php';
?>

<!-- Firma Admin Header -->
<nav class="navbar navbar-expand-lg navbar-dark mb-4" style="background: linear-gradient(90deg, #2d5016, #66bb6a);">
    <div class="container-fluid">
        <a class="navbar-brand" href="firm_admin_panel.php">
            <i class="fas fa-building"></i> <?php echo htmlspecialchars($company['name']); ?> - Admin Paneli
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="firm_admin_panel.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home"></i> Ana Sayfa
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? $_SESSION['email'] ?? 'Firma Admin'); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header">
                <h4><i class="fas fa-building"></i> <?php echo htmlspecialchars($company['name']); ?> - Firma Yönetimi</h4>
            </div>
            <div class="card-body">
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white text-center" style="background: linear-gradient(135deg, #2d5016, #4caf50);">
                            <div class="card-body">
                                <h3><?php echo $stats['total_trips']; ?></h3>
                                <small>Toplam Sefer</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white text-center" style="background: linear-gradient(135deg, #4caf50, #66bb6a);">
                            <div class="card-body">
                                <h3><?php echo $stats['active_trips']; ?></h3>
                                <small>Aktif Sefer</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white text-center" style="background: linear-gradient(135deg, #2e7d32, #4caf50);">
                            <div class="card-body">
                                <h3><?php echo $stats['total_tickets']; ?></h3>
                                <small>Satılan Bilet</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white text-center" style="background: linear-gradient(135deg, #1b5e20, #2d5016);">
                            <div class="card-body">
                                <h3><?php echo number_format($stats['total_revenue'], 0); ?> ₺</h3>
                                <small>Toplam Gelir</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Management Buttons -->
                <div class="row justify-content-center">
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-bus fa-3x mb-3" style="color: #2d5016;"></i>
                                <h5>Sefer Yönetimi</h5>
                                <p class="text-muted">Yeni sefer ekle, düzenle ve sil</p>
                                <button class="btn" style="background: linear-gradient(135deg, #2d5016, #4caf50); color: white; border: none;" data-bs-toggle="modal" data-bs-target="#tripManagementModal">
                                    <i class="fas fa-plus"></i> Sefer Yönet
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-tags fa-3x mb-3" style="color: #4caf50;"></i>
                                <h5>Kupon Yönetimi</h5>
                                <p class="text-muted">İndirim kuponları oluştur ve yönet</p>
                                <button class="btn" style="background: linear-gradient(135deg, #4caf50, #66bb6a); color: white; border: none;" data-bs-toggle="modal" data-bs-target="#couponManagementModal">
                                    <i class="fas fa-tag"></i> Kupon Yönet
                            </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                        </div>
                        
        <!-- Recent Trips -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bus"></i> Son Seferler</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentTrips)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-bus fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Henüz sefer eklenmemiş</h5>
                        <p class="text-muted">İlk seferinizi eklemek için "Sefer Yönet" butonuna tıklayın.</p>
                    </div>
                <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Güzergah</th>
                                        <th>Kalkış</th>
                                        <th>Varış</th>
                                        <th>Fiyat</th>
                                        <th>Kapasite</th>
                                    <th>Bilet Sayısı</th>
                                    <th>Gelir</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recentTrips as $trip): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($trip['departure_city']); ?> → <?php echo htmlspecialchars($trip['destination_city']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($trip['departure_time'])); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($trip['arrival_time'])); ?></td>
                                        <td><?php echo number_format($trip['price'], 2); ?> ₺</td>
                                        <td><?php echo $trip['capacity']; ?></td>
                                    <td><?php echo $trip['ticket_count']; ?></td>
                                    <td><?php echo number_format($trip['revenue'] ?? 0, 2); ?> ₺</td>
                                    <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editTrip('<?php echo $trip['id']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteTrip('<?php echo $trip['id']; ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

<!-- Trip Management Modal -->
<div class="modal fade" id="tripManagementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sefer Yönetimi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <button class="btn" style="background: linear-gradient(135deg, #2d5016, #4caf50); color: white; border: none;" data-bs-toggle="modal" data-bs-target="#addTripModal">
                        <i class="fas fa-plus"></i> Yeni Sefer Ekle
                    </button>
                    </div>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="tripsTable">
                        <thead>
                            <tr>
                                <th>Güzergah</th>
                                <th>Kalkış</th>
                                <th>Varış</th>
                                <th>Fiyat</th>
                                <th>Kapasite</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Trips will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Trip Modal -->
<div class="modal fade" id="addTripModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Sefer Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addTripForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="departure_city" class="form-label">Kalkış Şehri</label>
                            <input type="text" class="form-control" id="departure_city" name="departure_city" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="destination_city" class="form-label">Varış Şehri</label>
                            <input type="text" class="form-control" id="destination_city" name="destination_city" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="departure_time" class="form-label">Kalkış Zamanı</label>
                            <input type="datetime-local" class="form-control" id="departure_time" name="departure_time" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="arrival_time" class="form-label">Varış Zamanı</label>
                            <input type="datetime-local" class="form-control" id="arrival_time" name="arrival_time" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Fiyat (₺)</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="capacity" class="form-label">Kapasite</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn" style="background: linear-gradient(135deg, #2d5016, #4caf50); color: white; border: none;" onclick="submitTripForm()">Sefer Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Coupon Management Modal -->
<div class="modal fade" id="couponManagementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kupon Yönetimi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <button class="btn" style="background: linear-gradient(135deg, #4caf50, #66bb6a); color: white; border: none;" data-bs-toggle="modal" data-bs-target="#addCouponModal">
                        <i class="fas fa-plus"></i> Yeni Kupon Ekle
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="couponsTable">
                        <thead>
                            <tr>
                                <th>Kupon Kodu</th>
                                <th>İndirim Türü</th>
                                <th>İndirim Değeri</th>
                                <th>Kullanım Limiti</th>
                                <th>Kullanılan</th>
                                <th>Son Kullanma</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Coupons will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Coupon Modal -->
<div class="modal fade" id="addCouponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Kupon Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addCouponForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="coupon_code" class="form-label">Kupon Kodu</label>
                        <input type="text" class="form-control" id="coupon_code" name="code" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="discount_type" class="form-label">İndirim Türü</label>
                            <select class="form-select" id="discount_type" name="discount_type" required>
                                <option value="">Seçin</option>
                                <option value="percentage">Yüzde (%)</option>
                                <option value="fixed">Sabit Tutar (₺)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="discount_value" class="form-label">İndirim Değeri</label>
                            <input type="number" class="form-control" id="discount_value" name="discount_value" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="min_amount" class="form-label">Minimum Tutar (₺)</label>
                        <input type="number" class="form-control" id="min_amount" name="min_amount" step="0.01" min="0">
                        <div class="form-text">Boş bırakırsanız minimum tutar yok</div>
                    </div>
                    <div class="mb-3">
                        <label for="max_uses" class="form-label">Kullanım Limiti</label>
                        <input type="number" class="form-control" id="max_uses" name="max_uses" min="1">
                        <div class="form-text">Boş bırakırsanız sınırsız kullanım</div>
                    </div>
                    <div class="mb-3">
                        <label for="expires_at" class="form-label">Son Kullanma Tarihi</label>
                        <input type="date" class="form-control" id="expires_at" name="expires_at">
                        <div class="form-text">Boş bırakırsanız süresiz</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn" style="background: linear-gradient(135deg, #4caf50, #66bb6a); color: white; border: none;" onclick="submitCouponForm()">Kupon Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Load data when modals are opened
document.getElementById('tripManagementModal').addEventListener('shown.bs.modal', function() {
    loadTrips();
});

document.getElementById('couponManagementModal').addEventListener('shown.bs.modal', function() {
    loadCoupons();
});

// Load trips
function loadTrips() {
    fetch('ajax/get_trips.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#tripsTable tbody');
            tbody.innerHTML = '';
            
            data.forEach(trip => {
                const row = `
                    <tr>
                        <td>${trip.departure_city} → ${trip.destination_city}</td>
                        <td>${new Date(trip.departure_time).toLocaleString('tr-TR')}</td>
                        <td>${new Date(trip.arrival_time).toLocaleString('tr-TR')}</td>
                        <td>${trip.price} ₺</td>
                        <td>${trip.capacity}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editTrip('${trip.id}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteTrip('${trip.id}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        });
}

// Load coupons
function loadCoupons() {
    fetch('ajax/get_coupons.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#couponsTable tbody');
            tbody.innerHTML = '';
            
            data.forEach(coupon => {
                const row = `
                    <tr>
                        <td><strong>${coupon.code}</strong></td>
                        <td>${coupon.discount_type === 'percentage' ? 'Yüzde' : 'Sabit'}</td>
                        <td>${coupon.discount_value}${coupon.discount_type === 'percentage' ? '%' : ' ₺'}</td>
                        <td>${coupon.max_uses || 'Sınırsız'}</td>
                        <td>${coupon.used_count || 0}</td>
                        <td>${coupon.expires_at || 'Süresiz'}</td>
                        <td>
                            <span class="badge bg-${coupon.is_active ? 'success' : 'danger'}">
                                ${coupon.is_active ? 'Aktif' : 'Pasif'}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editCoupon('${coupon.id}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCoupon('${coupon.id}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        });
}

// Form submissions
function submitTripForm() {
    const form = document.getElementById('addTripForm');
    const formData = new FormData(form);
    
    fetch('ajax/add_trip_company_admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Sefer başarıyla eklendi.');
            bootstrap.Modal.getInstance(document.getElementById('addTripModal')).hide();
            loadTrips();
            form.reset();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Bir hata oluştu: ' + error.message);
    });
}

function submitCouponForm() {
    const form = document.getElementById('addCouponForm');
    const formData = new FormData(form);
    
    fetch('ajax/add_coupon.php', {
            method: 'POST',
        body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
            alert('Kupon başarıyla oluşturuldu.');
            bootstrap.Modal.getInstance(document.getElementById('addCouponModal')).hide();
            loadCoupons();
            form.reset();
            } else {
                alert('Hata: ' + data.message);
            }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Bir hata oluştu: ' + error.message);
        });
}
</script>

<?php require_once 'includes/footer.php'; ?>