<?php
$pageTitle  = 'Aktif Bağlantılar';
$activePage = 'connections';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Özet -->
<div class="stat-grid" style="margin-bottom:24px;">
  <div class="stat-card" style="--card-color:#3b82f6;">
    <div class="card-icon"><i class="fas fa-network-wired"></i></div>
    <div class="card-value" id="c-total">—</div>
    <div class="card-label">Toplam Bağlantı</div>
  </div>
  <div class="stat-card" style="--card-color:#10b981;">
    <div class="card-icon"><i class="fas fa-check-double"></i></div>
    <div class="card-value" id="c-established">—</div>
    <div class="card-label">Established</div>
  </div>
  <div class="stat-card" style="--card-color:#8b5cf6;">
    <div class="card-icon"><i class="fas fa-arrows-to-dot"></i></div>
    <div class="card-value" id="c-lan">—</div>
    <div class="card-label">LAN Kaynaklı</div>
  </div>
  <div class="stat-card" style="--card-color:#06b6d4;">
    <div class="card-icon"><i class="fas fa-database"></i></div>
    <div class="card-value" id="c-bytes">—</div>
    <div class="card-label">Toplam Veri</div>
  </div>
</div>

<!-- Protokol dağılımı -->
<div class="grid-2" style="margin-bottom:20px;">
  <div class="panel">
    <div class="panel-header">
      <h3><span class="ph-icon"><i class="fas fa-chart-pie"></i></span> Protokol Dağılımı</h3>
    </div>
    <div class="panel-body" style="display:flex;justify-content:center;">
      <div style="max-width:280px;width:100%;">
        <canvas id="protoChart" height="240"></canvas>
      </div>
    </div>
  </div>
  <div class="panel">
    <div class="panel-header">
      <h3><span class="ph-icon"><i class="fas fa-users-rays"></i></span> En Aktif Kaynak IP'ler</h3>
    </div>
    <div class="panel-body">
      <canvas id="srcIpChart" height="240"></canvas>
    </div>
  </div>
</div>

<!-- Bağlantı tablosu -->
<div class="panel">
  <div class="panel-header">
    <h3><span class="ph-icon"><i class="fas fa-table-list"></i></span> Bağlantı Listesi</h3>
    <div style="display:flex; gap:10px; align-items:center;">
      <select id="limit-select" style="
        background:rgba(255,255,255,0.05); border:1px solid var(--border);
        color:var(--text-primary); border-radius:7px; padding:6px 10px; font-size:12px; font-family:inherit;
      ">
        <option value="50">50 kayıt</option>
        <option value="100">100 kayıt</option>
        <option value="200">200 kayıt</option>
      </select>
      <div class="search-wrap">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" class="search-input table-search" data-target="#conn-table" placeholder="IP veya protokol ara...">
      </div>
    </div>
  </div>
  <div class="panel-body no-pad">
    <table class="data-table" id="conn-table">
      <thead>
        <tr>
          <th>Protokol</th>
          <th>Kaynak IP</th>
          <th>Hedef IP</th>
          <th>Durum</th>
          <th>Giden Veri</th>
          <th>Gelen Veri</th>
          <th>Timeout</th>
        </tr>
      </thead>
      <tbody id="conn-body">
        <tr class="loading-row"><td colspan="7"><div class="spinner" style="margin:0 auto;"></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {
  // Protokol pie
  const protoCtx = document.getElementById('protoChart').getContext('2d');
  const protoChart = new Chart(protoCtx, {
    type: 'doughnut',
    data: { labels: [], datasets: [{ data: [], backgroundColor: ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444'], borderColor: '#111827', borderWidth: 2 }] },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 11 }, boxWidth: 12 } },
      }
    }
  });

  // Kaynak IP bar
  const srcCtx = document.getElementById('srcIpChart').getContext('2d');
  const srcChart = new Chart(srcCtx, {
    type: 'bar',
    data: { labels: [], datasets: [{ label: 'Bağlantı Sayısı', data: [], backgroundColor: 'rgba(59,130,246,0.7)', borderRadius: 4 }] },
    options: {
      indexAxis: 'y', responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: 'rgba(31,45,68,0.5)' }, ticks: { color: '#475569', font: { size: 10 } } },
        y: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } }
      }
    }
  });

  function fetchConnections() {
    const limit = $('#limit-select').val() || 50;
    $.getJSON('./api.php?action=connections&limit=' + limit, function (res) {
      if (!res.success) return;
      const conns = res.connections || [];
      const total = res.total || conns.length;

      // Stat
      $('#c-total').text(total.toLocaleString('tr-TR'));
      const est = conns.filter(c => c.state && c.state.toLowerCase().includes('established')).length;
      $('#c-established').text(est);

      // LAN kaynaklı
      let lanCount = 0, totalBytes = 0;
      const protoMap = {}, srcMap = {};
      conns.forEach(c => {
        const srcIp = c.src.split(':')[0];
        if (/^(10\.|172\.(1[6-9]|2\d|3[01])\.|192\.168\.)/.test(srcIp)) lanCount++;
        totalBytes += c.orig_bytes + c.repl_bytes;
        protoMap[c.protocol] = (protoMap[c.protocol] || 0) + 1;
        srcMap[srcIp] = (srcMap[srcIp] || 0) + 1;
      });
      $('#c-lan').text(lanCount);
      $('#c-bytes').text(formatBytes(totalBytes));

      // Protokol chart
      const sortedProto = Object.entries(protoMap).sort((a, b) => b[1] - a[1]).slice(0, 5);
      protoChart.data.labels = sortedProto.map(e => e[0] || 'diğer');
      protoChart.data.datasets[0].data = sortedProto.map(e => e[1]);
      protoChart.update();

      // Kaynak IP chart (top 8, sadece LAN)
      const topSrc = Object.entries(srcMap)
        .filter(([ip]) => /^(10\.|172\.|192\.168\.)/.test(ip))
        .sort((a, b) => b[1] - a[1]).slice(0, 8);
      srcChart.data.labels = topSrc.map(e => e[0]);
      srcChart.data.datasets[0].data = topSrc.map(e => e[1]);
      srcChart.update();

      // Tablo
      const rows = conns.map(c => {
        const stCls = c.state && c.state.includes('established') ? 'pill-green' : 'pill-yellow';
        const proto = c.protocol || '?';
        const protoCls = proto === 'tcp' ? 'pill-blue' : proto === 'udp' ? 'pill-green' : 'pill-gray';
        return `
          <tr>
            <td><span class="pill ${protoCls}">${proto.toUpperCase()}</span></td>
            <td><span class="mono">${c.src}</span></td>
            <td><span class="mono" style="color:var(--text-dim);">${c.dst}</span></td>
            <td><span class="pill ${stCls}" style="font-size:10px;">${c.state || '—'}</span></td>
            <td style="font-size:12px; color:var(--success);">${formatBytes(c.orig_bytes)}</td>
            <td style="font-size:12px; color:var(--accent2);">${formatBytes(c.repl_bytes)}</td>
            <td style="font-size:11px; color:var(--text-muted);">${c.timeout || '—'}</td>
          </tr>
        `;
      });
      $('#conn-body').html(rows.join('') || '<tr class="loading-row"><td colspan="7">Bağlantı bulunamadı</td></tr>');
      setLastUpdate();
    });
  }

  $('#limit-select').on('change', fetchConnections);
  window.refreshAll = fetchConnections;
  fetchConnections();
  setInterval(fetchConnections, <?= REFRESH_INTERVAL ?>);
});
</script>
