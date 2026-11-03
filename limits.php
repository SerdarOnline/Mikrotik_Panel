<?php
$pageTitle  = 'Kısıtlı & Banlı Cihazlar';
$activePage = 'limits';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Stat Cards ──────────────────────────────────────────── -->
<div class="stat-grid" style="margin-bottom:24px;">
  <div class="stat-card" style="--card-color:#ef4444;">
    <div class="card-icon"><i class="fas fa-ban"></i></div>
    <div class="card-value" id="cnt-banned">—</div>
    <div class="card-label">Tam Engelli (BANLI)</div>
    <div class="card-sub">İnternet %100 Kapalı</div>
  </div>
  <div class="stat-card" style="--card-color:#f59e0b;">
    <div class="card-icon"><i class="fas fa-gauge-high"></i></div>
    <div class="card-value" id="cnt-limited">—</div>
    <div class="card-label">Hız Limiti Konulmuş</div>
    <div class="card-sub">Simple Queue Aktif</div>
  </div>
  <div class="stat-card" style="--card-color:#3b82f6;">
    <div class="card-icon"><i class="fas fa-shield-halved"></i></div>
    <div class="card-value" id="cnt-total">—</div>
    <div class="card-label">Toplam Kısıtlı Cihaz</div>
    <div class="card-sub">MikroTik Kontrolünde</div>
  </div>
</div>

<!-- ── Hızlı Kısıtlama Paneli (Manuel Ekleme) ─────────────── -->
<div class="panel" style="margin-bottom:20px;">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-plus-circle"></i></span>
      Yeni Kısıtlama veya Ban Ekle
    </h3>
  </div>
  <div class="panel-body">
    <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
      <div style="flex:1; min-width:200px;">
        <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Target IP Adresi:</label>
        <input type="text" id="add-ip-input" placeholder="Örn: 192.168.1.50" style="
          width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border);
          border-radius:8px; padding:8px 12px; color:#fff; font-family:'JetBrains Mono',monospace; font-size:13px;
        ">
      </div>
      <div style="width:180px;">
        <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">İşlem / Hız Limiti:</label>
        <select id="add-action-select" style="
          width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border);
          border-radius:8px; padding:8px 12px; color:#fff; font-size:13px; font-family:inherit;
        ">
          <option value="BAN">🚫 KESİN BAN (%100 İnternet Kes)</option>
          <option value="2M_1M">🔒 2 Mbps Download / 1M Up</option>
          <option value="5M_2M" selected>🔒 5 Mbps Download / 2M Up</option>
          <option value="10M_4M">🔒 10 Mbps Download / 4M Up</option>
          <option value="20M_5M">🔒 20 Mbps Download / 5M Up</option>
        </select>
      </div>
      <div style="padding-top:18px;">
        <button onclick="submitNewRestriction()" class="pill pill-blue" style="cursor:pointer; border:none; padding:9px 18px; font-size:13px; font-weight:700;">
          <i class="fas fa-check"></i> Kısıtlamayı Uygula
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ── Kısıtlı & Banlı Cihazlar Tablosu ───────────────────── -->
<div class="panel">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-list-check"></i></span>
      Kısıtlanan ve Engellenen Cihaz Listesi
    </h3>
    <div style="display:flex; align-items:center; gap:10px;">
      <button onclick="exportUsersCSV('#restricted-table')" class="pill pill-green" style="cursor:pointer; border:none; padding:6px 12px; font-size:12px;" title="CSV İndir">
        <i class="fas fa-file-csv"></i> CSV Aktar
      </button>
      <div class="search-wrap">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" class="search-input table-search" data-target="#restricted-table" placeholder="IP, hostname veya MAC ara...">
      </div>
    </div>
  </div>
  <div class="panel-body no-pad">
    <table class="data-table" id="restricted-table">
      <thead>
        <tr>
          <th style="width:50px;">#</th>
          <th>Cihaz / Hostname</th>
          <th>IP Adresi</th>
          <th>MAC Adresi</th>
          <th>Kısıtlama Türü</th>
          <th>Aktif Limit</th>
          <th>DHCP Açıklaması</th>
          <th style="text-align:right;">İşlemler</th>
        </tr>
      </thead>
      <tbody id="restricted-body">
        <tr class="loading-row">
          <td colspan="8">
            <div class="spinner" style="margin:0 auto;"></div>
            <div style="margin-top:10px;">Kısıtlı cihaz listesi yükleniyor...</div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {

  function fetchRestrictedUsers() {
    $.getJSON('./api.php?action=restricted_users', function (res) {
      if (!res.success) {
        showToast('Kısıtlı cihazlar alınamadı: ' + (res.error || ''), 'error');
        return;
      }

      const users = res.users || [];
      $('#cnt-banned').text(res.banned_cnt || 0);
      $('#cnt-limited').text(res.limit_cnt || 0);
      $('#cnt-total').text(res.total || 0);

      if (!users.length) {
        $('#restricted-body').html(`
          <tr class="loading-row">
            <td colspan="8">
              <i class="fas fa-circle-check" style="color:var(--success); font-size:24px;"></i>
              <div style="margin-top:10px; color:var(--text-dim);">Harika! Şu an engellenmiş veya kısıtlanmış hiç cihaz yok.</div>
            </td>
          </tr>
        `);
        return;
      }

      const rows = users.map((u, i) => {
        const typeBadge = u.is_banned
          ? `<span class="pill pill-red" style="font-weight:700;">🚫 KESİN BAN</span>`
          : `<span class="pill pill-yellow">🔒 Hız Limiti</span>`;

        const limitVal = u.is_banned
          ? `<span class="mono" style="color:var(--danger);">1k/1k (Sıfır İnternet)</span>`
          : u.limit
          ? `<span class="mono" style="color:var(--warning); font-weight:700;">${u.limit}</span>`
          : `<span style="color:var(--text-muted);">—</span>`;

        const actionBtn = u.is_banned
          ? `<button onclick="unbanIp('${u.ip}')" class="pill pill-green" style="cursor:pointer; border:none; padding:5px 10px; font-size:11.5px;">
               <i class="fas fa-unlock"></i> Engeli Kaldır
             </button>`
          : `<button onclick="removeModalSpeedLimitDirect('${u.ip}')" class="pill pill-green" style="cursor:pointer; border:none; padding:5px 10px; font-size:11.5px;">
               <i class="fas fa-trash-can"></i> Limiti Kaldır
             </button>`;

        return `
          <tr>
            <td>${rankBadge(i + 1)}</td>
            <td>
              <span style="font-weight:700; color:var(--text-primary);">${u.hostname || '—'}</span>
              ${u.queue_name ? `<div style="font-size:10px; color:var(--text-muted);">${u.queue_name}</div>` : ''}
            </td>
            <td><span class="mono">${u.ip}</span></td>
            <td><span class="mac">${u.mac || '—'}</span></td>
            <td>${typeBadge}</td>
            <td>${limitVal}</td>
            <td style="font-size:12px; color:var(--text-dim);">${u.comment || '—'}</td>
            <td style="text-align:right;">
              <div style="display:inline-flex; gap:6px;">
                <button onclick="openUserModal('${u.ip}')" class="pill pill-blue" style="cursor:pointer; border:none; padding:5px 10px; font-size:11.5px;">
                  <i class="fas fa-magnifying-glass"></i> Detay & Ayar
                </button>
                ${actionBtn}
              </div>
            </td>
          </tr>
        `;
      });

      $('#restricted-body').html(rows.join(''));
      setLastUpdate();
    });
  }

  // Manuel Ekleme Formu
  window.submitNewRestriction = function () {
    const ip = $('#add-ip-input').val().trim();
    const act = $('#add-action-select').val();

    if (!ip) {
      showToast('Lütfen geçerli bir IP adresi yazın', 'error');
      return;
    }

    if (act === 'BAN') {
      banIp(ip);
    } else {
      const parts = act.split('_');
      quickSetSpeed(ip, parts[0], parts[1]);
    }
    $('#add-ip-input').val('');
  };

  window.removeModalSpeedLimitDirect = function (ip) {
    $.getJSON('./api.php?action=remove_speed_limit&ip=' + encodeURIComponent(ip), function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        fetchRestrictedUsers();
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  window.refreshAll = fetchRestrictedUsers;
  fetchRestrictedUsers();
  setInterval(fetchRestrictedUsers, 10000);
});
</script>
