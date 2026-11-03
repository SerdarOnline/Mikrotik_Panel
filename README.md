# MikroTik Panel

PHP + TailwindCSS + jQuery + Chart.js ile geliştirilmiş MikroTik RouterOS yönetim paneli.

## Kurulum

1. Bu klasörü `ServBay/www/Mikrotik/` altında açın (zaten burada).
2. `config.php` dosyasını düzenleyin — şifrenizi yazın:
   ```php
   define('MT_PASS', 'şifreniz');
   ```
3. Tarayıcıdan açın: `http://localhost/Mikrotik/`

## MikroTik Tarafında Yapılması Gerekenler

RouterOS API'nin açık olduğunu kontrol edin:
```
/ip service enable api
/ip service set api port=8728
```

API erişimi için kullanıcı:
```
/user add name=panel-user password=sifre group=read
```

## Sayfalar

| Sayfa | Adres | Açıklama |
|-------|-------|----------|
| Dashboard | `/Mikrotik/index.php` | Genel özet, canlı grafik, top users |
| Canlı Trafik | `/Mikrotik/traffic.php` | Interface bazlı anlık bant genişliği |
| Kullanıcılar | `/Mikrotik/users.php` | DHCP leases + kim ne kadar download yapıyor |
| Bağlantılar | `/Mikrotik/connections.php` | Firewall connection tracking |
| Firewall | `/Mikrotik/firewall.php` | Filter kuralları listesi |

## Gereksinimler

- PHP 7.4+ (socket desteği açık olmalı)
- ServBay veya benzer local PHP sunucu
- MikroTik RouterOS 6.x / 7.x
- API Port: 8728 (TCP) router tarafında açık
