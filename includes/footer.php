<?php
/**
 * includes/footer.php
 * Ortak HTML footer - script çağrıları için
 */
?>
<!-- ── Kullanıcı Detay Modalı ── -->
<div id="user-modal" style="
  display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); backdrop-filter:blur(8px);
  z-index:99999; align-items:center; justify-content:center; padding:20px;
">
  <div style="
    background:var(--bg-card); border:1px solid var(--border); border-radius:16px;
    width:100%; max-width:800px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 50px rgba(0,0,0,0.6);
  ">
    <!-- Header -->
    <div style="padding:20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between;">
      <div style="display:flex; align-items:center; gap:12px;">
        <div style="width:42px; height:42px; background:rgba(59,130,246,0.15); color:var(--accent); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px;">
          <i class="fas fa-desktop"></i>
        </div>
        <div>
          <h2 id="modal-user-title" style="font-size:16px; font-weight:700; color:var(--text-primary);">Kullanıcı Detayı</h2>
          <div id="modal-user-sub" style="font-size:12px; color:var(--text-muted); font-family:'JetBrains Mono',monospace;">—</div>
        </div>
      </div>
      <button onclick="closeUserModal()" style="background:none; border:none; color:var(--text-muted); font-size:20px; cursor:pointer; padding:6px;">
        <i class="fas fa-xmark"></i>
      </button>
    </div>

    <!-- Body -->
    <div style="padding:20px;">
      <!-- Hızlı Eylem Butonları -->
      <div style="background:var(--bg-card2); border:1px solid var(--border); border-radius:12px; padding:16px; margin-bottom:20px;">
        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted); margin-bottom:12px;">
          ⚡ Hızlı İşlemler & Hız Limiti (Simple Queue)
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
          <div style="display:flex; align-items:center; gap:6px;">
            <span style="font-size:12px; color:var(--text-dim);">Limit Koy:</span>
            <button onclick="applyModalSpeedLimit('2M', '1M')" class="pill pill-blue" style="cursor:pointer; border:none; padding:6px 12px;">2 Mbps</button>
            <button onclick="applyModalSpeedLimit('5M', '2M')" class="pill pill-blue" style="cursor:pointer; border:none; padding:6px 12px;">5 Mbps</button>
            <button onclick="applyModalSpeedLimit('10M', '4M')" class="pill pill-blue" style="cursor:pointer; border:none; padding:6px 12px;">10 Mbps</button>
            <button onclick="applyModalSpeedLimit('20M', '5M')" class="pill pill-blue" style="cursor:pointer; border:none; padding:6px 12px;">20 Mbps</button>
            <button onclick="removeModalSpeedLimit()" class="pill pill-red" style="cursor:pointer; border:none; padding:6px 12px;"><i class="fas fa-trash-can"></i> Limiti Kaldır</button>
          </div>
          <div style="margin-left:auto; display:flex; gap:8px;">
            <button onclick="killModalConns()" class="pill pill-yellow" style="cursor:pointer; border:none; padding:6px 12px;">
              <i class="fas fa-power-off"></i> Soketleri Kes
            </button>
            <button onclick="banIp(currentModalIp)" class="pill pill-red" style="cursor:pointer; border:none; padding:6px 12px; font-weight:700;">
              <i class="fas fa-ban"></i> Cihazı Engelle (BAN)
            </button>
          </div>
        </div>
        <div id="modal-queue-status" style="margin-top:10px; font-size:12px; color:var(--accent2);"></div>
      </div>

      <!-- Bilgi Grid -->
      <div class="grid-2" style="margin-bottom:20px;">
        <!-- DHCP & Cihaz Bilgisi -->
        <div style="background:var(--bg-card2); border:1px solid var(--border); border-radius:12px; padding:16px;">
          <div style="font-size:12px; font-weight:700; color:var(--text-primary); margin-bottom:10px;"><i class="fas fa-id-card" style="color:var(--accent);"></i> Cihaz Bilgileri</div>
          <div style="font-size:12px; display:flex; flex-direction:column; gap:6px;">
            <div><span style="color:var(--text-muted);">IP:</span> <span id="m-ip" class="mono">—</span></div>
            <div><span style="color:var(--text-muted);">MAC:</span> <span id="m-mac" class="mac">—</span></div>
            <div><span style="color:var(--text-muted);">Hostname:</span> <span id="m-hostname" style="color:var(--text-primary);">—</span></div>
            <div><span style="color:var(--text-muted);">Açıklama:</span> <span id="m-comment" style="color:var(--accent2);">—</span></div>
            <div><span style="color:var(--text-muted);">Durum:</span> <span id="m-status">—</span></div>
          </div>
          <div style="margin-top:12px; display:flex; gap:6px;">
            <input type="text" id="m-comment-input" placeholder="İsim / açıklama yaz..." style="flex:1; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:6px; padding:4px 8px; font-size:12px; color:#fff;">
            <button onclick="saveModalComment()" class="pill pill-blue" style="cursor:pointer; border:none;">Kaydet</button>
          </div>
        </div>

        <!-- Trafik Türü Analizi -->
        <div style="background:var(--bg-card2); border:1px solid var(--border); border-radius:12px; padding:16px;">
          <div style="font-size:12px; font-weight:700; color:var(--text-primary); margin-bottom:10px;"><i class="fas fa-chart-pie" style="color:var(--accent2);"></i> Ne İndiriyor? (Trafik Türü)</div>
          <div id="m-categories" style="display:flex; flex-direction:column; gap:8px;"></div>
        </div>
      </div>

      <!-- Bağlantılar Tablosu -->
      <div style="background:var(--bg-card2); border:1px solid var(--border); border-radius:12px; padding:16px;">
        <div style="font-size:12px; font-weight:700; color:var(--text-primary); margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
          <span><i class="fas fa-network-wired" style="color:var(--success);"></i> Aktif Dış Bağlantılar (<span id="m-conn-count">0</span>)</span>
        </div>
        <div style="max-height:200px; overflow-y:auto;">
          <table class="data-table" style="font-size:11px;">
            <thead>
              <tr>
                <th>Hedef IP:Port</th>
                <th>Protokol</th>
                <th>Durum</th>
                <th>Download</th>
                <th>Upload</th>
              </tr>
            </thead>
            <tbody id="m-conn-body">
              <tr><td colspan="5" style="text-align:center;">Yükleniyor...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Ortak JS -->
<script src="./assets/js/app.js"></script>
<?php if (isset($extraJs)): ?>
<script><?= $extraJs ?></script>
<?php endif; ?>
</body>
</html>

