<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib/RouterosAPI.php';

$api = new RouterosAPI();
$ok  = $api->connect(MT_HOST, MT_USER, MT_PASS, MT_PORT, MT_TIMEOUT);

if ($ok) {
    $id = $api->comm('/system/identity/print');
    $res = $api->comm('/system/resource/print');
    echo "✅ BAĞLANTI BAŞARILI\n";
    echo "Router Adı : " . ($id[0]['name']       ?? '?') . "\n";
    echo "RouterOS   : " . ($res[0]['version']    ?? '?') . "\n";
    echo "Board      : " . ($res[0]['board-name'] ?? '?') . "\n";
    echo "CPU Load   : " . ($res[0]['cpu-load']   ?? '?') . "%\n";
    $api->disconnect();
} else {
    echo "❌ HATA: " . $api->error . "\n";
}
