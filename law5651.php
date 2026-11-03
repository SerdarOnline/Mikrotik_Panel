<?php
$pageTitle  = '5651 Sayılı Kanun Yasal Log Paneli';
$activePage = 'law5651';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Stat İstatistik Kartları ────────────────────────── -->
<div class="stat-grid" style="margin-bottom:20px;">
  <div class="stat-card blue">
    <div class="card-icon"><i class="fas fa-scale-balanced"></i></div>
    <div class="card-value" id="log-total-val">0</div>
    <div class="card-label">Toplam 5651 Yasal Kayıt</div>
    <div class="card-sub">SHA-256 İmzalı Müşteri Erişim Kayıtları</div>
  </div>

  <div class="stat-card green">
    <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
    <div class="card-value" id="log-today-val">0</div>
    <div class="card-label">Bugünkü 5651 Oturumları</div>
    <div class="card-sub">Bugün Doğrulanan Müşteri Sayısı</div>
  </div>

  <div class="stat-card purple">
    <div class="card-icon"><i class="fas fa-id-card"></i></div>
    <div class="card-value" id="log-tc-val">0</div>
    <div class="card-label">T.C. Kimlik Doğrulamalı</div>
    <div class="card-sub">T.C. No Kayıtlı Oturumlar</div>
  </div>

  <div class="stat-card yellow">
    <div class="card-icon"><i class="fas fa-shield-check"></i></div>
    <div class="card-value" style="font-size:18px; font-weight:800; color:var(--success);">✅ %100 UYUMLU</div>
    <div class="card-label">BTK / TİB Uyum Durumu</div>
    <div class="card-sub">SHA-256 Zaman Damgalı İmzalı</div>
  </div>
</div>

<!-- ── Eylemler & Filtreleme Barı ───────────────────────── -->
<div style="background:var(--bg-card); border:1px solid var(--border); border-radius:16px; padding:18px 20px; margin-bottom:20px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px;">
  <div>
    <h3 style="margin:0 0 4px 0; font-size:16px; display:flex; align-items:center; gap:8px;">
      🏛️ 5651 Sayılı Kanun Uyumlu Resmi Log Raporlayıcı
    </h3>
    <div style="font-size:12px; color:var(--text-dim);">
      Mahkeme, Emniyet veya BTK denetimlerinde ibraz edilecek SHA-256 zaman damgalı resmi log dosyası oluşturun.
    </div>
  </div>
  <div style="display:flex; gap:10px; flex-wrap:wrap;">
    <button onclick="exportBtk5651File()" class="pill pill-green" style="cursor:pointer; border:none; padding:10px 18px; font-weight:700; font-size:13px;">
      <i class="fas fa-file-contract"></i> 📜 BTK Uyumlu 5651 Log Dosyası İndir (.txt)
    </button>
    <button onclick="fetch5651Logs()" class="pill pill-blue" style="cursor:pointer; border:none; padding:10px 16px; font-weight:700; font-size:13px;">
      <i class="fas fa-rotate"></i> Yenile
    </button>
  </div>
</div>

<!-- ── 5651 Yasal Log Kayıtları Tablosu ────────────────────── -->
<div class="panel">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-scale-balanced"></i></span>
      5651 Sayılı Kanun Müşteri Erişim & Kimlik Kayıtları
      <span class="pill pill-blue" id="log-badge-count" style="margin-left:8px;">0 kayıt</span>
    </h3>
    <div style="display:flex; align-items:center; gap:10px;">
      <div class="search-wrap">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" class="search-input table-search" data-target="#log5651-table" placeholder="Müşteri Adı, TC No, MAC veya IP ara...">
      </div>
    </div>
  </div>
  <div class="panel-body no-pad">
    <table class="data-table" id="log5651-table">
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>Müşteri Ad Soyad</th>
          <th>T.C. Kimlik / Pasaport</th>
          <th>Telefon Numarası</th>
          <th>Kupon Kodu</th>
          <th>Cihaz MAC Adresi</th>
          <th>Atanan IP Adresi</th>
          <th>Giriş Tarihi / Saati</th>
          <th>SHA-256 Zaman Damgası İmzası</th>
        </tr>
      </thead>
      <tbody id="log5651-body">
        <tr class="loading-row"><td colspan="9"><div class="spinner" style="margin:0 auto;"></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {
  window.fetch5651Logs = function () {
    $.getJSON('./api.php?action=get_5651_logs', function (res) {
      if (!res.success) return;

      const logs = res.logs || [];
      $('#log-badge-count').text(logs.length + ' kayıt');
      $('#log-total-val').text(logs.length);

      const todayStr = new Date().toISOString().split('T')[0];
      let todayCnt = 0;
      let tcCnt = 0;

      if (!logs.length) {
        $('#log5651-body').html('<tr><td colspan="9" style="text-align:center; color:var(--text-muted);">Henüz 5651 yasal log kaydı bulunmamaktadır.</td></tr>');
        return;
      }

      const rows = logs.map((l, i) => {
        if (l.login_time && l.login_time.includes(todayStr)) todayCnt++;
        if (l.tc_no && l.tc_no.length >= 10) tcCnt++;

        const shortHash = l.sha256_hash ? l.sha256_hash.substring(0, 16) + '...' : '—';

        return `
          <tr>
            <td style="color:var(--text-muted); font-size:11px;">${l.id}</td>
            <td>
              <span style="font-weight:800; color:var(--text-primary); font-size:13px;">👤 ${l.full_name}</span>
            </td>
            <td><span class="mono" style="font-weight:700; color:var(--accent2);">${l.tc_no || '—'}</span></td>
            <td><span class="mono" style="color:var(--text-dim);">${l.phone || '—'}</span></td>
            <td><span class="pill pill-purple">${l.voucher_code}</span></td>
            <td><span class="mac">${l.mac_address}</span></td>
            <td><span class="mono" style="color:var(--success); font-weight:700;">${l.ip_address}</span></td>
            <td style="font-size:11.5px; color:var(--text-muted);">${l.login_time}</td>
            <td>
              <span class="pill pill-blue" title="${l.sha256_hash}" style="font-family:monospace; font-size:10.5px;">
                🔑 ${shortHash}
              </span>
            </td>
          </tr>
        `;
      });

      $('#log-today-val').text(todayCnt);
      $('#log-tc-val').text(tcCnt);
      $('#log5651-body').html(rows.join(''));
      setLastUpdate();
    });
  };

  window.exportBtk5651File = function () {
    $.getJSON('./api.php?action=export_btk_log_package', function (res) {
      if (!res.success) {
        showToast('BTK log paketi üretilemedi: ' + (res.error || ''), 'error');
        return;
      }

      // 1. Resmi IPLOG_YYYY-MM-DD.log Dosyasını İndir
      const blobLog = new Blob([res.log_content], { type: 'text/plain;charset=utf-8' });
      const linkLog = document.createElement('a');
      linkLog.href = URL.createObjectURL(blobLog);
      linkLog.download = res.log_filename;
      linkLog.click();

      // 2. Resmi IPLOG_YYYY-MM-DD.log.sha256 İmza Dosyasını İndir
      setTimeout(() => {
        const blobSha = new Blob([res.sha_content], { type: 'text/plain;charset=utf-8' });
        const linkSha = document.createElement('a');
        linkSha.href = URL.createObjectURL(blobSha);
        linkSha.download = res.sha_filename;
        linkSha.click();
      }, 500);

      showToast('BTK IP Log İmzalayıcı Uyumlu Log (.log) ve İmza (.sha256) dosyaları indirildi!', 'success');
    });
  };

  window.refreshAll = fetch5651Logs;
  fetch5651Logs();
  setInterval(fetch5651Logs, 15000);
});
</script>
