<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$sehir = isset($_GET['sehir']) ? trim($_GET['sehir']) : null;

if (!$sehir) {
    echo json_encode([
        'status' => 'error',
        'message' => 'sehir parametresi gerekli. Ornek: ?sehir=istanbul'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Sadece il - ilçe yok
$il = trim($sehir);
$ilce = null;

// Namaz vakitleri (Aladhan API)
$url = "http://api.aladhan.com/v1/timingsByCity?city=" . urlencode($il) . "&country=Turkey&method=13";
$response = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 10]]));

if (!$response) {
    echo json_encode(['status' => 'error', 'message' => 'APIye ulasilamadi'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$data = json_decode($response, true);

if ($data['code'] != 200) {
    echo json_encode(['status' => 'error', 'message' => 'Sehir bulunamadi'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$v = $data['data']['timings'];

// Koordinat (OpenStreetMap)
$lat = null;
$lng = null;

$osm_url = "https://nominatim.openstreetmap.org/search?q=" . urlencode("$il, Turkey") . "&format=json&limit=1";
$osm_ctx = stream_context_create([
    'http' => [
        'timeout' => 5,
        'header' => "User-Agent: SKAY-API/1.0\r\n"
    ]
]);
$osm_response = @file_get_contents($osm_url, false, $osm_ctx);

if ($osm_response) {
    $osm_data = json_decode($osm_response, true);
    if ($osm_data && count($osm_data) > 0) {
        $lat = $osm_data[0]['lat'] ?? null;
        $lng = $osm_data[0]['lon'] ?? null;
    }
}

echo json_encode([
    'status' => 'success',
    'il' => ucfirst($il),
    'ilce' => null,
    'konum' => null,
    'tarih' => $data['data']['date']['readable'],
    'coordinates' => [
        'latitude' => (string)$lat,
        'longitude' => (string)$lng
    ],
    'vakitler' => [
        'imsak' => explode(' ', $v['Fajr'])[0],
        'gunes' => explode(' ', $v['Sunrise'])[0],
        'ogle' => explode(' ', $v['Dhuhr'])[0],
        'ikindi' => explode(' ', $v['Asr'])[0],
        'aksam' => explode(' ', $v['Maghrib'])[0],
        'yatsi' => explode(' ', $v['Isha'])[0],
    ],
    'developer' => '@TuncaySkay'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
