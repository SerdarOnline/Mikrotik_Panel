<?php
$pageTitle  = 'Sistem & Güvenlik Logları';
$activePage = 'logs';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Log Filtre ve Arama ─────────────────────────────────── -->
<div class="panel" style="margin-bottom:20px;">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-list-ol"></i></span>
      MikroTik Sistem Logları
      <span class="pill pill-blue" id="log-count" style="margin-left:8px;">0 kayıt</span>
    </h3>
    <div style="display:flex; align-items:center; gap:10px;">
      <button onclick="fetchLogs()" class="pill pill-blue" style="cursor:pointer; border:none; padding:6px 12px; font-size:12px;">
        <i class="fas fa-rotate"></i> Yenile
      </button>
      <button onclick="exportUsersCSV('#logs-table')" class="pill pill-green" style="cursor:pointer; border:none; padding:6px 12px; font-size:12px;">
        <i class="fas fa-file-csv"></i> CSV Aktar
      </button>
      <div class="search-wrap">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" class="search-input table-search" data-target="#logs-table" placeholder="Loglarda ara (IP, mesaj, topic)...">
      </div>
    </div>
  </div>
  <div class="panel-body no-pad">
    <table class="data-table" id="logs-table">
      <thead>
        <tr>
          <th style="width:170px;">Zaman</th>
          <th style="width:180px;">Kategori (Topics)</th>
          <th>Mesaj / Olay Açıklaması</th>
        </tr>
      </thead>
      <tbody id="logs-body">
        <tr class="loading-row">
          <td colspan="3">
            <div class="spinner" style="margin:0 auto;"></div>
            <div style="margin-top:10px;">Loglar çekiliyor...</div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {
  function getTopicBadge(topics) {
    const t = (topics || '').toLowerCase();
    if (t.includes('critical') || t.includes('error')) {
      return `<span class="pill pill-red"><i class="fas fa-circle-xmark"></i> ${topics}</span>`;
    }
    if (t.includes('warning')) {
      return `<span class="pill pill-yellow"><i class="fas fa-triangle-exclamation"></i> ${topics}</span>`;
    }
    if (t.includes('dhcp')) {
      return `<span class="pill pill-blue"><i class="fas fa-network-wired"></i> ${topics}</span>`;
    }
    if (t.includes('account') || t.includes('system')) {
      return `<span class="pill pill-green"><i class="fas fa-user-shield"></i> ${topics}</span>`;
    }
    if (t.includes('firewall')) {
      return `<span class="pill pill-purple"><i class="fas fa-shield"></i> ${topics}</span>`;
    }
    return `<span class="pill pill-gray">${topics}</span>`;
  }

  window.fetchLogs = function () {
    $.getJSON('./api.php?action=logs&limit=250', function (res) {
      if (!res.success) {
        showToast('Loglar alınamadı: ' + (res.error || ''), 'error');
        return;
      }

      const logs = res.logs || [];
      $('#log-count').text(logs.length + ' log');

      if (!logs.length) {
        $('#logs-body').html('<tr class="loading-row"><td colspan="3">Log kaydı bulunamadı.</td></tr>');
        return;
      }

      const rows = logs.map(l => `
        <tr>
          <td><span class="mono" style="font-size:11.5px; color:var(--text-muted);">${l.time}</span></td>
          <td>${getTopicBadge(l.topics)}</td>
          <td style="font-size:12.5px; color:var(--text-primary); font-family:'JetBrains Mono',monospace;">${l.message}</td>
        </tr>
      `);

      $('#logs-body').html(rows.join(''));
      setLastUpdate();
    });
  };

  window.refreshAll = fetchLogs;
  fetchLogs();
  setInterval(fetchLogs, 15000); // 15s bir logları güncelle
});
</script>
