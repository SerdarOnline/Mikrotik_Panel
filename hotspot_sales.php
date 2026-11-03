<?php
$pageTitle  = 'Hotspot Satış & Gelir Raporları';
$activePage = 'hotspot_sales';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Stat Grid ───────────────────────────────────────────── -->
<div class="stat-grid" style="margin-bottom:24px;">
  <div class="stat-card" style="--card-color:#10b981;">
    <div class="card-icon"><i class="fas fa-sack-dollar"></i></div>
    <div class="card-value" id="rev-total">— ₺</div>
    <div class="card-label">Toplam Hotspot Geliri</div>
    <div class="card-sub">Tüm Zamanlar</div>
  </div>
  <div class="stat-card" style="--card-color:#3b82f6;">
    <div class="card-icon"><i class="fas fa-calendar-day"></i></div>
    <div class="card-value" id="rev-today">— ₺</div>
    <div class="card-label">Bugünün Geliri</div>
    <div class="card-sub">Bugünkü Satışlar</div>
  </div>
  <div class="stat-card" style="--card-color:#8b5cf6;">
    <div class="card-icon"><i class="fas fa-calendar-week"></i></div>
    <div class="card-value" id="rev-month">— ₺</div>
    <div class="card-label">Bu Ayın Geliri</div>
    <div class="card-sub">Aylık Toplam</div>
  </div>
  <div class="stat-card" style="--card-color:#f59e0b;">
    <div class="card-icon"><i class="fas fa-receipt"></i></div>
    <div class="card-value" id="rev-cnt">—</div>
    <div class="card-label">Satılan Kupon Adedi</div>
    <div class="card-sub">Toplam İşlem Sayısı</div>
  </div>
</div>

<!-- ── Manuel Satış Kaydı Ekle ─────────────────────────────── -->
<div class="panel" style="margin-bottom:20px;">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-cash-register"></i></span>
      Yeni Kupon Satışı Kaydet (Gelir Ekle)
    </h3>
  </div>
  <div class="panel-body">
    <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
      <div style="flex:1; min-width:180px;">
        <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Kupon Kodu:</label>
        <input type="text" id="sale-code" placeholder="Örn: 748291" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px; font-family:'JetBrains Mono',monospace;">
      </div>
      <div style="width:160px;">
        <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Satış Fiyatı (₺):</label>
        <input type="number" id="sale-price" value="20" min="1" step="0.5" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px; font-weight:700;">
      </div>
      <div style="width:180px;">
        <label style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px;">Profil / Paket:</label>
        <select id="sale-profile" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:#fff; font-size:13px;">
          <option value="1Gunluk">1 Günlük Paket (20 ₺)</option>
          <option value="1Haftalik">1 Haftalık Paket (80 ₺)</option>
          <option value="1Aylik">1 Aylık Paket (250 ₺)</option>
          <option value="default">default</option>
        </select>
      </div>
      <div style="padding-top:18px;">
        <button onclick="recordSale()" class="pill pill-green" style="cursor:pointer; border:none; padding:9px 18px; font-size:13px; font-weight:700;">
          <i class="fas fa-plus"></i> Satışı Kaydet
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ── Satış Geçmişi Tablosu ───────────────────────────────── -->
<div class="panel">
  <div class="panel-header">
    <h3>
      <span class="ph-icon"><i class="fas fa-file-invoice-dollar"></i></span>
      Hotspot Satış & Gelir Geçmişi
    </h3>
    <div style="display:flex; align-items:center; gap:10px;">
      <button onclick="exportUsersCSV('#sales-table')" class="pill pill-green" style="cursor:pointer; border:none; padding:6px 12px; font-size:12px;">
        <i class="fas fa-file-csv"></i> CSV / Rapor İndir
      </button>
      <div class="search-wrap">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" class="search-input table-search" data-target="#sales-table" placeholder="Kupon veya tarih ara...">
      </div>
    </div>
  </div>
  <div class="panel-body no-pad">
    <table class="data-table" id="sales-table">
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>Tarih / Saat</th>
          <th>Kupon Kodu</th>
          <th>Profil / Paket</th>
          <th>Satış Tutarı (₺)</th>
          <th>Satıcı</th>
          <th>Fiş Yazdır (ESC/POS)</th>
        </tr>
      </thead>
      <tbody id="sales-body">
        <tr class="loading-row"><td colspan="7"><div class="spinner" style="margin:0 auto;"></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$(function () {
  window.fetchSales = function () {
    $.getJSON('./api.php?action=get_sales_report', function (res) {
      if (!res.success || !res.sales_report) return;

      const r = res.sales_report;
      $('#rev-total').text(r.total_revenue.toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺');
      $('#rev-today').text(r.today_revenue.toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺');
      $('#rev-month').text(r.month_revenue.toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺');
      $('#rev-cnt').text(r.total_count || 0);

      const sales = r.sales || [];
      if (!sales.length) {
        $('#sales-body').html('<tr><td colspan="6" style="text-align:center; color:var(--text-muted);">Henüz kaydedilmiş satış bulunmuyor.</td></tr>');
      } else {
        const rows = sales.map((s, i) => `
          <tr>
            <td style="color:var(--text-muted); font-size:11px;">${i + 1}</td>
            <td><span class="mono" style="font-size:11.5px; color:var(--text-dim);">${s.date}</span></td>
            <td><span class="mono" style="font-weight:700; color:var(--accent2);">${s.code}</span></td>
            <td><span class="pill pill-purple">${s.profile}</span></td>
            <td style="font-weight:800; color:var(--success); font-family:'JetBrains Mono',monospace;">${s.price.toFixed(2)} ₺</td>
            <td><span class="pill pill-blue">${s.seller || 'admin'}</span></td>
            <td>
              <button onclick="printEscPosReceipt('${s.code}', ${s.price}, '${s.profile}')" class="pill pill-green" style="cursor:pointer; border:none; padding:4px 10px; font-weight:700; font-size:11px;">
                <i class="fas fa-print"></i> ESC/POS Fiş
              </button>
            </td>
          </tr>
        `);
        $('#sales-body').html(rows.join(''));
      }

      setLastUpdate();
    });
  };

  window.recordSale = function () {
    const code = $('#sale-code').val().trim();
    const price = $('#sale-price').val();
    const profile = $('#sale-profile').val();

    if (!code || !price) {
      showToast('Kupon kodu ve fiyat giriniz', 'error');
      return;
    }

    $.getJSON(`./api.php?action=record_sale&code=${encodeURIComponent(code)}&price=${encodeURIComponent(price)}&profile=${encodeURIComponent(profile)}`, function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        $('#sale-code').val('');
        fetchSales();
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  window.refreshAll = fetchSales;
  fetchSales();
  setInterval(fetchSales, 15000);
});
</script>
