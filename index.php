<?php
$pageTitle = "Ana Sayfa";
require_once 'config/auth.php';
require_once 'includes/header.php';

// Get cities for dropdown
$cities = $db->fetchAll("SELECT DISTINCT name FROM cities ORDER BY name");

// Handle search
$trips = [];
$searchParams = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $departureCity = $_POST['departure_city'] ?? '';
    $arrivalCity = $_POST['arrival_city'] ?? '';
    $departureDate = $_POST['departure_date'] ?? '';
    
    if ($departureCity && $arrivalCity && $departureDate) {
        $searchParams = [
            'departure_city' => $departureCity,
            'arrival_city' => $arrivalCity,
            'departure_date' => $departureDate
        ];
        
// Get trips for this search
$trips = $db->fetchAll("
    SELECT t.*, bc.name as company_name, bc.logo_path
    FROM trips t 
    JOIN bus_companies bc ON t.company_id = bc.id 
    WHERE t.departure_city = ? 
    AND t.destination_city = ? 
    AND DATE(t.departure_time) = ?
    ORDER BY t.departure_time
", [$departureCity, $arrivalCity, $departureDate]);
    }
}
?>

<div class="row">
    <div class="col-12">
        <!-- Search Form -->
        <div class="search-form">
            <h2 class="text-center mb-4">
                <i class="fas fa-search"></i> Sefer Ara
            </h2>
            <form id="searchForm" method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="departure_city" class="form-label">Kalkış Şehri</label>
                        <select class="form-select" id="departure_city" name="departure_city" required>
                            <option value="">Şehir Seçin</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo htmlspecialchars($city['name']); ?>" 
                                    <?php echo (isset($searchParams['departure_city']) && $searchParams['departure_city'] === $city['name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($city['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="arrival_city" class="form-label">Varış Şehri</label>
                        <select class="form-select" id="arrival_city" name="arrival_city" required>
                            <option value="">Şehir Seçin</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo htmlspecialchars($city['name']); ?>" 
                                    <?php echo (isset($searchParams['arrival_city']) && $searchParams['arrival_city'] === $city['name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($city['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="departure_date" class="form-label">Tarih</label>
                        <input type="date" class="form-control" id="departure_date" name="departure_date" 
                               value="<?php echo $searchParams['departure_date'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" name="search" class="btn btn-light btn-lg w-100">
                            <i class="fas fa-search"></i> Sefer Ara
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Search Results -->
        <?php if (!empty($trips)): ?>
            <div class="row">
                <div class="col-12">
                    <h3 class="mb-3">
                        <i class="fas fa-bus"></i> 
                        <?php echo htmlspecialchars($searchParams['departure_city']); ?> - 
                        <?php echo htmlspecialchars($searchParams['arrival_city']); ?> 
                        Seferleri
                    </h3>
                    
                    <?php foreach ($trips as $trip): ?>
                        <div class="card trip-card mb-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <div class="text-center">
                                            <i class="fas fa-building fa-2x text-primary mb-2"></i>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($trip['company_name']); ?></h6>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h6 class="text-muted mb-1">Kalkış</h6>
                                            <h5 class="mb-0"><?php echo date('H:i', strtotime($trip['departure_time'])); ?></h5>
                                            <small class="text-muted"><?php echo date('d.m.Y', strtotime($trip['departure_time'])); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-1 text-center">
                                        <i class="fas fa-arrow-right text-muted"></i>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h6 class="text-muted mb-1">Varış</h6>
                                            <h5 class="mb-0"><?php echo date('H:i', strtotime($trip['arrival_time'])); ?></h5>
                                            <small class="text-muted"><?php echo date('d.m.Y', strtotime($trip['arrival_time'])); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <div class="text-center">
                                            <h6 class="text-muted mb-1">Fiyat</h6>
                                            <h4 class="price-display mb-0"><?php echo number_format($trip['price'], 2); ?> ₺</h4>
                                            <small class="text-muted">Kapasite: <?php echo $trip['capacity']; ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-1">
                                        <div class="text-center">
                                            <?php if ($auth->isLoggedIn() && $auth->isUser()): ?>
                                                <a href="trip_details.php?id=<?php echo $trip['id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-ticket-alt"></i> Bilet Al
                                                </a>
                                            <?php else: ?>
                                                <a href="login.php" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-sign-in-alt"></i> Giriş Yap
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i>
                Aradığınız kriterlere uygun sefer bulunamadı.
            </div>
        <?php endif; ?>
        
        <!-- Popular Routes -->
        <?php if (empty($trips) && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <h3 class="text-center mb-4">Popüler Rotalar</h3>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-route fa-3x text-primary mb-3"></i>
                                    <h5>İstanbul - Ankara</h5>
                                    <p class="text-muted">Günlük 20+ sefer</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-route fa-3x text-primary mb-3"></i>
                                    <h5>İstanbul - İzmir</h5>
                                    <p class="text-muted">Günlük 15+ sefer</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-route fa-3x text-primary mb-3"></i>
                                    <h5>Ankara - İzmir</h5>
                                    <p class="text-muted">Günlük 10+ sefer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
