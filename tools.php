<?php
$pageTitle  = 'Ağ Diagnostik Araçları';
$activePage = 'tools';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="grid-2" style="margin-bottom:20px;">

  <!-- ── PING KONSOLU ────────────────────────────────────────── -->
  <div class="panel">
    <div class="panel-header">
      <h3>
        <span class="ph-icon"><i class="fas fa-terminal"></i></span>
        MikroTik RouterOS Ping Testi
      </h3>
    </div>
    <div class="panel-body">
      <div style="display:flex; gap:10px; margin-bottom:16px;">
        <input type="text" id="ping-target" value="8.8.8.8" placeholder="IP veya Domain (Örn: google.com)" style="
          flex:1; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-family:'JetBrains Mono',monospace; font-size:13px;
        ">
        <select id="ping-count" style="background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px;">
          <option value="4">4 Paket</option>
          <option value="8">8 Paket</option>
        </select>
        <button onclick="runPingTest()" id="btn-ping" class="pill pill-blue" style="cursor:pointer; border:none; padding:8px 16px; font-weight:700;">
          <i class="fas fa-paper-plane"></i> Ping Gönder
        </button>
      </div>

      <!-- Ping Sonuç Kartları -->
      <div id="ping-summary" style="display:none; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:16px;">
        <div style="background:var(--bg-card2); border:1px solid var(--border); border-radius:8px; padding:10px; text-align:center;">
          <div style="font-size:11px; color:var(--text-muted);">Paket Kaybı</div>
          <div id="p-loss" style="font-size:18px; font-weight:800; color:var(--success);">0%</div>
        </div>
        <div style="background:var(--bg-card2); border:1px solid var(--border); border-radius:8px; padding:10px; text-align:center;">
          <div style="font-size:11px; color:var(--text-muted);">Ortalama Gecikme</div>
          <div id="p-avg" style="font-size:18px; font-weight:800; color:var(--accent2);">0 ms</div>
        </div>
        <div style="background:var(--bg-card2); border:1px solid var(--border); border-radius:8px; padding:10px; text-align:center;">
          <div style="font-size:11px; color:var(--text-muted);">Min / Max</div>
          <div id="p-minmax" style="font-size:14px; font-weight:700; color:var(--text-primary); margin-top:4px;">0 / 0 ms</div>
        </div>
      </div>

      <div style="max-height:260px; overflow-y:auto;">
        <table class="data-table" style="font-size:12px;">
          <thead>
            <tr>
              <th>Host</th>
              <th>Durum</th>
              <th>Gecikme (RTT)</th>
              <th>TTL</th>
            </tr>
          </thead>
          <tbody id="ping-results-body">
            <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">Hedef adresi yazıp 'Ping Gönder' butonuna basın.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ── TRACEROUTE KONSOLU ──────────────────────────────────── -->
  <div class="panel">
    <div class="panel-header">
      <h3>
        <span class="ph-icon"><i class="fas fa-route"></i></span>
        MikroTik TraceRoute Testi
      </h3>
    </div>
    <div class="panel-body">
      <div style="display:flex; gap:10px; margin-bottom:16px;">
        <input type="text" id="trace-target" value="1.1.1.1" placeholder="IP veya Domain" style="
          flex:1; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-family:'JetBrains Mono',monospace; font-size:13px;
        ">
        <button onclick="runTracerouteTest()" id="btn-trace" class="pill pill-blue" style="cursor:pointer; border:none; padding:8px 16px; font-weight:700;">
          <i class="fas fa-play"></i> Iz Sür (Trace)
        </button>
      </div>

      <div style="max-height:315px; overflow-y:auto;">
        <table class="data-table" style="font-size:12px;">
          <thead>
            <tr>
              <th>Hop</th>
              <th>IP Adresi</th>
              <th>Kayıp %</th>
              <th>Son RTT</th>
              <th>Ort. RTT</th>
            </tr>
          </thead>
          <tbody id="trace-results-body">
            <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">Hedef yazıp 'Iz Sür' butonuna basın.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- ── ROUTEROS YEDEKLEME (EXPORT) ────────────────────────── -->
<div class="panel" style="margin-top:20px;">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-file-export"></i></span>
      MikroTik RouterOS Konfigürasyon Yedeği (Export)
    </h3>
    <button onclick="downloadRouterBackup()" id="btn-export" class="pill pill-green" style="cursor:pointer; border:none; padding:8px 16px; font-weight:700;">
      <i class="fas fa-download"></i> Konfigürasyon Yedeği İndir (.rsc)
    </button>
  </div>
  <div class="panel-body">
    <div style="font-size:12.5px; color:var(--text-dim); margin-bottom:12px;">
      MikroTik router'ınızın üzerindeki tüm IP, Firewall, DHCP ve Queue ayarlarını tek tıkla <code>.rsc</code> script formatında bilgisayarınıza aktarabilirsiniz.
    </div>
    <textarea id="backup-output" readonly placeholder="Yedek indir butonuna basıldığında konfigürasyon burada görüntülenecektir..." style="
      width:100%; height:180px; background:rgba(0,0,0,0.3); border:1px solid var(--border); border-radius:8px;
      padding:12px; color:var(--accent2); font-family:'JetBrains Mono',monospace; font-size:12px; resize:vertical;
    "></textarea>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
window.runPingTest = function () {
  const target = $('#ping-target').val().trim();
  const count  = $('#ping-count').val();

  if (!target) {
    showToast('Lütfen hedef IP veya Domain yazın', 'error');
    return;
  }

  $('#btn-ping').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Ping Atılıyor...');
  $('#ping-results-body').html('<tr><td colspan="4" style="text-align:center;"><div class="spinner" style="margin:0 auto;"></div> Paketler gönderiliyor...</td></tr>');

  $.getJSON('./api.php?action=ping_test&target=' + encodeURIComponent(target) + '&count=' + count, function (res) {
    $('#btn-ping').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Ping Gönder');
    if (!res.success || !res.ping) {
      showToast('Ping başarısız: ' + (res.error || ''), 'error');
      $('#ping-results-body').html('<tr><td colspan="4" style="text-align:center; color:var(--danger);">Ping atılamadı.</td></tr>');
      return;
    }

    const p = res.ping;
    $('#ping-summary').css('display', 'grid');
    $('#p-loss').text(p.loss_pct + '%').css('color', p.loss_pct > 0 ? 'var(--danger)' : 'var(--success)');
    $('#p-avg').text(p.avg_ms + ' ms');
    $('#p-minmax').text(p.min_ms + ' / ' + p.max_ms + ' ms');

    const rows = (p.packets || []).map(pkt => `
      <tr>
        <td><span class="mono">${pkt.host}</span></td>
        <td>${pkt.status === 'OK' ? '<span class="pill pill-green">Başarılı</span>' : '<span class="pill pill-red">' + pkt.status + '</span>'}</td>
        <td style="color:var(--accent2); font-weight:700;">${pkt.time}</td>
        <td><span class="mono">${pkt.ttl}</span></td>
      </tr>
    `);

    $('#ping-results-body').html(rows.join('') || '<tr><td colspan="4">Paket yanıtı alınamadı</td></tr>');
  }).fail(function () {
    $('#btn-ping').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Ping Gönder');
    showToast('Sunucu hatası', 'error');
  });
};

window.runTracerouteTest = function () {
  const target = $('#trace-target').val().trim();
  if (!target) return;

  $('#btn-trace').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Rota İzleniyor...');
  $('#trace-results-body').html('<tr><td colspan="5" style="text-align:center;"><div class="spinner" style="margin:0 auto;"></div> Rota izleniyor (10-15s sürebilir)...</td></tr>');

  $.getJSON('./api.php?action=traceroute_test&target=' + encodeURIComponent(target), function (res) {
    $('#btn-trace').prop('disabled', false).html('<i class="fas fa-play"></i> Iz Sür (Trace)');
    if (!res.success || !res.traceroute) {
      showToast('Traceroute başarısız', 'error');
      $('#trace-results-body').html('<tr><td colspan="5" style="text-align:center; color:var(--danger);">Rotaya ulaşılamadı.</td></tr>');
      return;
    }

    const hops = res.traceroute.hops || [];
    if (!hops.length) {
      $('#trace-results-body').html('<tr><td colspan="5" style="text-align:center; color:var(--text-muted);">Hop bilgisi dönmedi.</td></tr>');
      return;
    }

    const rows = hops.map(h => `
      <tr>
        <td><span class="rank-badge rank-n">${h.hop}</span></td>
        <td><span class="mono">${h.address}</span></td>
        <td>${h.loss}</td>
        <td style="color:var(--accent2);">${h.last}</td>
        <td>${h.avg}</td>
      </tr>
    `);

    $('#trace-results-body').html(rows.join(''));
  }).fail(function () {
    $('#btn-trace').prop('disabled', false).html('<i class="fas fa-play"></i> Iz Sür (Trace)');
    showToast('Sunucu hatası', 'error');
  });
};

window.downloadRouterBackup = function () {
  $('#btn-export').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Yedek Çıkarılıyor...');
  $.getJSON('./api.php?action=export_config', function (res) {
    $('#btn-export').prop('disabled', false).html('<i class="fas fa-download"></i> Konfigürasyon Yedeği İndir (.rsc)');
    if (!res.success || !res.export_text) {
      showToast('Yedek alınamadı', 'error');
      return;
    }

    const text = res.export_text;
    $('#backup-output').val(text);

    // Download .rsc file automatically
    const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'mikrotik_backup_' + new Date().toISOString().slice(0,10) + '.rsc';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    showToast('Konfigürasyon yedeği indirildi!', 'success');
  });
};
</script>
