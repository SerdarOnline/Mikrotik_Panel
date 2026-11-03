<?php
$pageTitle  = 'Ağdaki Komşu Cihazlar (MNDP / CDP / LLDP)';
$activePage = 'neighbors';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── MNDP Stat Kartları ──────────────────────────────────── -->
<div class="stat-grid" style="margin-bottom:20px;">
  <div class="stat-card blue">
    <div class="card-icon"><i class="fas fa-sitemap"></i></div>
    <div class="card-value" id="neigh-stat-total">0</div>
    <div class="card-label">Toplam Komşu Cihaz</div>
    <div class="card-sub">MNDP / CDP / LLDP Algılanan</div>
  </div>

  <div class="stat-card green">
    <div class="card-icon"><i class="fas fa-router"></i></div>
    <div class="card-value" id="neigh-stat-mt">0</div>
    <div class="card-label">MikroTik Cihazları</div>
    <div class="card-sub">RouterOS / SwOS</div>
  </div>

  <div class="stat-card purple">
    <div class="card-icon"><i class="fas fa-network-wired"></i></div>
    <div class="card-value" id="neigh-stat-other">0</div>
    <div class="card-label">Diğer Ağ Cihazları</div>
    <div class="card-sub">Cisco / Ubiquiti / Ruijie</div>
  </div>

  <div class="stat-card yellow">
    <div class="card-icon"><i class="fas fa-plug"></i></div>
    <div class="card-value" id="neigh-stat-ports">0</div>
    <div class="card-label">Aktif Port Sayısı</div>
    <div class="card-sub">Fiziksel Port Komşulukları</div>
  </div>
</div>

<!-- ── Komşu Cihazlar Tablosu ─────────────────────────────── -->
<div class="panel">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-sitemap"></i></span>
      Ağdaki Otomatik Algılanan Komşu Cihazlar (Neighbor Discovery)
      <span class="pill pill-blue" id="neigh-count" style="margin-left:8px;">0 cihaz</span>
    </h3>
    <div style="display:flex; align-items:center; gap:10px;">
      <button onclick="fetchNeighbors()" class="pill pill-blue" style="cursor:pointer; border:none; padding:6px 12px; font-size:12px;">
        <i class="fas fa-rotate"></i> Yeniden Tara
      </button>
      <button onclick="exportUsersCSV('#neigh-table')" class="pill pill-green" style="cursor:pointer; border:none; padding:6px 12px; font-size:12px;">
        <i class="fas fa-file-csv"></i> CSV Aktar
      </button>
      <div class="search-wrap">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" class="search-input table-search" data-target="#neigh-table" placeholder="Cihaz, IP veya MAC ara...">
      </div>
    </div>
  </div>
  <div class="panel-body no-pad">
    <table class="data-table" id="neigh-table">
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>Cihaz Adı / Kimlik</th>
          <th>IP Adresi</th>
          <th>MAC Adresi</th>
          <th>Bağlı Arayüz (Port)</th>
          <th>Platform / Üretici</th>
          <th>Model / Board</th>
          <th>Yazılım Sürümü</th>
        </tr>
      </thead>
      <tbody id="neigh-body">
        <tr class="loading-row"><td colspan="8"><div class="spinner" style="margin:0 auto;"></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {
  window.fetchNeighbors = function () {
    $.getJSON('./api.php?action=neighbors', function (res) {
      if (!res.success) return;

      const list = res.neighbors || [];
      $('#neigh-count').text(list.length + ' cihaz');

      if (!list.length) {
        $('#neigh-body').html('<tr><td colspan="8" style="text-align:center; color:var(--text-muted);">Komşu cihaz bulunamadı.</td></tr>');
        return;
      }

      const rows = list.map((n, i) => `
        <tr>
          <td style="color:var(--text-muted); font-size:11px;">${i + 1}</td>
          <td>
            <span style="font-weight:700; color:var(--text-primary);">${n.identity}</span>
          </td>
          <td><span class="mono" style="font-weight:700; color:var(--accent2);">${n.ip}</span></td>
          <td><span class="mac">${n.mac}</span></td>
          <td><span class="pill pill-purple">${n.interface}</span></td>
          <td><span class="pill pill-blue">${n.platform}</span></td>
          <td style="font-size:12px; color:var(--text-dim);">${n.board}</td>
          <td><span class="mono" style="font-size:11px; color:var(--text-muted);">${n.version}</span></td>
        </tr>
      `);

      let mtCnt = 0;
      let otherCnt = 0;
      const ports = new Set();

      list.forEach(n => {
        if ((n.platform || '').toLowerCase().includes('mikrotik') || (n.board || '').toLowerCase().includes('mikrotik') || (n.version || '').toLowerCase().includes('routeros')) {
          mtCnt++;
        } else {
          otherCnt++;
        }
        if (n.interface) ports.add(n.interface);
      });

      $('#neigh-stat-total').text(list.length);
      $('#neigh-stat-mt').text(mtCnt);
      $('#neigh-stat-other').text(otherCnt);
      $('#neigh-stat-ports').text(ports.size);

      $('#neigh-body').html(rows.join(''));
      setLastUpdate();
    });
  };

  window.refreshAll = fetchNeighbors;
  fetchNeighbors();
  setInterval(fetchNeighbors, 15000);
});
</script>
