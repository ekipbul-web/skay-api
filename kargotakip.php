<?php
/**
 * Kargo Takip API
 * Site: skayapi.rf.gd
 * Geliştirici: @TuncaySkay
 * 
 * Kullanım: skayapi.rf.gd/kargotakip.php?barkod=5196277493609
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Sonsuz çalışma süresi
set_time_limit(30);

// Barkod kontrolü
if (!isset($_GET['barkod']) || empty(trim($_GET['barkod']))) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Barkod parametresi gerekli.',
        'kullanim' => 'skayapi.rf.gd/kargotakip.php?barkod=5196277493609',
        'developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$barkod = trim($_GET['barkod']);

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
            'api' => 'yurtici',
            'data' => $yurtici,
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
        'api' => 'kargoradar',
        'data' => [
            [
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
            ]
        ],
        'developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'status' => 'success',
        'barkod' => $barkod,
        'data' => [
            [
                'durum' => 'Bilinmiyor',
                'mesaj' => 'KAYIT YOK',
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
            ]
        ],
        'developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

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
                'cikis_subesi' => ($d['DepartureCityName']??'').'/'.($d['DepartureCountyName']??''),
                'varis_subesi' => ($d['DeliveryCityName']??'').'/'.($d['DeliveryCountyName']??''),
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
