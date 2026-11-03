<?php
/**
 * pos_mobile.php — Mobil Dokunmatik Hızlı POS Satış Sayfası (Sadece Telefonlar İçin Minimalist Kasa Ekranı)
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
  <title>Mobil Hızlı POS — <?= htmlspecialchars($bName) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; -webkit-tap-highlight-color: transparent; }
    body { background: #0b0f19; color: #fff; min-height: 100vh; padding: 16px; display: flex; flex-direction: column; }

    /* Header */
    .mobile-header { background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 18px; padding: 16px; text-align: center; margin-bottom: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
    .brand { font-size: 16px; font-weight: 900; color: #3b82f6; text-transform: uppercase; letter-spacing: 1px; }
    .subtitle { font-size: 11px; color: #94a3b8; margin-top: 2px; }
    .printer-status { font-size: 10.5px; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 20px; padding: 4px 12px; display: inline-block; margin-top: 8px; font-weight: 700; }

    /* Touch Grid */
    .pos-grid { display: grid; grid-template-columns: 1fr; gap: 14px; flex: 1; margin-bottom: 16px; }

    /* Giant Touch Cards */
    .pos-touch-card {
      background: linear-gradient(145deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.95));
      border: 2px solid rgba(59, 130, 246, 0.25);
      border-radius: 20px;
      padding: 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer;
      transition: all 0.15s ease;
      box-shadow: 0 8px 20px rgba(0,0,0,0.4);
    }
    .pos-touch-card:active { transform: scale(0.97); border-color: #3b82f6; background: rgba(59, 130, 246, 0.2); }
    
    .card-left { text-align: left; }
    .prof-name { font-size: 18px; font-weight: 900; color: #fff; margin-bottom: 4px; }
    .prof-details { font-size: 12px; color: #94a3b8; display: flex; gap: 8px; }
    .badge { background: rgba(255,255,255,0.08); padding: 2px 8px; border-radius: 12px; font-size: 10.5px; font-weight: 700; color: #cbd5e1; }

    .card-right { text-align: right; }
    .prof-price { font-size: 24px; font-weight: 900; color: #10b981; font-family: 'Courier New', monospace; }
    .btn-tap { background: #3b82f6; color: #fff; border: none; border-radius: 12px; padding: 8px 14px; font-size: 12px; font-weight: 800; margin-top: 6px; display: inline-flex; align-items: center; gap: 6px; }

    /* Voucher Modal Pop-up */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(12px); z-index: 9999; align-items: center; justify-content: center; padding: 20px; }
    .modal-overlay.active { display: flex; }
    .modal-card { background: #1e293b; border: 2px solid #3b82f6; border-radius: 24px; padding: 28px 24px; width: 100%; max-width: 360px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.8); }
    .pin-code { font-size: 36px; font-weight: 900; letter-spacing: 4px; color: #3b82f6; background: rgba(15,23,42,0.8); border: 2px solid rgba(59,130,246,0.4); border-radius: 16px; padding: 12px; margin: 16px 0; font-family: monospace; }
    .close-btn { width: 100%; background: #3b82f6; border: none; border-radius: 14px; padding: 14px; color: #fff; font-size: 16px; font-weight: 800; cursor: pointer; margin-top: 12px; }

    /* Toast */
    .toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: #10b981; color: #fff; padding: 12px 24px; border-radius: 30px; font-weight: 700; font-size: 13px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); display: none; z-index: 10000; }
  </style>
</head>
<body>

  <!-- Mobil Header -->
  <div class="mobile-header">
    <div class="brand"><i class="fas fa-cash-register"></i> <?= htmlspecialchars($bName) ?></div>
    <div class="subtitle">MOBİL HIZLI POS KASA TERMINALİ</div>
    <div class="printer-status" id="p-status">🖨️ Yazıcı: <?= htmlspecialchars($printIp) ?> (TCP 9100)</div>
  </div>

  <!-- Profil Dokunmatik Listesi -->
  <div class="pos-grid" id="pos-grid">
    <div style="text-align:center; padding:40px; color:#64748b;">
      <i class="fas fa-spinner fa-spin" style="font-size:24px; margin-bottom:12px;"></i><br>
      Profiller yükleniyor...
    </div>
  </div>

  <!-- Tam Ekran Başarılı Kupon Modalı -->
  <div class="modal-overlay" id="voucher-modal">
    <div class="modal-card">
      <div style="font-size:40px; color:#10b981; margin-bottom:8px;"><i class="fas fa-circle-check"></i></div>
      <div style="font-size:18px; font-weight:900; color:#fff;">SATIŞ BAŞARILI!</div>
      <div style="font-size:12px; color:#94a3b8; margin-top:4px;" id="m-prof-name">1 SAATLİK KUPON</div>

      <div class="pin-code" id="m-pin-code">748291</div>

      <div style="font-size:12px; color:#10b981; font-weight:700; margin-bottom:12px;" id="m-print-msg">
        🖨️ Fiş termal yazıcıya basıldı!
      </div>

      <button class="close-btn" onclick="closeVoucherModal()">TAMAM / YENİ SATIŞ</button>
    </div>
  </div>

  <div class="toast" id="toast-msg"></div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    const defaultPrices = {
      'default': 10,
      '1_saat': 15,
      '24_saat': 30,
      '1_gun': 30,
      '7_gun': 100,
      '30_gun': 250,
    };

    function loadMobileProfiles() {
      $.getJSON('./api.php?action=get_hotspot_profiles', function(res) {
        if (!res.success || !res.profiles || !res.profiles.length) {
          $('#pos-grid').html('<div style="text-align:center; padding:30px; color:#94a3b8;">Profil bulunamadı.</div>');
          return;
        }

        const cards = res.profiles.map(p => {
          const pLower = p.name.toLowerCase();
          let price = 20;
          for (let k in defaultPrices) {
            if (pLower.includes(k)) price = defaultPrices[k];
          }

          return `
            <div class="pos-touch-card" onclick="mobileSale('${p.name}', ${price})">
              <div class="card-left">
                <div class="prof-name">⚡ ${p.name}</div>
                <div class="prof-details">
                  <span class="badge"><i class="fas fa-bolt"></i> ${p.rate_limit}</span>
                  <span class="badge"><i class="fas fa-clock"></i> ${p.session_timeout}</span>
                </div>
              </div>
              <div class="card-right">
                <div class="prof-price">${price} ₺</div>
                <div class="btn-tap"><i class="fas fa-print"></i> DOKUN & SAT</div>
              </div>
            </div>
          `;
        });

        $('#pos-grid').html(cards.join(''));
      });
    }

    function mobileSale(profileName, price) {
      if (navigator.vibrate) navigator.vibrate(50); // Haptic titreşim geribildirimi

      showToast('⚡ Fiş yazdırılıyor ve kupon oluşturuluyor...');

      $.getJSON(`./api.php?action=fast_pos_sale&profile=${encodeURIComponent(profileName)}&price=${encodeURIComponent(price)}`, function(res) {
        if (res.success && res.code) {
          $('#m-prof-name').text(profileName.toUpperCase() + ' — ' + price + ' ₺');
          $('#m-pin-code').text(res.code);
          $('#m-print-msg').text(res.print_status === 'SUCCESS' ? '🖨️ Fiş termal yazıcıya (' + res.printer_ip + ') basıldı!' : '⚠️ Fiş yazdırılamadı: ' + (res.print_message || ''));
          $('#voucher-modal').addClass('active');
        } else {
          alert('Hata: ' + (res.error || 'Satış yapılamadı'));
        }
      });
    }

    function closeVoucherModal() {
      $('#voucher-modal').removeClass('active');
    }

    function showToast(msg) {
      $('#toast-msg').text(msg).fadeIn(200).delay(2500).fadeOut(200);
    }

    $(function() {
      loadMobileProfiles();
    });
  </script>
</body>
</html>
HTML;
