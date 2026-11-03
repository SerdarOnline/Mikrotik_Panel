<?php
$pageTitle  = 'Access Point (AP) Yönetim Merkezi';
$activePage = 'access_points';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── AP Stat İstatistik Kartları ────────────────────────── -->
<div class="stat-grid" style="margin-bottom:20px;">
  <div class="stat-card blue">
    <div class="card-icon"><i class="fas fa-wifi"></i></div>
    <div class="card-value" id="ap-total-val">0</div>
    <div class="card-label">Toplam Tespit Edilen AP</div>
    <div class="card-sub">Otomatik Algılanan AP Cihazları</div>
  </div>

  <div class="stat-card green">
    <div class="card-icon"><i class="fas fa-circle-check"></i></div>
    <div class="card-value" id="ap-online-val">0</div>
    <div class="card-label">Aktif / Online AP</div>
    <div class="card-sub">Ağda Yanıt Veren Cihazlar</div>
  </div>

  <div class="stat-card purple">
    <div class="card-icon"><i class="fas fa-router"></i></div>
    <div class="card-value" id="ap-ruijie-val">0</div>
    <div class="card-label">Ruijie / Reyee AP Sayısı</div>
    <div class="card-sub">Ruijie Wi-Fi Cihazları</div>
  </div>

  <div class="stat-card yellow">
    <div class="card-icon"><i class="fas fa-broadcast-tower"></i></div>
    <div class="card-value" id="ap-ubiquiti-val">0</div>
    <div class="card-label">Ubiquiti / NanoStation</div>
    <div class="card-sub">AirMAX / UniFi Cihazları</div>
  </div>
</div>

<!-- ── Access Point Tablosu ────────────────────────────────── -->
<div class="panel">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-wifi"></i></span>
      Ağdaki Access Point (AP) Cihazları
      <span class="pill pill-blue" id="ap-badge-count" style="margin-left:8px;">0 AP</span>
    </h3>
    <div style="display:flex; align-items:center; gap:10px;">
      <button onclick="fetchAccessPoints()" class="pill pill-blue" style="cursor:pointer; border:none; padding:6px 12px; font-size:12px;">
        <i class="fas fa-rotate"></i> AP'leri Yeniden Tara
      </button>
      <button onclick="exportUsersCSV('#ap-table')" class="pill pill-green" style="cursor:pointer; border:none; padding:6px 12px; font-size:12px;">
        <i class="fas fa-file-csv"></i> CSV Aktar
      </button>
      <div class="search-wrap">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" class="search-input table-search" data-target="#ap-table" placeholder="AP adı, IP veya Marka ara...">
      </div>
    </div>
  </div>
  <div class="panel-body no-pad">
    <table class="data-table" id="ap-table">
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>Access Point Adı</th>
          <th>IP Adresi</th>
          <th>MAC Adresi</th>
          <th>Marka / Üretici</th>
          <th>Model</th>
          <th>Bağlı Port</th>
          <th>Durum</th>
          <th style="text-align:right;">Yönetim & Bağlantı</th>
        </tr>
      </thead>
      <tbody id="ap-body">
        <tr class="loading-row"><td colspan="9"><div class="spinner" style="margin:0 auto;"></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {
  window.fetchAccessPoints = function () {
    $.getJSON('./api.php?action=access_points', function (res) {
      if (!res.success) return;

      const aps = res.access_points || [];
      $('#ap-badge-count').text(aps.length + ' AP');
      $('#ap-total-val').text(aps.length);

      let onlineCnt = 0;
      let ruijieCnt = 0;
      let ubiCnt = 0;

      if (!aps.length) {
        $('#ap-body').html('<tr><td colspan="9" style="text-align:center; color:var(--text-muted);">Ağda Access Point (AP) bulunamadı.</td></tr>');
        return;
      }

      const rows = aps.map((ap, i) => {
        if (ap.status === 'Online') onlineCnt++;
        if (ap.brand.toLowerCase().includes('ruijie')) ruijieCnt++;
        if (ap.brand.toLowerCase().includes('ubiquiti') || ap.name.toLowerCase().includes('nanostation')) ubiCnt++;

        const brandCls = ap.brand.toLowerCase().includes('ruijie') ? 'pill-purple'
          : ap.brand.toLowerCase().includes('ubiquiti') || ap.name.toLowerCase().includes('nanostation') ? 'pill-blue'
          : ap.brand.toLowerCase().includes('tenda') ? 'pill-orange'
          : 'pill-gray';

        return `
          <tr>
            <td style="color:var(--text-muted); font-size:11px;">${i + 1}</td>
            <td>
              <span style="font-weight:800; color:var(--text-primary); font-size:13px;">📶 ${ap.name}</span>
            </td>
            <td><span class="mono" style="font-weight:700; color:var(--accent2);">${ap.ip}</span></td>
            <td><span class="mac">${ap.mac}</span></td>
            <td><span class="pill ${brandCls}">${ap.brand}</span></td>
            <td style="font-size:12px; color:var(--text-dim);">${ap.model}</td>
            <td><span class="pill pill-green">${ap.port}</span></td>
            <td>
              <span class="badge ${ap.status === 'Online' ? 'badge-active' : 'badge-banned'}">
                <i class="fas fa-circle" style="font-size:7px; margin-right:4px;"></i> ${ap.status}
              </span>
            </td>
            <td style="text-align:right;">
              <a href="http://${ap.ip}" target="_blank" class="pill pill-blue" style="text-decoration:none; padding:4px 10px; font-size:11px; font-weight:700;">
                <i class="fas fa-up-right-from-square"></i> AP Arayüzünü Aç
              </a>
            </td>
          </tr>
        `;
      });

      $('#ap-online-val').text(onlineCnt);
      $('#ap-ruijie-val').text(ruijieCnt);
      $('#ap-ubiquiti-val').text(ubiCnt);

      $('#ap-body').html(rows.join(''));
      setLastUpdate();
    });
  };

  window.refreshAll = fetchAccessPoints;
  fetchAccessPoints();
  setInterval(fetchAccessPoints, 15000);
});
</script>
