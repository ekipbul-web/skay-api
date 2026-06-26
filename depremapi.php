<?php
/**
 * Deprem API - Kandilli Rasathanesi
 * Kullanım: deprem.php?sehir=ankara
 *           deprem.php?sehir=istanbul&limit=10
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$sehir = isset($_GET['sehir']) ? mb_strtoupper(trim($_GET['sehir']), 'UTF-8') : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;

if (!$sehir) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'sehir parametresi gerekli'], JSON_UNESCAPED_UNICODE);
    exit;
}

function kandilliCek() {
    $url = 'http://www.koeri.boun.edu.tr/scripts/lst0.asp';
    $ctx = stream_context_create([
        'http' => ['timeout' => 15, 'header' => "User-Agent: Mozilla/5.0\r\n"]
    ]);
    return @file_get_contents($url, false, $ctx);
}

function parseDepremler($html, $sehir, $limit) {
    $depremler = [];
    $lines = explode("\n", $html);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || !ctype_digit($line[0])) continue;
        
        $parts = preg_split('/\s+/', $line);
        if (count($parts) < 9) continue;
        
        $tarih = $parts[0];
        $saat = $parts[1];
        $lat = floatval($parts[2]);
        $lng = floatval($parts[3]);
        $derinlik = floatval($parts[4]);
        $md = $parts[5];
        $ml = $parts[6];
        $mw = $parts[7];
        
        $yerParts = array_slice($parts, 8);
        $yer = implode(' ', $yerParts);
        $yer = preg_replace('/\s*(�lksel|İlksel|lksel|REVIZE|Revize)\s*$/u', '', $yer);
        $yer = trim($yer);
        
        // Şehir kontrolü
        if (!preg_match('/\(' . preg_quote($sehir, '/') . '\)/i', $yer)) continue;
        
        // Şehir çıkar
        preg_match('/\(([^)]+)\)/', $yer, $sehirMatch);
        $sehirAdi = $sehirMatch[1] ?? '';
        $baslik = trim(preg_replace('/\s*\([^)]+\)\s*$/', '', $yer));
        
        // Büyüklük
        $mag = max(
            $md !== '-.-' ? floatval($md) : 0,
            $ml !== '-.-' ? floatval($ml) : 0,
            $mw !== '-.-' ? floatval($mw) : 0
        );
        
        // Zaman damgası
        $dt = DateTime::createFromFormat('Y.m.d H:i:s', "$tarih $saat");
        $timestamp = $dt ? $dt->getTimestamp() * 1000 : 0;
        
        $depremler[] = [
            'baslik' => $baslik,
            'type' => 'İlksel',
            'tarih' => "$tarih $saat",
            'lat' => $lat,
            'lng' => $lng,
            'md' => $md,
            'ml' => $ml,
            'mw' => $mw,
            'derinlik' => $derinlik,
            'koordinatlar' => [$lng, $lat],
            'geojson' => [
                'tur' => 'Özellik',
                'geometri' => [
                    'tur' => 'Nokta',
                    'koordinatlar' => [$lng, $lat]
                ],
                'ozellikler' => [
                    'mag' => $mag,
                    'yer' => "$baslik ($sehirAdi)",
                    'zaman' => $timestamp
                ]
            ],
            'konum_ozellikleri' => '',
            'tarih_gun' => $tarih,
            'tarih_saat' => $saat,
            'zaman_damgasi' => $timestamp,
            'location_tz' => '',
            'sehir' => $sehirAdi
        ];
        
        if (count($depremler) >= $limit) break;
    }
    
    return $depremler;
}

$html = kandilliCek();
if (!$html) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Veri alınamadı'], JSON_UNESCAPED_UNICODE);
    exit;
}

$depremler = parseDepremler($html, $sehir, $limit);

echo json_encode([
    'durum' => 'başarı',
    'sehir' => mb_strtolower($sehir, 'UTF-8'),
    'toplam' => count($depremler),
    'depremler' => $depremler,
    'Developer' => '@TuncaySkay'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
