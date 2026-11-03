<?php
// ============================================================================
//  MikroTik Enterprise Yönetim Paneli — Merkezi Konfigürasyon Dosyası
//  Tüm sistem, RouterOS, SQLite3, Hotspot ve 5651 Yasal Ayarları Buradan Yönetilir
// ============================================================================

// 1. RouterOS API Bağlantı Bilgileri
define('MT_HOST',     '192.168.0.1');      // MikroTik Router IP adresi
define('MT_USER',     'admin');             // API Kullanıcı Adı
define('MT_PASS',     '48518790');          // API Şifresi
define('MT_PORT',     8728);                // API Portu (8728: Normal, 8729: SSL)
define('MT_TIMEOUT',  10);                  // Bağlantı Zaman Aşımı (saniye)

// 2. Yerel SQLite3 Veritabanı Yolu
define('DB_FILE',     __DIR__ . '/database.sqlite');

// 3. İşletme & Hotspot Varsayılan Bilgileri
define('BUSINESS_NAME',        'LAGOON HOTSPOT & WIFI');
define('BUSINESS_PHONE',       '0555 123 4567');
define('HOTSPOT_DNS_NAME',     'wifi.lagoon.com');
define('HOTSPOT_HTML_DIR',     'hotspot');
define('DEFAULT_FOOTER_NOTE',  '5651 Sayılı Kanun Gereğince İnternet Erişimi Kayıt Altına Alınmaktadır.');

// 4. 5651 Sayılı Kanun & BTK Yasal Uyum Ayarları
define('LAW5651_ENABLED',      true);
define('LAW5651_ORGANIZATION', 'LAGOON HOTEL & ENTERPRISE');
define('LAW5651_HASH_ALGO',    'sha256');  // BTK Resmi SHA-256 Hash Standartı

// 5. ESC/POS Termal Fiş Yazıcısı Donanım Ayarları
define('PRINTER_IP',             '192.168.0.200'); // Ağ Termal Fiş Yazıcısı IP Adresi
define('PRINTER_PORT',           9100);            // RAW TCP Portu (Standart: 9100)

// 6. Panel Performans & Trafik İkaz Eşik Ayarları
define('PANEL_TITLE',            'MikroTik Enterprise Yönetim Paneli');
define('REFRESH_INTERVAL',       5000);     // Canlı Grafik Yenileme Sıklığı (ms)
define('TOP_USER_COUNT',         15);       // En Çok İndirme Yapan Cihaz Sayısı
define('HIGH_SPEED_LIMIT_MBPS',  3.0);      // Yüksek Trafik Uyarısı Eşik Hızı (Mbps)
define('SOUND_ALERT_ENABLED',    true);     // Yüksek Trafik Sesli Uyarısı
define('SPEED_UNIT',             'auto');   // Hız Birimi ('auto', 'Mbps', 'Kbps')
define('THEME',                  'dark');   // Arayüz Renk Teması ('dark' / 'light')

// 6. Dinamik Ayar Yardımcısı (Önce SQLite Veritabanından Alır, Yoksa Config'den Okur)
if (!function_exists('get_config')) {
    function get_config(string $key, $default = null) {
        // SQLite veritabanı yüklüyse veritabanından dinamik oku
        if (class_exists('Database')) {
            try {
                $val = Database::getSetting($key);
                if ($val !== '') return $val;
            } catch (Exception $e) {}
        }
        
        // Sabit tanımlardan kontrol et
        $constName = strtoupper($key);
        if (defined($constName)) {
            return constant($constName);
        }
        
        return $default;
    }
}
