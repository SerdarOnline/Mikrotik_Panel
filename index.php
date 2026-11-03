<?php
$pageTitle  = 'Dashboard';
$activePage = 'index';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Yüksek Trafik Canlı Uyarı Bandı ── -->
<div id="high-speed-alert" style="display:none; background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.3); border-radius:12px; padding:12px 18px; margin-bottom:20px; align-items:center; justify-content:space-between;">
  <div style="display:flex; align-items:center; gap:12px;">
    <div style="color:var(--danger); font-size:20px;"><i class="fas fa-triangle-exclamation"></i></div>
    <div>
      <div style="font-size:13px; font-weight:700; color:var(--text-primary);" id="alert-title">Yüksek Download Uyarısı!</div>
      <div style="font-size:11.5px; color:var(--text-dim);" id="alert-msg">Cihaz yüksek bant genişliği tüketiyor.</div>
    </div>
  </div>
  <div id="alert-actions"></div>
</div>

<!-- ── Stat Cards ──────────────────────────────────────────── -->
<div class="stat-grid" id="stat-grid">
  <!-- CPU -->
  <div class="stat-card" style="--card-color:#3b82f6; --card-rgb:59,130,246;">
    <div class="card-icon"><i class="fas fa-microchip"></i></div>
    <div class="card-value" id="stat-cpu">—</div>
    <div class="card-label">CPU Kullanımı</div>
    <div class="card-sub" id="stat-uptime">Uptime: —</div>
  </div>
  <!-- RAM -->
  <div class="stat-card" style="--card-color:#8b5cf6; --card-rgb:139,92,246;">
    <div class="card-icon"><i class="fas fa-memory"></i></div>
    <div class="card-value" id="stat-ram">—</div>
    <div class="card-label">RAM Kullanımı</div>
    <div class="card-sub" id="stat-ram-detail">Boş: —</div>
  </div>
  <!-- Toplam Download -->
  <div class="stat-card" style="--card-color:#06b6d4; --card-rgb:6,182,212;">
    <div class="card-icon"><i class="fas fa-arrow-down"></i></div>
    <div class="card-value" id="stat-rx">—</div>
    <div class="card-label">Toplam Download</div>
    <div class="card-sub">Tüm interface'ler</div>
  </div>
  <!-- Aktif Bağlantılar -->
  <div class="stat-card" style="--card-color:#10b981; --card-rgb:16,185,129;">
    <div class="card-icon"><i class="fas fa-plug"></i></div>
    <div class="card-value" id="stat-conn">—</div>
    <div class="card-label">Aktif Bağlantı</div>
    <div class="card-sub" id="stat-version">RouterOS —</div>
  </div>
</div>

<!-- ── Ana Grid: Trafik Grafiği + Top Kullanıcılar ────────── -->
<div class="grid-2" style="margin-bottom:20px;">

  <!-- Interface Canlı Trafik Grafiği -->
  <div class="panel">
    <div class="panel-header">
      <h3>
        <span class="ph-icon"><i class="fas fa-wave-square"></i></span>
        Canlı Interface Trafiği
      </h3>
      <select id="iface-select" style="
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--border);
        color: var(--text-primary);
        border-radius: 7px;
        padding: 6px 10px;
        font-size: 12px;
        font-family: inherit;
        cursor: pointer;
      ">
        <option value="ether1">ether1</option>
      </select>
    </div>
    <div class="panel-body">
      <div style="display:flex; gap:20px; margin-bottom:14px;">
        <div>
          <div style="font-size:11px; color:var(--text-muted); margin-bottom:3px;">⬇ DOWNLOAD</div>
          <div class="speed-badge down" id="live-rx">— Mbps</div>
        </div>
        <div>
          <div style="font-size:11px; color:var(--text-muted); margin-bottom:3px;">⬆ UPLOAD</div>
          <div class="speed-badge up" id="live-tx">— Mbps</div>
        </div>
      </div>
      <div class="chart-wrap">
        <canvas id="trafficChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Sistem Kaynakları -->
  <div class="panel">
    <div class="panel-header">
      <h3>
        <span class="ph-icon"><i class="fas fa-server"></i></span>
        Sistem Kaynakları
      </h3>
      <span class="pill pill-blue" id="board-name">MikroTik</span>
    </div>
    <div class="panel-body">
      <!-- CPU Ring -->
      <div class="progress-ring-wrap" style="margin-bottom:24px;">
        <svg width="70" height="70" class="progress-ring" id="cpu-ring">
          <circle cx="35" cy="35" r="28" stroke="rgba(59,130,246,0.1)" stroke-width="6" fill="none"/>
          <circle cx="35" cy="35" r="28" stroke="#3b82f6" stroke-width="6" fill="none"
            stroke-linecap="round"
            class="ring-progress"
            style="stroke-dasharray:175.9; stroke-dashoffset:175.9; transition: stroke-dashoffset 0.6s ease;"/>
          <text x="35" y="40" text-anchor="middle" fill="var(--text-primary)" font-size="12" font-weight="700" class="ring-text" style="transform:rotate(90deg); transform-origin:35px 35px;">0%</text>
        </svg>
        <div>
          <div style="font-size:14px; font-weight:700; color:var(--text-primary);">CPU</div>
          <div style="font-size:12px; color:var(--text-muted); margin-top:4px;" id="cpu-detail">—</div>
          <div style="font-size:11px; color:var(--text-muted); margin-top:2px;" id="cpu-freq">Frekans: —</div>
        </div>
      </div>
      <!-- RAM Ring -->
      <div class="progress-ring-wrap" style="margin-bottom:24px;">
        <svg width="70" height="70" class="progress-ring" id="ram-ring">
          <circle cx="35" cy="35" r="28" stroke="rgba(139,92,246,0.1)" stroke-width="6" fill="none"/>
          <circle cx="35" cy="35" r="28" stroke="#8b5cf6" stroke-width="6" fill="none"
            stroke-linecap="round"
            class="ring-progress"
            style="stroke-dasharray:175.9; stroke-dashoffset:175.9; transition: stroke-dashoffset 0.6s ease;"/>
          <text x="35" y="40" text-anchor="middle" fill="var(--text-primary)" font-size="12" font-weight="700" class="ring-text" style="transform:rotate(90deg); transform-origin:35px 35px;">0%</text>
        </svg>
        <div>
          <div style="font-size:14px; font-weight:700; color:var(--text-primary);">RAM</div>
          <div style="font-size:12px; color:var(--text-muted); margin-top:4px;" id="ram-detail">—</div>
        </div>
      </div>
      <!-- Interface listesi -->
      <div style="margin-top:16px;">
        <div style="font-size:11px; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:10px;">
          Aktif Interface'ler
        </div>
        <div id="iface-list" class="iface-grid" style="grid-template-columns: 1fr 1fr;"></div>
      </div>
    </div>
  </div>

</div>

<!-- ── TOP USERS: Kim En Çok Download Yapıyor ─────────────── -->
<div class="panel">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-arrow-trend-down"></i></span>
      En Çok Download Yapanlar
      <span class="pill pill-blue" id="user-count" style="margin-left:8px;">0 kullanıcı</span>
    </h3>
    <div style="display:flex; align-items:center; gap:10px;">
      <button onclick="exportUsersCSV('#top-users-table')" class="pill pill-green" style="cursor:pointer; border:none; padding:6px 12px; font-size:12px; font-weight:600;" title="Verileri CSV/Excel Olarak İndir">
        <i class="fas fa-file-csv"></i> CSV Aktar
      </button>
      <div class="search-wrap">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" class="search-input table-search" data-target="#top-users-table" placeholder="IP veya hostname ara...">
      </div>
      <div class="chart-wrap" style="width:160px; height:36px; margin:0;">
        <canvas id="userPieChart" width="160" height="36" style="max-height:36px;"></canvas>
      </div>
    </div>
  </div>
  <div class="panel-body no-pad">
    <table class="data-table" id="top-users-table">
      <thead>
        <tr>
          <th style="width:50px;">#</th>
          <th>Kullanıcı</th>
          <th>IP Adresi</th>
          <th>MAC Adresi</th>
          <th style="color:var(--accent2); font-weight:800;">⚡ Canlı Download</th>
          <th>⚡ Canlı Upload</th>
          <th>Toplam DL</th>
          <th>Bağlantılar</th>
          <th>Limit</th>
          <th style="text-align:right;">İşlemler</th>
        </tr>
      </thead>
      <tbody id="top-users-body">
        <tr class="loading-row">
          <td colspan="8">
            <div class="spinner" style="margin:0 auto;"></div>
            <div style="margin-top:10px;">Kullanıcı verileri yükleniyor...</div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Download Dağılımı Çubuğu (TOP 5) ──────────────────── -->
<div class="panel">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-chart-bar"></i></span>
      Download Dağılımı — Top 5
    </h3>
  </div>
  <div class="panel-body">
    <canvas id="topUsersBarChart" height="80"></canvas>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {

  // ── Chart.js: Canlı Trafik ──────────────────────────────────
  const MAX_POINTS = 30;
  const rxData = new Array(MAX_POINTS).fill(0);
  const txData = new Array(MAX_POINTS).fill(0);
  const labels = new Array(MAX_POINTS).fill('');

  const trafficCtx = document.getElementById('trafficChart').getContext('2d');
  const trafficChart = new Chart(trafficCtx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Download (Mbps)',
          data: rxData,
          borderColor: '#06b6d4',
          backgroundColor: 'rgba(6,182,212,0.08)',
          borderWidth: 2,
          fill: true,
          tension: 0.4,
          pointRadius: 0,
        },
        {
          label: 'Upload (Mbps)',
          data: txData,
          borderColor: '#10b981',
          backgroundColor: 'rgba(16,185,129,0.08)',
          borderWidth: 2,
          fill: true,
          tension: 0.4,
          pointRadius: 0,
        },
      ],
    },
    options: {
      responsive: true,
      animation: { duration: 300 },
      plugins: {
        legend: {
          labels: { color: '#94a3b8', font: { size: 11, family: 'Inter' } }
        },
        tooltip: {
          backgroundColor: '#111827',
          borderColor: '#1f2d44',
          borderWidth: 1,
          titleColor: '#f1f5f9',
          bodyColor: '#94a3b8',
          callbacks: {
            label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.raw.toFixed(2) + ' Mbps'
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(31,45,68,0.5)', drawBorder: false },
          ticks: { color: '#475569', font: { size: 10 } }
        },
        y: {
          grid: { color: 'rgba(31,45,68,0.5)', drawBorder: false },
          ticks: { color: '#475569', font: { size: 10 }, callback: v => v + ' M' }
        },
      },
    },
  });

  // ── Chart.js: Top 5 Bar ─────────────────────────────────────
  const barCtx = document.getElementById('topUsersBarChart').getContext('2d');
  const barChart = new Chart(barCtx, {
    type: 'bar',
    data: {
      labels: [],
      datasets: [{
        label: 'Download (MB)',
        data: [],
        backgroundColor: [
          'rgba(251,191,36,0.7)',
          'rgba(59,130,246,0.7)',
          'rgba(139,92,246,0.7)',
          'rgba(6,182,212,0.7)',
          'rgba(16,185,129,0.7)',
        ],
        borderRadius: 6,
        borderSkipped: false,
      }],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => ' ' + ctx.raw.toFixed(1) + ' MB download'
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(31,45,68,0.5)' },
          ticks: { color: '#475569', font: { size: 10 } }
        },
        y: {
          grid: { display: false },
          ticks: { color: '#94a3b8', font: { size: 12, weight: '600' } }
        },
      },
    },
  });

  // ── Sistem Durumu Çek ────────────────────────────────────────
  function fetchStatus() {
    $.getJSON('./api.php?action=status', function (res) {
      if (!res.success) {
        showToast('Sistem durumu alınamadı: ' + (res.error || 'Bilinmeyen hata'), 'error');
        return;
      }
      const s = res.system;

      // Stat cards
      $('#stat-cpu').text(s.cpu_load + '%');
      $('#stat-uptime').text('Uptime: ' + s.uptime);
      $('#stat-version').text('RouterOS ' + s.version);
      $('#board-name').text(s.board);

      const totalMb  = Math.round(s.total_memory / 1048576);
      const freeMb   = Math.round(s.free_memory  / 1048576);
      const usedMb   = totalMb - freeMb;
      const ramPct   = Math.round((usedMb / totalMb) * 100);
      $('#stat-ram').text(ramPct + '%');
      $('#stat-ram-detail').text('Boş: ' + freeMb + ' MB');
      $('#stat-conn').text(s.connections.toLocaleString('tr-TR'));
      $('#stat-rx').text(formatBytes(s.total_rx));

      // CPU ring
      const cpuPct = s.cpu_load;
      const cpuColor = cpuPct > 80 ? '#ef4444' : cpuPct > 50 ? '#f59e0b' : '#3b82f6';
      const circ = 2 * Math.PI * 28;
      $('#cpu-ring .ring-progress')
        .attr('stroke', cpuColor)
        .css('stroke-dashoffset', circ - (cpuPct / 100) * circ);
      $('#cpu-ring .ring-text').text(cpuPct + '%');
      $('#cpu-detail').text('Yük: ' + cpuPct + '%');

      // RAM ring
      const ramColor = ramPct > 85 ? '#ef4444' : ramPct > 60 ? '#f59e0b' : '#8b5cf6';
      $('#ram-ring .ring-progress')
        .attr('stroke', ramColor)
        .css('stroke-dashoffset', circ - (ramPct / 100) * circ);
      $('#ram-ring .ring-text').text(ramPct + '%');
      $('#ram-detail').text(usedMb + ' / ' + totalMb + ' MB');

      // Interface listesi
      const $list = $('#iface-list').empty();
      (s.interfaces || []).slice(0, 6).forEach(function (iface) {
        $list.append(`
          <div class="iface-card">
            <div class="iface-name">
              <span>${iface.name}</span>
              <span class="pill pill-green" style="font-size:9px;">●</span>
            </div>
            <div class="iface-stat">
              <span class="label">⬇ DL</span>
              <span class="val" style="color:var(--accent2);">${formatBytes(iface.rx)}</span>
            </div>
            <div class="iface-stat">
              <span class="label">⬆ UL</span>
              <span class="val" style="color:var(--success);">${formatBytes(iface.tx)}</span>
            </div>
          </div>
        `);
      });

      // Interface select güncelle
      if (s.interfaces && s.interfaces.length) {
        const $sel = $('#iface-select');
        const current = $sel.val();
        $sel.empty();
        s.interfaces.forEach(function (iface) {
          $sel.append(`<option value="${iface.name}" ${iface.name === current ? 'selected' : ''}>${iface.name}</option>`);
        });
      }

      setLastUpdate();
    }).fail(function () {
      showToast('API\'ye bağlanılamadı', 'error');
    });
  }

  // ── Canlı Trafik Çek ────────────────────────────────────────
  let historyLoaded = false;
  function fetchTraffic() {
    const iface = $('#iface-select').val() || '1-WAN1';
    $.getJSON('./api.php?action=traffic&iface=' + encodeURIComponent(iface), function (res) {
      if (!res.success || !res.traffic) return;
      const t = res.traffic;
      const rxMbps = (t.rx_bps / 1_000_000).toFixed(3) * 1;
      const txMbps = (t.tx_bps / 1_000_000).toFixed(3) * 1;

      // Live labels
      $('#live-rx').text(formatSpeed(t.rx_bps));
      $('#live-tx').text(formatSpeed(t.tx_bps));

      // Grafik geçmişi yükleme (ilk açılışta)
      if (!historyLoaded && t.history && t.history.length) {
        const hist = t.history.slice(-MAX_POINTS);
        for (let i = 0; i < hist.length; i++) {
          rxData[i] = hist[i].rx;
          txData[i] = hist[i].tx;
          labels[i] = hist[i].time;
        }
        historyLoaded = true;
      } else {
        const now = new Date();
        const timeLabel = now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        rxData.push(rxMbps); rxData.shift();
        txData.push(txMbps); txData.shift();
        labels.push(timeLabel); labels.shift();
      }
      trafficChart.update('none');
    });
  }

  // ── Top Users Çek ───────────────────────────────────────────
  function fetchTopUsers() {
    $.getJSON('./api.php?action=top_users', function (res) {
      if (!res.success) {
        $('#top-users-body').html(`
          <tr class="loading-row">
            <td colspan="10">
              <i class="fas fa-triangle-exclamation" style="color:var(--danger);"></i>
              ${res.error || 'Veri alınamadı'}
            </td>
          </tr>
        `);
        return;
      }

      const users = res.users || [];
      $('#user-count').text(users.length + ' kullanıcı');

      if (!users.length) {
        $('#top-users-body').html(`
          <tr class="loading-row">
            <td colspan="10">
              <i class="fas fa-info-circle" style="color:var(--accent);"></i>
              Aktif trafik bulunamadı
            </td>
          </tr>
        `);
        return;
      }

      // High Speed Alert (> 3 Mbps download yapan)
      const topSpieder = users.find(u => u.rx_bps > 3000000);
      if (topSpieder) {
        const speedText = formatSpeed(topSpieder.rx_bps);
        $('#alert-title').html('🚨 YÜKSEK İNDİRME UYARISI: <span class="mono" style="color:var(--accent2);">' + topSpieder.ip + '</span> (' + (topSpieder.hostname || 'İsimsiz') + ')');
        $('#alert-msg').html('Cihaz şu an <strong>' + speedText + '</strong> hızında canlı download yapıyor!');
        $('#alert-actions').html(`
          <div style="display:flex; gap:6px;">
            <button onclick="quickSetSpeed('${topSpieder.ip}', '5M', '2M')" class="pill pill-yellow" style="cursor:pointer; border:none; padding:6px 12px; font-weight:700;">
              ⚡ 5M Limit Koy
            </button>
            <button onclick="quickKillConns('${topSpieder.ip}')" class="pill pill-red" style="cursor:pointer; border:none; padding:6px 12px;">
              <i class="fas fa-power-off"></i> Kes
            </button>
          </div>
        `);
        $('#high-speed-alert').css('display', 'flex');
        if (typeof window.playAlertSound === 'function') window.playAlertSound();
      } else {
        $('#high-speed-alert').hide();
      }

      // En yüksek rx değeri (bar width için)
      const maxRx = Math.max(...users.map(u => u.rx));

      const rows = users.map(function (u, i) {
        const rank = i + 1;
        const pct  = maxRx > 0 ? Math.round((u.rx / maxRx) * 100) : 0;
        const vendorBadge = u.vendor
          ? `<span class="pill pill-gray" style="font-size:10px; margin-left:4px;"><i class="fas fa-microchip"></i> ${u.vendor}</span>`
          : '';
        const displayLabel = u.hostname
          ? `<span style="font-weight:600;color:var(--text-primary);">${u.hostname}</span>${vendorBadge}`
          : `<span style="color:var(--text-muted);">—</span>${vendorBadge}`;

        const liveDl = formatSpeed(u.rx_bps);
        const liveUl = formatSpeed(u.tx_bps);
        const isDownloading = u.rx_bps > 50000; // > 50 Kbps
        const liveDlBadge = isDownloading
          ? `<span class="pill pill-green" style="font-family:'JetBrains Mono',monospace; font-size:12px; font-weight:700; box-shadow:0 0 10px rgba(16,185,129,0.3);">⚡ ${liveDl}</span>`
          : `<span style="font-family:'JetBrains Mono',monospace; font-size:12px; color:var(--text-muted);">${liveDl}</span>`;

        const liveUlBadge = u.tx_bps > 20000
          ? `<span class="pill pill-blue" style="font-family:'JetBrains Mono',monospace; font-size:11px;">${liveUl}</span>`
          : `<span style="font-family:'JetBrains Mono',monospace; font-size:11px; color:var(--text-muted);">${liveUl}</span>`;

        const limitBadge = u.banned
          ? `<span class="pill pill-red" title="Cihaz KESIN_ENGEL listesinde engelli!">🚫 BANLI</span>`
          : u.limit
          ? `<span class="pill pill-yellow" title="Simple Queue aktif: ${u.limit}">🔒 ${u.limit}</span>`
          : `<span style="color:var(--text-muted); font-size:11px;">—</span>`;

        const banBtn = u.banned
          ? `<button onclick="unbanIp('${u.ip}')" class="pill pill-green" style="cursor:pointer; border:none; padding:4px 8px; font-size:11px;" title="Engeli Kaldır">
               <i class="fas fa-unlock"></i> Unban
             </button>`
          : `<button onclick="banIp('${u.ip}')" class="pill pill-red" style="cursor:pointer; border:none; padding:4px 8px; font-size:11px;" title="Cihazı Engelle (BAN)">
               <i class="fas fa-ban"></i> Ban
             </button>`;

        return `
          <tr>
            <td>${rankBadge(rank)}</td>
            <td>
              ${displayLabel}
              ${u.comment ? `<div style="font-size:10px; color:var(--text-muted); margin-top:2px;">${u.comment}</div>` : ''}
            </td>
            <td><span class="mono">${u.ip}</span></td>
            <td><span class="mac">${u.mac || '—'}</span></td>
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
                ${banBtn}
              </div>
            </td>
          </tr>
        `;
      });

      $('#top-users-body').html(rows.join(''));

      // Bar chart güncelle (top 5)
      const top5 = users.slice(0, 5);
      barChart.data.labels   = top5.map(u => u.hostname || u.ip);
      barChart.data.datasets[0].data = top5.map(u => (u.rx / 1_048_576).toFixed(2) * 1);
      barChart.update();
    });
  }

  // ── Interface değişince yenile ───────────────────────────────
  $('#iface-select').on('change', fetchTraffic);

  // ── Global refresh ───────────────────────────────────────────
  window.refreshAll = function () {
    fetchStatus();
    fetchTraffic();
    fetchTopUsers();
  };

  // ── İlk yükleme ─────────────────────────────────────────────
  refreshAll();

  // ── Otomatik yenileme (config'den gelen süre) ────────────────
  const REFRESH_MS = <?= REFRESH_INTERVAL ?>;
  setInterval(fetchTraffic,   REFRESH_MS);
  setInterval(fetchTopUsers,  REFRESH_MS * 2);  // Top users daha seyrek
  setInterval(fetchStatus,    REFRESH_MS * 4);  // Sistem durumu daha seyrek
});
</script>
