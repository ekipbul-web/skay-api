<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$burc = isset($_GET['burc']) ? mb_strtolower(trim($_GET['burc']), 'UTF-8') : null;

if (!$burc) {
    echo json_encode([
        'durum' => 'hata',
        'mesaj' => 'burc parametresi gerekli. Ornek: ?burc=koc'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$BURCLAR = [
    'koc' => ['isim' => 'Koç', 'emoji' => '♈', 'tarih' => '21 Mart - 19 Nisan'],
    'boga' => ['isim' => 'Boğa', 'emoji' => '♉', 'tarih' => '20 Nisan - 20 Mayıs'],
    'ikizler' => ['isim' => 'İkizler', 'emoji' => '♊', 'tarih' => '21 Mayıs - 20 Haziran'],
    'yengec' => ['isim' => 'Yengeç', 'emoji' => '♋', 'tarih' => '21 Haziran - 22 Temmuz'],
    'aslan' => ['isim' => 'Aslan', 'emoji' => '♌', 'tarih' => '23 Temmuz - 22 Ağustos'],
    'basak' => ['isim' => 'Başak', 'emoji' => '♍', 'tarih' => '23 Ağustos - 22 Eylül'],
    'terazi' => ['isim' => 'Terazi', 'emoji' => '♎', 'tarih' => '23 Eylül - 22 Ekim'],
    'akrep' => ['isim' => 'Akrep', 'emoji' => '♏', 'tarih' => '23 Ekim - 21 Kasım'],
    'yay' => ['isim' => 'Yay', 'emoji' => '♐', 'tarih' => '22 Kasım - 21 Aralık'],
    'oglak' => ['isim' => 'Oğlak', 'emoji' => '♑', 'tarih' => '22 Aralık - 19 Ocak'],
    'kova' => ['isim' => 'Kova', 'emoji' => '♒', 'tarih' => '20 Ocak - 18 Şubat'],
    'balik' => ['isim' => 'Balık', 'emoji' => '♓', 'tarih' => '19 Şubat - 20 Mart'],
];

if (!isset($BURCLAR[$burc])) {
    echo json_encode([
        'durum' => 'hata',
        'mesaj' => 'Geçersiz burç. Kullan: koc, boga, ikizler, yengec, aslan, basak, terazi, akrep, yay, oglak, kova, balik'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$b = $BURCLAR[$burc];

function burcYorumu($burcIsim) {
    $mesaj = urlencode("$burcIsim burcu için bugünün kısa günlük burç yorumu yaz. Sadece yorumu yaz. Max 2 cümle.");
    $url = "https://text.pollinations.ai/$mesaj";
    $ctx = stream_context_create(['http' => ['timeout' => 20]]);
    $response = @file_get_contents($url, false, $ctx);
    
    return ($response && strlen($response) > 5) ? trim($response) : null;
}

$yorum = burcYorumu($b['isim']);

if ($yorum) {
    echo json_encode([
        'durum' => 'başarı',
        'tür' => 'burç yorumu',
        'imza' => [
            'anahtar' => $burc,
            'isim' => $b['isim'],
            'emoji' => $b['emoji'],
            'Tarih' => $b['tarih']
        ],
        'kategori' => 'günlük',
        'category_tr' => 'Günlük',
        'horoskop' => $yorum,
        'Tarih' => date('d.m.Y'),
        'Developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'durum' => 'hata',
        'mesaj' => 'Yorum alınamadı'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
