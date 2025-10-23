<?php
$pageTitle = "Admin Paneli";
require_once 'config/auth.php';

// Only allow admin users
$auth->requireLogin();
$auth->requireRole('admin');

// Get statistics
$stats = [
    'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'],
    'total_companies' => $db->fetchOne("SELECT COUNT(*) as count FROM bus_companies")['count'],
    'total_trips' => $db->fetchOne("SELECT COUNT(*) as count FROM trips")['count'],
    'total_tickets' => $db->fetchOne("SELECT COUNT(*) as count FROM tickets")['count'],
    'active_tickets' => $db->fetchOne("SELECT COUNT(*) as count FROM tickets WHERE status = 'active'")['count'],
    'total_revenue' => $db->fetchOne("SELECT SUM(total_price) as total FROM tickets WHERE status = 'active'")['total'] ?? 0
];

// Get recent activities
$recentTickets = $db->fetchAll("
    SELECT t.*, u.full_name, u.email, tr.departure_city, tr.destination_city, bc.name as company_name
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    JOIN trips tr ON t.trip_id = tr.id
    JOIN bus_companies bc ON tr.company_id = bc.id
    ORDER BY t.created_at DESC
    LIMIT 10
");

require_once 'includes/header.php';
?>

<!-- Admin Header -->
<nav class="navbar navbar-expand-lg navbar-dark mb-4" style="background: linear-gradient(90deg, #2d5016, #66bb6a);">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_panel.php">
            <i class="fas fa-crown"></i> Admin Paneli
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="admin_panel.php">
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
                        <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? $_SESSION['email'] ?? 'Admin'); ?>
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
                <h4><i class="fas fa-tachometer-alt"></i> Admin Paneli - Sistem Yönetimi</h4>
            </div>
            <div class="card-body">
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card text-white text-center" style="background: linear-gradient(135deg, #2d5016, #4caf50);">
                            <div class="card-body">
                                <h3><?php echo $stats['total_users']; ?></h3>
                                <small>Toplam Kullanıcı</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-white text-center" style="background: linear-gradient(135deg, #4caf50, #66bb6a);">
                            <div class="card-body">
                                <h3><?php echo $stats['total_companies']; ?></h3>
                                <small>Toplam Firma</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-white text-center" style="background: linear-gradient(135deg, #2e7d32, #4caf50);">
                            <div class="card-body">
                                <h3><?php echo $stats['total_trips']; ?></h3>
                                <small>Toplam Sefer</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-white text-center" style="background: linear-gradient(135deg, #66bb6a, #81c784);">
                            <div class="card-body">
                                <h3><?php echo $stats['active_tickets']; ?></h3>
                                <small>Aktif Bilet</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-white text-center" style="background: linear-gradient(135deg, #1b5e20, #2d5016);">
                            <div class="card-body">
                                <h3><?php echo number_format($stats['total_revenue'], 0); ?> ₺</h3>
                                <small>Toplam Gelir</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-white text-center" style="background: linear-gradient(135deg, #4caf50, #2e7d32);">
                            <div class="card-body">
                                <h3><?php echo $stats['total_tickets']; ?></h3>
                                <small>Toplam Bilet</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Management Buttons -->
                <div class="row justify-content-center">
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-building fa-3x mb-3" style="color: #2d5016;"></i>
                                <h5>Firma Yönetimi</h5>
                                <p class="text-muted">Otobüs firmalarını ekle, düzenle ve sil</p>
                                <button class="btn" style="background: linear-gradient(135deg, #2d5016, #4caf50); color: white; border: none;" data-bs-toggle="modal" data-bs-target="#companyManagementModal">
                                    <i class="fas fa-cogs"></i> Firma Yönet
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-user-tie fa-3x mb-3" style="color: #4caf50;"></i>
                                <h5>Firma Admin Yönetimi</h5>
                                <p class="text-muted">Firma admin kullanıcılarını oluştur ve ata</p>
                                <button class="btn" style="background: linear-gradient(135deg, #4caf50, #66bb6a); color: white; border: none;" data-bs-toggle="modal" data-bs-target="#companyAdminManagementModal">
                                    <i class="fas fa-user-plus"></i> Admin Oluştur
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-tags fa-3x mb-3" style="color: #2e7d32;"></i>
                                <h5>Kupon Yönetimi</h5>
                                <p class="text-muted">İndirim kuponlarını oluştur ve yönet</p>
                                <button class="btn" style="background: linear-gradient(135deg, #2e7d32, #4caf50); color: white; border: none;" data-bs-toggle="modal" data-bs-target="#couponManagementModal">
                                    <i class="fas fa-tag"></i> Kupon Yönet
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activities -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history"></i> Son Aktiviteler</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentTickets)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Henüz bilet satın alımı yok</h5>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Bilet No</th>
                                    <th>Yolcu</th>
                                    <th>Güzergah</th>
                                    <th>Firma</th>
                                    <th>Fiyat</th>
                                    <th>Tarih</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTickets as $ticket): ?>
                                <tr>
                                    <td>#<?php echo str_pad($ticket['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($ticket['full_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($ticket['email']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($ticket['departure_city']); ?> → <?php echo htmlspecialchars($ticket['destination_city']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($ticket['company_name']); ?></td>
                                    <td><?php echo number_format($ticket['total_price'], 2); ?> ₺</td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $ticket['status'] === 'active' ? 'success' : ($ticket['status'] === 'cancelled' ? 'danger' : 'secondary'); ?>">
                                            <?php echo $ticket['status'] === 'active' ? 'Aktif' : ($ticket['status'] === 'cancelled' ? 'İptal' : 'Kullanıldı'); ?>
                                        </span>
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

<!-- Company Management Modal -->
<div class="modal fade" id="companyManagementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Firma Yönetimi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <button class="btn" style="background: linear-gradient(135deg, #2d5016, #4caf50); color: white; border: none;" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
                        <i class="fas fa-plus"></i> Yeni Firma Ekle
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="companiesTable">
                        <thead>
                            <tr>
                                <th>Firma Adı</th>
                                <th>Logo</th>
                                <th>Sefer Sayısı</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Companies will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Add Company Modal -->
<div class="modal fade" id="addCompanyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Firma Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addCompanyForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Firma Adı</label>
                        <input type="text" class="form-control" id="company_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="company_logo" class="form-label">Logo Yolu</label>
                        <input type="text" class="form-control" id="company_logo" name="logo_path" placeholder="/assets/logos/firma.png">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn" style="background: linear-gradient(135deg, #2d5016, #4caf50); color: white; border: none;" onclick="submitCompanyForm()">Firma Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Company Admin Management Modal -->
<div class="modal fade" id="companyAdminManagementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Firma Admin Yönetimi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <button class="btn" style="background: linear-gradient(135deg, #4caf50, #66bb6a); color: white; border: none;" data-bs-toggle="modal" data-bs-target="#addCompanyAdminModal">
                        <i class="fas fa-user-plus"></i> Yeni Firma Admin Ekle
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="companyAdminsTable">
                        <thead>
                            <tr>
                                <th>Ad Soyad</th>
                                <th>Email</th>
                                <th>Firma</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Company admins will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Company Admin Modal -->
<div class="modal fade" id="addCompanyAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Firma Admin Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addCompanyAdminForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="admin_full_name" class="form-label">Ad Soyad</label>
                        <input type="text" class="form-control" id="admin_full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="admin_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="admin_password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_company_id" class="form-label">Firma</label>
                        <select class="form-select" id="admin_company_id" name="company_id" required>
                            <option value="">Firma Seçin</option>
                            <!-- Companies will be loaded here -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn" style="background: linear-gradient(135deg, #4caf50, #66bb6a); color: white; border: none;" onclick="submitCompanyAdminForm()">Firma Admin Ekle</button>
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
                    <button class="btn" style="background: linear-gradient(135deg, #2e7d32, #4caf50); color: white; border: none;" data-bs-toggle="modal" data-bs-target="#addCouponModal">
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
                    <button type="button" class="btn" style="background: linear-gradient(135deg, #2e7d32, #4caf50); color: white; border: none;" onclick="submitCouponForm()">Kupon Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Load data when modals are opened
document.getElementById('companyManagementModal').addEventListener('shown.bs.modal', function() {
    loadCompanies();
});

document.getElementById('companyAdminManagementModal').addEventListener('shown.bs.modal', function() {
    loadCompanyAdmins();
    loadCompaniesForSelect();
});

document.getElementById('couponManagementModal').addEventListener('shown.bs.modal', function() {
    loadCoupons();
});

// Load companies
function loadCompanies() {
    fetch('ajax/get_companies.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#companiesTable tbody');
            tbody.innerHTML = '';
            
            data.forEach(company => {
                const row = `
                    <tr>
                        <td>${company.name}</td>
                        <td>${company.logo_path ? '<img src="' + company.logo_path + '" height="30">' : '-'}</td>
                        <td>${company.trip_count}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editCompany('${company.id}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCompany('${company.id}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        });
}


// Load company admins
function loadCompanyAdmins() {
    fetch('ajax/get_company_admins.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#companyAdminsTable tbody');
            tbody.innerHTML = '';
            
            data.forEach(admin => {
                const row = `
                    <tr>
                        <td>${admin.full_name}</td>
                        <td>${admin.email}</td>
                        <td>${admin.company_name || '-'}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCompanyAdmin('${admin.id}')">
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
                        <td>${coupon.usage_limit || 'Sınırsız'}</td>
                        <td>${coupon.used_count}</td>
                        <td>${coupon.expiry_date || 'Süresiz'}</td>
                        <td>
                            <span class="badge bg-${coupon.is_active ? 'success' : 'danger'}">
                                ${coupon.is_active ? 'Aktif' : 'Pasif'}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editCoupon(${coupon.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCoupon(${coupon.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        });
}

// Load companies for select
function loadCompanyAdmins() {
    fetch('ajax/get_company_admins.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#companyAdminsTable tbody');
            if (tbody) {
                tbody.innerHTML = '';
                data.forEach(admin => {
                    const row = `
                        <tr>
                            <td>${admin.full_name}</td>
                            <td>${admin.email}</td>
                            <td>${admin.company_name}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteCompanyAdmin('${admin.id}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }
        });
}

function loadCoupons() {
    fetch('ajax/get_coupons.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#couponsTable tbody');
            if (tbody) {
                tbody.innerHTML = '';
                data.forEach(coupon => {
                    const discountText = coupon.discount_type === 'percentage' 
                        ? `%${coupon.discount_value}` 
                        : `₺${coupon.discount_value}`;
                    
                    const row = `
                        <tr>
                            <td><code>${coupon.code}</code></td>
                            <td>${discountText}</td>
                            <td>${coupon.min_amount ? '₺' + coupon.min_amount : '-'}</td>
                            <td>${coupon.used_count}/${coupon.max_uses || '∞'}</td>
                            <td>${coupon.expires_at ? new Date(coupon.expires_at).toLocaleDateString('tr-TR') : 'Süresiz'}</td>
                            <td>
                                <span class="badge bg-${coupon.is_active ? 'success' : 'danger'}">
                                    ${coupon.is_active ? 'Aktif' : 'Pasif'}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="toggleCoupon('${coupon.id}')">
                                    <i class="fas fa-${coupon.is_active ? 'pause' : 'play'}"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteCoupon('${coupon.id}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }
        });
}

function loadCompaniesForSelect() {
    fetch('ajax/get_companies.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('admin_company_id');
            if (select) {
                select.innerHTML = '<option value="">Firma Seçin</option>';
                data.forEach(company => {
                    select.innerHTML += `<option value="${company.id}">${company.name}</option>`;
                });
            }
        });
}


// Form submissions
function submitCompanyForm() {
    const form = document.getElementById('addCompanyForm');
    const formData = new FormData(form);
    
    console.log('Form submit edildi');
    
    // Form verilerini kontrol et
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }
    
    fetch('ajax/add_company.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Response is not JSON. Content-Type: ' + contentType);
        }
        
        return response.text().then(text => {
            console.log('Raw response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Invalid JSON response: ' + text);
            }
        });
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            alert('Firma başarıyla eklendi.');
            bootstrap.Modal.getInstance(document.getElementById('addCompanyModal')).hide();
            loadCompanies();
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


// Firma Admin ekleme fonksiyonu
function submitCompanyAdminForm() {
    const form = document.getElementById('addCompanyAdminForm');
    const formData = new FormData(form);
    
    fetch('ajax/add_company_admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Firma admin başarıyla oluşturuldu.');
            bootstrap.Modal.getInstance(document.getElementById('addCompanyAdminModal')).hide();
            loadCompanyAdmins();
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

// Kupon ekleme fonksiyonu
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

document.getElementById('addCompanyAdminForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('ajax/add_company_admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Firma admin başarıyla eklendi.');
            bootstrap.Modal.getInstance(document.getElementById('addCompanyAdminModal')).hide();
            loadCompanyAdmins();
            this.reset();
        } else {
            alert('Hata: ' + data.message);
        }
    });
});

document.getElementById('addCouponForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('ajax/add_coupon.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Kupon başarıyla eklendi.');
            bootstrap.Modal.getInstance(document.getElementById('addCouponModal')).hide();
            loadCoupons();
            this.reset();
        } else {
            alert('Hata: ' + data.message);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
