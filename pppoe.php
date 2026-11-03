<?php
$pageTitle  = 'PPPoE / PPP Abonelik Yönetimi';
$activePage = 'pppoe';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Stat Grid ───────────────────────────────────────────── -->
<div class="stat-grid" style="margin-bottom:24px;">
  <div class="stat-card" style="--card-color:#10b981;">
    <div class="card-icon"><i class="fas fa-network-wired"></i></div>
    <div class="card-value" id="ppp-active-cnt">—</div>
    <div class="card-label">Aktif PPPoE Oturumu</div>
    <div class="card-sub">Şu An Bağlı Aboneler</div>
  </div>
  <div class="stat-card" style="--card-color:#3b82f6;">
    <div class="card-icon"><i class="fas fa-id-card"></i></div>
    <div class="card-value" id="ppp-secret-cnt">—</div>
    <div class="card-label">Tanımlı PPPoE Abonesi</div>
    <div class="card-sub">PPP Secrets Veritabanı</div>
  </div>
  <div class="stat-card" style="--card-color:#8b5cf6;">
    <div class="card-icon"><i class="fas fa-sliders"></i></div>
    <div class="card-value" id="ppp-profile-cnt">—</div>
    <div class="card-label">PPP Profil Sayısı</div>
    <div class="card-sub">Hız & IP Havuzu Ayarları</div>
  </div>
</div>

<!-- ── Yeni PPPoE Abonesi Ekle ────────────────────────────── -->
<div class="panel" style="margin-bottom:20px;">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-user-plus"></i></span>
      Yeni PPPoE Abonesi (PPP Secret) Ekle
    </h3>
  </div>
  <div class="panel-body">
    <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
      <div style="flex:1; min-width:160px;">
        <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Kullanıcı Adı:</label>
        <input type="text" id="add-ppp-name" placeholder="Örn: musteri_104" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px;">
      </div>
      <div style="flex:1; min-width:160px;">
        <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Şifre:</label>
        <input type="text" id="add-ppp-pass" placeholder="Örn: 987654" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px;">
      </div>
      <div style="width:140px;">
        <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Servis:</label>
        <select id="add-ppp-service" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px;">
          <option value="pppoe" selected>pppoe</option>
          <option value="any">any (Tümü)</option>
          <option value="l2tp">l2tp</option>
          <option value="sstp">sstp</option>
        </select>
      </div>
      <div style="width:160px;">
        <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Profil:</label>
        <select id="add-ppp-profile" class="ppp-profile-select" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px;">
          <option value="default">default</option>
        </select>
      </div>
      <div style="flex:1; min-width:160px;">
        <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Statik IP (İsteğe Bağlı):</label>
        <input type="text" id="add-ppp-remote" placeholder="Örn: 10.10.10.50" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px; font-family:monospace;">
      </div>
      <div style="padding-top:18px;">
        <button onclick="addPppSecret()" class="pill pill-blue" style="cursor:pointer; border:none; padding:9px 18px; font-size:13px; font-weight:700;">
          <i class="fas fa-check"></i> Aboneyi Kaydet
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ── Aktif PPPoE Oturumları ────────────────────────────── -->
<div class="panel" style="margin-bottom:20px;">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-plug-circle-check"></i></span>
      Aktif PPPoE Oturumları (Şu An Bağlı Aboneler)
    </h3>
  </div>
  <div class="panel-body no-pad">
    <table class="data-table" id="ppp-active-table">
      <thead>
        <tr>
          <th>Kullanıcı Adı</th>
          <th>Servis</th>
          <th>Atanan IP Adresi</th>
          <th>Caller-ID (MAC / Port)</th>
          <th>Oturum Süresi</th>
          <th style="text-align:right;">İşlem</th>
        </tr>
      </thead>
      <tbody id="ppp-active-body">
        <tr class="loading-row"><td colspan="6"><div class="spinner" style="margin:0 auto;"></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Tüm PPPoE Aboneleri ────────────────────────────────── -->
<div class="panel">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-users-viewfinder"></i></span>
      PPPoE Abone Veritabanı (PPP Secrets)
    </h3>
    <div style="display:flex; align-items:center; gap:10px;">
      <button onclick="exportUsersCSV('#ppp-secrets-table')" class="pill pill-green" style="cursor:pointer; border:none; padding:6px 12px; font-size:12px;">
        <i class="fas fa-file-csv"></i> CSV Aktar
      </button>
      <div class="search-wrap">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" class="search-input table-search" data-target="#ppp-secrets-table" placeholder="Abone adı veya IP ara...">
      </div>
    </div>
  </div>
  <div class="panel-body no-pad">
    <table class="data-table" id="ppp-secrets-table">
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>Abone Kullanıcı Adı</th>
          <th>Şifre</th>
          <th>Servis</th>
          <th>Profil</th>
          <th>Atanan Statik IP</th>
          <th>Açıklama</th>
          <th style="text-align:right;">İşlem</th>
        </tr>
      </thead>
      <tbody id="ppp-secrets-body">
        <tr class="loading-row"><td colspan="8"><div class="spinner" style="margin:0 auto;"></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {
  window.fetchPpp = function () {
    $.getJSON('./api.php?action=ppp_status', function (res) {
      if (!res.success || !res.ppp) return;

      const ppp = res.ppp;
      $('#ppp-active-cnt').text(ppp.active_cnt || 0);
      $('#ppp-secret-cnt').text(ppp.secrets_cnt || 0);
      $('#ppp-profile-cnt').text(ppp.profiles_cnt || 0);

      // Profil dropdownlarını doldur
      const $profSelect = $('.ppp-profile-select').empty();
      (ppp.profiles || []).forEach(p => {
        $profSelect.append(`<option value="${p.name}">${p.name} ${p.rate_limit !== 'Sınırsız' ? '(' + p.rate_limit + ')' : ''}</option>`);
      });

      // Aktif Oturumlar
      if (!ppp.active || !ppp.active.length) {
        $('#ppp-active-body').html('<tr><td colspan="6" style="text-align:center; color:var(--text-muted);">Şu an aktif PPPoE bağlantısı bulunmuyor.</td></tr>');
      } else {
        const rowsA = ppp.active.map(a => `
          <tr>
            <td><span class="mono" style="font-weight:700; color:var(--accent2);">${a.name}</span></td>
            <td><span class="pill pill-purple">${a.service}</span></td>
            <td><span class="mono">${a.ip}</span></td>
            <td><span class="mac">${a.caller_id}</span></td>
            <td><span class="pill pill-green">${a.uptime}</span></td>
            <td style="text-align:right;">
              <button onclick="kickPppActive('${a.id}')" class="pill pill-red" style="cursor:pointer; border:none; padding:4px 8px; font-size:11px;">
                <i class="fas fa-power-off"></i> Bağlantıyı Kes
              </button>
            </td>
          </tr>
        `);
        $('#ppp-active-body').html(rowsA.join(''));
      }

      // PPPoE Aboneleri
      if (!ppp.secrets || !ppp.secrets.length) {
        $('#ppp-secrets-body').html('<tr><td colspan="8" style="text-align:center; color:var(--text-muted);">Tanımlı PPPoE abonesi bulunamadı.</td></tr>');
      } else {
        const rowsS = ppp.secrets.map((s, i) => `
          <tr>
            <td style="color:var(--text-muted); font-size:11px;">${i + 1}</td>
            <td><span class="mono" style="font-weight:700; color:var(--text-primary);">${s.name}</span></td>
            <td><span class="mono" style="color:var(--text-dim);">${s.password}</span></td>
            <td><span class="pill pill-blue">${s.service}</span></td>
            <td><span class="pill pill-purple">${s.profile}</span></td>
            <td><span class="mono">${s.remote_ip}</span></td>
            <td style="font-size:11px; color:var(--text-muted);">${s.comment || '—'}</td>
            <td style="text-align:right;">
              <button onclick="removePppSecret('${s.id}')" class="pill pill-red" style="cursor:pointer; border:none; padding:4px 8px; font-size:11px;">
                <i class="fas fa-trash-can"></i> Sil
              </button>
            </td>
          </tr>
        `);
        $('#ppp-secrets-body').html(rowsS.join(''));
      }

      setLastUpdate();
    });
  };

  window.addPppSecret = function () {
    const name = $('#add-ppp-name').val().trim();
    const pass = $('#add-ppp-pass').val().trim();
    const service = $('#add-ppp-service').val();
    const profile = $('#add-ppp-profile').val();
    const remoteIp = $('#add-ppp-remote').val().trim();

    if (!name || !pass) {
      showToast('Lütfen kullanıcı adı ve şifre girin', 'error');
      return;
    }

    $.getJSON(`./api.php?action=add_ppp_secret&name=${encodeURIComponent(name)}&password=${encodeURIComponent(pass)}&service=${encodeURIComponent(service)}&profile=${encodeURIComponent(profile)}&remote_ip=${encodeURIComponent(remoteIp)}`, function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        $('#add-ppp-name').val('');
        $('#add-ppp-pass').val('');
        $('#add-ppp-remote').val('');
        fetchPpp();
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  window.removePppSecret = function (id) {
    if (!confirm('Bu PPPoE abonesini silmek istediğinize emin misiniz?')) return;
    $.getJSON('./api.php?action=remove_ppp_secret&id=' + encodeURIComponent(id), function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        fetchPpp();
      }
    });
  };

  window.kickPppActive = function (id) {
    $.getJSON('./api.php?action=kick_ppp_active&id=' + encodeURIComponent(id), function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        fetchPpp();
      }
    });
  };

  window.refreshAll = fetchPpp;
  fetchPpp();
  setInterval(fetchPpp, 10000);
});
</script>
