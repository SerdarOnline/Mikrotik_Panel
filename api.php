<?php
/**
 * api.php — AJAX endpoint
 * jQuery her 5 saniyede bu dosyayı çağırır, JSON döner.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/RouterosAPI.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/btk_signer.php';
require_once __DIR__ . '/lib/escpos.php';

// ── Yardımcı Fonksiyonlar: UTF-8, IP & MAC Vendor ───────────
if (!function_exists('cleanUtf8Recursive')) {
    function cleanUtf8Recursive($data) {
        if (is_string($data)) {
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8, ISO-8859-9, Windows-1254, ISO-8859-1');
        }
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = cleanUtf8Recursive($v);
            }
        }
        return $data;
    }
}

if (!function_exists('isPrivateIP')) {
    function isPrivateIP(string $ip): bool
    {
        $ranges = [
            ['10.0.0.0',     '10.255.255.255'],
            ['172.16.0.0',   '172.31.255.255'],
            ['192.168.0.0',  '192.168.255.255'],
            ['100.64.0.0',   '100.127.255.255'],
        ];
        $ipLong = ip2long($ip);
        if ($ipLong === false) return false;
        foreach ($ranges as [$start, $end]) {
            if ($ipLong >= ip2long($start) && $ipLong <= ip2long($end)) return true;
        }
        return false;
    }
}

if (!function_exists('getMacVendor')) {
    function getMacVendor(string $mac): string {
        if (!$mac || strlen($mac) < 8) return '';
        $clean = strtoupper(str_replace([':', '-'], '', substr($mac, 0, 8)));
        $prefix = substr($clean, 0, 6);

        // 1. Önce SQLite önbelleğine bak
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT vendor FROM mac_vendors WHERE prefix = :p");
            $stmt->execute([':p' => $prefix]);
            $cached = $stmt->fetchColumn();
            if ($cached) return $cached;
        } catch (Exception $e) {}

        // 2. Rastgele / Özel MAC Kontrolü (Locally Administered MAC - 2nd hex digit is 2, 6, A, E)
        $firstByteHex = substr($prefix, 1, 1);
        $isPrivate = in_array(strtoupper($firstByteHex), ['2', '6', 'A', 'E']);

        $vendors = [
            'F41E57' => 'MikroTik', 'D401C3' => 'MikroTik', 'E828C1' => 'MikroTik', '2CC81B' => 'MikroTik', '488EEF' => 'MikroTik', '64D154' => 'MikroTik', 'B869F4' => 'MikroTik', 'CC2DE0' => 'MikroTik',
            'C8CD55' => 'Ruijie',   'E05D54' => 'Ruijie',   '4881D4' => 'Ruijie',   'BC620E' => 'Ruijie',   '30F772' => 'Ruijie',   'A4CAA0' => 'Ruijie',
            '18E829' => 'Ubiquiti', '24A43C' => 'Ubiquiti', '002722' => 'Ubiquiti', 'DC9FDB' => 'Ubiquiti', '788A20' => 'Ubiquiti', '68D79A' => 'Ubiquiti', 'F09FC2' => 'Ubiquiti', '44D9E7' => 'Ubiquiti',
            '50EBF6' => 'Apple',    'F62AB9' => 'Apple',    'F631C1' => 'Apple',    '3A984B' => 'Apple',    'A044B7' => 'Apple',    '1C9F4E' => 'Apple',    'BA9287' => 'Apple',    '82B19B' => 'Apple', '0E3236' => 'Apple', '9CFC01' => 'Apple', 'DCA904' => 'Apple', '88665D' => 'Apple', '40A3CC' => 'Apple', 'B8E856' => 'Apple', 'A483E7' => 'Apple', 'BCA920' => 'Apple', '286ABA' => 'Apple',
            '56CA39' => 'Samsung',  '5EC1EA' => 'Samsung',  '3CCA61' => 'Samsung',  '32ABA4' => 'Samsung',  '80D21D' => 'Samsung',  'DC7144' => 'Samsung',  '9852B1' => 'Samsung',  'CC07AB' => 'Samsung', 'B479A7' => 'Samsung', '7C9122' => 'Samsung', '48D224' => 'Samsung',
            'C29AC8' => 'Xiaomi',   'CAC9D9' => 'Xiaomi',   '503DC6' => 'Xiaomi',   '56B193' => 'Xiaomi',   '42699F' => 'Xiaomi',   'E2E852' => 'Xiaomi',   '94E1AC' => 'Xiaomi',   '286C07' => 'Xiaomi', '649ABE' => 'Xiaomi',
            '4C5499' => 'Huawei',   '708A09' => 'Huawei',   '4872C0' => 'Huawei',   'E0CC7A' => 'Huawei',   '90671C' => 'Huawei',
            '74563C' => 'Intel/PC', '60CF84' => 'Intel/PC', '4851B7' => 'Intel/PC', '8086F2' => 'Intel/PC', 'A44E31' => 'Intel/PC',
            '9440C9' => 'Microsoft','00155D' => 'Microsoft',
            'FEA975' => 'Realme',   '105F02' => 'Edimax/AP','CC2D21' => 'Tenda',    '502B73' => 'Tenda',    'C83A35' => 'Tenda',
            '98DAC4' => 'TP-Link',  '50C7BF' => 'TP-Link',  '74DA38' => 'TP-Link',  'E848B8' => 'TP-Link',  'B04E26' => 'TP-Link',  'C025E9' => 'TP-Link',
            '000413' => 'PlayStation', '001D0D' => 'PlayStation', '709E29' => 'PlayStation',
            '001C62' => 'LG TV',    'A823FE' => 'LG TV',    '64995D' => 'LG TV',
        ];

        $found = $vendors[$prefix] ?? ($isPrivate ? 'Gizli MAC' : '');

        if ($found) {
            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("INSERT OR IGNORE INTO mac_vendors (prefix, vendor) VALUES (:p, :v)");
                $stmt->execute([':p' => $prefix, ':v' => $found]);
            } catch (Exception $e) {}
        }

        return $found;
    }
}

$action = $_GET['action'] ?? 'status';

$api = new RouterosAPI();
$connected = $api->connect(MT_HOST, MT_USER, MT_PASS, MT_PORT, MT_TIMEOUT);

if (!$connected) {
    echo json_encode([
        'success' => false,
        'error'   => $api->error,
        'action'  => $action
    ]);
    exit;
}

$data = ['success' => true, 'action' => $action];

try {
    switch ($action) {

        // ── Dashboard Özeti ─────────────────────────────────────
        case 'status':
            // Sistem kaynakları
            $resources = $api->comm('/system/resource/print');
            $res = $resources[0] ?? [];

            // Interface trafik özeti
            $ifaces = $api->comm('/interface/print');
            $totalRx = 0;
            $totalTx = 0;
            $ifaceList = [];
            foreach ($ifaces as $iface) {
                if (($iface['running'] ?? 'false') === 'true') {
                    $rxBytes = (int)($iface['rx-byte'] ?? 0);
                    $txBytes = (int)($iface['tx-byte'] ?? 0);
                    $totalRx += $rxBytes;
                    $totalTx += $txBytes;
                    $ifaceList[] = [
                        'name'    => $iface['name'] ?? '',
                        'type'    => $iface['type'] ?? '',
                        'rx'      => $rxBytes,
                        'tx'      => $txBytes,
                        'running' => true,
                    ];
                }
            }

            // Aktif bağlantı sayısı
            $connections = $api->comm('/ip/firewall/connection/print', ['count-only' => '']);
            $connCount = 0;
            if (!empty($connections)) {
                $raw = $connections[0] ?? [];
                // count-only farklı RouterOS sürümlerinde farklı field döner
                $connCount = (int)($raw['ret'] ?? $raw['count'] ?? count($connections));
            }

            $data['system'] = [
                'uptime'        => $res['uptime']            ?? 'N/A',
                'cpu_load'      => (int)($res['cpu-load']    ?? 0),
                'free_memory'   => (int)($res['free-memory'] ?? 0),
                'total_memory'  => (int)($res['total-memory']?? 1),
                'version'       => $res['version']           ?? 'N/A',
                'board'         => $res['board-name']        ?? 'MikroTik',
                'total_rx'      => $totalRx,
                'total_tx'      => $totalTx,
                'connections'   => $connCount,
                'interfaces'    => $ifaceList,
            ];
            break;

        // ── Kim Ne Kadar Download Yapıyor (Ana Özellik) ─────────
        case 'top_users':
            // DHCP leases (IP ↔ hostname ↔ MAC eşleştirmesi)
            $leases = $api->comm('/ip/dhcp-server/lease/print');
            $ipMap = []; // ip => ['hostname', 'mac', 'comment']
            foreach ($leases as $lease) {
                $ip = $lease['address'] ?? '';
                if ($ip) {
                    $ipMap[$ip] = [
                        'hostname' => $lease['host-name']  ?? $lease['comment'] ?? '',
                        'mac'      => $lease['mac-address'] ?? '',
                        'comment'  => $lease['comment']    ?? '',
                        'status'   => $lease['status']     ?? '',
                    ];
                }
            }

            // Firewall connection tracking — kaynak IP'lere göre byte sayısı
            $conns = $api->comm('/ip/firewall/connection/print');
            $userStats = []; // ip => [rx_bytes, tx_bytes, conn_count]

            foreach ($conns as $conn) {
                $srcAddr = $conn['src-address'] ?? '';
                if (!$srcAddr) continue;
                // src-address = "1.2.3.4:port" formatında gelebilir
                $srcIp = explode(':', $srcAddr)[0];

                // Sadece LAN IP'lerini say (private ranges)
                if (!isPrivateIP($srcIp)) continue;

                if (!isset($userStats[$srcIp])) {
                    $userStats[$srcIp] = ['rx' => 0, 'tx' => 0, 'conns' => 0, 'rx_bps' => 0, 'tx_bps' => 0];
                }
                // repl-bytes = server -> src (LAN kullanıcısının indirdiği - download)
                // orig-bytes = src -> server (LAN kullanıcısının gönderdiği - upload)
                $userStats[$srcIp]['rx']    += (int)($conn['repl-bytes'] ?? 0);
                $userStats[$srcIp]['tx']    += (int)($conn['orig-bytes'] ?? 0);
                $userStats[$srcIp]['conns'] += 1;
            }

            // Delta hesabı — anlık canlı download/upload hızı (bps)
            $userTmpFile = sys_get_temp_dir() . '/mt_users_speed_' . md5(MT_HOST) . '.json';
            $now = microtime(true);
            $prevData = file_exists($userTmpFile) ? json_decode(file_get_contents($userTmpFile), true) : null;

            $elapsed = ($prevData && isset($prevData['t'])) ? ($now - $prevData['t']) : 0;
            $prevStats = $prevData['stats'] ?? [];

            foreach ($userStats as $ip => &$stats) {
                $pRx = $prevStats[$ip]['rx'] ?? 0;
                $pTx = $prevStats[$ip]['tx'] ?? 0;

                if ($elapsed > 0.5 && $elapsed < 60) {
                    $rxDelta = max(0, $stats['rx'] - $pRx);
                    $txDelta = max(0, $stats['tx'] - $pTx);
                    $stats['rx_bps'] = (int)(($rxDelta * 8) / $elapsed);
                    $stats['tx_bps'] = (int)(($txDelta * 8) / $elapsed);
                } else {
                    $stats['rx_bps'] = 0;
                    $stats['tx_bps'] = 0;
                }
            }
            unset($stats);

            // Önceki ölçüm için dosyaya kaydet
            file_put_contents($userTmpFile, json_encode([
                't' => $now,
                'stats' => array_map(fn($s) => ['rx' => $s['rx'], 'tx' => $s['tx']], $userStats)
            ]));

            // Anlık Canlı Download hızına göre (rx_bps), eşitse toplam indirmeye göre sırala
            uasort($userStats, function($a, $b) {
                if ($a['rx_bps'] !== $b['rx_bps']) {
                    return $b['rx_bps'] <=> $a['rx_bps'];
                }
                return $b['rx'] <=> $a['rx'];
            });

            // Simple Queue haritası (aktik kısıtlamaları al)
            $queues = $api->comm('/queue/simple/print');
            $queueMap = [];
            foreach ($queues as $q) {
                $target = $q['target'] ?? '';
                $qIp = trim(explode('/', explode(',', $target)[0])[0]);
                if ($qIp) {
                    $queueMap[$qIp] = $q['max-limit'] ?? '';
                }
            }

            // Address-List (Engelli / Banlı IP haritası)
            $addressLists = $api->comm('/ip/firewall/address-list/print');
            $bannedMap = [];
            foreach ($addressLists as $al) {
                $lName = strtoupper($al['list'] ?? '');
                if (strpos($lName, 'ENGEL') !== false || strpos($lName, 'BAN') !== false || strpos($lName, 'BLOCK') !== false) {
                    $bannedMap[$al['address'] ?? ''] = true;
                }
            }

            $topUsers = [];
            $i = 0;
            foreach ($userStats as $ip => $stats) {
                if ($i >= TOP_USER_COUNT) break;
                $info = $ipMap[$ip] ?? ['hostname' => '', 'mac' => '', 'comment' => '', 'status' => ''];
                $label = $info['hostname'] ?: $info['mac'] ?: $ip;
                $topUsers[] = [
                    'ip'       => $ip,
                    'label'    => $label,
                    'hostname' => $info['hostname'],
                    'mac'      => $info['mac'],
                    'vendor'   => getMacVendor($info['mac']),
                    'comment'  => $info['comment'],
                    'rx'       => $stats['rx'],      // toplam download byte
                    'tx'       => $stats['tx'],      // toplam upload byte
                    'rx_bps'   => $stats['rx_bps'],  // ANLIK CANLI DOWNLOAD HIZI (bps)
                    'tx_bps'   => $stats['tx_bps'],  // ANLIK CANLI UPLOAD HIZI (bps)
                    'conns'    => $stats['conns'],
                    'status'   => $info['status'],
                    'limit'    => $queueMap[$ip]  ?? null, // Aktif hız kısıtlaması
                    'banned'   => isset($bannedMap[$ip]),  // Engelli mi?
                ];
                $i++;
            }

            $data['users'] = $topUsers;
            $data['total_tracked'] = count($userStats);
            break;


        // ── Interface Anlık Trafik — Delta Hesabı + Geçmiş ───────
        case 'traffic':
            $ifaceName = $_GET['iface'] ?? '1-WAN1';

            // RouterOS 7: rx-byte/tx-byte sadece stats ile geliyor
            $allIfaces = $api->comm('/interface/print', ['stats' => '']);
            $ifaceNames = [];
            $rxBytes = 0; $txBytes = 0;
            $rxPkts  = 0; $txPkts  = 0;

            foreach ($allIfaces as $iface) {
                $name = $iface['name'] ?? '';
                if ($name) $ifaceNames[] = $name;
                if ($name === $ifaceName) {
                    $rxBytes = (int)($iface['rx-byte']   ?? 0);
                    $txBytes = (int)($iface['tx-byte']   ?? 0);
                    $rxPkts  = (int)($iface['rx-packet'] ?? 0);
                    $txPkts  = (int)($iface['tx-packet'] ?? 0);
                }
            }

            // Delta hesabı için önceki değerleri oku
            $tmpFile = sys_get_temp_dir() . '/mt_iface_' . md5(MT_HOST . $ifaceName) . '.json';
            $now     = microtime(true);
            $rxBps   = 0; $txBps = 0; $rxPps = 0; $txPps = 0;

            if (file_exists($tmpFile)) {
                $prev    = json_decode(file_get_contents($tmpFile), true) ?? [];
                $elapsed = $now - ($prev['t'] ?? $now);
                if ($elapsed > 0.5 && $elapsed < 60) {
                    $rxBps = (int)(max(0, $rxBytes - ($prev['rx'] ?? 0)) * 8 / $elapsed);
                    $txBps = (int)(max(0, $txBytes - ($prev['tx'] ?? 0)) * 8 / $elapsed);
                    $rxPps = (int)(max(0, $rxPkts  - ($prev['rp'] ?? 0)) / $elapsed);
                    $txPps = (int)(max(0, $txPkts  - ($prev['tp'] ?? 0)) / $elapsed);
                }
            }

            file_put_contents($tmpFile, json_encode([
                't'  => $now,
                'rx' => $rxBytes,
                'tx' => $txBytes,
                'rp' => $rxPkts,
                'tp' => $txPkts,
            ]));

            // Trafik geçmişi kaydı (son 60 veri noktası)
            $histFile = sys_get_temp_dir() . '/mt_hist_' . md5(MT_HOST . $ifaceName) . '.json';
            $history = file_exists($histFile) ? json_decode(file_get_contents($histFile), true) : [];
            if (!is_array($history)) $history = [];

            if ($rxBps > 0 || $txBps > 0 || !empty($history)) {
                $history[] = [
                    'time' => date('H:i:s'),
                    'rx'   => (float)round($rxBps / 1000000, 3),
                    'tx'   => (float)round($txBps / 1000000, 3),
                ];
                if (count($history) > 60) {
                    $history = array_slice($history, -60);
                }
                file_put_contents($histFile, json_encode($history));
            }

            $data['traffic'] = [
                'iface'      => $ifaceName,
                'rx_bps'     => $rxBps,
                'tx_bps'     => $txBps,
                'rx_packets' => $rxPps,
                'tx_packets' => $txPps,
                'rx_total'   => $rxBytes,
                'tx_total'   => $txBytes,
                'history'    => $history,
                'timestamp'  => time(),
            ];
            $data['interfaces'] = array_values($ifaceNames);
            break;


        // ── DHCP Lease Listesi ───────────────────────────────────
        case 'dhcp_leases':
            $leases = $api->comm('/ip/dhcp-server/lease/print');
            $result = [];
            foreach ($leases as $lease) {
                $result[] = [
                    'ip'       => $lease['address']     ?? '',
                    'mac'      => $lease['mac-address'] ?? '',
                    'hostname' => $lease['host-name']   ?? '',
                    'comment'  => $lease['comment']     ?? '',
                    'status'   => $lease['status']      ?? '',
                    'expires'  => $lease['expires-after'] ?? '',
                    'server'   => $lease['server']      ?? '',
                ];
            }
            $data['leases'] = $result;
            $data['count']  = count($result);
            break;

        // ── Aktif IP Bağlantıları ────────────────────────────────
        case 'connections':
            $limit = (int)($_GET['limit'] ?? 50);
            $conns = $api->comm('/ip/firewall/connection/print');
            $result = [];
            $count = 0;
            foreach ($conns as $conn) {
                if ($count >= $limit) break;
                $result[] = [
                    'protocol'   => $conn['protocol']    ?? '',
                    'src'        => $conn['src-address'] ?? '',
                    'dst'        => $conn['dst-address'] ?? '',
                    'state'      => $conn['tcp-state']   ?? $conn['connection-state'] ?? '',
                    'orig_bytes' => (int)($conn['orig-bytes'] ?? 0),
                    'repl_bytes' => (int)($conn['repl-bytes'] ?? 0),
                    'timeout'    => $conn['timeout']     ?? '',
                ];
                $count++;
            }
            $data['connections'] = $result;
            $data['total'] = count($conns);
            break;

        // ── Firewall Kuralları ───────────────────────────────────
        case 'firewall':
            $rules = $api->comm('/ip/firewall/filter/print');
            $data['rules'] = array_map(fn($r) => [
                'chain'    => $r['chain']    ?? '',
                'action'   => $r['action']   ?? '',
                'protocol' => $r['protocol'] ?? 'any',
                'src'      => $r['src-address'] ?? '',
                'dst'      => $r['dst-address'] ?? '',
                'comment'  => $r['comment']  ?? '',
                'disabled' => ($r['disabled'] ?? 'false') === 'true',
                'bytes'    => (int)($r['bytes'] ?? 0),
                'packets'  => (int)($r['packets'] ?? 0),
            ], $rules);
            break;

        // ── Hız Sınırlama Ekle / Güncelle (Simple Queue) ─────────
        case 'set_speed_limit':
            $ip    = $_REQUEST['ip'] ?? '';
            $maxRx = $_REQUEST['max_rx'] ?? '5M';  // Download limiti
            $maxTx = $_REQUEST['max_tx'] ?? '2M';  // Upload limiti

            if (!$ip) {
                $data['success'] = false;
                $data['error']   = 'IP adresi gerekli';
                break;
            }

            // Mevcut simple queue'ları ara
            $queues = $api->comm('/queue/simple/print');
            $targetQueueId = null;
            foreach ($queues as $q) {
                $target = $q['target'] ?? '';
                if (strpos($target, $ip) !== false) {
                    $targetQueueId = $q['.id'] ?? null;
                    break;
                }
            }

            $limitStr = $maxTx . '/' . $maxRx; // RouterOS formatı: max-limit=upload/download
            $queueName = 'Limit_' . str_replace('.', '_', $ip);

            if ($targetQueueId) {
                $api->comm('/queue/simple/set', [
                    '.id'       => $targetQueueId,
                    'max-limit' => $limitStr,
                ]);
                $data['message'] = "$ip için hız limiti güncellendi: $maxRx Down / $maxTx Up";
            } else {
                $api->comm('/queue/simple/add', [
                    'name'      => $queueName,
                    'target'    => $ip,
                    'max-limit' => $limitStr,
                    'comment'   => 'Panel tarafindan kısıtlandı',
                ]);
                $data['message'] = "$ip için hız limiti oluşturuldu: $maxRx Down / $maxTx Up";
            }
            break;

        // ── Hız Sınırlamasını Kaldır ──────────────────────────────
        case 'remove_speed_limit':
            $ip = $_REQUEST['ip'] ?? '';
            if (!$ip) {
                $data['success'] = false;
                $data['error']   = 'IP adresi gerekli';
                break;
            }

            $queues = $api->comm('/queue/simple/print');
            $removed = 0;
            foreach ($queues as $q) {
                $target = $q['target'] ?? '';
                if (strpos($target, $ip) !== false && isset($q['.id'])) {
                    $api->comm('/queue/simple/remove', ['.id' => $q['.id']]);
                    $removed++;
                }
            }
            $data['message'] = $removed > 0
                ? "$ip üzerindeki tüm hız kısıtlamaları kaldırıldı"
                : "$ip için aktif hız kısıtlaması bulunamadı";
            break;

        // ── Kullanıcı Bağlantılarını Kes (Kick / Kill Conns) ────
        case 'kill_user_conns':
            $ip = $_REQUEST['ip'] ?? '';
            if (!$ip) {
                $data['success'] = false;
                $data['error']   = 'IP adresi gerekli';
                break;
            }

            $conns = $api->comm('/ip/firewall/connection/print');
            $killedCount = 0;
            foreach ($conns as $c) {
                $src = explode(':', $c['src-address'] ?? '')[0];
                if ($src === $ip && isset($c['.id'])) {
                    $api->comm('/ip/firewall/connection/remove', ['.id' => $c['.id']]);
                    $killedCount++;
                }
            }
            $data['message'] = "$ip IP'li kullanıcının $killedCount adet aktif bağlantısı sıfırlandı";
            $data['killed']  = $killedCount;
            break;

        // ── Kullanıcı Detayı ve Trafik Analizi ────────────────────
        case 'user_detail':
            $ip = $_REQUEST['ip'] ?? '';
            if (!$ip) {
                $data['success'] = false;
                $data['error']   = 'IP adresi gerekli';
                break;
            }

            // DHCP bilgisi
            $leases = $api->comm('/ip/dhcp-server/lease/print');
            $leaseInfo = null;
            foreach ($leases as $l) {
                if (($l['address'] ?? '') === $ip) {
                    $leaseInfo = [
                        'hostname' => $l['host-name']   ?? '',
                        'mac'      => $l['mac-address'] ?? '',
                        'comment'  => $l['comment']     ?? '',
                        'status'   => $l['status']      ?? '',
                        'expires'  => $l['expires-after'] ?? '',
                        'server'   => $l['server']      ?? '',
                        'id'       => $l['.id']         ?? '',
                    ];
                    break;
                }
            }

            // Simple Queue limiti
            $queues = $api->comm('/queue/simple/print');
            $queueInfo = null;
            foreach ($queues as $q) {
                if (strpos($q['target'] ?? '', $ip) !== false) {
                    $queueInfo = [
                        'name'      => $q['name']      ?? '',
                        'max_limit' => $q['max-limit'] ?? '',
                        'comment'   => $q['comment']   ?? '',
                    ];
                    break;
                }
            }

            // O IP'ye ait aktif bağlantılar ve trafik türü analizi
            $conns = $api->comm('/ip/firewall/connection/print');
            $userConns = [];
            $categories = [
                'Web (80/443)'      => 0,
                'Streaming / Video' => 0,
                'DNS (53)'          => 0,
                'Torrent / P2P'     => 0,
                'Diğer'             => 0,
            ];

            foreach ($conns as $c) {
                $src = explode(':', $c['src-address'] ?? '')[0];
                if ($src === $ip) {
                    $dst = $c['dst-address'] ?? '';
                    $dstParts = explode(':', $dst);
                    $dstIp   = $dstParts[0] ?? '';
                    $dstPort = (int)($dstParts[1] ?? 0);
                    $bytes   = (int)($c['repl-bytes'] ?? 0) + (int)($c['orig-bytes'] ?? 0);

                    // Kategori belirle
                    if ($dstPort === 53) {
                        $categories['DNS (53)'] += $bytes;
                    } elseif ($dstPort === 80 || $dstPort === 443) {
                        $categories['Web (80/443)'] += $bytes;
                    } elseif (in_array($dstPort, [1935, 3478, 3479, 3480, 8080, 8443])) {
                        $categories['Streaming / Video'] += $bytes;
                    } elseif ($dstPort > 1024 && $dstPort != 8080 && $dstPort != 8443) {
                        $categories['Torrent / P2P'] += $bytes;
                    } else {
                        $categories['Diğer'] += $bytes;
                    }

                    if (count($userConns) < 30) {
                        $userConns[] = [
                            'dst'        => $dst,
                            'protocol'   => $c['protocol']   ?? '',
                            'state'      => $c['tcp-state']  ?? $c['connection-state'] ?? '',
                            'orig_bytes' => (int)($c['orig-bytes'] ?? 0),
                            'repl_bytes' => (int)($c['repl-bytes'] ?? 0),
                        ];
                    }
                }
            }

            $data['user'] = [
                'ip'          => $ip,
                'lease'       => $leaseInfo,
                'queue'       => $queueInfo,
                'connections' => $userConns,
                'categories'  => $categories,
                'conn_count'  => count($userConns),
            ];
            break;

        // ── Lease Yorum / İsim Güncelle ─────────────────────────
        case 'update_lease_comment':
            $ip      = $_REQUEST['ip'] ?? '';
            $comment = $_REQUEST['comment'] ?? '';

            if (!$ip) {
                $data['success'] = false;
                $data['error']   = 'IP adresi gerekli';
                break;
            }

            $leases = $api->comm('/ip/dhcp-server/lease/print');
            $foundId = null;
            foreach ($leases as $l) {
                if (($l['address'] ?? '') === $ip) {
                    $foundId = $l['.id'] ?? null;
                    break;
                }
            }

            if ($foundId) {
                $api->comm('/ip/dhcp-server/lease/set', [
                    '.id'     => $foundId,
                    'comment' => $comment,
                ]);
                $data['message'] = "$ip için cihaz ismi/açıklaması güncellendi";
            } else {
                $data['success'] = false;
                $data['error']   = "DHCP üzerinde $ip IP'li cihaz bulunamadı";
            }
            break;

        // ── IP Engelle / Banla (KESIN_ENGEL + 1k Queue + Conns Kill) ─
        case 'ban_ip':
            $ip = $_REQUEST['ip'] ?? '';
            if (!$ip) {
                $data['success'] = false;
                $data['error']   = 'IP adresi gerekli';
                break;
            }

            // 1. Address-List'e ekle (KESIN_ENGEL)
            $lists = $api->comm('/ip/firewall/address-list/print');
            $alreadyBanned = false;
            foreach ($lists as $l) {
                if (($l['address'] ?? '') === $ip && ($l['list'] ?? '') === 'KESIN_ENGEL') {
                    $alreadyBanned = true;
                    break;
                }
            }

            if (!$alreadyBanned) {
                $api->comm('/ip/firewall/address-list/add', [
                    'list'    => 'KESIN_ENGEL',
                    'address' => $ip,
                    'comment' => 'Panel tarafindan engellendi',
                ]);
            }

            // 2. Simple Queue ile hızını 1k/1k yap (Internet Sıfırla)
            $queues = $api->comm('/queue/simple/print');
            $targetQueueId = null;
            foreach ($queues as $q) {
                if (strpos($q['target'] ?? '', $ip) !== false) {
                    $targetQueueId = $q['.id'] ?? null;
                    break;
                }
            }

            if ($targetQueueId) {
                $api->comm('/queue/simple/set', [
                    '.id'       => $targetQueueId,
                    'max-limit' => '1k/1k',
                    'comment'   => 'PANEL BANLI',
                ]);
            } else {
                $api->comm('/queue/simple/add', [
                    'name'      => 'BAN_' . str_replace('.', '_', $ip),
                    'target'    => $ip,
                    'max-limit' => '1k/1k',
                    'comment'   => 'PANEL BANLI',
                ]);
            }

            // 3. Aktif soket bağlantılarını anında sıfırla
            $conns = $api->comm('/ip/firewall/connection/print');
            $killedCount = 0;
            foreach ($conns as $c) {
                $src = explode(':', $c['src-address'] ?? '')[0];
                if ($src === $ip && isset($c['.id'])) {
                    $api->comm('/ip/firewall/connection/remove', ['.id' => $c['.id']]);
                    $killedCount++;
                }
            }

            $data['message'] = "🚫 $ip IP'li cihaz tamamen ENGELLENDİ! (1k/1k Queue + Address-List + $killedCount Bağlantı Sıfırlandı)";
            break;

        // ── IP Engelini Kaldır (Unban) ───────────────────────────
        case 'unban_ip':
            $ip = $_REQUEST['ip'] ?? '';
            if (!$ip) {
                $data['success'] = false;
                $data['error']   = 'IP adresi gerekli';
                break;
            }

            // 1. Address-List'ten çıkar
            $lists = $api->comm('/ip/firewall/address-list/print');
            $unbanned = 0;
            foreach ($lists as $l) {
                if (($l['address'] ?? '') === $ip && isset($l['.id'])) {
                    $api->comm('/ip/firewall/address-list/remove', ['.id' => $l['.id']]);
                    $unbanned++;
                }
            }

            // 2. Simple Queue BAN kısıtlamasını kaldır
            $queues = $api->comm('/queue/simple/print');
            foreach ($queues as $q) {
                if (strpos($q['target'] ?? '', $ip) !== false && isset($q['.id'])) {
                    if (($q['max-limit'] ?? '') === '1k/1k' || strpos($q['name'] ?? '', 'BAN_') !== false) {
                        $api->comm('/queue/simple/remove', ['.id' => $q['.id']]);
                    }
                }
            }

            $data['message'] = "🟢 $ip IP'sinin engeli başarıyla kaldırıldı!";
            break;

        // ── Kısıtlanan / Banlanan Tüm Cihazların Listesi ──────────
        case 'restricted_users':
            // DHCP Leases
            $leases = $api->comm('/ip/dhcp-server/lease/print');
            $ipMap = [];
            foreach ($leases as $lease) {
                $ip = $lease['address'] ?? '';
                if ($ip) {
                    $ipMap[$ip] = [
                        'hostname' => $lease['host-name']  ?? $lease['comment'] ?? '',
                        'mac'      => $lease['mac-address'] ?? '',
                        'comment'  => $lease['comment']    ?? '',
                        'status'   => $lease['status']     ?? '',
                    ];
                }
            }

            // Simple Queues
            $queues = $api->comm('/queue/simple/print');
            $queueMap = [];
            foreach ($queues as $q) {
                $target = $q['target'] ?? '';
                $qIp = trim(explode('/', explode(',', $target)[0])[0]);
                if ($qIp) {
                    $queueMap[$qIp] = [
                        'name'      => $q['name']      ?? '',
                        'max_limit' => $q['max-limit'] ?? '',
                        'comment'   => $q['comment']   ?? '',
                        'id'        => $q['.id']       ?? '',
                    ];
                }
            }

            // Address-List (Bans)
            $addressLists = $api->comm('/ip/firewall/address-list/print');
            $bannedMap = [];
            foreach ($addressLists as $al) {
                $lName = strtoupper($al['list'] ?? '');
                if (strpos($lName, 'ENGEL') !== false || strpos($lName, 'BAN') !== false || strpos($lName, 'BLOCK') !== false) {
                    $bannedMap[$al['address'] ?? ''] = [
                        'list'    => $al['list']    ?? '',
                        'comment' => $al['comment'] ?? '',
                        'id'      => $al['.id']     ?? '',
                    ];
                }
            }

            // Tüm kısıtlı IP'leri birleştir
            $allRestrictedIps = array_unique(array_merge(array_keys($queueMap), array_keys($bannedMap)));
            $list = [];

            foreach ($allRestrictedIps as $ip) {
                $info  = $ipMap[$ip] ?? ['hostname' => '', 'mac' => '', 'comment' => '', 'status' => ''];
                $qInfo = $queueMap[$ip] ?? null;
                $bInfo = $bannedMap[$ip] ?? null;

                $isBan = ($bInfo !== null) || ($qInfo && ($qInfo['max_limit'] === '1k/1k' || strpos($qInfo['name'], 'BAN_') !== false));

                $list[] = [
                    'ip'          => $ip,
                    'hostname'    => $info['hostname'],
                    'mac'         => $info['mac'],
                    'comment'     => $info['comment'] ?: ($qInfo['comment'] ?? $bInfo['comment'] ?? ''),
                    'status'      => $info['status'],
                    'is_banned'   => $isBan,
                    'limit'       => $qInfo ? $qInfo['max_limit'] : null,
                    'queue_name'  => $qInfo['name'] ?? null,
                ];
            }

            // Önce banlılar, sonra hız limitliler
            usort($list, fn($a, $b) => $b['is_banned'] <=> $a['is_banned']);

            $data['users']      = $list;
            $data['total']      = count($list);
            $data['banned_cnt'] = count(array_filter($list, fn($u) => $u['is_banned']));
            $data['limit_cnt']  = count(array_filter($list, fn($u) => !$u['is_banned']));
            break;

        // ── MikroTik Sistem Logları ──────────────────────────────
        case 'logs':
            $limit = (int)($_GET['limit'] ?? 200);
            $rawLogs = $api->comm('/log/print');
            $slice = array_slice($rawLogs, -$limit);

            $result = array_map(fn($l) => [
                'time'    => $l['time']    ?? '',
                'topics'  => $l['topics']  ?? 'info',
                'message' => $l['message'] ?? '',
                'id'      => $l['.id']     ?? '',
            ], $slice);

            $data['logs']  = array_reverse($result); // En yeni en üste
            $data['total'] = count($rawLogs);
            break;

        // ── Ağ Testi: RouterOS Ping ──────────────────────────────
        case 'ping_test':
            $target = $_REQUEST['target'] ?? '8.8.8.8';
            $count  = min(10, max(1, (int)($_REQUEST['count'] ?? 4)));

            $pingRes = $api->comm('/ping', [
                'address' => $target,
                'count'   => $count,
            ]);

            $packets = [];
            $received = 0; $minRtt = 9999; $maxRtt = 0; $totalRtt = 0;

            foreach ($pingRes as $p) {
                $status = $p['status'] ?? 'OK';
                $timeMs = isset($p['time']) ? (float)preg_replace('/[^0-9.]/', '', $p['time']) : null;

                if ($status === 'OK' || $timeMs !== null) {
                    $received++;
                    if ($timeMs !== null) {
                        $minRtt   = min($minRtt, $timeMs);
                        $maxRtt   = max($maxRtt, $timeMs);
                        $totalRtt += $timeMs;
                    }
                }

                $packets[] = [
                    'host'   => $p['host']   ?? $target,
                    'status' => $status,
                    'time'   => $p['time']   ?? '—',
                    'ttl'    => $p['ttl']    ?? '—',
                    'size'   => $p['size']   ?? '56',
                ];
            }

            $lossPct = round((($count - $received) / $count) * 100);
            $avgRtt  = $received > 0 ? round($totalRtt / $received, 2) : 0;

            $data['ping'] = [
                'target'   => $target,
                'sent'     => $count,
                'received' => $received,
                'loss_pct' => $lossPct,
                'min_ms'   => $minRtt < 9999 ? $minRtt : 0,
                'max_ms'   => $maxRtt,
                'avg_ms'   => $avgRtt,
                'packets'  => $packets,
            ];
            break;

        // ── Ağ Testi: RouterOS Traceroute ────────────────────────
        case 'traceroute_test':
            $target = $_REQUEST['target'] ?? '8.8.8.8';
            $traceRes = $api->comm('/tool/traceroute', [
                'address' => $target,
                'count'   => 1,
            ]);

            $hops = [];
            foreach ($traceRes as $h) {
                $hops[] = [
                    'hop'    => $h['hop']    ?? '',
                    'address'=> $h['address']?? $h['host'] ?? '—',
                    'loss'   => $h['loss']   ?? '0%',
                    'sent'   => $h['sent']   ?? '1',
                    'last'   => $h['last']   ?? '—',
                    'avg'    => $h['avg']    ?? '—',
                    'best'   => $h['best']   ?? '—',
                    'worst'  => $h['worst']  ?? '—',
                ];
            }

            $data['traceroute'] = [
                'target' => $target,
                'hops'   => $hops,
            ];
            break;

        // ── RouterOS Konfigürasyon Yedeği (Export) ─────────────
        case 'export_config':
            $export = $api->comm('/export');
            $script = "";
            if (is_array($export)) {
                foreach ($export as $item) {
                    if (isset($item['#text'])) {
                        $script .= $item['#text'] . "\n";
                    } else {
                        foreach ($item as $k => $v) {
                            if ($k !== '.id') $script .= "$k=$v ";
                        }
                        $script .= "\n";
                    }
                }
            }
            if (empty($script)) {
                $script = "# MikroTik RouterOS Configuration Export\n# Host: " . MT_HOST . "\n# Date: " . date('Y-m-d H:i:s') . "\n";
            }
            $data['export_text'] = $script;
            break;

        // ── Hotspot Genel Durum & Kullanıcı Listesi ──────────────
        case 'hotspot_status':
            $servers  = $api->comm('/ip/hotspot/print');
            $users    = $api->comm('/ip/hotspot/user/print');
            $active   = $api->comm('/ip/hotspot/active/print');
            $profiles = $api->comm('/ip/hotspot/user/profile/print');

            $userList = array_map(fn($u) => [
                'id'         => $u['.id']        ?? '',
                'name'       => $u['name']       ?? '',
                'password'   => $u['password']   ?? '—',
                'profile'    => $u['profile']    ?? 'default',
                'mac'        => $u['mac-address']?? '—',
                'uptime'     => $u['uptime']     ?? '0s',
                'bytes_in'   => (int)($u['bytes-in']  ?? 0),
                'bytes_out'  => (int)($u['bytes-out'] ?? 0),
                'comment'    => $u['comment']    ?? '',
                'disabled'   => ($u['disabled']  ?? 'false') === 'true',
                'limit_time' => $u['limit-uptime'] ?? 'Sınırsız',
            ], $users);

            $activeList = array_map(fn($a) => [
                'id'       => $a['.id']         ?? '',
                'user'     => $a['user']        ?? '',
                'ip'       => $a['address']     ?? '',
                'mac'      => $a['mac-address'] ?? '',
                'uptime'   => $a['uptime']      ?? '',
                'bytes_in' => (int)($a['bytes-in']  ?? 0),
                'bytes_out'=> (int)($a['bytes-out'] ?? 0),
            ], $active);

            $profileList = array_map(fn($p) => [
                'id'           => $p['.id']           ?? '',
                'name'         => $p['name']         ?? '',
                'shared_users' => $p['shared-users']  ?? '1',
                'rate_limit'   => $p['rate-limit']    ?? 'Sınırsız',
            ], $profiles);

            $data['hotspot'] = [
                'servers_cnt'  => count($servers),
                'users_cnt'    => count($users),
                'active_cnt'   => count($active),
                'profiles_cnt' => count($profiles),
                'users'        => $userList,
                'active'       => $activeList,
                'profiles'     => $profileList,
                'servers'      => $servers,
            ];
            break;

        // ── Hotspot Kullanıcısı / Kupon Ekle ──────────────────────
        case 'add_hotspot_user':
            $name     = trim($_REQUEST['name'] ?? '');
            $password = trim($_REQUEST['password'] ?? '');
            $profile  = trim($_REQUEST['profile'] ?? 'default');
            $limitTime= trim($_REQUEST['limit_time'] ?? '');
            $comment  = trim($_REQUEST['comment'] ?? 'Panel tarafindan eklendi');

            if (!$name) {
                $data['success'] = false;
                $data['error']   = 'Kullanıcı adı / Kupon kodu gerekli';
                break;
            }

            $params = [
                'name'     => $name,
                'password' => $password ?: $name,
                'profile'  => $profile,
                'comment'  => $comment,
            ];

            if (!empty($limitTime)) {
                $params['limit-uptime'] = $limitTime;
            }

            $addRes = $api->comm('/ip/hotspot/user/add', $params);
            $data['message'] = "Kullanıcı / Kupon ($name) başarıyla oluşturuldu!";
            break;

        // ── Toplu Hotspot Kuponu (Voucher) Üret ───────────────────
        case 'generate_vouchers':
            $count     = min(100, max(1, (int)($_REQUEST['count'] ?? 10)));
            $length    = min(10, max(4, (int)($_REQUEST['length'] ?? 6)));
            $profile   = trim($_REQUEST['profile'] ?? 'default');
            $limitTime = trim($_REQUEST['limit_time'] ?? '');
            $prefix    = trim($_REQUEST['prefix'] ?? '');

            $generated = [];
            for ($i = 0; $i < $count; $i++) {
                // Rastgele 6 haneli kupon kodu üret
                $code = $prefix . sprintf("%0" . ($length - strlen($prefix)) . "d", mt_rand(1, pow(10, $length - strlen($prefix)) - 1));
                
                $params = [
                    'name'     => $code,
                    'password' => $code,
                    'profile'  => $profile,
                    'comment'  => 'Toplu Kupon (' . date('Y-m-d H:i') . ')',
                ];

                if (!empty($limitTime)) {
                    $params['limit-uptime'] = $limitTime;
                }

                $api->comm('/ip/hotspot/user/add', $params);
                $generated[] = [
                    'code'       => $code,
                    'profile'    => $profile,
                    'limit_time' => $limitTime ?: 'Sınırsız',
                ];
            }

            $data['message']   = "$count adet kupon başarıyla üretildi!";
            $data['vouchers']  = $generated;
            break;

        // ── Hotspot Kullanıcısı Sil ────────────────────────────────
        case 'remove_hotspot_user':
            $id = $_REQUEST['id'] ?? '';
            if (!$id) {
                $data['success'] = false;
                $data['error']   = 'Kullanıcı ID gerekli';
                break;
            }
            $api->comm('/ip/hotspot/user/remove', ['.id' => $id]);
            $data['message'] = "Hotspot kullanıcı kaydı silindi.";
            break;

        // ── Hotspot Aktif Oturumu Kes (Kick) ──────────────────────
        case 'kick_hotspot_active':
            $id = $_REQUEST['id'] ?? '';
            if (!$id) {
                $data['success'] = false;
                $data['error']   = 'Oturum ID gerekli';
                break;
            }
            $api->comm('/ip/hotspot/active/remove', ['.id' => $id]);
            $data['message'] = "Hotspot oturumu sonlandırıldı.";
            break;

        // ── Hotspot Profilleri Listele ─────────────────────────────
        case 'get_hotspot_profiles':
            $profiles = $api->comm('/ip/hotspot/user/profile/print');
            $pList = array_map(fn($p) => [
                'id'                 => $p['.id']                 ?? '',
                'name'               => $p['name']               ?? '',
                'rate_limit'         => $p['rate-limit']          ?? 'Sınırsız',
                'shared_users'       => $p['shared-users']        ?? '1',
                'session_timeout'    => $p['session-timeout']     ?? 'Yok',
                'idle_timeout'       => $p['idle-timeout']        ?? 'Yok',
                'keepalive_timeout'  => $p['keepalive-timeout']   ?? 'Yok',
                'status_autorefresh' => $p['status-autorefresh']  ?? 'Yok',
                'transparent_proxy'  => $p['transparent-proxy']  ?? 'no',
                'default'            => ($p['default']            ?? 'false') === 'true',
            ], $profiles);

            $data['profiles'] = $pList;
            $data['total']    = count($pList);
            break;

        // ── Hotspot Profil Ekle ───────────────────────────────────
        case 'add_hotspot_profile':
            $pName        = trim($_REQUEST['name'] ?? '');
            $rateLimit    = trim($_REQUEST['rate_limit'] ?? '');
            $shared       = trim($_REQUEST['shared_users'] ?? '1');
            $sessionTime  = trim($_REQUEST['session_timeout'] ?? '');
            $idleTime     = trim($_REQUEST['idle_timeout'] ?? '');
            $keepalive    = trim($_REQUEST['keepalive_timeout'] ?? '');
            $statusRefresh= trim($_REQUEST['status_autorefresh'] ?? '');

            if (!$pName) {
                $data['success'] = false;
                $data['error']   = 'Profil adı zorunludur';
                break;
            }

            $params = [
                'name'         => $pName,
                'shared-users' => $shared,
            ];
            if (!empty($rateLimit))    $params['rate-limit'] = $rateLimit;
            if (!empty($sessionTime)) $params['session-timeout'] = $sessionTime;
            if (!empty($idleTime))    $params['idle-timeout'] = $idleTime;
            if (!empty($keepalive))   $params['keepalive-timeout'] = $keepalive;
            if (!empty($statusRefresh)) $params['status-autorefresh'] = $statusRefresh;

            $api->comm('/ip/hotspot/user/profile/add', $params);
            $data['message'] = "Hotspot profili ($pName) başarıyla oluşturuldu ve RouterOS'a işlendi!";
            break;

        // ── Hotspot Profil Sil ────────────────────────────────────
        case 'remove_hotspot_profile':
            $id = $_REQUEST['id'] ?? '';
            if (!$id) {
                $data['success'] = false;
                $data['error']   = 'Profil ID gerekli';
                break;
            }
            $api->comm('/ip/hotspot/user/profile/remove', ['.id' => $id]);
            $data['message'] = "Hotspot profili RouterOS'tan silindi.";
            break;

        // ── PPP / PPPoE Genel Durum & Abone Listesi ──────────────
        case 'ppp_status':
            $secrets  = $api->comm('/ppp/secret/print');
            $active   = $api->comm('/ppp/active/print');
            $profiles = $api->comm('/ppp/profile/print');

            $secretList = array_map(fn($s) => [
                'id'         => $s['.id']         ?? '',
                'name'       => $s['name']        ?? '',
                'password'   => $s['password']    ?? '—',
                'service'    => $s['service']     ?? 'any',
                'profile'    => $s['profile']     ?? 'default',
                'local_ip'   => $s['local-address']  ?? '—',
                'remote_ip'  => $s['remote-address'] ?? '—',
                'comment'    => $s['comment']     ?? '',
                'disabled'   => ($s['disabled']   ?? 'false') === 'true',
            ], $secrets);

            $activeList = array_map(fn($a) => [
                'id'       => $a['.id']         ?? '',
                'name'     => $a['name']        ?? '',
                'service'  => $a['service']     ?? '',
                'caller_id'=> $a['caller-id']   ?? '—',
                'ip'       => $a['address']     ?? '',
                'uptime'   => $a['uptime']      ?? '',
            ], $active);

            $profileList = array_map(fn($p) => [
                'id'           => $p['.id']           ?? '',
                'name'         => $p['name']         ?? '',
                'rate_limit'   => $p['rate-limit']    ?? 'Sınırsız',
                'local_ip'     => $p['local-address']  ?? '—',
                'remote_ip'    => $p['remote-address'] ?? '—',
            ], $profiles);

            $data['ppp'] = [
                'secrets_cnt'  => count($secrets),
                'active_cnt'   => count($active),
                'profiles_cnt' => count($profiles),
                'secrets'      => $secretList,
                'active'       => $activeList,
                'profiles'     => $profileList,
            ];
            break;

        // ── PPP / PPPoE Abonesi Ekle ──────────────────────────────
        case 'add_ppp_secret':
            $name     = trim($_REQUEST['name'] ?? '');
            $password = trim($_REQUEST['password'] ?? '');
            $service  = trim($_REQUEST['service'] ?? 'pppoe');
            $profile  = trim($_REQUEST['profile'] ?? 'default');
            $remoteIp = trim($_REQUEST['remote_ip'] ?? '');
            $comment  = trim($_REQUEST['comment'] ?? 'Panel tarafindan eklendi');

            if (!$name || !$password) {
                $data['success'] = false;
                $data['error']   = 'Kullanıcı adı ve şifre gereklidir';
                break;
            }

            $params = [
                'name'     => $name,
                'password' => $password,
                'service'  => $service,
                'profile'  => $profile,
                'comment'  => $comment,
            ];
            if (!empty($remoteIp)) {
                $params['remote-address'] = $remoteIp;
            }

            $api->comm('/ppp/secret/add', $params);
            $data['message'] = "PPPoE / PPP abonesi ($name) başarıyla oluşturuldu!";
            break;

        // ── PPP Abone Kaydı Sil ──────────────────────────────────
        case 'remove_ppp_secret':
            $id = $_REQUEST['id'] ?? '';
            if (!$id) {
                $data['success'] = false;
                $data['error']   = 'ID gerekli';
                break;
            }
            $api->comm('/ppp/secret/remove', ['.id' => $id]);
            $data['message'] = "PPPoE / PPP abonesi silindi.";
            break;

        // ── PPP Aktif Oturumu Kes ─────────────────────────────────
        case 'kick_ppp_active':
            $id = $_REQUEST['id'] ?? '';
            if (!$id) {
                $data['success'] = false;
                $data['error']   = 'ID gerekli';
                break;
            }
            $api->comm('/ppp/active/remove', ['.id' => $id]);
            $data['message'] = "PPPoE aktif oturumu sonlandırıldı.";
            break;

        // ── Hotspot Satış & Gelir Raporlama ──────────────────────
        case 'get_sales_report':
            $salesFile = sys_get_temp_dir() . '/mt_hotspot_sales.json';
            $sales = [];
            if (file_exists($salesFile)) {
                $sales = json_decode(file_get_contents($salesFile), true) ?: [];
            }

            $today = date('Y-m-d');
            $thisMonth = date('Y-m');

            $totalRev = 0; $todayRev = 0; $monthRev = 0;
            foreach ($sales as $s) {
                $amt = (float)($s['price'] ?? 0);
                $totalRev += $amt;
                $dateStr = substr($s['date'] ?? '', 0, 10);
                if ($dateStr === $today) $todayRev += $amt;
                if (substr($dateStr, 0, 7) === $thisMonth) $monthRev += $amt;
            }

            $data['sales_report'] = [
                'total_revenue' => $totalRev,
                'today_revenue' => $todayRev,
                'month_revenue' => $monthRev,
                'total_count'   => count($sales),
                'sales'         => array_reverse($sales),
            ];
            break;

        // ── Hotspot Satış Kaydı Ekle ──────────────────────────────
        case 'record_sale':
            $code  = trim($_REQUEST['code'] ?? '');
            $price = (float)($_REQUEST['price'] ?? 0);
            $prof  = trim($_REQUEST['profile'] ?? 'default');

            if (!$code || $price <= 0) {
                $data['success'] = false;
                $data['error']   = 'Kupon kodu ve geçerli bir fiyat girilmelidir';
                break;
            }

            $salesFile = sys_get_temp_dir() . '/mt_hotspot_sales.json';
            $sales = [];
            if (file_exists($salesFile)) {
                $sales = json_decode(file_get_contents($salesFile), true) ?: [];
            }

            $sales[] = [
                'code'      => $code,
                'price'     => $price,
                'profile'   => $prof,
                'date'      => date('Y-m-d H:i:s'),
                'seller'    => 'admin',
            ];

            file_put_contents($salesFile, json_encode($sales, JSON_PRETTY_PRINT));
            $data['message'] = "Kupon satışı ($code — {$price} ₺) kaydedildi!";
            break;

        // ── ESC/POS Termal Fiş Komut Verisi Üret ─────────────────
        case 'get_escpos_data':
            $code    = trim($_REQUEST['code'] ?? 'KUPON-000');
            $price   = (float)($_REQUEST['price'] ?? 0);
            $profile = trim($_REQUEST['profile'] ?? 'Standard');

            $bName = Database::getSetting('business_name', BUSINESS_NAME);
            $phone = Database::getSetting('phone', BUSINESS_PHONE);
            $note  = Database::getSetting('footer_note', DEFAULT_FOOTER_NOTE);

            $rawBin  = EscPosPrinter::buildRawBinary($code, $price, $profile, $bName, $phone, $note);
            $txt58mm = EscPosPrinter::buildThermalText($code, $price, $profile, $bName, $phone, $note);

            $data['code']          = $code;
            $data['price']         = $price;
            $data['profile']       = $profile;
            $data['business_name'] = $bName;
            $data['phone']         = $phone;
            $data['note']          = $note;
            $data['escpos_text']   = $txt58mm;
            $data['escpos_base64'] = base64_encode($rawBin);
            $data['message']       = 'ESC/POS termal yazıcı verisi başarıyla oluşturuldu!';
            break;

        // ── 👥 Personel Termal Yazıcı Tanımları (Multi-Staff Printers) ──
        case 'get_staff_printers':
            $db = Database::getInstance();
            $stmt = $db->query("SELECT * FROM staff_printers ORDER BY id ASC");
            $staffList = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $data['staff_printers'] = $staffList;
            break;

        case 'save_staff_printer':
            $id    = (int)($_REQUEST['id'] ?? 0);
            $sName = trim($_REQUEST['staff_name'] ?? '');
            $pIp   = trim($_REQUEST['printer_ip'] ?? '');
            $pPort = (int)($_REQUEST['printer_port'] ?? 9100);

            if (!$sName || !$pIp) {
                $data['success'] = false;
                $data['error']   = 'Personel adı ve yazıcı IP adresi boş olamaz.';
                break;
            }

            $db = Database::getInstance();
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE staff_printers SET staff_name=?, printer_ip=?, printer_port=? WHERE id=?");
                $stmt->execute([$sName, $pIp, $pPort, $id]);
                $data['message'] = "Personel ($sName) yazıcı ayarları güncellendi!";
            } else {
                $stmt = $db->prepare("INSERT INTO staff_printers (staff_name, printer_ip, printer_port) VALUES (?, ?, ?)");
                $stmt->execute([$sName, $pIp, $pPort]);
                $data['message'] = "Yeni personel ($sName — $pIp) tanımlandı!";
            }
            break;

        case 'delete_staff_printer':
            $id = (int)($_REQUEST['id'] ?? 0);
            if ($id > 0) {
                $db = Database::getInstance();
                $stmt = $db->prepare("DELETE FROM staff_printers WHERE id=?");
                $stmt->execute([$id]);
                $data['message'] = "Personel yazıcı kaydı silindi.";
            } else {
                $data['success'] = false;
                $data['error']   = 'Geçersiz personel ID.';
            }
            break;

        // ── ⚡ 1-Tıkla Hızlı POS Satışı & Otomatik ESC/POS Fiş Basımı ─
        case 'fast_pos_sale':
            $profile   = trim($_REQUEST['profile'] ?? 'default');
            $price     = (float)($_REQUEST['price'] ?? 10.0);
            $staffName = trim($_REQUEST['staff_name'] ?? '');

            // 1. Rastgele 6 Haneli Kupon Kodu Üret
            $code = strval(rand(100000, 999999));

            // 2. RouterOS'ta Kuponu Oluştur
            $api->comm('/ip/hotspot/user/add', [
                'name'    => $code,
                'password'=> $code,
                'profile' => $profile,
                'comment' => "Hızlı POS Satışı ($profile — {$price} TL" . ($staffName ? " - $staffName" : "") . ")",
            ]);

            // 3. Satışı Kaydet
            $salesFile = sys_get_temp_dir() . '/mt_hotspot_sales.json';
            $sales = [];
            if (file_exists($salesFile)) {
                $sales = json_decode(file_get_contents($salesFile), true) ?: [];
            }
            $sales[] = [
                'code'    => $code,
                'price'   => $price,
                'profile' => $profile,
                'date'    => date('Y-m-d H:i:s'),
                'seller'  => $staffName ?: 'POS Terminal',
            ];
            file_put_contents($salesFile, json_encode($sales, JSON_PRETTY_PRINT));

            // 4. Personel Özel Yazıcısı veya Varsayılan IP'deki Termal Fiş Yazıcısına Gönder
            $printerIp   = Database::getSetting('printer_ip', PRINTER_IP);
            $printerPort = (int)Database::getSetting('printer_port', (string)PRINTER_PORT);

            if ($staffName) {
                $db = Database::getInstance();
                $stmt = $db->prepare("SELECT * FROM staff_printers WHERE staff_name = ? OR id = ? LIMIT 1");
                $stmt->execute([$staffName, (int)$staffName]);
                $sRow = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($sRow && !empty($sRow['printer_ip'])) {
                    $printerIp   = $sRow['printer_ip'];
                    $printerPort = (int)($sRow['printer_port'] ?: 9100);
                }
            }

            $bName = Database::getSetting('business_name', BUSINESS_NAME);
            $phone = Database::getSetting('phone', BUSINESS_PHONE);
            $note  = Database::getSetting('footer_note', DEFAULT_FOOTER_NOTE);

            $rawBin  = EscPosPrinter::buildRawBinary($code, $price, $profile, $bName, $phone, $note);
            $txt58mm = EscPosPrinter::buildThermalText($code, $price, $profile, $bName, $phone, $note);

            $printRes = EscPosPrinter::printToNetworkPrinter($printerIp, $printerPort, $rawBin);

            $data['code']          = $code;
            $data['price']         = $price;
            $data['profile']       = $profile;
            $data['seller']        = $staffName ?: 'POS Terminal';
            $data['printer_ip']    = $printerIp;
            $data['printer_port']  = $printerPort;
            $data['print_status']  = $printRes['success'] ? 'SUCCESS' : 'FAILED';
            $data['print_message'] = $printRes['message'] ?? ($printRes['error'] ?? '');
            $data['escpos_base64'] = base64_encode($rawBin);
            $data['message']       = "⚡ Kupon ($code) başarıyla satıldı! " . ($printRes['success'] ? "Fiş " . ($staffName ? "($staffName)" : "") . " yazıcısına ($printerIp:$printerPort) basıldı." : "Uyarı: " . $printRes['error']);
            break;

        // ── SQLite3 & Config.php Tüm Sistem Ayarları ──────────────
        case 'get_settings':
            $data['settings'] = [
                // Router Connection
                'mt_host'                => Database::getSetting('mt_host', MT_HOST),
                'mt_user'                => Database::getSetting('mt_user', MT_USER),
                'mt_pass'                => Database::getSetting('mt_pass', MT_PASS),
                'mt_port'                => (int)Database::getSetting('mt_port', (string)MT_PORT),
                'mt_timeout'             => (int)Database::getSetting('mt_timeout', (string)MT_TIMEOUT),
                // Thermal Printer Hardware
                'printer_ip'             => Database::getSetting('printer_ip', PRINTER_IP),
                'printer_port'           => (int)Database::getSetting('printer_port', (string)PRINTER_PORT),
                // Business & Hotspot
                'business_name'          => Database::getSetting('business_name', BUSINESS_NAME),
                'phone'                  => Database::getSetting('phone', BUSINESS_PHONE),
                'footer_note'            => Database::getSetting('footer_note', DEFAULT_FOOTER_NOTE),
                'hotspot_dns_name'       => Database::getSetting('hotspot_dns_name', HOTSPOT_DNS_NAME),
                'login_title'            => Database::getSetting('login_title', 'Hoş Geldiniz — İnternet Erişimi'),
                'login_welcome_msg'      => Database::getSetting('login_welcome_msg', '5651 Sayılı Kanun gereğince kimlik bilgilerinizi ve kupon kodunuzu giriniz.'),
                // 5651 Law
                'law5651_enabled'        => Database::getSetting('law5651_enabled', 'true') === 'true',
                'law5651_organization'   => Database::getSetting('law5651_organization', LAW5651_ORGANIZATION),
                // Alerts & UI
                'sound_alert'            => Database::getSetting('sound_alert', 'true') === 'true',
                'high_speed_limit_mbps'  => (float)Database::getSetting('high_speed_limit_mbps', (string)HIGH_SPEED_LIMIT_MBPS),
                'speed_unit'             => Database::getSetting('speed_unit', SPEED_UNIT),
                'theme'                  => Database::getSetting('theme', THEME),
            ];
            break;

        case 'save_settings':
            // Router Connection
            $mtHost    = trim($_REQUEST['mt_host'] ?? MT_HOST);
            $mtUser    = trim($_REQUEST['mt_user'] ?? MT_USER);
            $mtPass    = trim($_REQUEST['mt_pass'] ?? MT_PASS);
            $mtPort    = trim($_REQUEST['mt_port'] ?? (string)MT_PORT);
            $mtTimeout = trim($_REQUEST['mt_timeout'] ?? (string)MT_TIMEOUT);

            // Thermal Printer
            $pIp   = trim($_REQUEST['printer_ip'] ?? PRINTER_IP);
            $pPort = trim($_REQUEST['printer_port'] ?? (string)PRINTER_PORT);

            // Business & Hotspot
            $b     = trim($_REQUEST['business_name'] ?? BUSINESS_NAME);
            $p     = trim($_REQUEST['phone'] ?? BUSINESS_PHONE);
            $n     = trim($_REQUEST['footer_note'] ?? DEFAULT_FOOTER_NOTE);
            $dns   = trim($_REQUEST['hotspot_dns_name'] ?? HOTSPOT_DNS_NAME);
            $title = trim($_REQUEST['login_title'] ?? 'Hoş Geldiniz — İnternet Erişimi');
            $msg   = trim($_REQUEST['welcome_msg'] ?? '');

            // 5651 Law & Alerts
            $lawEn  = ($_REQUEST['law5651_enabled'] ?? 'true') === 'true' ? 'true' : 'false';
            $lawOrg = trim($_REQUEST['law5651_organization'] ?? LAW5651_ORGANIZATION);
            $snd    = ($_REQUEST['sound_alert'] ?? 'true') === 'true' ? 'true' : 'false';
            $lim    = (string)($_REQUEST['high_speed_limit_mbps'] ?? '3');
            $unit   = trim($_REQUEST['speed_unit'] ?? 'auto');
            $theme  = trim($_REQUEST['theme'] ?? 'dark');

            // SQLite'a Sakla
            Database::setSetting('mt_host', $mtHost);
            Database::setSetting('mt_user', $mtUser);
            Database::setSetting('mt_pass', $mtPass);
            Database::setSetting('mt_port', $mtPort);
            Database::setSetting('mt_timeout', $mtTimeout);

            Database::setSetting('printer_ip', $pIp);
            Database::setSetting('printer_port', $pPort);

            Database::setSetting('business_name', $b);
            Database::setSetting('phone', $p);
            Database::setSetting('footer_note', $n);
            Database::setSetting('hotspot_dns_name', $dns);
            Database::setSetting('login_title', $title);
            Database::setSetting('login_welcome_msg', $msg);

            Database::setSetting('law5651_enabled', $lawEn);
            Database::setSetting('law5651_organization', $lawOrg);
            Database::setSetting('sound_alert', $snd);
            Database::setSetting('high_speed_limit_mbps', $lim);
            Database::setSetting('speed_unit', $unit);
            Database::setSetting('theme', $theme);

            // Programatik Olarak config.php Dosyasını Güncelle
            $configFile = __DIR__ . '/config.php';
            if (file_exists($configFile) && is_writable($configFile)) {
                $cfgContent = file_get_contents($configFile);
                $cfgContent = preg_replace("/define\('MT_HOST',\s*'.*?'\);/", "define('MT_HOST',     '$mtHost');", $cfgContent);
                $cfgContent = preg_replace("/define\('MT_USER',\s*'.*?'\);/", "define('MT_USER',     '$mtUser');", $cfgContent);
                $cfgContent = preg_replace("/define\('MT_PASS',\s*'.*?'\);/", "define('MT_PASS',     '$mtPass');", $cfgContent);
                $cfgContent = preg_replace("/define\('MT_PORT',\s*\d+\);/", "define('MT_PORT',     $mtPort);", $cfgContent);
                $cfgContent = preg_replace("/define\('MT_TIMEOUT',\s*\d+\);/", "define('MT_TIMEOUT',  $mtTimeout);", $cfgContent);

                $cfgContent = preg_replace("/define\('BUSINESS_NAME',\s*'.*?'\);/", "define('BUSINESS_NAME',        '$b');", $cfgContent);
                $cfgContent = preg_replace("/define\('BUSINESS_PHONE',\s*'.*?'\);/", "define('BUSINESS_PHONE',       '$p');", $cfgContent);
                $cfgContent = preg_replace("/define\('HOTSPOT_DNS_NAME',\s*'.*?'\);/", "define('HOTSPOT_DNS_NAME',     '$dns');", $cfgContent);

                file_put_contents($configFile, $cfgContent);
            }

            $data['message'] = 'Tüm Sistem ve RouterOS Ayarları Başarıyla Kaydedildi ve config.php Dosyası Güncellendi!';
            break;

        // ── MikroTik Router'ına Konfigürasyon Gönder ──────────────
        case 'push_config_to_mikrotik':
            $dnsName = Database::getSetting('hotspot_dns_name', 'wifi.lagoon.com');
            
            // 1. Hotspot Profile DNS ismini ve html-directory ayarla
            $hsProfiles = $api->comm('/ip/hotspot/profile/print');
            $updatedProfiles = 0;
            foreach ($hsProfiles as $hsp) {
                if (isset($hsp['.id'])) {
                    $api->comm('/ip/hotspot/profile/set', [
                        '.id'      => $hsp['.id'],
                        'dns-name' => $dnsName,
                    ]);
                    $updatedProfiles++;
                }
            }

            // 2. DNS Static kaydı ekle/güncelle
            $dnsStatics = $api->comm('/ip/dns/static/print');
            $foundStatic = false;
            foreach ($dnsStatics as $ds) {
                if (($ds['name'] ?? '') === $dnsName) {
                    $foundStatic = true;
                    break;
                }
            }
            if (!$foundStatic) {
                $api->comm('/ip/dns/static/add', [
                    'name'    => $dnsName,
                    'address' => MT_HOST,
                    'comment' => 'Hotspot Login DNS',
                ]);
            }

            $data['message'] = "🚀 Konfigürasyon MikroTik Router'ına Gönderildi! ($updatedProfiles Hotspot Profili 'dns-name=$dnsName' olarak güncellendi ve DNS static kaydı eklendi)";
            break;

        // ── Hotspot Logo Yükle (Base64 Sıkıştırarak SQLite'a Sakla) ─
        case 'upload_logo':
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $tmpPath = $_FILES['logo']['tmp_name'];
                $mime    = mime_content_type($tmpPath) ?: 'image/png';
                $imgData = file_get_contents($tmpPath);
                $b64     = 'data:' . $mime . ';base64,' . base64_encode($imgData);
                
                Database::setSetting('hotspot_logo_base64', $b64);
                $data['message'] = 'Logo görseli başarıyla yüklendi ve Base64 formatında SQLite veritabanına gömüldü!';
                $data['base64']  = $b64;
            } elseif (isset($_REQUEST['base64_data'])) {
                $b64 = trim($_REQUEST['base64_data']);
                Database::setSetting('hotspot_logo_base64', $b64);
                $data['message'] = 'Logo görseli Base64 olarak kaydedildi!';
                $data['base64']  = $b64;
            } else {
                $data['success'] = false;
                $data['error']   = 'Lütfen bir resim dosyası seçin';
            }
            break;

        // ── 5651 Sayılı Kanun Uyumlu Log Kaydı ───────────────────
        case 'record_5651_log':
            $tc       = trim($_REQUEST['tc_no'] ?? '');
            $name     = trim($_REQUEST['full_name'] ?? '');
            $phone    = trim($_REQUEST['phone'] ?? '');
            $voucher  = trim($_REQUEST['voucher_code'] ?? '');
            $mac      = trim($_REQUEST['mac'] ?? ($_SERVER['REMOTE_ADDR'] ?? '00:00:00:00:00:00'));
            $ip       = trim($_REQUEST['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? '192.168.1.1'));

            if (!$name || !$voucher) {
                $data['success'] = false;
                $data['error']   = 'Ad Soyad ve Kupon Kodu zorunludur (5651 Kanun Maddesi)';
                break;
            }

            // SHA-256 Zaman Damgalı Yasal Log İmzası Üret
            $nowStr   = date('Y-m-d H:i:s');
            $rawLog   = "$nowStr|$tc|$name|$phone|$voucher|$mac|$ip";
            $sha256   = hash('sha256', $rawLog);

            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO log_5651 (tc_no, full_name, phone, voucher_code, mac_address, ip_address, login_time, sha256_hash)
                VALUES (:tc, :name, :phone, :v, :mac, :ip, :time, :sha)
            ");
            $stmt->execute([
                ':tc'    => $tc,
                ':name'  => $name,
                ':phone' => $phone,
                ':v'     => $voucher,
                ':mac'   => $mac,
                ':ip'    => $ip,
                ':time'  => $nowStr,
                ':sha'   => $sha256,
            ]);

            $data['message']     = "5651 Sayılı Kanuna uygun erişim kaydı başarıyla oluşturuldu ve SHA-256 ile imzalandı!";
            $data['sha256_hash'] = $sha256;
            break;

        case 'get_5651_logs':
            $db = Database::getConnection();
            $stmt = $db->query("SELECT * FROM log_5651 ORDER BY id DESC LIMIT 200");
            $logs = $stmt->fetchAll();
            $data['logs']  = $logs;
            $data['total'] = count($logs);
            break;

        case 'export_btk_log_package':
            $db = Database::getConnection();
            $stmt = $db->query("SELECT * FROM log_5651 ORDER BY id ASC");
            $logs = $stmt->fetchAll();

            $logContent = BtkLogSigner::generateLogContent($logs);
            $shaContent = BtkLogSigner::calculateSha256Signature($logContent);
            $dateStr    = date('Y-m-d');

            $data['log_filename'] = "IPLOG_{$dateStr}.log";
            $data['log_content']  = $logContent;
            $data['sha_filename'] = "IPLOG_{$dateStr}.log.sha256";
            $data['sha_content']  = $shaContent;
            $data['message']      = "BTK IP Log İmzalayıcı uyumlu resmi log ve imza dosyaları üretildi!";
            break;

        // ── Hotspot HTML5 Login Sayfası Üret (7 Dilli & Pasaportlu) ─
        case 'generate_hotspot_login':
            $bName = Database::getSetting('business_name', 'LAGOON HOTSPOT & WIFI');
            $phone = Database::getSetting('phone', '');
            $note  = Database::getSetting('footer_note', '5651 Sayılı Kanun Gereğince İnternet Erişimi Kayıt Altına Alınmaktadır.');
            $title = Database::getSetting('login_title', 'Hoş Geldiniz — İnternet Erişimi');
            $msg   = Database::getSetting('login_welcome_msg', 'Lütfen 5651 Sayılı Kanun gereğince kimlik bilgilerinizi ve kupon kodunuzu giriniz.');

            // Base64 Logo Al (Veritabanından veya varsayılan üretilen logodan)
            $logoBase64 = Database::getSetting('hotspot_logo_base64', '');
            if (empty($logoBase64) && file_exists(__DIR__ . '/assets/img/hotspot_logo.jpg')) {
                $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents(__DIR__ . '/assets/img/hotspot_logo.jpg'));
                Database::setSetting('hotspot_logo_base64', $logoBase64);
            }

            $logoTag = $logoBase64
                ? '<img src="' . $logoBase64 . '" style="max-width:100px; max-height:100px; border-radius:18px; margin-bottom:10px; box-shadow:0 8px 25px rgba(59,130,246,0.3); border:2px solid rgba(255,255,255,0.15);" alt="Logo">'
                : '<div class="logo">📶</div>';

            $html = <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$bName} — Hotspot Giriş / WiFi Login</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background: #0f172a; color: #fff; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 16px; }
    .card { background: rgba(30, 41, 59, 0.9); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 24px; padding: 28px 24px; width: 100%; max-width: 430px; text-align: center; box-shadow: 0 25px 50px rgba(0,0,0,0.6); }
    
    /* Lang Bar & Only Circular Flags */
    .lang-bar { display: flex; justify-content: center; gap: 10px; margin-bottom: 18px; }
    .lang-btn { background: rgba(255,255,255,0.06); border: 2px solid rgba(255,255,255,0.12); border-radius: 50%; width: 34px; height: 34px; padding: 0; cursor: pointer; transition: all 0.25s ease; display: inline-flex; align-items: center; justify-content: center; }
    .lang-btn .flag-img { width: 24px; height: 24px; border-radius: 50%; object-fit: cover; }
    .lang-btn.active { background: rgba(59,130,246,0.3); border-color: #3b82f6; transform: scale(1.15); box-shadow: 0 0 15px rgba(59,130,246,0.6); }
    .lang-btn:hover { border-color: #3b82f6; transform: scale(1.1); }

    /* Identity Tabs */
    .tab-wrap { display: flex; background: rgba(15, 23, 42, 0.6); border-radius: 12px; padding: 4px; margin-bottom: 16px; border: 1px solid rgba(255,255,255,0.1); }
    .tab-btn { flex: 1; padding: 8px; border: none; background: transparent; color: #94a3b8; font-size: 11.5px; font-weight: 700; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
    .tab-btn.active { background: #3b82f6; color: #fff; }

    .title { font-size: 20px; font-weight: 800; color: #3b82f6; margin-bottom: 4px; }
    .subtitle { font-size: 11.5px; color: #94a3b8; margin-bottom: 16px; }
    .input-group { margin-bottom: 11px; text-align: left; }
    .label { font-size: 10px; font-weight: 700; color: #cbd5e1; text-transform: uppercase; margin-bottom: 4px; display: block; letter-spacing: 0.5px; }
    .input { width: 100%; background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255, 255, 255, 0.18); border-radius: 10px; padding: 10px 13px; color: #fff; font-size: 13.5px; outline: none; transition: all 0.2s; }
    .input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3); }
    .btn { width: 100%; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border: none; border-radius: 11px; padding: 13px; color: #fff; font-size: 14.5px; font-weight: 800; cursor: pointer; margin-top: 8px; box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4); }
    .btn:active { transform: scale(0.98); }
    .footer { margin-top: 18px; font-size: 10px; color: #64748b; border-top: 1px dashed rgba(255,255,255,0.12); padding-top: 12px; line-height: 1.4; }
    .legal-box { font-size: 9.5px; color: #94a3b8; margin-top: 8px; text-align: left; display: flex; gap: 6px; align-items: flex-start; }

    /* RTL for Arabic */
    body.rtl { direction: rtl; text-align: right; }
    body.rtl .input-group, body.rtl .legal-box { text-align: right; }
  </style>
</head>
<body>
  <div class="card">
    
    <!-- 7 Multi-Language Switcher (Sadece Dairesel Bayraklar) -->
    <div class="lang-bar">
      <button onclick="setLang('tr')" class="lang-btn active" id="l-tr" title="Türkçe"><img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><circle cx='256' cy='256' r='256' fill='%23e30a17'/><path fill='%23fff' d='M236.8 256a76.8 76.8 0 1 0 128.8 56.4 96 96 0 1 1-128.8-56.4zm100-24 16.8 51.6-44-32h54.4l-44 32z'/></svg>" class="flag-img" alt="TR"></button>
      <button onclick="setLang('en')" class="lang-btn" id="l-en" title="English"><img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><circle cx='256' cy='256' r='256' fill='%23012169'/><path fill='%23fff' d='M0 0l512 512M512 0L0 512' stroke='%23fff' stroke-width='80'/><path fill='%23c8102e' d='M0 0l512 512M512 0L0 512' stroke='%23c8102e' stroke-width='40'/><path fill='%23fff' d='M256 0v512M0 256h512' stroke='%23fff' stroke-width='120'/><path fill='%23c8102e' d='M256 0v512M0 256h512' stroke='%23c8102e' stroke-width='70'/></svg>" class="flag-img" alt="EN"></button>
      <button onclick="setLang('de')" class="lang-btn" id="l-de" title="Deutsch"><img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><g clip-path='url(%23c)'><circle cx='256' cy='256' r='256' fill='%23ffce00'/><path fill='%23000' d='M0 0h512v170.7H0z'/><path fill='%23dd0000' d='M0 170.7h512v170.6H0z'/></g><clipPath id='c'><circle cx='256' cy='256' r='256'/></clipPath></svg>" class="flag-img" alt="DE"></button>
      <button onclick="setLang('fr')" class="lang-btn" id="l-fr" title="Français"><img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><g clip-path='url(%23c)'><circle cx='256' cy='256' r='256' fill='%23fff'/><path fill='%23002395' d='M0 0h170.7v512H0z'/><path fill='%23ed2939' d='M341.3 0H512v512H341.3z'/></g><clipPath id='c'><circle cx='256' cy='256' r='256'/></clipPath></svg>" class="flag-img" alt="FR"></button>
      <button onclick="setLang('it')" class="lang-btn" id="l-it" title="Italiano"><img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><g clip-path='url(%23c)'><circle cx='256' cy='256' r='256' fill='%23fff'/><path fill='%23009246' d='M0 0h170.7v512H0z'/><path fill='%23ce2b37' d='M341.3 0H512v512H341.3z'/></g><clipPath id='c'><circle cx='256' cy='256' r='256'/></clipPath></svg>" class="flag-img" alt="IT"></button>
      <button onclick="setLang('ru')" class="lang-btn" id="l-ru" title="Русский"><img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><g clip-path='url(%23c)'><circle cx='256' cy='256' r='256' fill='%23fff'/><path fill='%230039a6' d='M0 170.7h512v170.6H0z'/><path fill='%23d52b1e' d='M0 341.3h512V512H0z'/></g><clipPath id='c'><circle cx='256' cy='256' r='256'/></clipPath></svg>" class="flag-img" alt="RU"></button>
      <button onclick="setLang('ar')" class="lang-btn" id="l-ar" title="العربية"><img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><circle cx='256' cy='256' r='256' fill='%23006c35'/><path fill='%23fff' d='M140 320h232v20H140zM220 200h72v40h-72z'/></svg>" class="flag-img" alt="AR"></button>
    </div>

    {$logoTag}
    <div class="title">{$bName}</div>
    <div class="subtitle" id="t-subtitle">{$title}</div>

    <!-- Identity Type Switcher (T.C. Citizen vs Foreign Guest Passport) -->
    <div class="tab-wrap">
      <button type="button" onclick="setMode('tc')" class="tab-btn active" id="tab-tc">🇹🇷 T.C. Vatandaşı</button>
      <button type="button" onclick="setMode('pass')" class="tab-btn" id="tab-pass">🌍 Foreign Guest / Pasaport</button>
    </div>

    <!-- 5651 Sayılı Kanun Uyumlu Giriş Formu -->
    <form name="sendin" action="$(link-login-only)" method="post" onsubmit="record5651Log(event)">
      <input type="hidden" name="dst" value="$(link-orig)" />
      <input type="hidden" name="popup" value="true" />
      
      <div class="input-group">
        <label class="label" id="lbl-name">Ad Soyad (Müşteri İsim)*</label>
        <input type="text" id="full_name" name="full_name" class="input" placeholder="Örn: Ahmet Yılmaz / John Doe" required>
      </div>

      <!-- T.C. No Field -->
      <div class="input-group" id="group-tc">
        <label class="label" id="lbl-tc">T.C. Kimlik Numarası*</label>
        <input type="text" id="tc_no" name="tc_no" class="input" placeholder="11 Haneli T.C. Kimlik No" maxlength="11">
      </div>

      <!-- Passport No Field -->
      <div class="input-group" id="group-pass" style="display:none;">
        <label class="label" id="lbl-pass">Passport Number / Pasaport No*</label>
        <input type="text" id="passport_no" name="passport_no" class="input" placeholder="Passport Number">
      </div>

      <div class="input-group">
        <label class="label" id="lbl-phone">Telefon Numarası*</label>
        <input type="tel" id="phone" name="phone" class="input" placeholder="+90 5XX XXX XX XX" required>
      </div>

      <div class="input-group">
        <label class="label" id="lbl-voucher">Kupon Kodu / Voucher PIN*</label>
        <input type="text" id="username" name="username" class="input" placeholder="Kupon Kodunu Giriniz" style="letter-spacing:2px; font-family:monospace; text-align:center; font-weight:700;" required autocomplete="off">
        <input type="hidden" name="password" value="$(username)">
      </div>

      <div class="legal-box">
        <input type="checkbox" id="kvkk-check" required checked style="margin-top:2px;">
        <label for="kvkk-check" id="lbl-kvkk">
          <strong>5651 Sayılı Kanun</strong> uyarınca erişim kayıtlarımın (IP, MAC, Zaman) tutulmasını kabul ediyorum.
        </label>
      </div>

      <button type="submit" class="btn" id="btn-submit">🔒 Doğrula & İnternete Bağlan</button>
    </form>

    <div class="footer">
      <p id="t-footer1">🏛️ <strong>5651 Sayılı Kanun</strong> kapsamında tüm erişimler SHA-256 ile zaman damgalı loglanmaktadır.</p>
      <p style="margin-top:4px;">{$note}</p>
    </div>
  </div>

  <script>
    let currentMode = 'tc';
    let currentLang = 'tr';

    const dict = {
      tr: {
        subtitle: "Hoş Geldiniz — İnternet Erişimi",
        tabTc: "🇹🇷 T.C. Vatandaşı",
        tabPass: "🌍 Yabancı / Pasaport",
        name: "Ad Soyad (Müşteri İsim)*",
        tc: "T.C. Kimlik Numarası*",
        pass: "Pasaport Numarası (Passport No)*",
        phone: "Telefon Numarası*",
        voucher: "Kupon Kodu / PIN*",
        kvkk: "5651 Sayılı Kanun uyarınca erişim kayıtlarımın (IP, MAC, Zaman) tutulmasını kabul ediyorum.",
        submit: "🔒 Doğrula & İnternete Bağlan",
        footer1: "🏛️ 5651 Sayılı Kanun kapsamında tüm erişimler SHA-256 ile zaman damgalı loglanmaktadır.",
      },
      en: {
        subtitle: "Welcome — Wi-Fi Internet Access",
        tabTc: "🇹🇷 Turkish Citizen",
        tabPass: "🌍 Foreign Guest (Passport)",
        name: "Full Name*",
        tc: "T.C. ID Number*",
        pass: "Passport Number*",
        phone: "Phone Number (with Country Code)*",
        voucher: "Voucher Code / PIN*",
        kvkk: "I accept the logging of my access records (IP, MAC, Timestamp) according to Law No. 5651.",
        submit: "🔒 Verify & Connect to Internet",
        footer1: "🏛️ All Wi-Fi access logs are cryptographically signed with SHA-256 under Law No. 5651.",
      },
      de: {
        subtitle: "Willkommen — WLAN-Internetzugang",
        tabTc: "🇹🇷 Türkischer Staatsbürger",
        tabPass: "🌍 Ausländischer Gast (Reisepass)",
        name: "Vollständiger Name*",
        tc: "T.C. Identitätsnummer*",
        pass: "Reisepassnummer*",
        phone: "Telefonnummer*",
        voucher: "Gutscheincode / PIN*",
        kvkk: "Ich akzeptiere die Protokollierung meiner Zugriffsdaten (IP, MAC, Zeitstempel) gemäß Gesetz 5651.",
        submit: "🔒 Bestätigen & Verbinden",
        footer1: "🏛️ Alle WLAN-Zugriffsprotokolle sind gemäß Gesetz Nr. 5651 mit SHA-256 signiert.",
      },
      fr: {
        subtitle: "Bienvenue — Accès Internet Wi-Fi",
        tabTc: "🇹🇷 Citoyen Turc",
        tabPass: "🌍 Invité Étranger (Passeport)",
        name: "Nom Et Prénom*",
        tc: "Numéro D'Identité T.C.*",
        pass: "Numéro De Passeport*",
        phone: "Numéro De Téléphone*",
        voucher: "Code Promo / PIN*",
        kvkk: "J'accepte l'enregistrement de mes données d'accès (IP, MAC, Horodatage) selon la loi 5651.",
        submit: "🔒 Vérifier Et Se Connecter",
        footer1: "🏛️ Tous les journaux d'accès sont signés cryptographiquement en SHA-256.",
      },
      it: {
        subtitle: "Benvenuto — Accesso Internet Wi-Fi",
        tabTc: "🇹🇷 Cittadino Turco",
        tabPass: "🌍 Ospite Straniero (Passaporto)",
        name: "Nome E Cognome*",
        tc: "Numero Identificativo T.C.*",
        pass: "Numero Di Passaporto*",
        phone: "Numero Di Telefono*",
        voucher: "Codice Coupon / PIN*",
        kvkk: "Accetto la registrazione dei miei dati di accesso (IP, MAC, Timestamp) ai sensi della legge 5651.",
        submit: "🔒 Verifica E Connetti",
        footer1: "🏛️ Tutti i log di accesso sono firmati in modo crittografico con SHA-256.",
      },
      ru: {
        subtitle: "Добро пожаловать — Wi-Fi Интернет",
        tabTc: "🇹🇷 Гражданин Турции",
        tabPass: "🌍 Иностранный Гость (Паспорт)",
        name: "Имя и Фамилия*",
        tc: "Номер T.C.*",
        pass: "Номер Паспорта*",
        phone: "Номер Телефона*",
        voucher: "Код Купона / PIN*",
        kvkk: "Я согласен на регистрацию данных доступа (IP, MAC, Время) согласно Закону № 5651.",
        submit: "🔒 Подтвердить и Подключиться",
        footer1: "🏛️ Все журналы доступа зашифрованы с помощью SHA-256 согласно Закону 5651.",
      },
      ar: {
        subtitle: "مرحباً بكم — خدمة الإنترنيت الواي فاي",
        tabTc: "🇹🇷 مواطن تركي",
        tabPass: "🌍 زائر أجنبي (جواز السفر)",
        name: "الاسم واللقب الكامل*",
        tc: "رقم الهوية التركية*",
        pass: "رقم جواز السفر*",
        phone: "رقم الهاتف*",
        voucher: "رمز القسيمة / PIN*",
        kvkk: "أوافق على تسجيل بيانات الاتصال (IP, MAC, الوقت) وفقًا للقانون رقم 5651.",
        submit: "🔒 التحقق والاتصال بالإنترنت",
        footer1: "🏛️ يتم تسجيل جميع سجلات الوصول مع التوقيع المشفر SHA-256 وفقًا للقانون رقم 5651.",
      }
    };

    function setMode(mode) {
      currentMode = mode;
      document.getElementById('tab-tc').className = 'tab-btn' + (mode === 'tc' ? ' active' : '');
      document.getElementById('tab-pass').className = 'tab-btn' + (mode === 'pass' ? ' active' : '');

      if (mode === 'tc') {
        document.getElementById('group-tc').style.display = 'block';
        document.getElementById('group-pass').style.display = 'none';
        document.getElementById('tc_no').required = true;
        document.getElementById('passport_no').required = false;
      } else {
        document.getElementById('group-tc').style.display = 'none';
        document.getElementById('group-pass').style.display = 'block';
        document.getElementById('tc_no').required = false;
        document.getElementById('passport_no').required = true;
      }
    }

    function setLang(lang) {
      currentLang = lang;
      document.querySelectorAll('.lang-btn').forEach(b => b.className = 'lang-btn');
      document.getElementById('l-' + lang).className = 'lang-btn active';

      if (lang === 'ar') {
        document.body.className = 'rtl';
      } else {
        document.body.className = '';
      }

      const d = dict[lang] || dict.tr;
      document.getElementById('t-subtitle').innerText = d.subtitle;
      document.getElementById('tab-tc').innerText = d.tabTc;
      document.getElementById('tab-pass').innerText = d.tabPass;
      document.getElementById('lbl-name').innerText = d.name;
      document.getElementById('lbl-tc').innerText = d.tc;
      document.getElementById('lbl-pass').innerText = d.pass;
      document.getElementById('lbl-phone').innerText = d.phone;
      document.getElementById('lbl-voucher').innerText = d.voucher;
      document.getElementById('lbl-kvkk').innerHTML = '<strong>5651</strong> ' + d.kvkk;
      document.getElementById('btn-submit').innerText = d.submit;
      document.getElementById('t-footer1').innerHTML = d.footer1;
    }

    function record5651Log(e) {
      const name = document.getElementById('full_name').value;
      const tc = currentMode === 'tc' ? document.getElementById('tc_no').value : ("PASS:" + document.getElementById('passport_no').value);
      const phone = document.getElementById('phone').value;
      const voucher = document.getElementById('username').value;
      const mac = "$(mac)";
      const ip = "$(ip)";

      const xhr = new XMLHttpRequest();
      xhr.open("GET", "./api.php?action=record_5651_log&full_name=" + encodeURIComponent(name) + "&tc_no=" + encodeURIComponent(tc) + "&phone=" + encodeURIComponent(phone) + "&voucher_code=" + encodeURIComponent(voucher) + "&mac=" + encodeURIComponent(mac) + "&ip=" + encodeURIComponent(ip), true);
      xhr.send();
    }
  </script>
</body>
</html>
HTML;

            $loginFile = __DIR__ . '/login.html';
            file_put_contents($loginFile, $html);

            $data['login_html'] = $html;
            $data['logo_base64']= $logoBase64;
            $data['message']    = "Hotspot HTML5 Login Sayfası (login.html) Base64 gömülü logo ile üretildi!";
            break;

        // ── Firewall NAT & Port Yönlendirme (Port Forwarding) ──────
        case 'nat_rules':
            $nats = $api->comm('/ip/firewall/nat/print');
            $rules = array_map(fn($n) => [
                'id'       => $n['.id']         ?? '',
                'chain'    => $n['chain']       ?? 'dstnat',
                'action'   => $n['action']      ?? 'passthrough',
                'protocol' => $n['protocol']    ?? 'tcp',
                'src_port' => $n['src-port']    ?? 'any',
                'dst_port' => $n['dst-port']    ?? 'any',
                'to_addr'  => $n['to-addresses']?? '—',
                'to_port'  => $n['to-ports']    ?? '—',
                'comment'  => $n['comment']     ?? '',
                'disabled' => ($n['disabled']   ?? 'false') === 'true',
                'bytes'    => (int)($n['bytes']   ?? 0),
                'packets'  => (int)($n['packets'] ?? 0),
            ], $nats);
            $data['nat_rules'] = $rules;
            $data['total']     = count($nats);
            break;

        case 'add_nat_rule':
            $proto   = trim($_REQUEST['protocol'] ?? 'tcp');
            $dstPort = trim($_REQUEST['dst_port'] ?? '');
            $toAddr  = trim($_REQUEST['to_addr'] ?? '');
            $toPort  = trim($_REQUEST['to_port'] ?? '');
            $comment = trim($_REQUEST['comment'] ?? 'Panel Port Forwarding');

            if (!$dstPort || !$toAddr) {
                $data['success'] = false;
                $data['error']   = 'Dış Port (Dst-Port) ve İç IP Adresi (To-Addresses) gereklidir.';
                break;
            }

            $params = [
                'chain'        => 'dstnat',
                'action'       => 'dst-nat',
                'protocol'     => $proto,
                'dst-port'     => $dstPort,
                'to-addresses' => $toAddr,
                'comment'      => $comment,
            ];
            if (!empty($toPort)) {
                $params['to-ports'] = $toPort;
            }

            $api->comm('/ip/firewall/nat/add', $params);
            $data['message'] = "Port Yönlendirme Kuralı (Dış Port: $dstPort -> İç IP: $toAddr) başarıyla eklendi!";
            break;

        case 'remove_nat_rule':
            $id = $_REQUEST['id'] ?? '';
            if (!$id) {
                $data['success'] = false;
                $data['error']   = 'ID gerekli';
                break;
            }
            $api->comm('/ip/firewall/nat/remove', ['.id' => $id]);
            $data['message'] = "NAT kuralı silindi.";
            break;

        // ── IP Adresleri Yönetimi ─────────────────────────────────
        case 'ip_addresses':
            $addrs = $api->comm('/ip/address/print');
            $list = array_map(fn($a) => [
                'id'        => $a['.id']       ?? '',
                'address'   => $a['address']   ?? '',
                'network'   => $a['network']   ?? '',
                'interface' => $a['interface'] ?? '',
                'comment'   => $a['comment']   ?? '',
                'disabled'  => ($a['disabled'] ?? 'false') === 'true',
            ], $addrs);
            $data['addresses'] = $list;
            break;

        case 'add_ip_address':
            $addr  = trim($_REQUEST['address'] ?? '');
            $iface = trim($_REQUEST['interface'] ?? '');
            $comm  = trim($_REQUEST['comment'] ?? 'Panel IP');

            if (!$addr || !$iface) {
                $data['success'] = false;
                $data['error']   = 'IP/Subnet ve Arayüz gereklidir';
                break;
            }

            $api->comm('/ip/address/add', [
                'address'   => $addr,
                'interface' => $iface,
                'comment'   => $comm,
            ]);
            $data['message'] = "IP adresi ($addr -> $iface) atandı!";
            break;

        // ── IP Rotaları (Routing Table) ───────────────────────────
        case 'ip_routes':
            $routes = $api->comm('/ip/route/print');
            $list = array_map(fn($r) => [
                'id'       => $r['.id']         ?? '',
                'dst'      => $r['dst-address'] ?? '',
                'gateway'  => $r['gateway']     ?? '',
                'distance' => $r['distance']    ?? '1',
                'active'   => ($r['active']    ?? 'false') === 'true',
                'static'   => ($r['static']    ?? 'false') === 'true',
                'disabled' => ($r['disabled']  ?? 'false') === 'true',
            ], $routes);
            $data['routes'] = $list;
            break;

        // ── Access Point (AP) Otomatik Tespit Motoru ─────────────
        case 'access_points':
            $detectedAps = [];

            // 1. Neighbor Discovery (MNDP / CDP / LLDP)
            $neighs = $api->comm('/ip/neighbor/print');
            foreach ($neighs as $n) {
                $board    = $n['board']       ?? '';
                $platform = $n['platform']    ?? '';
                $identity = $n['identity']    ?? '';
                $ip       = $n['address']     ?? '';
                $mac      = $n['mac-address'] ?? '';
                $iface    = $n['interface']   ?? '';

                $apBrand = getMacVendor($mac);
                $isAp    = false;

                if (preg_match('/(nanostation|unifi|ruijie|reyee|tp-link|tenda|edimax|ap|wireless|mesh)/i', $board . ' ' . $platform . ' ' . $identity . ' ' . $apBrand)) {
                    $isAp = true;
                }

                if ($isAp && $ip) {
                    $detectedAps[$ip] = [
                        'name'     => $identity ?: ($board ?: 'Access Point'),
                        'ip'       => $ip,
                        'mac'      => $mac ?: '—',
                        'brand'    => $apBrand ?: ($platform ?: 'Access Point'),
                        'model'    => $board ?: 'AP Device',
                        'port'     => $iface ?: 'LAN',
                        'source'   => 'MNDP/Neighbor',
                        'status'   => 'Online',
                    ];
                }
            }

            // 2. DHCP Leases & ARP Check
            $leases = $api->comm('/ip/dhcp-server/lease/print');
            foreach ($leases as $l) {
                $ip   = $l['address']     ?? '';
                $mac  = $l['mac-address'] ?? '';
                $host = $l['host-name']   ?? '';
                $comm = $l['comment']     ?? '';

                $brand = getMacVendor($mac);
                if (in_array($brand, ['Ruijie', 'Ubiquiti', 'Edimax/AP', 'TP-Link', 'Tenda']) || preg_match('/(ap|wifi|nanostation|unifi|ruijie|reyee|extender|mesh)/i', $host . ' ' . $comm)) {
                    if (!isset($detectedAps[$ip]) && $ip) {
                        $detectedAps[$ip] = [
                            'name'   => $host ?: ($comm ?: 'Access Point'),
                            'ip'     => $ip,
                            'mac'    => $mac,
                            'brand'  => $brand ?: 'Wi-Fi AP',
                            'model'  => 'Access Point',
                            'port'   => $l['server'] ?? 'LAN',
                            'source' => 'DHCP Lease',
                            'status' => ($l['status'] ?? 'bound') === 'bound' ? 'Online' : 'Offline',
                        ];
                    }
                }
            }

            $data['access_points'] = array_values($detectedAps);
            $data['total']         = count($detectedAps);
            break;



        // ── Bağlantı Testi ───────────────────────────────────────
        case 'ping':
            $identity = $api->comm('/system/identity/print');
            $data['identity'] = $identity[0]['name'] ?? 'MikroTik';
            $data['message'] = 'Bağlantı başarılı!';
            break;

        default:
            $data['error'] = 'Bilinmeyen action: ' . htmlspecialchars($action);
            $data['success'] = false;
    }
} catch (Exception $e) {
    $data['success'] = false;
    $data['error'] = $e->getMessage();
}

$api->disconnect();
$data = cleanUtf8Recursive($data);
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);

