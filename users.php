<?php
$pageTitle  = 'Kullanıcılar';
$activePage = 'users';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Özet Kartlar -->
<div class="stat-grid" style="margin-bottom:24px;">
  <div class="stat-card" style="--card-color:#10b981;">
    <div class="card-icon"><i class="fas fa-circle-check"></i></div>
    <div class="card-value" id="u-active">—</div>
    <div class="card-label">Aktif Cihaz (bound)</div>
  </div>
  <div class="stat-card" style="--card-color:#f59e0b;">
    <div class="card-icon"><i class="fas fa-hourglass-half"></i></div>
    <div class="card-value" id="u-waiting">—</div>
    <div class="card-label">Bekleyen / Diğer</div>
  </div>
  <div class="stat-card" style="--card-color:#3b82f6;">
    <div class="card-icon"><i class="fas fa-list-ol"></i></div>
    <div class="card-value" id="u-total">—</div>
    <div class="card-label">Toplam Lease</div>
  </div>
  <div class="stat-card" style="--card-color:#8b5cf6;">
    <div class="card-icon"><i class="fas fa-download"></i></div>
    <div class="card-value" id="u-dl-total">—</div>
    <div class="card-label">Toplam Download</div>
  </div>
</div>

<!-- Kullanıcı tablosu + Trafik dağılımı -->
<div class="grid-2" style="margin-bottom:20px; align-items:start;">

  <!-- Pie chart -->
  <div class="panel">
    <div class="panel-header">
      <h3><span class="ph-icon"><i class="fas fa-chart-pie"></i></span> Download Dağılımı</h3>
    </div>
    <div class="panel-body" style="display:flex; justify-content:center;">
      <div style="max-width:300px; width:100%;">
        <canvas id="userPieChart" height="260"></canvas>
      </div>
    </div>
  </div>

  <!-- Kullanıcı bazlı bar -->
  <div class="panel">
    <div class="panel-header">
      <h3><span class="ph-icon"><i class="fas fa-chart-bar"></i></span> Kullanıcı Trafiği</h3>
    </div>
    <div class="panel-body">
      <canvas id="userBarChart" height="260"></canvas>
    </div>
  </div>

</div>

<!-- DHCP Lease Tablosu -->
<div class="panel" style="margin-bottom:20px;">
  <div class="panel-header">
    <h3><span class="ph-icon"><i class="fas fa-ethernet"></i></span> DHCP Lease Listesi</h3>
    <div class="search-wrap">
      <i class="fas fa-magnifying-glass"></i>
      <input type="text" class="search-input table-search" data-target="#lease-table" placeholder="IP, MAC veya hostname ara...">
    </div>
  </div>
  <div class="panel-body no-pad">
    <table class="data-table" id="lease-table">
      <thead>
        <tr>
          <th>#</th>
          <th>IP Adresi</th>
          <th>MAC Adresi</th>
          <th>Hostname</th>
          <th>Yorum</th>
          <th>Durum</th>
          <th>DHCP Sunucu</th>
          <th>Bitiş</th>
        </tr>
      </thead>
      <tbody id="lease-body">
        <tr class="loading-row"><td colspan="8"><div class="spinner" style="margin:0 auto;"></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Top Users (kim ne kadar download yapıyor) -->
<div class="panel">
  <div class="panel-header">
    <h3><span class="ph-icon"><i class="fas fa-arrow-trend-down"></i></span> Trafik Analizi — Kim Ne Kadar İndiriyor?</h3>
    <div style="display:flex; align-items:center; gap:10px;">
      <button onclick="exportUsersCSV('#users-traffic-table')" class="pill pill-green" style="cursor:pointer; border:none; padding:6px 12px; font-size:12px; font-weight:600;" title="Verileri CSV/Excel Olarak İndir">
        <i class="fas fa-file-csv"></i> CSV Aktar
      </button>
      <div class="search-wrap">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" class="search-input table-search" data-target="#users-traffic-table" placeholder="Kullanıcı ara...">
      </div>
    </div>
  </div>
  <!-- Subnet Filtre Tabları -->
  <div style="padding:12px 20px 0; border-bottom:1px solid var(--border); display:flex; gap:6px; flex-wrap:wrap;">
    <button class="tab-btn active" data-subnet="all" onclick="filterSubnet('all', this)">
      Tüm Subnetler <span id="cnt-sub-all" class="pill pill-blue" style="margin-left:4px;">0</span>
    </button>
    <button class="tab-btn" data-subnet="192.168.1." onclick="filterSubnet('192.168.1.', this)">
      192.168.1.x (Sunucu/PC) <span id="cnt-sub-1" class="pill pill-gray" style="margin-left:4px;">0</span>
    </button>
    <button class="tab-btn" data-subnet="192.168.2." onclick="filterSubnet('192.168.2.', this)">
      192.168.2.x (Mobil/Wi-Fi) <span id="cnt-sub-2" class="pill pill-gray" style="margin-left:4px;">0</span>
    </button>
    <button class="tab-btn" data-subnet="192.168.3." onclick="filterSubnet('192.168.3.', this)">
      192.168.3.x (Depo/Tablet) <span id="cnt-sub-3" class="pill pill-gray" style="margin-left:4px;">0</span>
    </button>
  </div>
  <div class="panel-body no-pad">
    <table class="data-table" id="users-traffic-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Kullanıcı / Hostname</th>
          <th>IP Adresi</th>
          <th style="color:var(--accent2); font-weight:800;">⚡ Canlı Download</th>
          <th>⚡ Canlı Upload</th>
          <th>Toplam DL</th>
          <th>Bağlantılar</th>
          <th>Limit</th>
          <th style="text-align:right;">İşlemler</th>
        </tr>
      </thead>
      <tbody id="users-traffic-body">
        <tr class="loading-row"><td colspan="8"><div class="spinner" style="margin:0 auto;"></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {

  // ── Pie Chart ────────────────────────────────────────────────
  const pieCtx = document.getElementById('userPieChart').getContext('2d');
  const pieChart = new Chart(pieCtx, {
    type: 'doughnut',
    data: {
      labels: [],
      datasets: [{
        data: [],
        backgroundColor: [
          '#fbbf24','#3b82f6','#8b5cf6','#06b6d4','#10b981',
          '#f59e0b','#ef4444','#ec4899','#14b8a6','#a855f7',
        ],
        borderColor: '#111827',
        borderWidth: 2,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: '#94a3b8', font: { size: 11 }, padding: 10, boxWidth: 12 }
        },
        tooltip: {
          callbacks: {
            label: c => ' ' + c.label + ': ' + formatBytes(c.raw * 1048576)
          }
        }
      }
    }
  });

  // ── Bar Chart ─────────────────────────────────────────────────
  const barCtx = document.getElementById('userBarChart').getContext('2d');
  const barChart = new Chart(barCtx, {
    type: 'bar',
    data: {
      labels: [],
      datasets: [
        { label: '⬇ Download (MB)', data: [], backgroundColor: 'rgba(6,182,212,0.7)', borderRadius: 4 },
        { label: '⬆ Upload (MB)',   data: [], backgroundColor: 'rgba(16,185,129,0.7)',  borderRadius: 4 },
      ]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: {
        legend: { labels: { color: '#94a3b8', font: { size: 11 } } },
        tooltip: { callbacks: { label: c => ' ' + c.raw.toFixed(1) + ' MB' } }
      },
      scales: {
        x: { grid: { color: 'rgba(31,45,68,0.5)' }, ticks: { color: '#475569', font: { size: 10 } } },
        y: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11, weight: '600' } } }
      }
    }
  });

  // ── DHCP Leases ───────────────────────────────────────────────
  function fetchLeases() {
    $.getJSON('./api.php?action=dhcp_leases', function (res) {
      if (!res.success) return;
      const leases = res.leases || [];
      const active  = leases.filter(l => l.status === 'bound').length;
      const waiting = leases.length - active;

      $('#u-total').text(leases.length);
      $('#u-active').text(active);
      $('#u-waiting').text(waiting);

      const rows = leases.map((l, i) => {
        const st = l.status === 'bound'
          ? '<span class="pill pill-green">Aktif</span>'
          : l.status === 'waiting'
          ? '<span class="pill pill-yellow">Bekliyor</span>'
          : `<span class="pill pill-gray">${l.status || '?'}</span>`;
        return `
          <tr>
            <td style="color:var(--text-muted);">${i + 1}</td>
            <td><span class="mono">${l.ip}</span></td>
            <td><span class="mac">${l.mac}</span></td>
            <td style="font-weight:600; color:var(--text-primary);">${l.hostname || '<span style="color:var(--text-muted);">—</span>'}</td>
            <td style="color:var(--text-muted); font-size:12px;">${l.comment || '—'}</td>
            <td>${st}</td>
            <td style="font-size:11px; color:var(--text-muted);">${l.server || '—'}</td>
            <td style="font-size:11px; color:var(--text-muted);">${l.expires || '—'}</td>
          </tr>
        `;
      });
      $('#lease-body').html(rows.join('') || '<tr class="loading-row"><td colspan="8">Lease bulunamadı</td></tr>');
    });
  }

  let allTrafficUsers = [];
  let currentSubnet = 'all';

  window.filterSubnet = function (subnet, btn) {
    currentSubnet = subnet;
    document.querySelectorAll('[data-subnet]').forEach(b => b.classList.remove('active'));
    $(btn).addClass('active');
    renderTrafficTable();
  };

  function renderTrafficTable() {
    const filtered = currentSubnet === 'all'
      ? allTrafficUsers
      : allTrafficUsers.filter(u => u.ip.startsWith(currentSubnet));

    const maxRx = Math.max(...filtered.map(u => u.rx), 1);
    const rows = filtered.map((u, i) => {
      const pct = Math.round((u.rx / maxRx) * 100);
      const liveDl = formatSpeed(u.rx_bps);
      const liveUl = formatSpeed(u.tx_bps);
      const isDownloading = u.rx_bps > 50000;
      const liveDlBadge = isDownloading
        ? `<span class="pill pill-green" style="font-family:'JetBrains Mono',monospace; font-size:12px; font-weight:700; box-shadow:0 0 10px rgba(16,185,129,0.3);">⚡ ${liveDl}</span>`
        : `<span style="font-family:'JetBrains Mono',monospace; font-size:12px; color:var(--text-muted);">${liveDl}</span>`;

      const liveUlBadge = u.tx_bps > 20000
        ? `<span class="pill pill-blue" style="font-family:'JetBrains Mono',monospace; font-size:11px;">${liveUl}</span>`
        : `<span style="font-family:'JetBrains Mono',monospace; font-size:11px; color:var(--text-muted);">${liveUl}</span>`;

      const limitBadge = u.limit
        ? `<span class="pill pill-yellow" title="Simple Queue aktif: ${u.limit}">🔒 ${u.limit}</span>`
        : `<span style="color:var(--text-muted); font-size:11px;">—</span>`;

      return `
          <tr>
            <td>${rankBadge(i + 1)}</td>
            <td>
              <span style="font-weight:600;color:var(--text-primary);">${u.hostname || '—'}</span>
              ${u.comment ? `<div style="font-size:10px;color:var(--text-muted);">${u.comment}</div>` : ''}
            </td>
            <td><span class="mono">${u.ip}</span></td>
            <td>${liveDlBadge}</td>
            <td>${liveUlBadge}</td>
            <td>
              <span class="speed-badge down" style="font-size:11px;">${formatBytes(u.rx)}</span>
              <div class="dl-bar-wrap"><div class="dl-bar" style="width:${pct}%;"></div></div>
            </td>
            <td><span class="pill pill-blue">${u.conns}</span></td>
            <td>${limitBadge}</td>
            <td style="text-align:right;">
              <div style="display:inline-flex; gap:4px;">
                <button onclick="openUserModal('${u.ip}')" class="pill pill-blue" style="cursor:pointer; border:none; padding:4px 8px; font-size:11px;" title="Detay & Analiz">
                  <i class="fas fa-magnifying-glass"></i> Detay
                </button>
                <button onclick="quickSetSpeed('${u.ip}', '5M', '2M')" class="pill pill-yellow" style="cursor:pointer; border:none; padding:4px 8px; font-size:11px;" title="5 Mbps Limit Koy">
                  <i class="fas fa-gauge-high"></i> 5M
                </button>
                <button onclick="quickKillConns('${u.ip}')" class="pill pill-red" style="cursor:pointer; border:none; padding:4px 8px; font-size:11px;" title="Bağlantıları Kes">
                  <i class="fas fa-power-off"></i>
                </button>
              </div>
            </td>
          </tr>
        `;
      });
      $('#users-traffic-body').html(rows.join('') || '<tr class="loading-row"><td colspan="8">Trafik verisi bulunamadı</td></tr>');

      // Pie chart — top 8
      const top8 = users.slice(0, 8);
      pieChart.data.labels = top8.map(u => u.hostname || u.ip);
      pieChart.data.datasets[0].data = top8.map(u => +(u.rx / 1048576).toFixed(2));
      pieChart.update();

      // Bar chart — top 8
      barChart.data.labels = top8.map(u => u.hostname || u.ip);
      barChart.data.datasets[0].data = top8.map(u => +(u.rx / 1048576).toFixed(2));
      barChart.data.datasets[1].data = top8.map(u => +(u.tx / 1048576).toFixed(2));
      barChart.update();

      setLastUpdate();
    });
  }

  window.refreshAll = function () { fetchLeases(); fetchTopUsers(); };
  refreshAll();
  setInterval(fetchLeases,    20000);
  setInterval(fetchTopUsers,  <?= REFRESH_INTERVAL ?> * 2);
});
</script>
