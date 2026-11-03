<?php
$pageTitle  = 'Canlı Trafik';
$activePage = 'traffic';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Interface seçici + özet -->
<div class="stat-grid" style="grid-template-columns: repeat(4,1fr); margin-bottom:24px;">
  <div class="stat-card" style="--card-color:#06b6d4;">
    <div class="card-icon"><i class="fas fa-arrow-down"></i></div>
    <div class="card-value" id="t-rx-speed">—</div>
    <div class="card-label">Download Hızı</div>
  </div>
  <div class="stat-card" style="--card-color:#10b981;">
    <div class="card-icon"><i class="fas fa-arrow-up"></i></div>
    <div class="card-value" id="t-tx-speed">—</div>
    <div class="card-label">Upload Hızı</div>
  </div>
  <div class="stat-card" style="--card-color:#3b82f6;">
    <div class="card-icon"><i class="fas fa-boxes-stacked"></i></div>
    <div class="card-value" id="t-rx-pps">—</div>
    <div class="card-label">DL Paket/sn</div>
  </div>
  <div class="stat-card" style="--card-color:#8b5cf6;">
    <div class="card-icon"><i class="fas fa-paper-plane"></i></div>
    <div class="card-value" id="t-tx-pps">—</div>
    <div class="card-label">UL Paket/sn</div>
  </div>
</div>

<!-- Canlı grafik paneli -->
<div class="panel" style="margin-bottom:20px;">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-wave-square"></i></span>
      Anlık Bant Genişliği
      <span class="pill pill-green" style="margin-left:8px; animation: pulse-dot 2s infinite;">● CANLI</span>
    </h3>
    <div style="display:flex; align-items:center; gap:12px;">
      <label style="font-size:12px; color:var(--text-muted);">Interface:</label>
      <select id="traffic-iface" style="
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--border);
        color: var(--text-primary);
        border-radius: 7px;
        padding: 7px 12px;
        font-size: 12px;
        font-family: inherit;
        cursor: pointer;
        min-width: 120px;
      ">
        <option value="ether1">ether1</option>
      </select>
    </div>
  </div>
  <div class="panel-body">
    <canvas id="liveTrafficChart" height="100"></canvas>
  </div>
</div>

<!-- Dual chart: RX ve TX ayrı -->
<div class="grid-2">
  <div class="panel">
    <div class="panel-header">
      <h3 style="color:var(--accent2);">
        <span class="ph-icon"><i class="fas fa-arrow-down"></i></span>
        Download Geçmişi
      </h3>
      <span id="peak-rx" style="font-size:11px; color:var(--text-muted);">Tepe: —</span>
    </div>
    <div class="panel-body">
      <canvas id="rxHistChart" height="120"></canvas>
    </div>
  </div>
  <div class="panel">
    <div class="panel-header">
      <h3 style="color:var(--success);">
        <span class="ph-icon"><i class="fas fa-arrow-up"></i></span>
        Upload Geçmişi
      </h3>
      <span id="peak-tx" style="font-size:11px; color:var(--text-muted);">Tepe: —</span>
    </div>
    <div class="panel-body">
      <canvas id="txHistChart" height="120"></canvas>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {
  const MAX_POINTS = 60;
  const mkLabels = () => new Array(MAX_POINTS).fill('');
  const mkData   = () => new Array(MAX_POINTS).fill(0);

  const rxAll = mkData(), txAll = mkData(), lbAll = mkLabels();
  const rxHist = mkData(), txHist = mkData();

  // Chart ortak ayarlar
  function mkScales(yLabel) {
    return {
      x: {
        grid: { color: 'rgba(31,45,68,0.5)' },
        ticks: { color: '#475569', font: { size: 9 }, maxTicksLimit: 8 }
      },
      y: {
        grid: { color: 'rgba(31,45,68,0.5)' },
        ticks: { color: '#475569', font: { size: 9 }, callback: v => v.toFixed(1) + ' M' }
      }
    };
  }

  // Ana canlı grafik
  const liveCtx = document.getElementById('liveTrafficChart').getContext('2d');
  const liveChart = new Chart(liveCtx, {
    type: 'line',
    data: {
      labels: lbAll,
      datasets: [
        { label: 'Download', data: rxAll, borderColor: '#06b6d4', backgroundColor: 'rgba(6,182,212,0.06)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0 },
        { label: 'Upload',   data: txAll, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.06)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0 },
      ]
    },
    options: {
      responsive: true, animation: { duration: 200 },
      plugins: {
        legend: { labels: { color: '#94a3b8', font: { size: 11 } } },
        tooltip: { backgroundColor: '#111827', borderColor: '#1f2d44', borderWidth: 1, bodyColor: '#94a3b8', callbacks: { label: c => ' ' + c.dataset.label + ': ' + c.raw.toFixed(2) + ' Mbps' } }
      },
      scales: mkScales(),
    }
  });

  // RX History
  const rxCtx = document.getElementById('rxHistChart').getContext('2d');
  const rxChart = new Chart(rxCtx, {
    type: 'bar',
    data: { labels: mkLabels(), datasets: [{ label: 'Download (Mbps)', data: rxHist, backgroundColor: 'rgba(6,182,212,0.5)', borderColor: '#06b6d4', borderWidth: 1, borderRadius: 3 }] },
    options: { responsive: true, animation: false, plugins: { legend: { display: false } }, scales: mkScales() }
  });

  // TX History
  const txCtx = document.getElementById('txHistChart').getContext('2d');
  const txChart = new Chart(txCtx, {
    type: 'bar',
    data: { labels: mkLabels(), datasets: [{ label: 'Upload (Mbps)', data: txHist, backgroundColor: 'rgba(16,185,129,0.5)', borderColor: '#10b981', borderWidth: 1, borderRadius: 3 }] },
    options: { responsive: true, animation: false, plugins: { legend: { display: false } }, scales: mkScales() }
  });

  let peakRx = 0, peakTx = 0;

  function fetchTraffic() {
    const iface = $('#traffic-iface').val() || 'ether1';
    $.getJSON('./api.php?action=traffic&iface=' + encodeURIComponent(iface), function (res) {
      if (!res.success || !res.traffic) return;
      const t = res.traffic;
      const rxMbps = +(t.rx_bps / 1e6).toFixed(3);
      const txMbps = +(t.tx_bps / 1e6).toFixed(3);
      const now    = new Date().toLocaleTimeString('tr-TR');

      // Stat cards
      $('#t-rx-speed').text(formatSpeed(t.rx_bps));
      $('#t-tx-speed').text(formatSpeed(t.tx_bps));
      $('#t-rx-pps').text((t.rx_packets || 0).toLocaleString('tr-TR'));
      $('#t-tx-pps').text((t.tx_packets || 0).toLocaleString('tr-TR'));

      // Peak hesapla
      if (rxMbps > peakRx) { peakRx = rxMbps; $('#peak-rx').text('Tepe: ' + peakRx.toFixed(2) + ' Mbps'); }
      if (txMbps > peakTx) { peakTx = txMbps; $('#peak-tx').text('Tepe: ' + peakTx.toFixed(2) + ' Mbps'); }

      // Update charts
      rxAll.push(rxMbps); rxAll.shift();
      txAll.push(txMbps); txAll.shift();
      lbAll.push(now); lbAll.shift();
      liveChart.update('none');

      rxHist.push(rxMbps); rxHist.shift();
      txHist.push(txMbps); txHist.shift();
      rxChart.data.labels.push(now); rxChart.data.labels.shift();
      txChart.data.labels.push(now); txChart.data.labels.shift();
      rxChart.update('none');
      txChart.update('none');

      // Interface listesi
      if (res.interfaces) {
        const $sel = $('#traffic-iface');
        const cur = $sel.val();
        $sel.empty();
        res.interfaces.forEach(i => $sel.append(`<option value="${i}" ${i === cur ? 'selected' : ''}>${i}</option>`));
      }

      setLastUpdate();
    });
  }

  $('#traffic-iface').on('change', fetchTraffic);

  window.refreshAll = function () { fetchTraffic(); };
  fetchTraffic();
  setInterval(fetchTraffic, <?= REFRESH_INTERVAL ?>);
});
</script>
