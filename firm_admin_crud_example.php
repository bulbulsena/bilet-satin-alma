<?php
/**
 * Firma Admin CRUD Örneği
 * 
 * Bu dosya, Firma Admin'in sadece kendi firmasının seferlerini
 * nasıl yönetebileceğini gösterir.
 */

$pageTitle = "Firma Admin CRUD Örneği";
require_once 'config/auth.php';

// Sadece Firma Admin erişimi
$auth->requireRole('company_admin');

$user = $auth->getUser();
$company = $auth->getCompany();

// Firma Admin'in kendi firmasının seferlerini getir
$companyFilter = $auth->getCompanyFilter();
$filterParams = $auth->getCompanyFilterParams();

// Seferleri listele (sadece kendi firmasının)
$trips = $db->fetchAll("
    SELECT t.*, bc.name as company_name
    FROM trips t
    JOIN bus_companies bc ON t.company_id = bc.id
    " . ($companyFilter ? "WHERE " . $companyFilter : "") . "
    ORDER BY t.departure_time DESC
    LIMIT 10
", $filterParams);

require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-building"></i> <?php echo htmlspecialchars($company['name']); ?> - Sefer Yönetimi</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Firma Admin CRUD Kısıtlaması:</strong>  
                        Bu sayfada sadece <strong><?php echo htmlspecialchars($company['name']); ?></strong> firmasına ait seferler görüntülenir.
                    </div>
                    
                    <h5>SQL Sorgusu:</h5>
                    <pre class="bg-light p-3"><code>SELECT t.*, bc.name as company_name
FROM trips t
JOIN bus_companies bc ON t.company_id = bc.id
<?php echo $companyFilter ? "WHERE " . $companyFilter : ""; ?>
ORDER BY t.departure_time DESC</code></pre>
                    
                    <h5>Filtre Parametreleri:</h5>
                    <pre class="bg-light p-3"><code><?php echo json_encode($filterParams, JSON_PRETTY_PRINT); ?></code></pre>
                    
                    <h5 class="mt-4">Seferler (Sadece Kendi Firması):</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Firma</th>
                                    <th>Güzergah</th>
                                    <th>Kalkış</th>
                                    <th>Varış</th>
                                    <th>Fiyat</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trips as $trip): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($trip['company_name']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($trip['departure_city']); ?> → 
                                        <?php echo htmlspecialchars($trip['destination_city']); ?>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($trip['departure_time'])); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($trip['arrival_time'])); ?></td>
                                    <td><?php echo number_format($trip['price'], 2); ?> ₺</td>
                                    <td>
                                        <?php if ($auth->canManageTrip($trip['id'])): ?>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editTrip('<?php echo $trip['id']; ?>')">
                                                <i class="fas fa-edit"></i> Düzenle
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteTrip('<?php echo $trip['id']; ?>')">
                                                <i class="fas fa-trash"></i> Sil
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">Yetkisiz</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <h5>CRUD İşlemleri:</h5>
                        <ul>
                            <li><strong>Create (Ekleme):</strong> Sadece kendi firmasına ait sefer ekleyebilir</li>
                            <li><strong>Read (Okuma):</strong> Sadece kendi firmasının seferlerini görebilir</li>
                            <li><strong>Update (Güncelleme):</strong> Sadece kendi firmasının seferlerini düzenleyebilir</li>
                            <li><strong>Delete (Silme):</strong> Sadece kendi firmasının seferlerini silebilir</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editTrip(tripId) {
    alert('Sefer düzenleme: ' + tripId + '\n\nFirma Admin sadece kendi firmasının seferlerini düzenleyebilir.');
}

function deleteTrip(tripId) {
    if (confirm('Bu seferi silmek istediğinizden emin misiniz?\n\nFirma Admin sadece kendi firmasının seferlerini silebilir.')) {
        alert('Sefer silme işlemi: ' + tripId);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
