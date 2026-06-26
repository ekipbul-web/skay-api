<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$sehir = isset($_GET['sehir']) ? mb_strtoupper(trim($_GET['sehir']), 'UTF-8') : null;

if (!$sehir) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'sehir parametresi gerekli'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Kandilli'yi dene - ÖNCE BU
$html = @file_get_contents('http://www.koeri.boun.edu.tr/scripts/lst0.asp', false, stream_context_create([
    'http' => ['timeout' => 15, 'header' => "User-Agent: Mozilla/5.0\r\n"]
]));

// Eğer Kandilli çalışmazsa, AFAD'ı dene
if (!$html || strlen($html) < 100) {
    // AFAD API
    $html = @file_get_contents('https://deprem.afad.gov.tr/apiv2/event/filter?start=' . date('Y-m-d', strtotime('-7 days')) . '&end=' . date('Y-m-d'), false, stream_context_create([
        'http' => ['timeout' => 15],
        'ssl' => ['verify_peer' => false]
    ]));
    
    if ($html) {
        $afadData = json_decode($html, true);
        $depremler = [];
        
        if (is_array($afadData)) {
            foreach ($afadData as $d) {
                if (stripos($d['location'] ?? '', $sehir) === false) continue;
                
                $depremler[] = [
                    'baslik' => $d['location'] ?? '',
                    'tarih' => $d['date'] ?? '',
                    'lat' => floatval($d['latitude'] ?? 0),
                    'lng' => floatval($d['longitude'] ?? 0),
                    'ml' => $d['magnitude'] ?? '-.-',
                    'derinlik' => floatval($d['depth'] ?? 0),
                    'sehir' => $sehir,
                ];
            }
        }
        
        echo json_encode([
            'durum' => 'başarı',
            'kaynak' => 'AFAD',
            'sehir' => mb_strtolower($sehir, 'UTF-8'),
            'toplam' => count($depremler),
            'depremler' => $depremler,
            'Developer' => '@TuncaySkay'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Veri kaynağına ulaşılamadı'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Kandilli verisini işle
$lines = explode("\n", $html);
$depremler = [];

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || !ctype_digit($line[0])) continue;
    
    $parts = preg_split('/\s+/', $line);
    if (count($parts) < 9) continue;
    
    $yer = implode(' ', array_slice($parts, 8));
    
    // Şehir kontrolü - büyük/küçük harf duyarsız
    if (stripos($yer, "($sehir)") === false && stripos($yer, "($sehir") === false) continue;
    
    $tarih = $parts[0];
    $saat = $parts[1];
    $lat = floatval($parts[2]);
    $lng = floatval($parts[3]);
    $derinlik = floatval($parts[4]);
    
    preg_match('/\(([^)]+)\)/', $yer, $m);
    $sehirAdi = $m[1] ?? $sehir;
    $baslik = trim(preg_replace('/\s*\([^)]+\)\s*$/', '', $yer));
    $baslik = preg_replace('/\s*(�lksel|İlksel|lksel|REVIZE)\s*$/u', '', $baslik);
    
    $depremler[] = [
        'baslik' => trim($baslik),
        'tarih' => "$tarih $saat",
        'lat' => $lat,
        'lng' => $lng,
        'ml' => $parts[6],
        'derinlik' => $derinlik,
        'sehir' => $sehirAdi,
    ];
    
    if (count($depremler) >= 10) break;
}

echo json_encode([
    'durum' => 'başarı',
    'kaynak' => 'Kandilli',
    'ham_veri_boyutu' => strlen($html),
    'sehir' => mb_strtolower($sehir, 'UTF-8'),
    'toplam' => count($depremler),
    'depremler' => $depremler,
    'Developer' => '@TuncaySkay'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
