<?php
/**
 * kasa.php — Bağımsız Mobil POS Kasa Ekranı (Personel İçin Özel Sayfa)
 * Ana panele bağımlılığı yoktur, doğrudan http://mik.tr/kasa.php adresinden çalışır.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

$bName   = get_config('business_name', 'LAGOON HOTSPOT & WIFI');
$printIp = get_config('printer_ip', '192.168.0.200');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <title>Kasa POS — <?= htmlspecialchars($bName) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; -webkit-tap-highlight-color: transparent; }
    body { background: #080c14; color: #fff; min-height: 100vh; padding: 14px; display: flex; flex-direction: column; }

    /* Top Bar */
    .top-bar {
      background: rgba(30, 41, 59, 0.85);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 16px;
      text-align: center;
      margin-bottom: 14px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.6);
    }
    .brand-title { font-size: 17px; font-weight: 900; color: #3b82f6; text-transform: uppercase; letter-spacing: 1px; }
    .brand-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }
    .printer-tag { font-size: 10px; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 20px; padding: 4px 12px; display: inline-block; margin-top: 8px; font-weight: 700; }

    /* Touch Grid */
    .pos-grid { display: grid; grid-template-columns: 1fr; gap: 12px; flex: 1; margin-bottom: 14px; }

    /* Giant Touch Button Cards */
    .touch-card {
      background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
      border: 2px solid rgba(59, 130, 246, 0.3);
      border-radius: 20px;
      padding: 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer;
      transition: all 0.15s ease;
      box-shadow: 0 8px 25px rgba(0,0,0,0.5);
    }
    .touch-card:active { transform: scale(0.96); border-color: #10b981; background: rgba(16, 185, 129, 0.2); }
    
    .c-info { text-align: left; }
    .c-name { font-size: 19px; font-weight: 900; color: #fff; margin-bottom: 4px; }
    .c-badges { display: flex; gap: 6px; }
    .c-badge { background: rgba(255,255,255,0.08); padding: 3px 8px; border-radius: 10px; font-size: 10.5px; font-weight: 700; color: #cbd5e1; }

    .c-price-box { text-align: right; }
    .c-price { font-size: 26px; font-weight: 900; color: #10b981; font-family: 'Courier New', monospace; }
    .c-btn { background: #10b981; color: #fff; border: none; border-radius: 10px; padding: 6px 12px; font-size: 11.5px; font-weight: 800; margin-top: 4px; display: inline-flex; align-items: center; gap: 5px; }

    /* Pop-up Voucher Result Modal */
    .modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.88); backdrop-filter: blur(14px); z-index: 9999; align-items: center; justify-content: center; padding: 20px; }
    .modal-backdrop.show { display: flex; }
    .modal-box { background: #1e293b; border: 2px solid #10b981; border-radius: 26px; padding: 28px 22px; width: 100%; max-width: 350px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.9); }
    .pin-display { font-size: 38px; font-weight: 900; letter-spacing: 5px; color: #3b82f6; background: rgba(15,23,42,0.9); border: 2px solid rgba(59,130,246,0.5); border-radius: 18px; padding: 14px; margin: 16px 0; font-family: monospace; }
    .btn-finish { width: 100%; background: #10b981; border: none; border-radius: 14px; padding: 15px; color: #fff; font-size: 16px; font-weight: 800; cursor: pointer; margin-top: 10px; box-shadow: 0 6px 20px rgba(16,185,129,0.4); }

    /* Toast */
    .toast-popup { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); background: #3b82f6; color: #fff; padding: 12px 24px; border-radius: 30px; font-weight: 800; font-size: 13px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); display: none; z-index: 10000; }
  </style>
</head>
<body>

  <!-- Top Bar -->
  <div class="top-bar">
    <div class="brand-title"><i class="fas fa-cash-register"></i> <?= htmlspecialchars($bName) ?></div>
    <div class="brand-sub">PERSONEL MOBİL KASA TERMINALİ</div>
    
    <!-- Personel / Kasa Seçici -->
    <div style="margin: 10px 0 6px;">
      <select id="staff-select" onchange="changeStaff()" style="width:100%; background:rgba(15,23,42,0.95); border:1px solid rgba(59,130,246,0.4); border-radius:12px; padding:9px 12px; color:#fff; font-weight:800; font-size:13px; text-align-last:center;">
        <option value="">👤 Kasa / Personel Seçiniz...</option>
      </select>
    </div>

    <div class="printer-tag" id="p-tag">🖨️ Yazıcı: <?= htmlspecialchars($printIp) ?> (TCP 9100)</div>
  </div>

  <!-- Profil Dokunmatik Kartlar Listesi -->
  <div class="pos-grid" id="pos-grid">
    <div style="text-align:center; padding:50px; color:#64748b;">
      <i class="fas fa-spinner fa-spin" style="font-size:28px; margin-bottom:12px; color:#3b82f6;"></i><br>
      Profiller Yükleniyor...
    </div>
  </div>

  <!-- Sonuç Pop-up Modalı -->
  <div class="modal-backdrop" id="modal">
    <div class="modal-box">
      <div style="font-size:44px; color:#10b981; margin-bottom:6px;"><i class="fas fa-circle-check"></i></div>
      <div style="font-size:20px; font-weight:900; color:#fff;">SATIŞ TAMAMLANDI!</div>
      <div style="font-size:12.5px; color:#94a3b8; margin-top:4px;" id="m-profile">1 SAATLİK KUPON</div>

      <div class="pin-display" id="m-pin">748291</div>

      <div style="font-size:12px; color:#10b981; font-weight:700; margin-bottom:14px;" id="m-status">
        🖨️ Fiş termal yazıcıya basıldı!
      </div>

      <button class="btn-finish" onclick="closeModal()">TAMAM / YENİ SATIŞ YAP</button>
    </div>
  </div>

  <div class="toast-popup" id="toast"></div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    const prices = {
      'default': 10,
      '1_saat': 15,
      '24_saat': 30,
      '1_gun': 30,
      '7_gun': 100,
      '30_gun': 250,
    };

    let staffList = [];
    let defaultSystemIp = '<?= htmlspecialchars($printIp) ?>';

    function loadStaffList() {
      $.getJSON('./api.php?action=get_staff_printers', function(res) {
        if (!res.success || !res.staff_printers) return;
        staffList = res.staff_printers;

        let options = '<option value="">👤 Varsayılan Kasa Yazıcısı (' + defaultSystemIp + ')</option>';
        staffList.forEach(s => {
          options += `<option value="${s.staff_name}" data-ip="${s.printer_ip}">👤 ${s.staff_name} — 🖨️ ${s.printer_ip}</option>`;
        });

        $('#staff-select').html(options);

        const savedStaff = localStorage.getItem('pos_selected_staff');
        if (savedStaff) {
          $('#staff-select').val(savedStaff);
          changeStaff();
        }
      });
    }

    function changeStaff() {
      const selected = $('#staff-select').val();
      localStorage.setItem('pos_selected_staff', selected || '');
      
      const opt = $('#staff-select').find('option:selected');
      const ip = opt.data('ip') || defaultSystemIp;
      
      $('#p-tag').text('🖨️ Yazıcı: ' + ip + ' (TCP 9100)');
    }

    function loadProfiles() {
      $.getJSON('./api.php?action=get_hotspot_profiles', function(res) {
        if (!res.success || !res.profiles || !res.profiles.length) {
          $('#pos-grid').html('<div style="text-align:center; padding:40px; color:#94a3b8;">Profil bulunamadı.</div>');
          return;
        }

        const cards = res.profiles.map(p => {
          const nameLower = p.name.toLowerCase();
          let price = 20;
          for (let k in prices) {
            if (nameLower.includes(k)) price = prices[k];
          }

          return `
            <div class="touch-card" onclick="doSale('${p.name}', ${price})">
              <div class="c-info">
                <div class="c-name">⚡ ${p.name}</div>
                <div class="c-badges">
                  <span class="c-badge"><i class="fas fa-bolt"></i> ${p.rate_limit}</span>
                  <span class="c-badge"><i class="fas fa-clock"></i> ${p.session_timeout}</span>
                </div>
              </div>
              <div class="c-price-box">
                <div class="c-price">${price} ₺</div>
                <div class="c-btn"><i class="fas fa-print"></i> BAS & SAT</div>
              </div>
            </div>
          `;
        });

        $('#pos-grid').html(cards.join(''));
      });
    }

    function doSale(profileName, price) {
      if (navigator.vibrate) navigator.vibrate(60);

      const staffName = $('#staff-select').val() || '';

      showToast('⚡ Fiş basılıyor ve kupon oluşturuluyor...');

      $.getJSON(`./api.php?action=fast_pos_sale&profile=${encodeURIComponent(profileName)}&price=${encodeURIComponent(price)}&staff_name=${encodeURIComponent(staffName)}`, function(res) {
        if (res.success && res.code) {
          $('#m-profile').text(profileName.toUpperCase() + ' — ' + price + ' ₺' + (staffName ? ' (' + staffName + ')' : ''));
          $('#m-pin').text(res.code);
          $('#m-status').text(res.print_status === 'SUCCESS' ? '🖨️ Fiş termal yazıcıya (' + res.printer_ip + ') basıldı!' : '⚠️ Fiş yazdırılamadı: ' + (res.print_message || ''));
          $('#modal').addClass('show');
        } else {
          alert('Hata: ' + (res.error || 'Satış yapılamadı'));
        }
      });
    }

    function closeModal() {
      $('#modal').removeClass('show');
    }

    function showToast(msg) {
      $('#toast').text(msg).fadeIn(200).delay(2000).fadeOut(200);
    }

    $(function() {
      loadStaffList();
      loadProfiles();
    });
  </script>
</body>
</html>
HTML;
