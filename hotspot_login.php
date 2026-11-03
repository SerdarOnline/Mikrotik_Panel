<?php
$pageTitle  = 'Hotspot Login Sayfası & MikroTik Config Gönder';
$activePage = 'hotspot_login';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Eylem Butonları ──────────────────────────────────────── -->
<div style="background:var(--bg-card); border:1px solid var(--border); border-radius:16px; padding:20px; margin-bottom:24px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px;">
  <div>
    <h2 style="margin:0 0 4px 0; font-size:18px; display:flex; align-items:center; gap:8px;">
      🚀 MikroTik Konfigürasyon Gönder & Hotspot Login Yayınla
    </h2>
    <div style="font-size:12.5px; color:var(--text-dim);">
      Hazırladığınız Hotspot ayarlarını ve özel HTML5 login sayfasını tek tıkla MikroTik router'ınıza gönderin.
    </div>
  </div>
  <div style="display:flex; gap:10px; flex-wrap:wrap;">
    <button onclick="pushConfigToMikrotik()" id="btn-push" class="pill pill-green" style="cursor:pointer; border:none; padding:10px 18px; font-weight:700; font-size:13px;">
      <i class="fas fa-paper-plane"></i> 🚀 MikroTik'e Config Gönder
    </button>
    <button onclick="generateLoginHtml()" id="btn-gen-login" class="pill pill-blue" style="cursor:pointer; border:none; padding:10px 18px; font-weight:700; font-size:13px;">
      <i class="fas fa-code"></i> HTML5 Login Sayfasını Üret
    </button>
    <a href="./login.html" download="login.html" class="pill pill-purple" style="text-decoration:none; padding:10px 18px; font-weight:700; font-size:13px;">
      <i class="fas fa-download"></i> login.html İndir
    </a>
  </div>
</div>

<div class="grid-2" style="margin-bottom:20px;">

  <!-- ── Hotspot Login Sayfası Ayarları ────────────────────── -->
  <div class="panel">
    <div class="panel-header">
      <h3>
        <span class="ph-icon"><i class="fas fa-laptop-code"></i></span>
        Hotspot Giriş Ekranı Ayarları
      </h3>
    </div>
    <div class="panel-body">
      <div style="margin-bottom:14px;">
        <label style="font-size:12px; color:var(--text-muted); display:block; margin-bottom:6px;">Özel Logo Görseli (Base64 Sıkıştırılıp HTML'e Gömülür):</label>
        <div style="display:flex; gap:10px; align-items:center;">
          <input type="file" id="logo-file-input" accept="image/*" style="display:none;" onchange="uploadLogoFile(this)">
          <button onclick="$('#logo-file-input').click()" class="pill pill-purple" style="cursor:pointer; border:none; padding:9px 16px; font-size:12px; font-weight:700;">
            <i class="fas fa-image"></i> Logo Yükle / Değiştir
          </button>
          <span id="logo-status-text" style="font-size:11.5px; color:var(--success); font-weight:600;"><i class="fas fa-check-circle"></i> Base64 Logo Hazır</span>
        </div>
      </div>

      <div style="margin-bottom:14px;">
        <label style="font-size:12px; color:var(--text-muted); display:block; margin-bottom:6px;">Hotspot DNS Adı (Örn: wifi.lagoon.com):</label>
        <input type="text" id="hs-dns-name" placeholder="wifi.lagoon.com" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:10px 14px; color:#fff; font-size:13.5px; font-family:monospace;">
      </div>

      <div style="margin-bottom:14px;">
        <label style="font-size:12px; color:var(--text-muted); display:block; margin-bottom:6px;">Karşılama Başlığı:</label>
        <input type="text" id="hs-title" placeholder="Hoş Geldiniz — İnternet Erişimi" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:10px 14px; color:#fff; font-size:13.5px;">
      </div>

      <div style="margin-bottom:16px;">
        <label style="font-size:12px; color:var(--text-muted); display:block; margin-bottom:6px;">Karşılama Mesajı:</label>
        <textarea id="hs-welcome-msg" rows="3" placeholder="Lütfen resepsiyondan veya kasadan aldığınız kupon kodunu giriniz." style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:10px 14px; color:#fff; font-size:13px; resize:vertical;"></textarea>
      </div>

      <button onclick="saveAndGenerate()" class="pill pill-blue" style="width:100%; cursor:pointer; border:none; padding:12px; font-weight:700; font-size:13.5px;">
        <i class="fas fa-sync"></i> Ayarları Sakla ve Login Sayfasını Güncelle
      </button>
    </div>
  </div>

  <!-- ── Canlı HTML5 Login Ekranı Önizlemesi ────────────────── -->
  <div class="panel">
    <div class="panel-header">
      <h3>
        <span class="ph-icon"><i class="fas fa-mobile-screen"></i></span>
        Canlı Hotspot Giriş Ekranı Önizleme
      </h3>
    </div>
    <div class="panel-body no-pad" style="height:380px; background:#000;">
      <iframe id="login-preview-iframe" src="./login.html" style="width:100%; height:100%; border:none;"></iframe>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {
  function loadLoginSettings() {
    $.getJSON('./api.php?action=get_settings', function (res) {
      if (!res.success || !res.settings) return;
      const s = res.settings;
      $('#hs-dns-name').val(s.hotspot_dns_name || 'wifi.lagoon.com');
      $('#hs-title').val(s.login_title || 'Hoş Geldiniz — İnternet Erişimi');
      $('#hs-welcome-msg').val(s.login_welcome_msg || '');
    });
  }

  window.saveAndGenerate = function () {
    const dns = $('#hs-dns-name').val().trim();
    const title = $('#hs-title').val().trim();
    const msg = $('#hs-welcome-msg').val().trim();

    $.getJSON(`./api.php?action=save_settings&hotspot_dns_name=${encodeURIComponent(dns)}&login_title=${encodeURIComponent(title)}&welcome_msg=${encodeURIComponent(msg)}`, function (res) {
      if (res.success) {
        generateLoginHtml();
      }
    });
  };

  window.generateLoginHtml = function () {
    $.getJSON('./api.php?action=generate_hotspot_login', function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        document.getElementById('login-preview-iframe').src = './login.html?t=' + new Date().getTime();
      }
    });
  };

  window.uploadLogoFile = function (input) {
    if (!input.files || !input.files[0]) return;
    const formData = new FormData();
    formData.append('logo', input.files[0]);

    $('#logo-status-text').html('<i class="fas fa-spinner fa-spin"></i> Base64 Sıkıştırılıyor...').css('color', 'var(--warning)');

    $.ajax({
      url: './api.php?action=upload_logo',
      type: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      success: function (res) {
        if (res.success) {
          showToast(res.message, 'success');
          $('#logo-status-text').html('<i class="fas fa-check-circle"></i> Base64 Logo Hazır').css('color', 'var(--success)');
          generateLoginHtml();
        } else {
          showToast('Logo yüklenemedi: ' + (res.error || ''), 'error');
          $('#logo-status-text').text('Hata').css('color', 'var(--danger)');
        }
      }
    });
  };

  window.pushConfigToMikrotik = function () {
    $('#btn-push').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Router\'a Gönderiliyor...');
    $.getJSON('./api.php?action=push_config_to_mikrotik', function (res) {
      $('#btn-push').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> 🚀 MikroTik\'e Config Gönder');
      if (res.success) {
        showToast(res.message, 'success');
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  loadLoginSettings();
});
</script>
