<?php
$pageTitle  = 'Tüm Sistem ve RouterOS Ayarları';
$activePage = 'settings';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="grid-2" style="margin-bottom:20px;">

  <!-- ── Tüm Sistem Ayarları Formu ──────────────────────────── -->
  <div class="panel">
    <div class="panel-header">
      <h3>
        <span class="ph-icon"><i class="fas fa-sliders"></i></span>
        Tüm Panel ve RouterOS Ayarlarını Düzenle
      </h3>
      <button onclick="saveAllSettings()" class="pill pill-green" style="cursor:pointer; border:none; padding:8px 16px; font-weight:700; font-size:13px;">
        <i class="fas fa-floppy-disk"></i> Tüm Ayarları Kaydet
      </button>
    </div>
    <div class="panel-body">
      
      <!-- 1. RouterOS Bağlantı Ayarları -->
      <div style="border-bottom:1px solid var(--border); padding-bottom:16px; margin-bottom:16px;">
        <h4 style="margin:0 0 12px 0; color:var(--accent2); font-size:14px; display:flex; align-items:center; gap:8px;">
          <i class="fas fa-router"></i> MikroTik RouterOS API Bağlantı Ayarları
        </h4>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">Router IP Adresi (MT_HOST):</label>
            <input type="text" id="cfg-mt-host" placeholder="192.168.0.1" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px; font-family:monospace;">
          </div>
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">API Kullanıcı Adı (MT_USER):</label>
            <input type="text" id="cfg-mt-user" placeholder="admin" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px;">
          </div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">API Şifresi (MT_PASS):</label>
            <input type="password" id="cfg-mt-pass" placeholder="••••••••" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px;">
          </div>
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">API Portu (MT_PORT):</label>
            <input type="number" id="cfg-mt-port" placeholder="8728" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px;">
          </div>
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">Zaman Aşımı (s):</label>
            <input type="number" id="cfg-mt-timeout" placeholder="10" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px;">
          </div>
        </div>
      </div>

      <!-- 2. ESC/POS Donanım Termal Yazıcı Ayarları -->
      <div style="border-bottom:1px solid var(--border); padding-bottom:16px; margin-bottom:16px;">
        <h4 style="margin:0 0 12px 0; color:var(--success); font-size:14px; display:flex; align-items:center; gap:8px;">
          <i class="fas fa-print"></i> ESC/POS Donanım Ağ Termal Fiş Yazıcısı Ayarları
        </h4>
        <div style="display:grid; grid-template-columns:2fr 1fr; gap:12px;">
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">Termal Yazıcı IP Adresi (PRINTER_IP):</label>
            <input type="text" id="cfg-printer-ip" placeholder="192.168.0.200" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px; font-family:monospace;">
          </div>
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">RAW TCP Portu:</label>
            <input type="number" id="cfg-printer-port" placeholder="9100" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px; font-family:monospace;">
          </div>
        </div>
      </div>

      <!-- 2b. Personel & Termal Yazıcı (IP) Eşleştirme Yönetimi -->
      <div style="border-bottom:1px solid var(--border); padding-bottom:16px; margin-bottom:16px;">
        <h4 style="margin:0 0 12px 0; color:var(--accent2); font-size:14px; display:flex; align-items:center; gap:8px;">
          <i class="fas fa-users-gear"></i> Personel & Özel Termal Yazıcı (IP) Eşleştirme Yönetimi
        </h4>
        <div style="font-size:12px; color:var(--text-muted); margin-bottom:12px;">
          Her garson, bar veya kasa personeline özel bir Termal Yazıcı IP adresi atayın. Personel kupon sattığında fiş otomatik olarak kendi yazıcısına basılır.
        </div>

        <div style="display:flex; gap:10px; align-items:center; margin-bottom:14px;">
          <input type="text" id="new-staff-name" placeholder="Personel / Kasa Adı (Örn: Ahmet - Havuz Bar)" style="flex:2; padding:9px 12px; font-size:13px;">
          <input type="text" id="new-staff-ip" placeholder="Yazıcı IP (Örn: 192.168.0.201)" style="flex:1.5; padding:9px 12px; font-size:13px; font-family:monospace;">
          <input type="number" id="new-staff-port" value="9100" placeholder="9100" style="width:80px; padding:9px 12px; font-size:13px; font-family:monospace;">
          <button onclick="addStaffPrinter()" class="pill pill-green" style="cursor:pointer; border:none; padding:10px 16px; font-weight:700;">
            <i class="fas fa-plus"></i> Personel Yazıcısı Ekle
          </button>
        </div>

        <table class="data-table" style="font-size:12px;">
          <thead>
            <tr>
              <th>Personel / Kasa Adı</th>
              <th>Özel Yazıcı IP Adresi</th>
              <th>Port</th>
              <th>İşlem</th>
            </tr>
          </thead>
          <tbody id="staff-printers-body">
            <tr><td colspan="4" style="text-align:center; color:var(--text-muted); padding:16px;">Personel yazıcısı tanımlanmadı. (Sistem varsayılan IP'yi kullanır)</td></tr>
          </tbody>
        </table>
      </div>

      <!-- 3. İşletme & Hotspot Ayarları -->
      <div style="border-bottom:1px solid var(--border); padding-bottom:16px; margin-bottom:16px;">
        <h4 style="margin:0 0 12px 0; color:var(--accent); font-size:14px; display:flex; align-items:center; gap:8px;">
          <i class="fas fa-building"></i> İşletme & Hotspot Kupon Fişi Ayarları
        </h4>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">İşletme Adı (Fiş Başlığı):</label>
            <input type="text" id="cfg-business" placeholder="LAGOON HOTSPOT & WIFI" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px;">
          </div>
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">İletişim / Telefon:</label>
            <input type="text" id="cfg-phone" placeholder="0555 123 4567" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px;">
          </div>
        </div>
        <div style="margin-bottom:12px;">
          <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">Hotspot DNS Adı (Örn: wifi.lagoon.com):</label>
          <input type="text" id="cfg-dns-name" placeholder="wifi.lagoon.com" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px; font-family:monospace;">
        </div>
        <div>
          <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">Fiş Alt Açıklama Notu:</label>
          <input type="text" id="cfg-note" placeholder="5651 Sayılı Kanun Gereğince İnternet Erişimi Kayıt Altına Alınmaktadır." style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px;">
        </div>
      </div>

      <!-- 3. 5651 Sayılı Kanun & BTK Yasal Ayarlar -->
      <div style="border-bottom:1px solid var(--border); padding-bottom:16px; margin-bottom:16px;">
        <h4 style="margin:0 0 12px 0; color:var(--success); font-size:14px; display:flex; align-items:center; gap:8px;">
          <i class="fas fa-scale-balanced"></i> 5651 Sayılı Kanun & BTK Yasal Ayarları
        </h4>
        <div style="display:grid; grid-template-columns:2fr 1fr; gap:12px;">
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">Kurumsal Şirket / İşletme Unvanı:</label>
            <input type="text" id="cfg-law-org" placeholder="LAGOON HOTEL & ENTERPRISE" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px;">
          </div>
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">5651 Loglama:</label>
            <select id="cfg-law-enabled" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px;">
              <option value="true" selected>✅ 5651 Yasal Log AÇIK</option>
              <option value="false">❌ KAPALI</option>
            </select>
          </div>
        </div>
      </div>

      <!-- 4. Trafik & Panel Tercihleri -->
      <div>
        <h4 style="margin:0 0 12px 0; color:var(--warning); font-size:14px; display:flex; align-items:center; gap:8px;">
          <i class="fas fa-bell"></i> Trafik Uyarısı & Panel Tercihleri
        </h4>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">Yüksek İndirme Sesli İkazı:</label>
            <select id="cfg-sound" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px;">
              <option value="true" selected>🔊 Sesli İkaz AÇIK</option>
              <option value="false">🔇 Sesli İkaz KAPALI</option>
            </select>
          </div>
          <div>
            <label style="font-size:11.5px; color:var(--text-muted); display:block; margin-bottom:4px;">Uyarı Eşik Hızı (Mbps):</label>
            <select id="cfg-limit-mbps" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:#fff; font-size:13px;">
              <option value="2">⚡ 2 Mbps ve üzeri</option>
              <option value="3" selected>⚡ 3 Mbps ve üzeri</option>
              <option value="5">⚡ 5 Mbps ve üzeri</option>
              <option value="10">⚡ 10 Mbps ve üzeri</option>
            </select>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── POS Termal Fiş Canlı Önizleme ──────────────────────── -->
  <div class="panel">
    <div class="panel-header">
      <h3>
        <span class="ph-icon"><i class="fas fa-print"></i></span>
        POS Termal Kupon Fişi Canlı Önizleme
      </h3>
    </div>
    <div class="panel-body" style="display:flex; justify-content:center; align-items:center; background:#0a0a0c; padding:30px; min-height:450px;">
      
      <!-- POS Receipt Box -->
      <div style="
        background:#fff; color:#000; border:2px solid #000; border-radius:8px; padding:20px; text-align:center;
        font-family:'JetBrains Mono', monospace; width:260px; box-shadow:0 8px 25px rgba(0,0,0,0.6);
      ">
        <div id="prev-business" style="font-size:13px; font-weight:900; text-transform:uppercase; letter-spacing:1px; border-bottom:1px dashed #000; padding-bottom:6px; margin-bottom:8px;">
          LAGOON HOTSPOT & WIFI
        </div>
        <div id="prev-phone" style="font-size:10px; color:#444; margin-bottom:6px;">Tel: 0555 123 4567</div>
        <div style="font-size:10px; color:#555; margin-top:8px;">KUPON GİRİŞ KODU</div>
        <div style="font-size:24px; font-weight:900; letter-spacing:3px; background:#f0f0f0; padding:6px; border:1px solid #ccc; border-radius:4px; margin:6px 0;">
          748291
        </div>
        <div style="font-size:11px; margin:6px 0;">
          ŞİFRE: <strong style="font-size:14px;">748291</strong>
        </div>
        <div style="font-size:10px; color:#333; border-top:1px dashed #000; padding-top:6px; margin-top:8px;">
          Paket: <strong>1 Günlük</strong> | Fiyat: <strong>20 ₺</strong>
        </div>
        <div id="prev-note" style="font-size:8.5px; color:#666; margin-top:6px; line-height:1.3;">
          5651 Sayılı Kanun Gereğince İnternet Erişimi Kayıt Altına Alınmaktadır.
        </div>
      </div>

    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {

  function loadAllSettings() {
    $.getJSON('./api.php?action=get_settings', function (res) {
      if (!res.success || !res.settings) return;
      const s = res.settings;

      $('#cfg-mt-host').val(s.mt_host || '192.168.0.1');
      $('#cfg-mt-user').val(s.mt_user || 'admin');
      $('#cfg-mt-pass').val(s.mt_pass || '');
      $('#cfg-mt-port').val(s.mt_port || 8728);
      $('#cfg-mt-timeout').val(s.mt_timeout || 10);

      $('#cfg-printer-ip').val(s.printer_ip || '192.168.0.200');
      $('#cfg-printer-port').val(s.printer_port || 9100);

      $('#cfg-business').val(s.business_name || '');
      $('#cfg-phone').val(s.phone || '');
      $('#cfg-dns-name').val(s.hotspot_dns_name || 'wifi.lagoon.com');
      $('#cfg-note').val(s.footer_note || '');

      $('#cfg-law-org').val(s.law5651_organization || '');
      $('#cfg-law-enabled').val(s.law5651_enabled ? 'true' : 'false');
      $('#cfg-sound').val(s.sound_alert ? 'true' : 'false');
      $('#cfg-limit-mbps').val(s.high_speed_limit_mbps || 3);

      updatePreview();
    });
  }

  function updatePreview() {
    const b = $('#cfg-business').val().trim() || 'LAGOON HOTSPOT & WIFI';
    const p = $('#cfg-phone').val().trim();
    const n = $('#cfg-note').val().trim() || '5651 Sayılı Kanun Gereğince İnternet Erişimi Kayıt Altına Alınmaktadır.';

    $('#prev-business').text(b);
    $('#prev-phone').text(p ? 'Tel: ' + p : '').css('display', p ? 'block' : 'none');
    $('#prev-note').text(n);
  }

  $('#cfg-business, #cfg-phone, #cfg-note').on('input', updatePreview);

  window.saveAllSettings = function () {
    const data = {
      mt_host: $('#cfg-mt-host').val().trim(),
      mt_user: $('#cfg-mt-user').val().trim(),
      mt_pass: $('#cfg-mt-pass').val().trim(),
      mt_port: $('#cfg-mt-port').val().trim(),
      mt_timeout: $('#cfg-mt-timeout').val().trim(),

      printer_ip: $('#cfg-printer-ip').val().trim(),
      printer_port: $('#cfg-printer-port').val().trim(),

      business_name: $('#cfg-business').val().trim(),
      phone: $('#cfg-phone').val().trim(),
      hotspot_dns_name: $('#cfg-dns-name').val().trim(),
      footer_note: $('#cfg-note').val().trim(),

      law5651_organization: $('#cfg-law-org').val().trim(),
      law5651_enabled: $('#cfg-law-enabled').val(),
      sound_alert: $('#cfg-sound').val(),
      high_speed_limit_mbps: $('#cfg-limit-mbps').val(),
    };

    const query = $.param(data);

    $.getJSON('./api.php?action=save_settings&' + query, function (res) {
      if (res.success) {
        showToast(res.message, 'success');
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  loadAllSettings();
  fetchStaffPrinters();
});

window.fetchStaffPrinters = function () {
  $.getJSON('./api.php?action=get_staff_printers', function (res) {
    if (!res.success || !res.staff_printers) return;
    const list = res.staff_printers;

    if (!list.length) {
      $('#staff-printers-body').html('<tr><td colspan="4" style="text-align:center; color:var(--text-muted); padding:16px;">Personel yazıcısı tanımlanmadı. (Sistem varsayılan IP\'yi kullanır)</td></tr>');
      return;
    }

    const rows = list.map(s => `
      <tr>
        <td><span style="font-weight:700; color:var(--text-primary);">${s.staff_name}</span></td>
        <td><span class="mono" style="font-weight:700; color:var(--accent2);">${s.printer_ip}</span></td>
        <td><span class="pill pill-purple">${s.printer_port}</span></td>
        <td>
          <button onclick="deleteStaffPrinter(${s.id}, '${s.staff_name}')" class="pill pill-red" style="cursor:pointer; border:none; padding:4px 8px; font-size:11px; font-weight:700;">
            <i class="fas fa-trash-can"></i> Sil
          </button>
        </td>
      </tr>
    `);

    $('#staff-printers-body').html(rows.join(''));
  });
};

window.addStaffPrinter = function () {
  const sName = $('#new-staff-name').val().trim();
  const pIp   = $('#new-staff-ip').val().trim();
  const pPort = $('#new-staff-port').val().trim() || 9100;

  if (!sName || !pIp) {
    showToast('Lütfen personel adını ve yazıcı IP adresini giriniz', 'error');
    return;
  }

  $.getJSON(`./api.php?action=save_staff_printer&staff_name=${encodeURIComponent(sName)}&printer_ip=${encodeURIComponent(pIp)}&printer_port=${encodeURIComponent(pPort)}`, function (res) {
    if (res.success) {
      showToast(res.message, 'success');
      $('#new-staff-name').val('');
      $('#new-staff-ip').val('');
      fetchStaffPrinters();
    } else {
      showToast('Hata: ' + (res.error || ''), 'error');
    }
  });
};

window.deleteStaffPrinter = function (id, name) {
  if (!confirm(`${name} personeli yazıcı tanımını silmek istediğinize emin misiniz?`)) return;

  $.getJSON('./api.php?action=delete_staff_printer&id=' + id, function (res) {
    if (res.success) {
      showToast(res.message, 'success');
      fetchStaffPrinters();
    } else {
      showToast('Hata: ' + (res.error || ''), 'error');
    }
  });
};
</script>
