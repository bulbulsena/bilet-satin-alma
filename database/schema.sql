-- Bilet Satın Alma Platformu Veritabanı Şeması
-- ERD'ye göre güncellenmiş SQLite veritabanı için tablo yapıları

-- Kullanıcılar tablosu - Yetkilendirme yapısı
CREATE TABLE users (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    full_name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    role TEXT NOT NULL DEFAULT 'user' CHECK (role IN ('admin', 'company_admin', 'user')),
    password TEXT NOT NULL,
    company_id TEXT, -- Sadece company_admin rolü için dolu olur
    balance DECIMAL(10,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES bus_companies(id)
);

-- Otobüs firmaları tablosu
CREATE TABLE bus_companies (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    name TEXT UNIQUE NOT NULL,
    logo_path TEXT,
    contact_email TEXT,
    contact_phone TEXT,
    address TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Seferler tablosu
CREATE TABLE trips (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    company_id TEXT NOT NULL,
    destination_city TEXT NOT NULL,
    arrival_time DATETIME NOT NULL,
    departure_time DATETIME NOT NULL,
    departure_city TEXT NOT NULL,
    price INTEGER NOT NULL,
    capacity INTEGER NOT NULL,
    created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES bus_companies(id)
);

-- Biletler tablosu
CREATE TABLE tickets (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    trip_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    total_price INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Rezerve edilen koltuklar tablosu
CREATE TABLE booked_seats (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    ticket_id TEXT NOT NULL,
    seat_number INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
);

-- Kuponlar tablosu
CREATE TABLE coupons (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    code TEXT NOT NULL,
    discount_type TEXT NOT NULL DEFAULT 'percentage' CHECK (discount_type IN ('percentage', 'fixed')),
    discount_value REAL NOT NULL,
    min_amount REAL DEFAULT NULL,
    max_uses INTEGER DEFAULT NULL,
    used_count INTEGER DEFAULT 0,
    expires_at DATETIME DEFAULT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Kullanıcı-Kupon ilişki tablosu
CREATE TABLE user_coupons (
    id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
    coupon_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Şehirler tablosu (otomatik tamamlama için)
CREATE TABLE cities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    country TEXT DEFAULT 'Turkey'
);

-- Örnek şehirler ekleme
INSERT INTO cities (name) VALUES 
('İstanbul'), ('Ankara'), ('İzmir'), ('Bursa'), ('Antalya'), 
('Adana'), ('Konya'), ('Gaziantep'), ('Mersin'), ('Diyarbakır'),
('Kayseri'), ('Eskişehir'), ('Urfa'), ('Malatya'), ('Erzurum'),
('Van'), ('Batman'), ('Elazığ'), ('Isparta'), ('Trabzon');

-- Örnek otobüs firmaları
INSERT INTO bus_companies (name, logo_path) VALUES 
('Metro Turizm', '/assets/logos/metro.png'),
('Ulusoy', '/assets/logos/ulusoy.png'),
('Kamil Koç', '/assets/logos/kamilkoc.png');

-- Örnek admin kullanıcısı
INSERT INTO users (email, password, full_name, role, balance) VALUES 
('admin@bilet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sistem Yöneticisi', 'admin', 0.00);

-- Örnek Firma Admin kullanıcıları
INSERT INTO users (full_name, email, role, password, company_id, balance) VALUES 
('Metro Admin', 'admin@metro.com.tr', 'company_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 (SELECT id FROM bus_companies WHERE name = 'Metro Turizm'), 0),
('Ulusoy Admin', 'admin@ulusoy.com.tr', 'company_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 (SELECT id FROM bus_companies WHERE name = 'Ulusoy'), 0);

-- Örnek seferler
INSERT INTO trips (company_id, departure_city, arrival_city, departure_time, arrival_time, price, capacity) VALUES 
((SELECT id FROM bus_companies WHERE name = 'Metro Turizm'), 'İstanbul', 'Ankara', '2024-12-01 08:00:00', '2024-12-01 14:00:00', 150, 50),
((SELECT id FROM bus_companies WHERE name = 'Metro Turizm'), 'İstanbul', 'İzmir', '2024-12-01 10:00:00', '2024-12-01 18:00:00', 200, 50),
((SELECT id FROM bus_companies WHERE name = 'Ulusoy'), 'Ankara', 'İstanbul', '2024-12-01 09:00:00', '2024-12-01 15:00:00', 160, 50),
((SELECT id FROM bus_companies WHERE name = 'Ulusoy'), 'İzmir', 'Ankara', '2024-12-01 11:00:00', '2024-12-01 19:00:00', 180, 50);

-- Örnek kuponlar
INSERT INTO coupons (code, discount, usage_limit, expire_date) VALUES 
('WELCOME10', 10.0, 100, '2024-12-31 23:59:59'),
('SAVE50', 50.0, 50, '2024-12-31 23:59:59'),
('STUDENT20', 20.0, 200, '2024-12-31 23:59:59');