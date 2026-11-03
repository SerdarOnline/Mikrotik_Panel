<?php
$pageTitle  = 'Hotspot Kullanıcı Profilleri';
$activePage = 'hotspot_profiles';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── İstatistik Kartları ────────────────────────────────── -->
<div class="stat-grid" style="margin-bottom:20px;">
  <div class="stat-card blue">
    <div class="card-icon"><i class="fas fa-layer-group"></i></div>
    <div class="card-value" id="prof-total-val">—</div>
    <div class="card-label">Toplam Profil Sayısı</div>
    <div class="card-sub">RouterOS Üzerindeki Tanımlı Profiller</div>
  </div>

  <div class="stat-card green">
    <div class="card-icon"><i class="fas fa-gauge-high"></i></div>
    <div class="card-value" id="prof-limited-val">—</div>
    <div class="card-label">Hız Limitli Profiller</div>
    <div class="card-sub">Rx/Tx Bant Genişliği Sınırlandırılmış</div>
  </div>

  <div class="stat-card purple">
    <div class="card-icon"><i class="fas fa-network-wired"></i></div>
    <div class="card-value" id="prof-shared-val">—</div>
    <div class="card-label">Çoklu Cihaz Profilleri</div>
    <div class="card-sub">Birden Çok Cihaz İzinli Profiller</div>
  </div>

  <div class="stat-card yellow">
    <div class="card-icon"><i class="fas fa-star"></i></div>
    <div class="card-value" id="prof-default-val">default</div>
    <div class="card-label">Varsayılan Profil</div>
    <div class="card-sub">Varsayılan Hotspot Ayarı</div>
  </div>
</div>

<div class="grid-2" style="margin-bottom:20px;">

  <!-- ── Yeni Hotspot Profili Oluştur ───────────────────────── -->
  <div class="panel">
    <div class="panel-header">
      <h3>
        <span class="ph-icon"><i class="fas fa-plus-circle"></i></span>
        Yeni Hotspot Profili Oluştur (RouterOS)
      </h3>
    </div>
    <div class="panel-body">
      <div style="display:flex; flex-direction:column; gap:12px;">
        
        <div>
          <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">Profil Adı / Paket İsmi*</label>
          <input type="text" id="prof-name" placeholder="Örn: 1_Saatlik_Voucher veya Otel_Misafir_5M" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px;">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">Hız Limiti (Rx/Tx Rate Limit):</label>
            <input type="text" id="prof-rate" placeholder="Örn: 5M/2M veya 10M/4M" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px; font-family:monospace;">
          </div>
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">Eşzamanlı Cihaz (Shared Users):</label>
            <select id="prof-shared" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px;">
              <option value="1" selected>1 Cihaz (Tekli Kupon)</option>
              <option value="2">2 Cihaz</option>
              <option value="3">3 Cihaz</option>
              <option value="5">5 Cihaz (Aile / Grup)</option>
              <option value="10">10 Cihaz</option>
            </select>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">Oturum Süresi (Session Timeout):</label>
            <input type="text" id="prof-session" placeholder="Örn: 1h, 1d, 30d (Boş=Sınırsız)" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px;">
          </div>
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">Boşta Disconnect (Idle Timeout):</label>
            <input type="text" id="prof-idle" placeholder="Örn: 10m, 30m" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px;">
          </div>
        </div>

        <button onclick="createNewProfile()" class="pill pill-green" style="cursor:pointer; border:none; padding:12px; font-weight:700; font-size:13.5px; margin-top:8px;">
          <i class="fas fa-plus"></i> Hotspot Profilini RouterOS'a Kaydet
        </button>

      </div>
    </div>
  </div>

  <!-- ── Mevcut Profiller Listesi ───────────────────────────── -->
  <div class="panel">
    <div class="panel-header">
      <h3>
        <span class="ph-icon"><i class="fas fa-list-check"></i></span>
        RouterOS Tanımlı Hotspot Profilleri
      </h3>
    </div>
    <div class="panel-body" style="padding:0; max-height:450px; overflow-y:auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Profil Adı</th>
            <th>Hız Limiti</th>
            <th>Cihaz</th>
            <th>Oturum Süresi</th>
            <th>İşlem</th>
          </tr>
        </thead>
        <tbody id="profiles-body">
          <tr class="loading-row"><td colspan="5"><div class="spinner" style="margin:0 auto;"></div></td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {

  window.fetchProfiles = function () {
    $.getJSON('./api.php?action=get_hotspot_profiles', function (res) {
      if (!res.success || !res.profiles) return;

      const profiles = res.profiles;
      $('#prof-total-val').text(profiles.length);

      let limitedCnt = 0;
      let sharedCnt = 0;

      const rows = profiles.map(p => {
        if (p.rate_limit && p.rate_limit !== 'Sınırsız') limitedCnt++;
        if (parseInt(p.shared_users, 10) > 1) sharedCnt++;

        const isDefault = p.default || p.name === 'default';

        return `
          <tr>
            <td>
              <span style="font-weight:800; color:var(--text-primary); font-size:13px;">${p.name}</span>
              ${isDefault ? '<span class="pill pill-yellow" style="font-size:9.5px; margin-left:4px;">Varsayılan</span>' : ''}
            </td>
            <td><span class="mono" style="font-weight:700; color:var(--accent2);">${p.rate_limit}</span></td>
            <td><span class="pill pill-purple">${p.shared_users} Cihaz</span></td>
            <td><span class="mono" style="color:var(--text-dim);">${p.session_timeout}</span></td>
            <td>
              ${isDefault ? '—' : `
                <button onclick="removeProfile('${p.id}', '${p.name}')" class="pill pill-red" style="cursor:pointer; border:none; padding:4px 8px; font-weight:700; font-size:11px;">
                  <i class="fas fa-trash-can"></i> Sil
                </button>
              `}
            </td>
          </tr>
        `;
      });

      $('#prof-limited-val').text(limitedCnt);
      $('#prof-shared-val').text(sharedCnt);
      $('#profiles-body').html(rows.join(''));
      setLastUpdate();
    });
  };

  window.createNewProfile = function () {
    const name = $('#prof-name').val().trim();
    const rate = $('#prof-rate').val().trim();
    const shared = $('#prof-shared').val();
    const session = $('#prof-session').val().trim();
    const idle = $('#prof-idle').val().trim();

    if (!name) {
      showToast('Lütfen profil adı yazınız', 'error');
      return;
    }

    const query = $.param({
      name: name,
      rate_limit: rate,
      shared_users: shared,
      session_timeout: session,
      idle_timeout: idle,
    });

    $.getJSON('./api.php?action=add_hotspot_profile&' + query, function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        $('#prof-name').val('');
        $('#prof-rate').val('');
        $('#prof-session').val('');
        $('#prof-idle').val('');
        fetchProfiles();
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  window.removeProfile = function (id, name) {
    if (!confirm(`${name} profilini silmek istediğinize emin misiniz?`)) return;

    $.getJSON('./api.php?action=remove_hotspot_profile&id=' + encodeURIComponent(id), function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        fetchProfiles();
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  window.refreshAll = fetchProfiles;
  fetchProfiles();
  setInterval(fetchProfiles, 15000);
});
</script>
