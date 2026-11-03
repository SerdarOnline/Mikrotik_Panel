<?php
$pageTitle  = 'Firewall Kuralları';
$activePage = 'firewall';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Özet -->
<div class="stat-grid" style="margin-bottom:24px;">
  <div class="stat-card" style="--card-color:#3b82f6;">
    <div class="card-icon"><i class="fas fa-shield-halved"></i></div>
    <div class="card-value" id="fw-total">—</div>
    <div class="card-label">Toplam Kural</div>
  </div>
  <div class="stat-card" style="--card-color:#10b981;">
    <div class="card-icon"><i class="fas fa-circle-check"></i></div>
    <div class="card-value" id="fw-accept">—</div>
    <div class="card-label">Accept Kuralı</div>
  </div>
  <div class="stat-card" style="--card-color:#ef4444;">
    <div class="card-icon"><i class="fas fa-ban"></i></div>
    <div class="card-value" id="fw-drop">—</div>
    <div class="card-label">Drop/Reject</div>
  </div>
  <div class="stat-card" style="--card-color:#f59e0b;">
    <div class="card-icon"><i class="fas fa-pause"></i></div>
    <div class="card-value" id="fw-disabled">—</div>
    <div class="card-label">Devre Dışı</div>
  </div>
</div>

<!-- Filter chain tabları -->
<div class="panel">
  <div class="panel-header">
    <h3><span class="ph-icon"><i class="fas fa-filter"></i></span> Firewall Filter Kuralları</h3>
    <div class="search-wrap">
      <i class="fas fa-magnifying-glass"></i>
      <input type="text" class="search-input table-search" data-target="#fw-table" placeholder="Kural ara...">
    </div>
  </div>

  <!-- Chain tabları -->
  <div style="padding:12px 20px 0; border-bottom:1px solid var(--border); display:flex; gap:6px;">
    <button class="tab-btn active" data-chain="all" onclick="filterChain('all', this)">
      Tümü <span id="cnt-all" class="pill pill-blue" style="margin-left:4px;">0</span>
    </button>
    <button class="tab-btn" data-chain="input" onclick="filterChain('input', this)">
      Input <span id="cnt-input" class="pill pill-gray" style="margin-left:4px;">0</span>
    </button>
    <button class="tab-btn" data-chain="forward" onclick="filterChain('forward', this)">
      Forward <span id="cnt-forward" class="pill pill-gray" style="margin-left:4px;">0</span>
    </button>
    <button class="tab-btn" data-chain="output" onclick="filterChain('output', this)">
      Output <span id="cnt-output" class="pill pill-gray" style="margin-left:4px;">0</span>
    </button>
  </div>

  <div class="panel-body no-pad">
    <table class="data-table" id="fw-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Chain</th>
          <th>Action</th>
          <th>Protokol</th>
          <th>Kaynak</th>
          <th>Hedef</th>
          <th>Paket</th>
          <th>Byte</th>
          <th>Yorum</th>
          <th>Durum</th>
        </tr>
      </thead>
      <tbody id="fw-body">
        <tr class="loading-row"><td colspan="10"><div class="spinner" style="margin:0 auto;"></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
let allRules = [];
let currentChain = 'all';

function filterChain(chain, btn) {
  currentChain = chain;
  document.querySelectorAll('[data-chain]').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderRules();
}

function renderRules() {
  const rules = currentChain === 'all'
    ? allRules
    : allRules.filter(r => r.chain === currentChain);

  const rows = rules.map((r, i) => {
    const actionCls = r.action === 'accept' ? 'pill-green'
      : (r.action === 'drop' || r.action === 'reject') ? 'pill-red'
      : 'pill-yellow';
    const dis = r.disabled
      ? '<span class="pill pill-yellow">Devre Dışı</span>'
      : '<span class="pill pill-green">Aktif</span>';
    const chainCls = r.chain === 'input' ? 'pill-blue'
      : r.chain === 'forward' ? 'pill-purple'
      : 'pill-gray';

    return `
      <tr style="${r.disabled ? 'opacity:0.5;' : ''}">
        <td style="color:var(--text-muted); font-size:11px;">${i + 1}</td>
        <td><span class="pill ${chainCls}">${r.chain}</span></td>
        <td><span class="pill ${actionCls}">${r.action}</span></td>
        <td><span style="font-family:monospace; font-size:12px; color:var(--text-dim);">${r.protocol}</span></td>
        <td><span class="mono">${r.src || 'any'}</span></td>
        <td><span class="mono" style="color:var(--text-dim);">${r.dst || 'any'}</span></td>
        <td style="font-family:monospace; font-size:12px;">${r.packets.toLocaleString('tr-TR')}</td>
        <td style="font-size:12px;">${formatBytes(r.bytes)}</td>
        <td style="font-size:11px; color:var(--text-muted);">${r.comment || '—'}</td>
        <td>${dis}</td>
      </tr>
    `;
  });

  document.getElementById('fw-body').innerHTML = rows.join('') || '<tr class="loading-row"><td colspan="10">Kural bulunamadı</td></tr>';
}

$(function () {
  function fetchFirewall() {
    $.getJSON('./api.php?action=firewall', function (res) {
      if (!res.success) return;
      allRules = res.rules || [];

      const total    = allRules.length;
      const accept   = allRules.filter(r => r.action === 'accept').length;
      const drop     = allRules.filter(r => r.action === 'drop' || r.action === 'reject').length;
      const disabled = allRules.filter(r => r.disabled).length;

      $('#fw-total').text(total);
      $('#fw-accept').text(accept);
      $('#fw-drop').text(drop);
      $('#fw-disabled').text(disabled);

      // Chain counts
      $('#cnt-all').text(total);
      $('#cnt-input').text(allRules.filter(r => r.chain === 'input').length);
      $('#cnt-forward').text(allRules.filter(r => r.chain === 'forward').length);
      $('#cnt-output').text(allRules.filter(r => r.chain === 'output').length);

      renderRules();
      setLastUpdate();
    });
  }

  window.refreshAll = fetchFirewall;
  fetchFirewall();
  setInterval(fetchFirewall, 30000); // Firewall daha seyrek yenilenir
});
</script>
