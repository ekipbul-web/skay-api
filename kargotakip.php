<?php
/**
 * Kargo Takip API - TR + AZ Desteği
 * Geliştirici: @TuncaySkay
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

set_time_limit(30);

// Barkod kontrolü
if (!isset($_GET['barkod']) || empty(trim($_GET['barkod']))) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Barkod parametresi gerekli.',
        'kullanim' => '?barkod=5196277493609',
        'developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$barkod = trim($_GET['barkod']);

// Azerpoçt formatı kontrolü (RR123456789AZ, EE123456789AZ, CY123456789AZ)
if (preg_match('/^[A-Z]{2}\d{9}AZ$/i', $barkod)) {
    $azResult = azerpoctSorgula($barkod);
    echo json_encode($azResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Desteklenen firmalar
$firmalar = ['aras', 'yurtici', 'mng', 'ptt', 'surat', 'ups', 'fedex', 'dhl'];

$sonuc = null;
$bulunanFirma = null;

foreach ($firmalar as $firma) {
    $veri = kargoRadarSorgula($barkod, $firma);
    if ($veri && !bosSonucMu($veri)) {
        $sonuc = $veri;
        $bulunanFirma = $firma;
        break;
    }
}

// Yurtiçi yedek
if (!$sonuc) {
    $yurtici = yurticiSorgula($barkod);
    if ($yurtici) {
        echo json_encode([
            'status' => 'success',
            'barkod' => $barkod,
            'firma' => 'Yurtiçi Kargo',
            'ulke' => 'Türkiye',
            'api' => 'yurtici',
            'data' => $yurtici,
            'developer' => '@TuncaySkay'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

// PTT direkt sorgu
if (!$sonuc) {
    $ptt = pttSorgula($barkod);
    if ($ptt) {
        echo json_encode([
            'status' => 'success',
            'barkod' => $barkod,
            'firma' => 'PTT Kargo',
            'ulke' => 'Türkiye',
            'api' => 'ptt',
            'data' => $ptt,
            'developer' => '@TuncaySkay'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

if ($sonuc) {
    echo json_encode([
        'status' => 'success',
        'barkod' => $barkod,
        'firma' => $sonuc['companyName'] ?? strtoupper($bulunanFirma),
        'ulke' => 'Türkiye',
        'api' => 'kargoradar',
        'data' => [[
            'durum' => $sonuc['statusDescription'] ?? 'Bilinmiyor',
            'mesaj' => $sonuc['statusDescription'] ?? 'KAYIT YOK',
            'teslim_edildi' => $sonuc['isDelivered'] ?? false,
            'kabul_tarihi' => $sonuc['sendDate'] ?? null,
            'teslim_tarihi' => $sonuc['deliveredDate'] ?? null,
            'cikis_subesi' => $sonuc['branchDeparture'] ?? null,
            'varis_subesi' => $sonuc['branchDelivery'] ?? null,
            'alici' => $sonuc['receiver'] ?? null,
            'gonderici' => $sonuc['sender'] ?? null,
            'takip_no' => $sonuc['trackingNumber'] ?? $barkod,
            'hareket_sayisi' => count($sonuc['movement'] ?? []),
            'hareketler' => hareketleriDuzenle($sonuc['movement'] ?? [])
        ]],
        'developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'status' => 'success',
        'barkod' => $barkod,
        'ulke' => 'Türkiye',
        'data' => [[
            'durum' => 'Bilinmiyor',
            'mesaj' => 'KAYIT YOK - Barkod hiçbir kargo firmasında bulunamadı.',
            'teslim_edildi' => false,
            'kabul_tarihi' => null,
            'teslim_tarihi' => null,
            'cikis_subesi' => null,
            'varis_subesi' => null,
            'alici' => null,
            'gonderici' => null,
            'takip_no' => $barkod,
            'hareket_sayisi' => 0,
            'hareketler' => []
        ]],
        'developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// ============ FONKSİYONLAR ============

function kargoRadarSorgula($barkod, $firma) {
    $url = "https://kargoradar.com/api/track?number=" . urlencode($barkod) . "&company=" . urlencode($firma);
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0\r\nAccept: */*\r\n",
            'timeout' => 10
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ];
    
    $response = @file_get_contents($url, false, stream_context_create($options));
    if ($response) {
        $data = json_decode($response, true);
        return $data['value'] ?? null;
    }
    return null;
}

function yurticiSorgula($barkod) {
    $url = "https://www.yurticikargo.com/service/shipmentstracking?id=" . urlencode($barkod) . "&language=tr";
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0\r\nX-Requested-With: XMLHttpRequest\r\n",
            'timeout' => 10
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ];
    
    $response = @file_get_contents($url, false, stream_context_create($options));
    if ($response) {
        $d = json_decode($response, true);
        if (isset($d['ShipmentStatus'])) {
            $t = $d['IsDelivered'] ?? false;
            return [[
                'durum' => $d['ShipmentStatus'],
                'mesaj' => $t ? 'TESLIM EDILDI' : 'TESLIMAT BEKLENIYOR',
                'teslim_edildi' => $t,
                'kabul_tarihi' => $d['ShipmentDate'] ?? null,
                'teslim_tarihi' => $d['DeliveryDate'] ?? null,
                'cikis_subesi' => ($d['DepartureCityName'] ?? '') . '/' . ($d['DepartureCountyName'] ?? ''),
                'varis_subesi' => ($d['DeliveryCityName'] ?? '') . '/' . ($d['DeliveryCountyName'] ?? ''),
                'alici' => $d['Receiver'] ?? null,
                'gonderici' => $d['Sender'] ?? null,
                'takip_no' => $barkod,
                'hareket_sayisi' => $d['ShipmentStatusCount'] ?? 0,
                'hareketler' => []
            ]];
        }
    }
    return null;
}

function pttSorgula($barkod) {
    $url = "https://gonderitakip.ptt.gov.tr/Track/Status?trackNumber=" . urlencode($barkod);
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0\r\nAccept: application/json\r\n",
            'timeout' => 10
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ];
    
    $response = @file_get_contents($url, false, stream_context_create($options));
    if ($response) {
        $d = json_decode($response, true);
        if (isset($d['sonuc']) && $d['sonuc'] === 'basari') {
            $hareketler = [];
            foreach ($d['hareketler'] ?? [] as $h) {
                $hareketler[] = [
                    'tarih' => $h['tarih'] ?? null,
                    'aciklama' => $h['durum'] ?? '',
                    'konum' => $h['sube'] ?? '',
                    'durum' => 'ptt'
                ];
            }
            $teslim = !empty($hareketler) && stripos($hareketler[0]['aciklama'], 'teslim') !== false;
            return [[
                'durum' => $teslim ? 'TESLIM EDILDI' : ($hareketler[0]['aciklama'] ?? 'Bilinmiyor'),
                'mesaj' => $hareketler[0]['aciklama'] ?? 'Bilinmiyor',
                'teslim_edildi' => $teslim,
                'kabul_tarihi' => $hareketler[count($hareketler)-1]['tarih'] ?? null,
                'teslim_tarihi' => $teslim ? $hareketler[0]['tarih'] : null,
                'cikis_subesi' => null,
                'varis_subesi' => null,
                'alici' => null,
                'gonderici' => null,
                'takip_no' => $barkod,
                'hareket_sayisi' => count($hareketler),
                'hareketler' => $hareketler
            ]];
        }
    }
    return null;
}

/**
 * Azerpoçt Kargo Takibi
 * Format: RR123456789AZ, EE123456789AZ, CY123456789AZ
 */
function azerpoctSorgula($barkod) {
    $barkodUpper = strtoupper($barkod);
    
    // Azerpoçt takip sayfasına istek
    $url = "https://www.azerpost.az/az/track-and-trace?barcode=" . urlencode($barkodUpper);
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\nAccept: text/html,application/xhtml+xml\r\nAccept-Language: az,tr,en\r\n",
            'timeout' => 10
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ];
    
    $response = @file_get_contents($url, false, stream_context_create($options));
    
    // Azerpoçt API denemesi (eğer varsa)
    $apiUrl = "https://www.azerpost.az/api/track/" . urlencode($barkodUpper);
    $apiResponse = @file_get_contents($apiUrl, false, stream_context_create([
        'http' => ['timeout' => 5],
        'ssl' => ['verify_peer' => false]
    ]));
    
    if ($apiResponse) {
        $apiData = json_decode($apiResponse, true);
        if (isset($apiData['status'])) {
            $hareketler = [];
            foreach ($apiData['events'] ?? [] as $e) {
                $hareketler[] = [
                    'tarih' => $e['date'] ?? null,
                    'aciklama' => $e['description'] ?? '',
                    'konum' => $e['location'] ?? '',
                    'durum' => $e['status'] ?? 'unknown'
                ];
            }
            
            return [
                'status' => 'success',
                'barkod' => $barkodUpper,
                'firma' => 'Azerpoçt',
                'ulke' => 'Azerbaycan',
                'api' => 'azerpoct',
                'not' => 'Azerpoçt rəsmi saytı: https://www.azerpost.az/az/track-and-trace?barcode=' . $barkodUpper,
                'data' => [[
                    'durum' => $apiData['status'] ?? 'Bilinmiyor',
                    'mesaj' => $apiData['status_description'] ?? 'Göndəriş izlənilir',
                    'teslim_edildi' => ($apiData['status'] ?? '') === 'delivered',
                    'kabul_tarihi' => $hareketler[count($hareketler)-1]['tarih'] ?? null,
                    'teslim_tarihi' => ($apiData['status'] ?? '') === 'delivered' ? ($hareketler[0]['tarih'] ?? null) : null,
                    'cikis_subesi' => $apiData['from'] ?? null,
                    'varis_subesi' => $apiData['to'] ?? null,
                    'alici' => $apiData['receiver'] ?? null,
                    'gonderici' => $apiData['sender'] ?? null,
                    'takip_no' => $barkodUpper,
                    'hareket_sayisi' => count($hareketler),
                    'hareketler' => $hareketler
                ]],
                'developer' => '@TuncaySkay'
            ];
        }
    }
    
    // API çalışmazsa manuel bilgi döndür
    return [
        'status' => 'success',
        'barkod' => $barkodUpper,
        'firma' => 'Azerpoçt',
        'ulke' => 'Azerbaycan',
        'api' => 'azerpoct',
        'not' => 'Tam izləmə üçün: https://www.azerpost.az/az/track-and-trace?barcode=' . $barkodUpper,
        'data' => [[
            'durum' => 'Sorgulanıyor',
            'mesaj' => 'Azerpoçt göndərişi aşkarlandı. Ətraflı izləmə üçün rəsmi saytı ziyarət edin.',
            'teslim_edildi' => false,
            'kabul_tarihi' => null,
            'teslim_tarihi' => null,
            'cikis_subesi' => null,
            'varis_subesi' => 'Azərbaycan',
            'alici' => null,
            'gonderici' => null,
            'takip_no' => $barkodUpper,
            'hareket_sayisi' => 0,
            'hareketler' => [[
                'tarih' => date('Y-m-d'),
                'aciklama' => 'Azerpoçt barkodu aşkarlandı (RR/EE/CY format)',
                'konum' => 'Azərbaycan',
                'durum' => 'registered'
            ]]
        ]],
        'developer' => '@TuncaySkay'
    ];
}

function bosSonucMu($veri) {
    if (!$veri || !is_array($veri)) return true;
    $msg = strtolower($veri['statusDescription'] ?? $veri['message'] ?? '');
    return (strpos($msg, 'bulunamadı') !== false || strpos($msg, 'bulunamadi') !== false);
}

function hareketleriDuzenle($movements) {
    if (!is_array($movements)) return [];
    $d = [];
    foreach ($movements as $m) {
        $d[] = [
            'tarih' => $m['date'] ?? null,
            'aciklama' => $m['description'] ?? '',
            'konum' => $m['location'] ?? '',
            'durum' => $m['statusSlug'] ?? $m['status'] ?? 'unknown'
        ];
    }
    return $d;
}
?>
