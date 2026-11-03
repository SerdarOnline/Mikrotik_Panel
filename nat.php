<?php
$pageTitle  = 'NAT & Port Yönlendirme';
$activePage = 'nat';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Yeni Port Yönlendirme (Port Forwarding) Ekle ────────── -->
<div class="panel" style="margin-bottom:20px;">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-arrow-right-to-city"></i></span>
      Yeni Port Yönlendirme (Port Forwarding / Dst-NAT) Ekle
    </h3>
  </div>
  <div class="panel-body">
    <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
      <div style="width:120px;">
        <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Protokol:</label>
        <select id="nat-proto" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8.5px 12px; color:#fff; font-size:13px;">
          <option value="tcp" selected>TCP</option>
          <option value="udp">UDP</option>
        </select>
      </div>
      <div style="flex:1; min-width:140px;">
        <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Dış Port (Dst-Port):</label>
        <input type="text" id="nat-dst-port" placeholder="Örn: 8080 veya 5000" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px; font-family:monospace;">
      </div>
      <div style="flex:1; min-width:160px;">
        <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">İç Hedef IP (To-Addresses):</label>
        <input type="text" id="nat-to-addr" placeholder="Örn: 192.168.1.246" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px; font-family:monospace;">
      </div>
      <div style="flex:1; min-width:140px;">
        <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">İç Port (İsteğe Bağlı):</label>
        <input type="text" id="nat-to-port" placeholder="Boş ise Dış Port ile aynı" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px; font-family:monospace;">
      </div>
      <div style="flex:1; min-width:160px;">
        <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Açıklama / Yorum:</label>
        <input type="text" id="nat-comment" placeholder="Örn: Kamera Kayıt Cihazı" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px;">
      </div>
      <div style="padding-top:18px;">
        <button onclick="addNatRule()" class="pill pill-blue" style="cursor:pointer; border:none; padding:9px 18px; font-size:13px; font-weight:700;">
          <i class="fas fa-plus"></i> Port Forwarding Ekle
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ── Firewall NAT Kuralları Tablosu ─────────────────────── -->
<div class="panel">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-network-wired"></i></span>
      MikroTik Firewall NAT Kuralları
      <span class="pill pill-blue" id="nat-count" style="margin-left:8px;">0 kural</span>
    </h3>
    <div style="display:flex; align-items:center; gap:10px;">
      <button onclick="exportUsersCSV('#nat-table')" class="pill pill-green" style="cursor:pointer; border:none; padding:6px 12px; font-size:12px;">
        <i class="fas fa-file-csv"></i> CSV Aktar
      </button>
      <div class="search-wrap">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" class="search-input table-search" data-target="#nat-table" placeholder="Kural veya IP ara...">
      </div>
    </div>
  </div>
  <div class="panel-body no-pad">
    <table class="data-table" id="nat-table">
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>Chain</th>
          <th>Eylem (Action)</th>
          <th>Protokol</th>
          <th>Dış Port</th>
          <th>Hedef İç IP</th>
          <th>İç Port</th>
          <th>Trafik (Byte / Paket)</th>
          <th>Açıklama</th>
          <th style="text-align:right;">İşlem</th>
        </tr>
      </thead>
      <tbody id="nat-body">
        <tr class="loading-row"><td colspan="10"><div class="spinner" style="margin:0 auto;"></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {
  window.fetchNatRules = function () {
    $.getJSON('./api.php?action=nat_rules', function (res) {
      if (!res.success) return;

      const rules = res.nat_rules || [];
      $('#nat-count').text(rules.length + ' kural');

      if (!rules.length) {
        $('#nat-body').html('<tr><td colspan="10" style="text-align:center; color:var(--text-muted);">NAT kuralı bulunamadı.</td></tr>');
        return;
      }

      const rows = rules.map((r, i) => {
        const actionCls = r.action === 'masquerade' ? 'pill-purple'
          : r.action === 'dst-nat' ? 'pill-green'
          : r.action === 'src-nat' ? 'pill-blue'
          : 'pill-gray';

        return `
          <tr style="${r.disabled ? 'opacity:0.5;' : ''}">
            <td style="color:var(--text-muted); font-size:11px;">${i + 1}</td>
            <td><span class="pill pill-blue">${r.chain}</span></td>
            <td><span class="pill ${actionCls}">${r.action}</span></td>
            <td><span class="mono" style="color:var(--text-dim);">${r.protocol}</span></td>
            <td><span class="mono" style="font-weight:700; color:var(--accent2);">${r.dst_port}</span></td>
            <td><span class="mono" style="font-weight:700; color:var(--success);">${r.to_addr}</span></td>
            <td><span class="mono">${r.to_port}</span></td>
            <td style="font-size:11.5px; font-family:monospace;">${formatBytes(r.bytes)} (${r.packets.toLocaleString('tr-TR')} pkt)</td>
            <td style="font-size:11px; color:var(--text-muted);">${r.comment || '—'}</td>
            <td style="text-align:right;">
              <button onclick="removeNatRule('${r.id}')" class="pill pill-red" style="cursor:pointer; border:none; padding:4px 8px; font-size:11px;">
                <i class="fas fa-trash-can"></i> Sil
              </button>
            </td>
          </tr>
        `;
      });

      $('#nat-body').html(rows.join(''));
      setLastUpdate();
    });
  };

  window.addNatRule = function () {
    const proto = $('#nat-proto').val();
    const dstPort = $('#nat-dst-port').val().trim();
    const toAddr = $('#nat-to-addr').val().trim();
    const toPort = $('#nat-to-port').val().trim();
    const comment = $('#nat-comment').val().trim();

    if (!dstPort || !toAddr) {
      showToast('Lütfen Dış Port ve İç IP Adresini yazın', 'error');
      return;
    }

    $.getJSON(`./api.php?action=add_nat_rule&protocol=${proto}&dst_port=${encodeURIComponent(dstPort)}&to_addr=${encodeURIComponent(toAddr)}&to_port=${encodeURIComponent(toPort)}&comment=${encodeURIComponent(comment)}`, function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        $('#nat-dst-port').val('');
        $('#nat-to-addr').val('');
        $('#nat-to-port').val('');
        $('#nat-comment').val('');
        fetchNatRules();
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  window.removeNatRule = function (id) {
    if (!confirm('Bu NAT kuralını silmek istediğinize emin misiniz?')) return;
    $.getJSON('./api.php?action=remove_nat_rule&id=' + encodeURIComponent(id), function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        fetchNatRules();
      }
    });
  };

  window.refreshAll = fetchNatRules;
  fetchNatRules();
  setInterval(fetchNatRules, 15000);
});
</script>
