<?php
/**
 * includes/header.php
 * Tüm sayfalarda kullanılan ortak HTML başlık
 */
$pageTitle  = $pageTitle  ?? 'Dashboard';
$activePage = $activePage ?? 'index';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — MikroTik Paneli</title>
  <meta name="description" content="MikroTik RouterOS yönetim paneli — gerçek zamanlı trafik izleme ve kullanıcı analizi">
  <meta name="robots" content="noindex, nofollow">

  <!-- TailwindCSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            'mt-dark': '#0a0e1a',
            'mt-card': '#111827',
          }
        }
      }
    }
  </script>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="./assets/css/app.css">

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <!-- CSChart -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="dark">

<!-- ── Sidebar ── -->
<nav id="sidebar">
  <div class="logo">
    <div class="logo-icon">🛡️</div>
    <div class="logo-text">
      MikroTik Panel
      <span id="router-name">Bağlanıyor...</span>
    </div>
  </div>

  <div id="conn-badge" class="disconnected">
    <span class="dot"></span>
    <span id="conn-text">Bağlantı kontrol ediliyor</span>
  </div>

  <nav>
    <!-- 1. GENEL BAKIŞ & TRAFİK -->
    <div class="nav-section">📊 Genel Bakış</div>
    <a href="./index.php" class="<?= $activePage === 'index' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-chart-pie"></i></span>
      Dashboard
    </a>
    <a href="./traffic.php" class="<?= $activePage === 'traffic' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-wave-square"></i></span>
      Canlı Trafik
    </a>
    <a href="./users.php" class="<?= $activePage === 'users' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-users"></i></span>
      Cihazlar & Müşteriler
    </a>
    <a href="./limits.php" class="<?= $activePage === 'limits' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-user-slash" style="color:var(--warning);"></i></span>
      Ban & Limit Paneli
    </a>

    <!-- 2. AĞ & TOPOLOJİ YÖNETİMİ -->
    <div class="nav-section">🌐 Ağ & Topoloji</div>
    <a href="./access_points.php" class="<?= $activePage === 'access_points' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-wifi" style="color:var(--accent2);"></i></span>
      Access Point (AP) Cihazları
    </a>
    <a href="./nat.php" class="<?= $activePage === 'nat' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-arrow-right-to-city" style="color:var(--success);"></i></span>
      NAT & Port Yönlendirme
    </a>
    <a href="./firewall.php" class="<?= $activePage === 'firewall' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-shield-halved"></i></span>
      Firewall Kuralları
    </a>
    <a href="./neighbors.php" class="<?= $activePage === 'neighbors' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-sitemap" style="color:var(--accent);"></i></span>
      Komşu Cihaz Taraması
    </a>
    <a href="./connections.php" class="<?= $activePage === 'connections' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-network-wired"></i></span>
      Aktif Bağlantılar
    </a>

    <!-- 3. HOTSPOT & PPPOE (MİKHMON ENTERPRISE) -->
    <div class="nav-section">🎟️ Hotspot & PPPoE</div>
    <a href="./pos.php" class="<?= $activePage === 'pos' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-cash-register" style="color:var(--success);"></i></span>
      ⚡ Hızlı POS Satış Terminali
    </a>
    <a href="./pos_mobile.php" target="_blank" class="<?= $activePage === 'pos_mobile' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-mobile-screen-button" style="color:var(--accent2);"></i></span>
      📱 Mobil POS (Telefon Ekranı)
    </a>
    <a href="./hotspot.php" class="<?= $activePage === 'hotspot' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-ticket" style="color:var(--accent);"></i></span>
      Hotspot & Kuponlar
    </a>
    <a href="./hotspot_profiles.php" class="<?= $activePage === 'hotspot_profiles' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-layer-group" style="color:var(--accent2);"></i></span>
      Hotspot Profilleri
    </a>
    <a href="./hotspot_sales.php" class="<?= $activePage === 'hotspot_sales' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-sack-dollar" style="color:var(--success);"></i></span>
      Satış & Gelir Raporları
    </a>
    <a href="./hotspot_login.php" class="<?= $activePage === 'hotspot_login' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-paper-plane" style="color:var(--warning);"></i></span>
      Login Ekranı & Config Gönder
    </a>
    <a href="./pppoe.php" class="<?= $activePage === 'pppoe' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-id-card" style="color:var(--accent2);"></i></span>
      PPPoE Aboneleri
    </a>

    <!-- 4. YASAL UYUM & LOGLAR -->
    <div class="nav-section">🏛️ Yasal Uyum & Loglar</div>
    <a href="./law5651.php" class="<?= $activePage === 'law5651' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-scale-balanced" style="color:var(--success);"></i></span>
      5651 Yasal Loglar
    </a>
    <a href="./logs.php" class="<?= $activePage === 'logs' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-list-ol"></i></span>
      Sistem Logları
    </a>

    <!-- 5. ARAÇLAR & SİSTEM -->
    <div class="nav-section">⚙️ Araçlar & Sistem</div>
    <a href="./webfig.php" class="<?= $activePage === 'webfig' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-display" style="color:var(--warning);"></i></span>
      WebFig Gömülü Konsol
    </a>
    <a href="./tools.php" class="<?= $activePage === 'tools' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-terminal"></i></span>
      Ağ Araçları (Ping/Trace)
    </a>
    <a href="./settings.php" class="<?= $activePage === 'settings' ? 'active' : '' ?>">
      <span class="nav-icon"><i class="fas fa-gear"></i></span>
      Panel & Router Ayarları
    </a>
  </nav>

  <!-- Sidebar footer -->
  <div style="padding: 12px 16px; border-top: 1px solid var(--border); font-size: 10.5px; color: var(--text-muted);">
    <div style="margin-bottom: 4px;"><i class="fas fa-clock" style="margin-right: 6px;"></i><span id="sidebar-time"></span></div>
    <div><i class="fas fa-server" style="margin-right: 6px;"></i>192.168.0.1</div>
  </div>
</nav>

<!-- ── Main ── -->
<div id="main">
  <!-- Topbar -->
  <div id="topbar">
    <div style="display:flex; align-items:center; gap:12px;">
      <button id="mobile-menu" style="display:none; background:none; border:none; color:var(--text-primary); font-size:18px; cursor:pointer;">
        <i class="fas fa-bars"></i>
      </button>
      <h1><?= htmlspecialchars($pageTitle) ?></h1>
    </div>
    <div class="topbar-right">
      <span class="last-update">Son güncelleme: <span id="last-update-time">—</span></span>
      <button class="refresh-btn" id="manual-refresh">
        <i class="fas fa-rotate-right"></i>
        Yenile
      </button>
    </div>
  </div>

  <!-- Toast container -->
  <div id="toast-container"></div>

  <!-- Page content starts here -->
  <div class="page-content">
