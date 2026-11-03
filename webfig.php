<?php
$pageTitle  = 'MikroTik WebFig Gömülü Konsol';
$activePage = 'webfig';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Hızlı WebFig Menü Kısayolları ────────────────────────── -->
<div style="background:var(--bg-card); border:1px solid var(--border); border-radius:16px; padding:20px; margin-bottom:20px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px;">
  <div>
    <h2 style="margin:0 0 4px 0; font-size:18px; display:flex; align-items:center; gap:8px;">
      🌐 MikroTik WebFig Entegre Yönetim Konsolu
    </h2>
    <div style="font-size:12.5px; color:var(--text-dim);">
      RouterOS WebFig arayüzüne doğrudan panel içerisinden güvenli erişim sağlayın.
    </div>
  </div>

  <div style="display:flex; gap:8px; flex-wrap:wrap;">
    <button onclick="openWebfigShortcut('Interfaces')" class="pill pill-blue" style="cursor:pointer; border:none; padding:8px 14px; font-size:12px; font-weight:700;">
      <i class="fas fa-network-wired"></i> Arayüzler
    </button>
    <button onclick="openWebfigShortcut('IP:Firewall')" class="pill pill-green" style="cursor:pointer; border:none; padding:8px 14px; font-size:12px; font-weight:700;">
      <i class="fas fa-shield-halved"></i> Firewall
    </button>
    <button onclick="openWebfigShortcut('IP:Hotspot')" class="pill pill-purple" style="cursor:pointer; border:none; padding:8px 14px; font-size:12px; font-weight:700;">
      <i class="fas fa-ticket"></i> Hotspot
    </button>
    <button onclick="openWebfigShortcut('PPP')" class="pill pill-orange" style="cursor:pointer; border:none; padding:8px 14px; font-size:12px; font-weight:700;">
      <i class="fas fa-id-card"></i> PPP / PPPoE
    </button>
    <button onclick="openWebfigShortcut('Terminal')" class="pill pill-red" style="cursor:pointer; border:none; padding:8px 14px; font-size:12px; font-weight:700;">
      <i class="fas fa-terminal"></i> Terminal
    </button>
    <a href="http://<?= MT_HOST ?>" target="_blank" class="pill pill-blue" style="text-decoration:none; padding:8px 16px; font-size:12px; font-weight:700;">
      <i class="fas fa-up-right-from-square"></i> Yeni Sekmede Aç
    </a>
  </div>
</div>

<!-- ── Gömülü WebFig Iframe Konsolu ────────────────────────── -->
<div class="panel">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-display"></i></span>
      Canlı WebFig RouterOS Arayüzü (192.168.0.1)
    </h3>
    <div style="font-size:11.5px; color:var(--text-muted);">
      Kullanıcı Adı: <strong style="color:#fff;"><?= MT_USER ?></strong> | IP: <strong style="color:var(--accent2);"><?= MT_HOST ?></strong>
    </div>
  </div>
  <div class="panel-body no-pad" style="height:720px; background:#000;">
    <iframe id="webfig-iframe" src="http://<?= MT_HOST ?>" style="width:100%; height:100%; border:none;"></iframe>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {
  window.openWebfigShortcut = function (hash) {
    const baseUrl = 'http://<?= MT_HOST ?>/#';
    document.getElementById('webfig-iframe').src = baseUrl + hash;
    showToast('WebFig Kısayolu Yüklendi: ' + hash, 'info');
  };
});
</script>
