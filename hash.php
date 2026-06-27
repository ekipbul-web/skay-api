<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$hash = isset($_GET['hash']) ? trim($_GET['hash']) : null;

if (!$hash) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'hash parametresi gerekli'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Hash tipi tespit
$lengths = [32 => 'MD5', 40 => 'SHA1', 64 => 'SHA256', 128 => 'SHA512'];
$type = $lengths[strlen($hash)] ?? 'Unknown';

// MD5Decrypt API
$url = "https://md5decrypt.net/Api/api.php?hash=$hash&hash_type=" . strtolower($type) . "&email=free@free.com&code=free";
$ctx = stream_context_create(['http' => ['timeout' => 10]]);
$response = @file_get_contents($url, false, $ctx);

$solved = false;
$result = null;

if ($response && strlen($response) > 1 && !str_starts_with($response, 'ERROR') && !str_starts_with($response, 'CODE ERREUR')) {
    $solved = true;
    $result = $response;
}

// Nitrx yedek
if (!$solved) {
    $url2 = "https://www.nitrxgen.net/md5db/$hash";
    $response2 = @file_get_contents($url2, false, $ctx);
    if ($response2 && strlen($response2) > 1 && strlen($response2) < 100) {
        $solved = true;
        $result = $response2;
    }
}

echo json_encode([
    'durum' => 'başarı',
    'hash' => $hash,
    'tip' => $type,
    'cozuldu' => $solved,
    'sonuc' => $result,
    'Developer' => '@TuncaySkay'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>