<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$sehir = isset($_GET['sehir']) ? mb_strtolower(trim($_GET['sehir']), 'UTF-8') : null;
$ulke = isset($_GET['ulke']) ? mb_strtolower(trim($_GET['ulke']), 'UTF-8') : 'tr';

if (!$sehir) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'sehir parametresi gerekli'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Azerbaycan şehirleri
$azCities = [
    'baki', 'bakü', 'baku', 'gence', 'gencə', 'sumqayit', 'sumqayıt', 'sumqayt',
    'mingecevir', 'mingəçevir', 'mingachevir', 'xirdalan', 'xırdalan',
    'siyazen', 'siyəzən', 'şirvan', 'shirvan', 'şəki', 'sheki', 'şeki',
    'yevlax', 'yevlah', 'lenkeran', 'lənkəran', 'lenkoran',
    'şamaxı', 'shamaxi', 'quba', 'guba', 'qazax', 'gazakh',
    'zengilan', 'zəngilan', 'fuzuli', 'füzuli', 'ağdam', 'agdam',
    'ağdaş', 'agdash', 'beyləqan', 'beylegan', 'berde', 'bərdə',
    'astara', 'ağsu', 'agsu', 'imisli', 'imişli', 'ismayilli', 'ismayıllı',
    'kelbecer', 'kəlbəcər', 'kürdemir', 'kürdəmir', 'laçin', 'lachin',
    'masalli', 'masallı', 'neftçala', 'neftchala', 'oğuz', 'oguz',
    'saatli', 'saatlı', 'sabirabad', 'salyani', 'salyan', 'terter', 'tərtər',
    'ucar', 'uçar', 'xaçmaz', 'khachmaz', 'xızı', 'khizi',
    'cebrayil', 'cəbrayıl', 'gubadli', 'qubadlı', 'şuşa', 'shusha',
    'hankendi', 'hankəndi', 'nahçivan', 'naxçıvan', 'nakhchivan'
];

// Otomatik ülke tespiti
if (in_array($sehir, $azCities)) {
    $ulke = 'az';
}

// Türkiye için
if ($ulke === 'tr') {
    // ============ KANDILLI ============
    $html = @file_get_contents('http://www.koeri.boun.edu.tr/scripts/lst0.asp', false, stream_context_create([
        'http' => ['timeout' => 15, 'header' => "User-Agent: Mozilla/5.0\r\n"]
    ]));

    // AFAD yedek
    if (!$html || strlen($html) < 100) {
        $afadUrl = 'https://deprem.afad.gov.tr/apiv2/event/filter?start=' . date('Y-m-d', strtotime('-7 days')) . '&end=' . date('Y-m-d');
        $html = @file_get_contents($afadUrl, false, stream_context_create([
            'http' => ['timeout' => 15],
            'ssl' => ['verify_peer' => false]
        ]));
        
        if ($html) {
            $afadData = json_decode($html, true);
            $depremler = [];
            
            if (is_array($afadData)) {
                foreach ($afadData as $d) {
                    $yer = $d['location'] ?? '';
                    if (stripos($yer, $sehir) === false) continue;
                    
                    $depremler[] = [
                        'baslik' => $yer,
                        'tarih' => $d['date'] ?? '',
                        'lat' => floatval($d['latitude'] ?? 0),
                        'lng' => floatval($d['longitude'] ?? 0),
                        'ml' => $d['magnitude'] ?? '-.-',
                        'derinlik' => floatval($d['depth'] ?? 0),
                        'sehir' => $sehir,
                        'ulke' => 'Türkiye',
                    ];
                }
            }
            
            echo json_encode([
                'durum' => 'başarı',
                'kaynak' => 'AFAD',
                'sehir' => $sehir,
                'ulke' => 'Türkiye',
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
            'ulke' => 'Türkiye',
        ];
        
        if (count($depremler) >= 10) break;
    }

    echo json_encode([
        'durum' => 'başarı',
        'kaynak' => 'Kandilli',
        'sehir' => $sehir,
        'ulke' => 'Türkiye',
        'toplam' => count($depremler),
        'depremler' => $depremler,
        'Developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ============ AZERBAYCAN DEPREMLERİ (USGS) ============
if ($ulke === 'az') {
    // AZ koordinat sınırları
    $minLat = 38.4;
    $maxLat = 42.0;
    $minLng = 44.7;
    $maxLng = 50.5;
    
    $azUrl = "https://earthquake.usgs.gov/fdsnws/event/1/query?format=geojson&minlatitude=$minLat&maxlatitude=$maxLat&minlongitude=$minLng&maxlongitude=$maxLng&limit=50&orderby=time";
    
    $azResponse = @file_get_contents($azUrl, false, stream_context_create([
        'http' => ['timeout' => 15],
        'ssl' => ['verify_peer' => false]
    ]));
    
    if (!$azResponse) {
        echo json_encode(['durum' => 'hata', 'mesaj' => 'USGS API\'ye ulaşılamadı'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $azData = json_decode($azResponse, true);
    $depremler = [];
    
    if (isset($azData['features'])) {
        // AZ şehir koordinatları (yaklaşık)
        $azCityCoords = [
            'baki' => [40.41, 49.87], 'bakü' => [40.41, 49.87], 'baku' => [40.41, 49.87],
            'gence' => [40.68, 46.36], 'gencə' => [40.68, 46.36],
            'sumqayit' => [40.59, 49.67], 'sumqayıt' => [40.59, 49.67], 'sumqayt' => [40.59, 49.67],
            'nahçivan' => [39.21, 45.41], 'naxçıvan' => [39.21, 45.41],
            'lenkeran' => [38.75, 48.85], 'lənkəran' => [38.75, 48.85],
            'şəki' => [41.19, 47.17], 'sheki' => [41.19, 47.17], 'şeki' => [41.19, 47.17],
            'yevlax' => [40.62, 47.15], 'şirvan' => [39.95, 48.93], 'shirvan' => [39.95, 48.93],
            'quba' => [41.36, 48.51], 'guba' => [41.36, 48.51],
            'qazax' => [41.09, 45.37], 'şamaxı' => [40.63, 48.64], 'shamaxi' => [40.63, 48.64],
        ];
        
        $targetLat = isset($azCityCoords[$sehir]) ? $azCityCoords[$sehir][0] : 40.41;
        $targetLng = isset($azCityCoords[$sehir]) ? $azCityCoords[$sehir][1] : 49.87;
        
        foreach ($azData['features'] as $eq) {
            $props = $eq['properties'];
            $coords = $eq['geometry']['coordinates'];
            $eqLat = $coords[1];
            $eqLng = $coords[0];
            
            // Mesafe hesapla (basit)
            $distance = sqrt(pow(($eqLat - $targetLat) * 111, 2) + pow(($eqLng - $targetLng) * 85, 2));
            
            // 200km içindekiler
            if ($distance > 200) continue;
            
            $time = new DateTime('@' . intval($props['time'] / 1000));
            $time->setTimezone(new DateTimeZone('Asia/Baku'));
            
            $depremler[] = [
                'baslik' => $props['place'] ?? 'Azerbaycan',
                'tarih' => $time->format('Y.m.d H:i:s'),
                'lat' => round($eqLat, 4),
                'lng' => round($eqLng, 4),
                'ml' => round($props['mag'], 1),
                'derinlik' => round($coords[2], 1),
                'sehir' => $sehir,
                'mesafe_km' => round($distance, 1),
                'ulke' => 'Azerbaycan',
            ];
            
            if (count($depremler) >= 15) break;
        }
    }
    
    // Büyüklüğe göre sırala
    usort($depremler, function($a, $b) {
        return $b['ml'] - $a['ml'];
    });
    
    echo json_encode([
        'durum' => 'başarı',
        'kaynak' => 'USGS',
        'sehir' => $sehir,
        'ulke' => 'Azerbaycan',
        'toplam' => count($depremler),
        'depremler' => $depremler,
        'Developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

echo json_encode(['durum' => 'hata', 'mesaj' => 'Geçersiz ülke'], JSON_UNESCAPED_UNICODE);
?>
