/**
 * app.js — MikroTik Panel Global JavaScript
 * jQuery + Chart.js (CSChart uyumlu)
 */

$(function () {

  // ── Yardımcı: Hız Formatla ─────────────────────────────────
  window.formatSpeed = function (bps) {
    if (bps === null || bps === undefined || isNaN(bps)) return '—';
    bps = parseInt(bps, 10);
    if (bps < 1000)           return bps + ' bps';
    if (bps < 1_000_000)      return (bps / 1000).toFixed(1) + ' Kbps';
    if (bps < 1_000_000_000)  return (bps / 1_000_000).toFixed(2) + ' Mbps';
    return (bps / 1_000_000_000).toFixed(2) + ' Gbps';
  };

  // ── Yardımcı: Byte Formatla ─────────────────────────────────
  window.formatBytes = function (bytes) {
    if (!bytes || isNaN(bytes)) return '—';
    bytes = parseInt(bytes, 10);
    if (bytes < 1024)             return bytes + ' B';
    if (bytes < 1_048_576)        return (bytes / 1024).toFixed(1) + ' KB';
    if (bytes < 1_073_741_824)    return (bytes / 1_048_576).toFixed(2) + ' MB';
    return (bytes / 1_073_741_824).toFixed(2) + ' GB';
  };

  // ── Yardımcı: Toast Bildirimi ────────────────────────────────
  window.showToast = function (message, type = 'success', duration = 4000) {
    const icon = type === 'success'
      ? '<i class="fas fa-check-circle" style="color:var(--success)"></i>'
      : '<i class="fas fa-triangle-exclamation" style="color:var(--danger)"></i>';
    const $toast = $(`
      <div class="toast toast-${type}">
        ${icon}
        <span>${message}</span>
      </div>
    `);
    $('#toast-container').append($toast);
    setTimeout(() => $toast.fadeOut(300, () => $toast.remove()), duration);
  };

  // ── Saat güncelleyici ────────────────────────────────────────
  function updateClock() {
    const now = new Date();
    const t = now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    $('#sidebar-time').text(t);
  }
  updateClock();
  setInterval(updateClock, 1000);

  // ── Son güncelleme zamanı ────────────────────────────────────
  window.setLastUpdate = function () {
    const now = new Date();
    $('#last-update-time').text(now.toLocaleTimeString('tr-TR'));
  };

  // ── Bağlantı durumu ──────────────────────────────────────────
  window.setConnectionStatus = function (ok, msg) {
    const $badge = $('#conn-badge');
    const $text  = $('#conn-text');
    if (ok) {
      $badge.removeClass('disconnected');
      $text.text(msg || 'Bağlı');
    } else {
      $badge.addClass('disconnected');
      $text.text(msg || 'Bağlantı Yok');
    }
  };

  // ── Yenile butonu ────────────────────────────────────────────
  $('#manual-refresh').on('click', function () {
    $(this).addClass('spinning');
    if (typeof window.refreshAll === 'function') {
      window.refreshAll();
    }
    setTimeout(() => $(this).removeClass('spinning'), 800);
  });

  // ── ESC/POS Termal Fiş Yazdırma Motoru (58mm / 80mm POS Printer Uyumlu) ──
  window.printEscPosReceipt = function (code, price, profile) {
    $.getJSON('./api.php?action=get_escpos_data&code=' + encodeURIComponent(code) + '&price=' + encodeURIComponent(price) + '&profile=' + encodeURIComponent(profile), function (res) {
      if (!res.success) {
        showToast('ESC/POS verisi alınamadı: ' + (res.error || ''), 'error');
        return;
      }

      const pWin = window.open('', '_blank', 'width=340,height=500');
      if (!pWin) {
        showToast('Lütfen tarayıcı açılır pencerelerine (Pop-up) izin veriniz', 'error');
        return;
      }

      const bName = res.business_name || 'LAGOON HOTSPOT & WIFI';
      const phone = res.phone ? ('Tel: ' + res.phone) : '';
      const note  = res.note || '5651 Sayılı Kanun Gereğince İnternet Erişimi Kayıt Altına Alınmaktadır.';

      pWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>ESC/POS Fiş - ${code}</title>
          <style>
            @page { size: 58mm auto; margin: 0; }
            body {
              font-family: 'JetBrains Mono', 'Courier New', monospace;
              width: 58mm;
              margin: 0 auto;
              padding: 10px;
              background: #fff;
              color: #000;
              font-size: 11px;
              line-height: 1.3;
              text-align: center;
            }
            .border { border-top: 1px dashed #000; border-bottom: 1px dashed #000; margin: 8px 0; padding: 6px 0; }
            .title { font-size: 13px; font-weight: 900; text-transform: uppercase; }
            .code { font-size: 22px; font-weight: 900; letter-spacing: 2px; border: 2px solid #000; padding: 6px; margin: 8px 0; display: block; }
            .details { font-size: 10px; text-align: left; margin: 6px 0; }
            .footer { font-size: 8.5px; color: #333; margin-top: 8px; }
            @media print {
              body { width: 58mm; padding: 0; }
              .no-print { display: none; }
            }
          </style>
        </head>
        <body>
          <div class="title">${bName}</div>
          ${phone ? `<div style="font-size:10px;">${phone}</div>` : ''}
          <div class="border">HOTSPOT KUPON KODU</div>
          <div style="font-size:10px;">PIN / ŞİFRE</div>
          <div class="code">${code}</div>
          <div class="details">
            <div>Paket: <strong>${profile}</strong></div>
            <div>Fiyat: <strong>${price} ₺</strong></div>
            <div>Tarih: <strong>${new Date().toLocaleString('tr-TR')}</strong></div>
          </div>
          <div class="border footer">${note}</div>
          <button onclick="window.print()" class="no-print" style="margin-top:10px; padding:6px 12px; cursor:pointer;">🖨️ YAZDIR</button>
          <script>
            setTimeout(() => { window.print(); }, 300);
          </script>
        </body>
        </html>
      `);
      pWin.document.close();
    });
  };

  // ── Mobil sidebar toggle ─────────────────────────────────────
  $('#mobile-menu').on('click', function () {
    $('#sidebar').toggleClass('open');
  });

  // ── Tab sistemi ──────────────────────────────────────────────
  $(document).on('click', '.tab-btn', function () {
    const target = $(this).data('tab');
    $(this).closest('.tabs').find('.tab-btn').removeClass('active');
    $(this).addClass('active');
    $(this).closest('.tab-wrap').find('.tab-content').removeClass('active');
    $('#tab-' + target).addClass('active');
  });

  // ── Tablo arama ──────────────────────────────────────────────
  $(document).on('keyup', '.table-search', function () {
    const val   = $(this).val().toLowerCase();
    const table = $($(this).data('target'));
    table.find('tbody tr').each(function () {
      const text = $(this).text().toLowerCase();
      $(this).toggle(text.includes(val));
    });
  });

  // ── Ping testi (bağlantı kontrolü) ──────────────────────────
  function checkConnection() {
    $.getJSON('./api.php?action=ping')
      .done(function (res) {
        if (res.success) {
          setConnectionStatus(true, res.identity || 'Bağlı');
          $('#router-name').text(res.identity || 'MikroTik');
        } else {
          setConnectionStatus(false, 'API Hatası');
        }
      })
      .fail(function () {
        setConnectionStatus(false, 'Sunucu Hatası');
      });
  }
  checkConnection();
  setInterval(checkConnection, 30000); // 30 sn de bir ping

  // ── Rank badge helper ────────────────────────────────────────
  window.rankBadge = function (n) {
    const cls = n === 1 ? 'rank-1' : n === 2 ? 'rank-2' : n === 3 ? 'rank-3' : 'rank-n';
    return `<span class="rank-badge ${cls}">${n}</span>`;
  };

  // ── Web Audio Synth Chime for High Bandwidth Alert ───────────
  let lastSoundTime = 0;
  window.playAlertSound = function () {
    const now = Date.now();
    if (now - lastSoundTime < 10000) return; // En az 10 saniye arayla çal
    lastSoundTime = now;
    try {
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();

      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, audioCtx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(1320, audioCtx.currentTime + 0.15);

      gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.3);

      osc.connect(gain);
      gain.connect(audioCtx.destination);

      osc.start();
      osc.stop(audioCtx.currentTime + 0.3);
    } catch (e) {}
  };

  // ── Progress ring helper ─────────────────────────────────────
  window.updateProgressRing = function (svgId, percent, color) {
    const r = 28;
    const circ = 2 * Math.PI * r;
    const offset = circ - (percent / 100) * circ;
    $(`#${svgId} .ring-progress`)
      .attr('stroke', color)
      .css('stroke-dasharray', circ)
      .css('stroke-dashoffset', offset);
    $(`#${svgId} .ring-text`).text(percent + '%');
  };

  // ── Modal & Kullanıcı Yönetimi ────────────────────────────────
  let currentModalIp = null;

  window.openUserModal = function (ip) {
    currentModalIp = ip;
    $('#user-modal').css('display', 'flex').hide().fadeIn(200);
    $('#modal-user-title').text('Kullanıcı: ' + ip);
    $('#modal-user-sub').text('Veriler yükleniyor...');
    $('#m-ip').text(ip);
    $('#m-mac').text('—');
    $('#m-hostname').text('—');
    $('#m-comment').text('—');
    $('#m-status').html('<span class="spinner"></span>');
    $('#m-conn-body').html('<tr><td colspan="5" style="text-align:center;"><div class="spinner"></div> Bağlantılar çekiliyor...</td></tr>');
    $('#m-categories').html('<div class="spinner"></div>');
    $('#modal-queue-status').text('');

    $.getJSON('./api.php?action=user_detail&ip=' + encodeURIComponent(ip), function (res) {
      if (!res.success || !res.user) {
        showToast('Kullanıcı detayı alınamadı: ' + (res.error || ''), 'error');
        return;
      }
      const u = res.user;
      const l = u.lease || {};
      const q = u.queue || {};

      $('#modal-user-title').text(l.hostname || l.comment || ip);
      $('#modal-user-sub').text(ip + ' • ' + (l.mac || 'MAC Yok'));

      $('#m-ip').text(ip);
      $('#m-mac').text(l.mac || '—');
      $('#m-hostname').text(l.hostname || '—');
      $('#m-comment').text(l.comment || '—');
      $('#m-comment-input').val(l.comment || '');
      $('#m-status').html(l.status === 'bound' ? '<span class="pill pill-green">Aktif (bound)</span>' : '<span class="pill pill-gray">' + (l.status || '—') + '</span>');

      // Simple queue status
      if (q.max_limit) {
        $('#modal-queue-status').html('<span class="pill pill-yellow">🔒 Aktif Hız Limiti: ' + q.max_limit + '</span>');
      } else {
        $('#modal-queue-status').html('<span class="pill pill-green">🟢 Limitsiz (Kısıtlama Yok)</span>');
      }

      // Kategori dağılımı
      const cats = u.categories || {};
      const totalCatBytes = Object.values(cats).reduce((a, b) => a + b, 0) || 1;
      let catHtml = '';
      for (const [name, bytes] of Object.entries(cats)) {
        const pct = Math.round((bytes / totalCatBytes) * 100);
        catHtml += `
          <div>
            <div style="display:flex; justify-content:space-between; font-size:11px; margin-bottom:2px;">
              <span>${name}</span>
              <span style="color:var(--text-dim);">${formatBytes(bytes)} (${pct}%)</span>
            </div>
            <div style="height:4px; background:rgba(255,255,255,0.06); border-radius:2px; overflow:hidden;">
              <div style="width:${pct}%; height:100%; background:var(--accent2); border-radius:2px;"></div>
            </div>
          </div>
        `;
      }
      $('#m-categories').html(catHtml || '<div style="font-size:12px; color:var(--text-muted);">Trafik kategorisi verisi yok</div>');

      // Bağlantılar tablosu
      const conns = u.connections || [];
      $('#m-conn-count').text(u.conn_count || conns.length);

      if (!conns.length) {
        $('#m-conn-body').html('<tr><td colspan="5" style="text-align:center; color:var(--text-muted);">Aktif dış bağlantı yok</td></tr>');
        return;
      }

      const connRows = conns.map(c => `
        <tr>
          <td><span class="mono">${c.dst}</span></td>
          <td><span class="pill pill-blue">${c.protocol.toUpperCase()}</span></td>
          <td><span style="font-size:10px; color:var(--text-dim);">${c.state || '—'}</span></td>
          <td style="color:var(--accent2);">${formatBytes(c.repl_bytes)}</td>
          <td style="color:var(--success);">${formatBytes(c.orig_bytes)}</td>
        </tr>
      `);
      $('#m-conn-body').html(connRows.join(''));
    });
  };

  window.closeUserModal = function () {
    $('#user-modal').fadeOut(200);
  };

  // Hız Limiti Uygula
  window.applyModalSpeedLimit = function (maxRx, maxTx) {
    if (!currentModalIp) return;
    $.getJSON('./api.php?action=set_speed_limit&ip=' + encodeURIComponent(currentModalIp) + '&max_rx=' + maxRx + '&max_tx=' + maxTx, function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        openUserModal(currentModalIp);
        if (typeof window.refreshAll === 'function') window.refreshAll();
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  // Limiti Kaldır
  window.removeModalSpeedLimit = function () {
    if (!currentModalIp) return;
    $.getJSON('./api.php?action=remove_speed_limit&ip=' + encodeURIComponent(currentModalIp), function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        openUserModal(currentModalIp);
        if (typeof window.refreshAll === 'function') window.refreshAll();
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  // Bağlantıları Kes
  window.killModalConns = function () {
    if (!currentModalIp) return;
    if (!confirm(currentModalIp + ' kullanıcısının tüm aktif bağlantılarını sıfırlamak istediğinize emin misiniz?')) return;

    $.getJSON('./api.php?action=kill_user_conns&ip=' + encodeURIComponent(currentModalIp), function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        openUserModal(currentModalIp);
        if (typeof window.refreshAll === 'function') window.refreshAll();
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  // Yorum Kaydet
  window.saveModalComment = function () {
    if (!currentModalIp) return;
    const comment = $('#m-comment-input').val().trim();
    $.getJSON('./api.php?action=update_lease_comment&ip=' + encodeURIComponent(currentModalIp) + '&comment=' + encodeURIComponent(comment), function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        $('#m-comment').text(comment || '—');
        if (typeof window.refreshAll === 'function') window.refreshAll();
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  // Hızlı Eylemler (Tablodan Doğrudan)
  window.quickSetSpeed = function (ip, maxRx, maxTx) {
    $.getJSON('./api.php?action=set_speed_limit&ip=' + encodeURIComponent(ip) + '&max_rx=' + maxRx + '&max_tx=' + maxTx, function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        if (typeof window.refreshAll === 'function') window.refreshAll();
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  // Ban / Unban İşlemleri
  window.banIp = function (ip) {
    if (!confirm(ip + " IP'li cihazı engellemek (BAN) ve bağlantılarını kesmek istediğinize emin misiniz?")) return;
    $.getJSON('./api.php?action=ban_ip&ip=' + encodeURIComponent(ip), function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        if (currentModalIp === ip) openUserModal(ip);
        if (typeof window.refreshAll === 'function') window.refreshAll();
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  window.unbanIp = function (ip) {
    $.getJSON('./api.php?action=unban_ip&ip=' + encodeURIComponent(ip), function (res) {
      if (res.success) {
        showToast(res.message, 'success');
        if (currentModalIp === ip) openUserModal(ip);
        if (typeof window.refreshAll === 'function') window.refreshAll();
      } else {
        showToast('Hata: ' + (res.error || ''), 'error');
      }
    });
  };

  // CSV Dışa Aktarma
  window.exportUsersCSV = function (tableId = '#top-users-table') {
    let csv = [];
    const rows = document.querySelectorAll(tableId + ' tr');

    for (let i = 0; i < rows.length; i++) {
      let row = [], cols = rows[i].querySelectorAll('td, th');
      for (let j = 0; j < cols.length - 1; j++) { // Son 'İşlemler' kolonunu atla
        let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/"/g, '""');
        row.push('"' + text.trim() + '"');
      }
      csv.push(row.join(','));
    }

    const csvFile = new Blob(['\uFEFF' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const downloadLink = document.createElement('a');
    downloadLink.download = 'mikrotik_download_analizi_' + new Date().toISOString().slice(0,10) + '.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
    showToast('CSV dosyası başarıyla indirildi!', 'success');
  };

});


