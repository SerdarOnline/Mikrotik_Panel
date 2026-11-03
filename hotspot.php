<?php
$pageTitle  = 'Hotspot & Kupon Yönetimi';
$activePage = 'hotspot';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Stat Grid ───────────────────────────────────────────── -->
<div class="stat-grid" style="margin-bottom:24px;">
  <div class="stat-card" style="--card-color:#10b981;">
    <div class="card-icon"><i class="fas fa-wifi"></i></div>
    <div class="card-value" id="hs-active-cnt">—</div>
    <div class="card-label">Aktif Hotspot Oturumu</div>
    <div class="card-sub">Şu An Bağlı Misafirler</div>
  </div>
  <div class="stat-card" style="--card-color:#3b82f6;">
    <div class="card-icon"><i class="fas fa-ticket"></i></div>
    <div class="card-value" id="hs-user-cnt">—</div>
    <div class="card-label">Tanımlı Kullanıcı / Kupon</div>
    <div class="card-sub">Hotspot Veritabanı</div>
  </div>
  <div class="stat-card" style="--card-color:#8b5cf6;">
    <div class="card-icon"><i class="fas fa-sliders"></i></div>
    <div class="card-value" id="hs-profile-cnt">—</div>
    <div class="card-label">Hotspot Profili</div>
    <div class="card-sub">Hız & Süre Tanımları</div>
  </div>
  <div class="stat-card" style="--card-color:#f59e0b;">
    <div class="card-icon"><i class="fas fa-server"></i></div>
    <div class="card-value" id="hs-server-cnt">—</div>
    <div class="card-label">Hotspot Sunucusu</div>
    <div class="card-sub">Aktif Arayüzler</div>
  </div>
</div>

<!-- ── Toplu Kupon Üretim & Tekli Ekleme Paneli ─────────────── -->
<div class="grid-2" style="margin-bottom:20px;">
  
  <!-- Toplu Kupon Üretici -->
  <div class="panel">
    <div class="panel-header">
      <h3>
        <span class="ph-icon"><i class="fas fa-wand-magic-sparkles"></i></span>
        🎟️ Toplu Hotspot Kuponu (Voucher) Üret
      </h3>
    </div>
    <div class="panel-body">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
        <div>
          <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Kupon Adedi:</label>
          <input type="number" id="gen-count" value="10" min="1" max="100" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px;">
        </div>
        <div>
          <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Kod Uzunluğu:</label>
          <select id="gen-length" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px;">
            <option value="6" selected>6 Haneli (Örn: 748291)</option>
            <option value="8">8 Haneli (Örn: 84920194)</option>
          </select>
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
        <div>
          <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Profil:</label>
          <select id="gen-profile" class="hs-profile-select" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px;">
            <option value="default">default</option>
          </select>
        </div>
        <div>
          <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Süre Limiti:</label>
          <select id="gen-limit-time" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px;">
            <option value="">Sınırsız Süre</option>
            <option value="1h">1 Saat</option>
            <option value="2h">2 Saat</option>
            <option value="1d">1 Gün</option>
            <option value="7d">7 Gün (1 Hafta)</option>
            <option value="30d">30 Gün (1 Ay)</option>
          </select>
        </div>
      </div>

      <button onclick="generateVouchers()" id="btn-gen" class="pill pill-purple" style="width:100%; cursor:pointer; border:none; padding:10px; font-weight:700; font-size:13px;">
        <i class="fas fa-ticket"></i> Toplu Kuponları Üret ve Yazdır
      </button>
    </div>
  </div>

  <!-- Tekli Kullanıcı Ekleme -->
  <div class="panel">
    <div class="panel-header">
      <h3>
        <span class="ph-icon"><i class="fas fa-user-plus"></i></span>
        Tekli Hotspot Kullanıcısı Ekle
      </h3>
    </div>
    <div class="panel-body">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
        <div>
          <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Kullanıcı Adı / PIN:</label>
          <input type="text" id="add-user-name" placeholder="Örn: ahmet123" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px;">
        </div>
        <div>
          <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Şifre:</label>
          <input type="text" id="add-user-pass" placeholder="Boş bırakılırsa adıyla aynı olur" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px;">
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
        <div>
          <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Profil:</label>
          <select id="add-user-profile" class="hs-profile-select" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px;">
            <option value="default">default</option>
          </select>
        </div>
        <div>
          <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Süre Limiti:</label>
          <input type="text" id="add-user-limit" placeholder="Örn: 2h, 1d (Boş = Sınırsız)" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px;">
        </div>
      </div>

      <button onclick="addSingleUser()" class="pill pill-blue" style="width:100%; cursor:pointer; border:none; padding:10px; font-weight:700; font-size:13px;">
        <i class="fas fa-check"></i> Hotspot Kullanıcısını Kaydet
      </button>
    </div>
  </div>

</div>

<!-- ── Aktif Oturumlar ────────────────────────────────────── -->
<div class="panel" style="margin-bottom:20px;">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-signal"></i></span>
      Aktif Hotspot Oturumları (Şu An Bağlı Olanlar)
    </h3>
  </div>
  <div class="panel-body no-pad">
    <table class="data-table" id="hs-active-table">
      <thead>
        <tr>
          <th>Kullanıcı / PIN</th>
          <th>IP Adresi</th>
          <th>MAC Adresi</th>
          <th>Oturum Süresi</th>
          <th>İndirme (DL)</th>
          <th>Yükleme (UL)</th>
          <th style="text-align:right;">İşlem</th>
        </tr>
      </thead>
      <tbody id="hs-active-body">
        <tr class="loading-row"><td colspan="7"><div class="spinner" style="margin:0 auto;"></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Tüm Kullanıcılar & Kuponlar ────────────────────────── -->
<div class="panel">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-users-gear"></i></span>
      Hotspot Kullanıcı ve Kupon Veritabanı
    </h3>
    <div style="display:flex; align-items:center; gap:10px;">
      <button onclick="exportUsersCSV('#hs-users-table')" class="pill pill-green" style="cursor:pointer; border:none; padding:6px 12px; font-size:12px;">
        <i class="fas fa-file-csv"></i> CSV Aktar
      </button>
      <div class="search-wrap">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" class="search-input table-search" data-target="#hs-users-table" placeholder="Kullanıcı veya kupon ara...">
      </div>
    </div>
  </div>
  <div class="panel-body no-pad">
    <table class="data-table" id="hs-users-table">
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>Kullanıcı Adı / Kupon Kodu</th>
          <th>Şifre</th>
          <th>Profil</th>
          <th>Süre Limiti</th>
          <th>Kullanılan Süre</th>
          <th>Toplam Trafik</th>
          <th>Açıklama</th>
          <th style="text-align:right;">İşlem</th>
        </tr>
      </thead>
      <tbody id="hs-users-body">
        <tr class="loading-row"><td colspan="9"><div class="spinner" style="margin:0 auto;"></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Kupon Çıktı Alma Modalı ────────────────────────────── -->
<div id="voucher-modal" style="
  display:none; position:fixed; top:0; left:0; width:100%; height:100%;
  background:rgba(0,0,0,0.85); backdrop-filter:blur(10px); z-index:9999;
  justify-content:center; align-items:center; padding:20px;
">
  <div style="
    background:var(--bg-card); border:1px solid var(--border); border-radius:16px;
    width:100%; max-width:900px; max-height:90vh; display:flex; flex-direction:column; overflow:hidden;
  ">
    <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
      <h3 style="margin:0; display:flex; align-items:center; gap:8px;">
        🎟️ Üretilen Hotspot Kupon Kartları (Mikhmon v4 Multi-Template)
      </h3>
      <div style="display:flex; gap:8px; align-items:center;">
        <select id="print-template-select" onchange="switchPrintTemplate(this.value)" style="background:rgba(255,255,255,0.08); border:1px solid var(--border); border-radius:8px; padding:6px 12px; color:#fff; font-size:12px;">
          <option value="pos">🧾 POS Termal Fiş (58mm/80mm)</option>
          <option value="a4">📄 A4 Sayfa Tabakası (3x5 Izgara)</option>
        </select>
        <button onclick="window.print()" class="pill pill-blue" style="cursor:pointer; border:none; padding:6px 14px; font-weight:700;">
          <i class="fas fa-print"></i> Yazdır / PDF Al
        </button>
        <button onclick="$('#voucher-modal').css('display', 'none')" class="pill pill-red" style="cursor:pointer; border:none; padding:6px 12px;">
          <i class="fas fa-xmark"></i> Kapat
        </button>
      </div>
    </div>
    <div id="voucher-cards-container" style="
      padding:20px; overflow-y:auto; display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:16px; background:#111;
    ">
      <!-- Kupon kartları JS ile dolacak -->
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {

  window.fetchHotspot = function () {
    $.getJSON('./api.php?action=hotspot_status', function (res) {
      if (!res.success || !res.hotspot) return;

      const hs = res.hotspot;
      $('#hs-active-cnt').text(hs.active_cnt || 0);
      $('#hs-user-cnt').text(hs.users_cnt || 0);
      $('#hs-profile-cnt').text(hs.profiles_cnt || 0);
      $('#hs-server-cnt').text(hs.servers_cnt || 0);

      // Profil dropdownlarını doldur
      const $profSelects = $('.hs-profile-select').empty();
      (hs.profiles || []).forEach(p => {
        $profSelects.append(`<option value="${p.name}">${p.name} ${p.rate_limit !== 'Sınırsız' ? '(' + p.rate_limit + ')' : ''}</option>`);
      });

      // Aktif Oturumlar
      if (!hs.active || !hs.active.length) {
        $('#hs-active-body').html('<tr><td colspan="7" style="text-align:center; color:var(--text-muted);">Şu an aktif Hotspot oturumu bulunmuyor.</td></tr>');
      } else {
        const rowsA = hs.active.map(a => `
          <tr>
            <td><span class="mono" style="font-weight:700; color:var(--accent2);">${a.user}</span></td>
            <td><span class="mono">${a.ip}</span></td>
            <td><span class="mac">${a.mac}</span></td>
            <td><span class="pill pill-green">${a.uptime}</span></td>
            <td style="color:var(--accent2);">${formatBytes(a.bytes_out)}</td>
            <td style="color:var(--success);">${formatBytes(a.bytes_in)}</td>
            <td style="text-align:right;">
              <button onclick="kickHotspotActive('${a.id}')" class="pill pill-red" style="cursor:pointer; border:none; padding:4px 8px; font-size:11px;">
                <i class="fas fa-power-off"></i> Oturumu Kes
              </button>
            </td>
          </tr>
        `);
        $('#hs-active-body').html(rowsA.join(''));
      }

      // Tüm Kullanıcılar
      if (!hs.users || !hs.users.length) {
        $('#hs-users-body').html('<tr><td colspan="9" style="text-align:center; color:var(--text-muted);">Kayıtlı kullanıcı veya kupon bulunamadı.</td></tr>');
      } else {
        const rowsU = hs.users.map((u, i) => `
          <tr>
            <td style="color:var(--text-muted); font-size:11px;">${i + 1}</td>
            <td>
              <span class="mono" style="font-weight:700; color:var(--text-primary);">${u.name}</span>
            </td>
            <td><span class="mono" style="color:var(--text-dim);">${u.password}</span></td>
            <td><span class="pill pill-purple">${u.profile}</span></td>
            <td><span class="pill pill-yellow">${u.limit_time}</span></td>
            <td style="font-size:12px; color:var(--text-muted);">${u.uptime}</td>
            <td style="font-size:12px;">${formatBytes(u.bytes_in + u.bytes_out)}</td>
            <td style="font-size:11px; color:var(--text-muted);">${u.comment || '—'}</td>
            <td style="text-align:right;">
              <button onclick="removeHotspotUser('${u.id}')" class="pill pill-red" style="cursor:pointer; border:none; padding:4px 8px; font-size:11px;">
                <i class="fas fa-trash-can"></i> Sil
              </button>
            </td>
          </tr>
        `);
        $('#hs-users-body').html(rowsU.join(''));
      }

      setLastUpdate();
    });
  };

  // Toplu Kupon Üret
  // Toplu Kupon Üret
  window.generateVouchers = function () {
    const count = $('#gen-count').val();
    const length = $('#gen-length').val();
    const profile = $('#gen-profile').val();
    const limitTime = $('#gen-limit-time').val();

    $('#btn-gen').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Kuponlar Üretiliyor...');

    $.getJSON(`./api.php?action=generate_vouchers&count=${count}&length=${length}&profile=${encodeURIComponent(profile)}&limit_time=${encodeURIComponent(limitTime)}`, function (res) {
      $('#btn-gen').prop('disabled', false).html('<i class="fas fa-ticket"></i> Toplu Kuponları Üret ve Yazdır');

      if (!res.success) {
        showToast('Kuponlar üretilemedi: ' + (res.error || ''), 'error');
        return;
      }

      showToast(res.message, 'success');
      fetchHotspot();

      // Kupon kartlarını göster (Mikhmon v4 Multi-Template)
      window.latestVouchers = res.vouchers || [];
      renderVoucherCards($('#print-template-select').val() || 'pos');
      $('#voucher-modal').css('display', 'flex');
    });
  };

  window.switchPrintTemplate = function (mode) {
    renderVoucherCards(mode);
  };

  function renderVoucherCards(mode) {
    const vouchers = window.latestVouchers || [];
    const $container = $('#voucher-cards-container');

    if (mode === 'a4') {
      $container.css('grid-template-columns', 'repeat(3, 1fr)');
      const cardsHtml = vouchers.map(v => `
        <div class="a4-voucher-card" style="
          background:#fff; color:#000; border:1px dashed #333; border-radius:6px; padding:12px; text-align:center;
          font-family:'JetBrains Mono', monospace; font-size:11px;
        ">
          <div style="font-weight:800; font-size:11px; border-bottom:1px solid #ddd; padding-bottom:4px; margin-bottom:6px;">
            🌐 LAGOON WIFI
          </div>
          <div style="font-size:9px; color:#666;">KUPON / PIN</div>
          <div style="font-size:18px; font-weight:900; letter-spacing:2px; color:#1d4ed8; margin:4px 0;">
            ${v.code}
          </div>
          <div style="font-size:9.5px; color:#333;">
            Profil: <strong>${v.profile}</strong> | Süre: <strong>${v.limit_time}</strong>
          </div>
        </div>
      `);
      $container.html(cardsHtml.join(''));
    } else {
      $container.css('grid-template-columns', 'repeat(auto-fill, minmax(220px, 1fr))');
      const cardsHtml = vouchers.map(v => `
        <div class="pos-receipt-card" style="
          background:#fff; color:#000; border:2px solid #000; border-radius:8px; padding:16px; text-align:center;
          font-family:'JetBrains Mono', monospace; box-shadow:0 4px 15px rgba(0,0,0,0.5);
        ">
          <div style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:1px; border-bottom:1px dashed #000; padding-bottom:6px; margin-bottom:8px;">
            🌐 LAGOON MİSAFİR WIFI
          </div>
          <div style="font-size:10px; color:#555; margin-bottom:4px;">KUPON GİRİŞ KODU</div>
          <div style="font-size:24px; font-weight:900; letter-spacing:3px; background:#f0f0f0; padding:6px; border:1px solid #ccc; border-radius:4px; margin:6px 0;">
            ${v.code}
          </div>
          <div style="font-size:11px; margin:6px 0;">
            ŞİFRE: <strong style="font-size:14px;">${v.code}</strong>
          </div>
          <div style="font-size:10px; color:#333; border-top:1px dashed #000; padding-top:6px; margin-top:6px;">
            Paket: <strong>${v.profile}</strong> | Süre: <strong>${v.limit_time}</strong>
          </div>
          <div style="font-size:8.5px; color:#666; margin-top:6px;">
            Ağa bağlandığınızda karşınıza çıkan giriş ekranına kodu yazın.
          </div>
          <button onclick="printEscPosReceipt('${v.code}', 0, '${v.profile}')" style="margin-top:8px; width:100%; background:#000; color:#fff; border:none; border-radius:4px; padding:6px; font-weight:700; font-size:11px; cursor:pointer;">
            🖨️ ESC/POS Fiş Yazdır
          </button>
        </div>
      `);
      $container.html(cardsHtml.join(''));
    }
  }

  // Tekli Kullanıcı Ekle
  window.addSingleUser = function () {
    const name = $('#add-user-name').val().trim();
    const pass = $('#add-user-pass').val().trim();
    const profile = $('#add-user-profile').val();
    const limit = $('#add-user-limit').val().trim();

    if (!name) {
      showToast('Kullanıcı adı yazın', 'error');
      return;
    }

    $.getJSON(`./api.php?action=add_hotspot_user&name=${encodeURIComponent(name)}&password=${encodeURIComponent(pass)}&profile=${encodeURIComponent(profile)}&limit_time=${encodeURIComponent(limit)}`, function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        $('#add-user-name').val('');
        $('#add-user-pass').val('');
        fetchHotspot();
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  window.removeHotspotUser = function (id) {
    if (!confirm('Bu Hotspot kullanıcısını/kuponunu silmek istediğinize emin misiniz?')) return;
    $.getJSON('./api.php?action=remove_hotspot_user&id=' + encodeURIComponent(id), function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        fetchHotspot();
      }
    });
  };

  window.kickHotspotActive = function (id) {
    $.getJSON('./api.php?action=kick_hotspot_active&id=' + encodeURIComponent(id), function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        fetchHotspot();
      }
    });
  };

  window.refreshAll = fetchHotspot;
  fetchHotspot();
  setInterval(fetchHotspot, 10000);
});
</script>
