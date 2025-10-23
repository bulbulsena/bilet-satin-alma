# Bilet Satın Alma Platformu
# README.md

## Proje Hakkında

Bu proje, modern web teknolojilerini kullanarak geliştirilmiş dinamik bir otobüs bileti satış platformudur. PHP, SQLite ve Bootstrap teknolojileri kullanılarak geliştirilmiştir.

## Özellikler

### Kullanıcı Rolleri
- **Ziyaretçi**: Sefer arayabilir, detayları görüntüleyebilir
- **Yolcu (User)**: Bilet satın alabilir, iptal edebilir, PDF indirebilir
- **Firma Admin**: Kendi firmasına ait seferleri yönetebilir
- **Admin**: Sistem genelinde yönetim yapabilir

### Ana Özellikler
- ✅ Kullanıcı kayıt/giriş sistemi
- ✅ Sefer arama ve listeleme
- ✅ Bilet satın alma ve koltuk seçimi
- ✅ Kupon sistemi ile indirim uygulama
- ✅ Bilet iptal etme (1 saat kuralı)
- ✅ PDF bilet indirme
- ✅ Firma Admin paneli (CRUD işlemleri)
- ✅ Admin paneli (Firma, Kullanıcı, Kupon yönetimi)
- ✅ Responsive tasarım
- ✅ Docker container desteği

## Teknolojiler

- **Backend**: PHP 8.1
- **Veritabanı**: SQLite
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Icons**: Font Awesome
- **Container**: Docker

## Kurulum

### Docker ile Kurulum (Önerilen)

1. Projeyi klonlayın:
```bash
git clone <repository-url>
cd bilet-satin-alma
```

2. Docker Compose ile çalıştırın:
```bash
docker-compose up -d
```

3. Tarayıcınızda `http://localhost:8080` adresine gidin.

### Manuel Kurulum

1. PHP 8.1+ ve SQLite desteği olan bir web sunucusu kurun
2. Proje dosyalarını web sunucusu dizinine kopyalayın
3. `init_database.php` dosyasını çalıştırarak veritabanını oluşturun
4. Web sunucusunu başlatın

## Demo Hesaplar

### Admin
- **Kullanıcı Adı**: admin
- **Şifre**: password

### Firma Admin
- **Kullanıcı Adı**: metro_admin
- **Şifre**: password
- **Firma**: Metro Turizm

- **Kullanıcı Adı**: ulusoy_admin
- **Şifre**: password
- **Firma**: Ulusoy

### Normal Kullanıcı
Kayıt ol sayfasından yeni hesap oluşturabilirsiniz.

## Veritabanı Şeması

### Tablolar
- `users`: Kullanıcı bilgileri
- `firms`: Otobüs firmaları
- `trips`: Seferler
- `tickets`: Biletler
- `coupons`: İndirim kuponları
- `coupon_usage`: Kupon kullanımları
- `cities`: Şehirler

## API Endpoints

### AJAX İşlemleri
- `ajax/purchase_ticket.php`: Bilet satın alma
- `ajax/cancel_ticket.php`: Bilet iptal etme
- `ajax/validate_coupon.php`: Kupon doğrulama
- `ajax/add_trip.php`: Sefer ekleme (Firma Admin)
- `ajax/edit_trip.php`: Sefer düzenleme (Firma Admin)
- `ajax/delete_trip.php`: Sefer silme (Firma Admin)
- `ajax/add_firm.php`: Firma ekleme (Admin)
- `ajax/add_firm_admin.php`: Firma Admin ekleme (Admin)
- `ajax/add_coupon.php`: Kupon ekleme (Admin)

## Güvenlik Özellikleri

- Password hashing (PHP password_hash)
- SQL injection koruması (PDO prepared statements)
- XSS koruması (htmlspecialchars)
- Session yönetimi
- Rol tabanlı erişim kontrolü
- CSRF koruması (form tokenları)

## Dosya Yapısı

```
bilet-satin-alma/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── script.js
├── config/
│   ├── auth.php
│   └── database.php
├── database/
│   └── schema.sql
├── includes/
│   ├── header.php
│   └── footer.php
├── ajax/
│   ├── purchase_ticket.php
│   ├── cancel_ticket.php
│   ├── validate_coupon.php
│   ├── add_trip.php
│   ├── edit_trip.php
│   ├── delete_trip.php
│   ├── add_firm.php
│   ├── add_firm_admin.php
│   └── add_coupon.php
├── admin_panel.php
├── firm_admin_panel.php
├── index.php
├── login.php
├── register.php
├── logout.php
├── trip_details.php
├── my_tickets.php
├── download_ticket.php
├── init_database.php
├── Dockerfile
├── docker-compose.yml
└── README.md
```

## Geliştirme

### Yeni Özellik Ekleme
1. Veritabanı şemasını güncelleyin (`database/schema.sql`)
2. İlgili PHP dosyalarını oluşturun/düzenleyin
3. AJAX işlemleri için `ajax/` dizinine dosya ekleyin
4. Frontend için JavaScript fonksiyonları ekleyin

### Test Etme
- Docker container içinde test edin
- Farklı kullanıcı rolleri ile test yapın
- Responsive tasarımı farklı cihazlarda test edin

## Lisans

Bu proje eğitim amaçlı geliştirilmiştir.

## İletişim

Proje hakkında sorularınız için issue açabilirsiniz.
