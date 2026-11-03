<?php
/**
 * lib/db.php — SQLite3 Veritabanı Yöneticisi
 */

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dbFile = defined('DB_FILE') ? DB_FILE : __DIR__ . '/../database.sqlite';
            self::$instance = new PDO('sqlite:' . $dbFile);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            self::initTables();
        }
        return self::$instance;
    }

    private static function initTables(): void {
        $db = self::$instance;
        
        // Tabloları oluştur
        $db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT
            );

            CREATE TABLE IF NOT EXISTS vouchers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT NOT NULL,
                price REAL DEFAULT 0,
                profile TEXT DEFAULT 'default',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS routers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                host TEXT NOT NULL,
                user TEXT NOT NULL,
                pass TEXT NOT NULL,
                port INTEGER DEFAULT 8728,
                is_active INTEGER DEFAULT 0
            );

            CREATE TABLE IF NOT EXISTS mac_vendors (
                prefix TEXT PRIMARY KEY,
                vendor TEXT NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS log_5651 (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tc_no TEXT,
                full_name TEXT NOT NULL,
                phone TEXT,
                voucher_code TEXT NOT NULL,
                mac_address TEXT NOT NULL,
                ip_address TEXT NOT NULL,
                login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                logout_time DATETIME,
                bytes_in INTEGER DEFAULT 0,
                bytes_out INTEGER DEFAULT 0,
                sha256_hash TEXT
            );

            CREATE TABLE IF NOT EXISTS staff_printers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                staff_name TEXT NOT NULL,
                printer_ip TEXT NOT NULL,
                printer_port INTEGER DEFAULT 9100,
                is_default INTEGER DEFAULT 0
            );
        ");

        // Varsayılan ayarları yükle
        $defaults = [
            'business_name'          => 'LAGOON HOTSPOT & WIFI',
            'phone'                  => '0555 123 4567',
            'footer_note'            => 'Ağa bağlandığınızda çıkan ekrana kupon kodunu yazın.',
            'sound_alert'            => 'true',
            'high_speed_limit_mbps'  => '3',
            'hotspot_dns_name'       => 'wifi.lagoon.com',
            'hotspot_html_dir'       => 'hotspot',
            'login_bg_color'         => '#0f172a',
            'login_accent_color'     => '#3b82f6',
            'login_title'            => 'Hoş Geldiniz — İnternet Erişimi',
            'login_welcome_msg'      => 'Lütfen resepsiyondan veya kasadan aldığınız kupon kodunu giriniz.',
        ];

        foreach ($defaults as $k => $v) {
            $db->exec("INSERT OR IGNORE INTO settings (key, value) VALUES ('$k', '$v')");
        }
    }

    public static function getSetting(string $key, string $default = ''): string {
        $db = self::getConnection();
        $stmt = $db->prepare("SELECT value FROM settings WHERE key = :k");
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    }

    public static function setSetting(string $key, string $value): void {
        $db = self::getConnection();
        $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (:k, :v)");
        $stmt->execute([':k' => $key, ':v' => $value]);
    }
}
