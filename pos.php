<?php
$pageTitle  = 'Hızlı POS Satış Terminali & Fiş Basımı';
$activePage = 'pos';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── POS Üst Bilgi Barı ─────────────────────────────────── -->
<div style="background:rgba(17,24,39,0.85); backdrop-filter:blur(16px); border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:18px 24px; margin-bottom:24px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px; box-shadow:0 10px 30px rgba(0,0,0,0.5);">
  <div>
    <h2 style="margin:0 0 4px 0; font-size:18px; color:var(--text-primary); display:flex; align-items:center; gap:10px;">
      <span style="background:linear-gradient(135deg, var(--success), var(--accent2)); width:36px; height:36px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; color:#fff; font-size:16px;">⚡</span>
      1-Tıkla Hızlı POS Satış & Otomatik Fiş Terminali
    </h2>
    <div style="font-size:12px; color:var(--text-muted);">
      Profe basarak anında kupon satışı yapabilir ve <strong style="color:var(--success);" id="pos-printer-ip-label">ESC/POS Yazıcıya (TCP 9100)</strong> otomatik fiş bastırabilirsiniz.
    </div>
  </div>
  <div style="display:flex; align-items:center; gap:12px;">
    <a href="./settings.php" class="pill pill-gray" style="text-decoration:none; padding:8px 14px; font-weight:700;">
      <i class="fas fa-print"></i> Yazıcı IP Ayarları
    </a>
    <button onclick="fetchPosProfiles()" class="pill pill-blue" style="cursor:pointer; border:none; padding:8px 16px; font-weight:700; font-size:13px;">
      <i class="fas fa-rotate"></i> Profilleri Yenile
    </button>
  </div>
</div>

<div class="grid-3" style="margin-bottom:24px;">

  <!-- ── Sol / Orta: Sıcak Profil Satış Kartları Grid ───────── -->
  <div style="grid-column: span 2;">
    <div class="panel">
      <div class="panel-header">
        <h3>
          <span class="ph-icon"><i class="fas fa-cubes"></i></span>
          Satışa Hazır Hotspot Profilleri (Dokunmatik POS Kartları)
        </h3>
      </div>
      <div class="panel-body">
        <div id="pos-profiles-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(230px, 1fr)); gap:16px;">
          <div style="text-align:center; grid-column:1/-1; padding:40px; color:var(--text-muted);">
            <div class="spinner" style="margin:0 auto 12px;"></div>
            Profiller RouterOS'tan yükleniyor...
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Sağ: Son Satışlar Stream & Canlı Fiş Önizlemesi ────── -->
  <div>
    
    <!-- Fiş Yazıcısı Durum Kartı -->
    <div class="panel" style="margin-bottom:20px;">
      <div class="panel-header">
        <h3>
          <span class="ph-icon"><i class="fas fa-print"></i></span>
          ESC/POS Termal Yazıcı Durumu
        </h3>
      </div>
      <div class="panel-body">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
          <span style="font-size:12px; color:var(--text-muted);">Yazıcı IP Adresi:</span>
          <span class="mono" id="print-ip-val" style="font-weight:700;">192.168.0.200</span>
        </div>
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
          <span style="font-size:12px; color:var(--text-muted);">Bağlantı Portu:</span>
          <span class="pill pill-purple">RAW 9100 TCP</span>
        </div>
        <div style="display:flex; align-items:center; justify-content:space-between;">
          <span style="font-size:12px; color:var(--text-muted);">Yazıcı Durumu:</span>
          <span class="pill pill-green" id="print-status-badge">✅ YAZICI HAZIR</span>
        </div>
      </div>
    </div>

    <!-- Son Satışlar Akışı -->
    <div class="panel">
      <div class="panel-header">
        <h3>
          <span class="ph-icon"><i class="fas fa-history"></i></span>
          Son Satılan Kuponlar
        </h3>
      </div>
      <div class="panel-body no-pad" style="max-height:380px; overflow-y:auto;">
        <table class="data-table" style="font-size:11.5px;">
          <thead>
            <tr>
              <th>Kupon</th>
              <th>Profil</th>
              <th>Tutar</th>
              <th>Fiş</th>
            </tr>
          </thead>
          <tbody id="recent-pos-sales">
            <tr><td colspan="4" style="text-align:center; color:var(--text-muted); padding:20px;">Henüz satış yapılmadı.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {
  let activeProfiles = [];

  window.fetchPosProfiles = function () {
    $.getJSON('./api.php?action=get_settings', function (sRes) {
      if (sRes.success && sRes.settings) {
        const ip = sRes.settings.printer_ip || '192.168.0.200';
        const port = sRes.settings.printer_port || 9100;
        $('#print-ip-val').text(ip + ':' + port);
        $('#pos-printer-ip-label').text('ESC/POS Yazıcıya (' + ip + ':' + port + ')');
      }
    });

    $.getJSON('./api.php?action=get_hotspot_profiles', function (res) {
      if (!res.success || !res.profiles) return;
      activeProfiles = res.profiles;

      if (!activeProfiles.length) {
        $('#pos-profiles-grid').html('<div style="grid-column:1/-1; text-align:center; padding:30px; color:var(--text-muted);">Hiç Hotspot profili bulunamadı.</div>');
        return;
      }

      const defaultPrices = {
        'default': 10,
        '1_saat': 15,
        '24_saat': 30,
        '1_gun': 30,
        '7_gun': 100,
        '30_gun': 250,
      };

      const cardsHtml = activeProfiles.map((p, idx) => {
        const pNameLower = p.name.toLowerCase();
        let defPrice = 20;
        for (let k in defaultPrices) {
          if (pNameLower.includes(k)) defPrice = defaultPrices[k];
        }

        return `
          <div style="
            background:rgba(26,34,53,0.8); border:1px solid rgba(255,255,255,0.1); border-radius:16px; padding:20px;
            text-align:center; transition:all 0.25s ease; position:relative; overflow:hidden;
            box-shadow:0 8px 25px rgba(0,0,0,0.3);
          " onmouseover="this.style.borderColor='var(--accent)';" onmouseout="this.style.borderColor='rgba(255,255,255,0.1)';">
            
            <div style="position:absolute; top:12px; right:12px;">
              <span class="pill pill-purple" style="font-size:10px;">${p.shared_users} Cihaz</span>
            </div>

            <div style="width:48px; height:48px; background:linear-gradient(135deg, rgba(59,130,246,0.2), rgba(6,182,212,0.2)); color:var(--accent); border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; margin:0 auto 12px;">
              <i class="fas fa-ticket"></i>
            </div>

            <div style="font-size:16px; font-weight:800; color:var(--text-primary); margin-bottom:4px;">${p.name}</div>
            
            <div style="display:flex; justify-content:center; gap:6px; margin-bottom:14px;">
              <span class="pill pill-blue" style="font-size:10.5px;"><i class="fas fa-bolt"></i> ${p.rate_limit}</span>
              <span class="pill pill-gray" style="font-size:10.5px;"><i class="fas fa-clock"></i> ${p.session_timeout}</span>
            </div>

            <!-- Satış Fiyatı Girdisi -->
            <div style="margin-bottom:14px; background:rgba(0,0,0,0.3); border-radius:10px; padding:8px 12px; display:flex; align-items:center; justify-content:space-between;">
              <span style="font-size:11.5px; color:var(--text-muted);">Satış Fiyatı:</span>
              <div style="display:flex; align-items:center; gap:4px;">
                <input type="number" id="pos-price-${idx}" value="${defPrice}" style="width:65px; text-align:right; font-weight:800; font-family:'JetBrains Mono',monospace; color:var(--success) !important; font-size:15px;">
                <span style="font-weight:700; color:var(--success);">₺</span>
              </div>
            </div>

            <!-- 1-Tıkla Satış Butonu -->
            <button onclick="executePosSale('${p.name}', ${idx})" class="pill pill-green" style="
              width:100%; border:none; padding:12px; font-size:14px; font-weight:800; cursor:pointer;
              background:linear-gradient(135deg, #10b981, #059669); color:#fff; box-shadow:0 4px 15px rgba(16,185,129,0.4);
            ">
              <i class="fas fa-print"></i> SATIŞ YAP & FİŞ BASTIR
            </button>

          </div>
        `;
      });

      $('#pos-profiles-grid').html(cardsHtml.join(''));
      fetchRecentSalesStream();
    });
  };

  window.executePosSale = function (profileName, idx) {
    const priceVal = parseFloat($('#pos-price-' + idx).val()) || 10.0;

    showToast(`⚡ ${profileName} paketi satılıyor ve ESC/POS fiş basılıyor...`, 'info');

    $.getJSON(`./api.php?action=fast_pos_sale&profile=${encodeURIComponent(profileName)}&price=${encodeURIComponent(priceVal)}`, function (res) {
      if (res.success) {
        showToast(res.message, 'success', 6000);

        // Canlı Tarayıcı Fiş Açılışı (Yazıcı IP'ye doğrudan gitsin veya ekranda basılsın)
        if (res.code) {
          printEscPosReceipt(res.code, priceVal, profileName);
        }

        fetchRecentSalesStream();
      } else {
        showToast('Satış hatası: ' + (res.error || ''), 'error');
      }
    });
  };

  function fetchRecentSalesStream() {
    $.getJSON('./api.php?action=get_sales_report', function (res) {
      if (!res.success || !res.sales_report || !res.sales_report.sales) return;
      const sales = res.sales_report.sales.slice(0, 10);

      if (!sales.length) {
        $('#recent-pos-sales').html('<tr><td colspan="4" style="text-align:center; color:var(--text-muted); padding:20px;">Henüz satış yok.</td></tr>');
        return;
      }

      const rows = sales.map(s => `
        <tr>
          <td><span class="mono" style="font-weight:800; color:var(--accent2);">${s.code}</span></td>
          <td><span class="pill pill-purple" style="font-size:9.5px;">${s.profile}</span></td>
          <td style="font-weight:800; color:var(--success); font-family:'JetBrains Mono',monospace;">${s.price.toFixed(2)} ₺</td>
          <td>
            <button onclick="printEscPosReceipt('${s.code}', ${s.price}, '${s.profile}')" class="pill pill-blue" style="cursor:pointer; border:none; padding:2px 6px; font-size:10px;">
              <i class="fas fa-print"></i>
            </button>
          </td>
        </tr>
      `);

      $('#recent-pos-sales').html(rows.join(''));
    });
  }

  fetchPosProfiles();
});
</script>
