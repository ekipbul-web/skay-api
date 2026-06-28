<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$sehir = isset($_GET['sehir']) ? trim(mb_strtolower($_GET['sehir'], 'UTF-8')) : null;

if (!$sehir) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'sehir parametresi gerekli (il/ilçe formatında)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// il/ilçe ayır
$parts = explode('/', $sehir);
$il = trim($parts[0]);
$ilce = isset($parts[1]) ? trim($parts[1]) : '';

if (!$ilce) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Lütfen il/ilçe formatında girin. Örn: istanbul/kadikoy veya baki/nerimanov'], JSON_UNESCAPED_UNICODE);
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
    'nahçivan', 'naxçıvan', 'nakhchivan'
];

$trCities = ['adana', 'adiyaman', 'afyon', 'ankara', 'antalya', 'istanbul', 'izmir', 'bursa', 'konya', 'gaziantep',
    'trabzon', 'samsun', 'kayseri', 'mersin', 'diyarbakir', 'diyarbakır', 'erzurum', 'eskişehir', 'eskisehir',
    'malatya', 'kahramanmaraş', 'kahramanmaras', 'van', 'denizli', 'sivas', 'batman', 'elazığ', 'elazig',
    'kocaeli', 'adıyaman', 'adiyaman', 'sakarya', 'manisa', 'balıkesir', 'balikesir', 'hatay', 'muğla', 'mugla',
    'aydın', 'aydin', 'çanakkale', 'canakkale', 'tekirdağ', 'tekirdag', 'kütahya', 'kutahya', 'ordu', 'rize'];

$country = null;
if (in_array($il, $azCities)) {
    $country = 'az';
} elseif (in_array($il, $trCities)) {
    $country = 'tr';
}

if (!$country) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Şehir bulunamadı. Azerbaycan için: baki/nerimanov, Türkiye için: istanbul/kadikoy'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Koordinatları bul
$cityName = $il;
if ($country === 'az' && $ilce) {
    $cityName = $ilce . ', ' . $il . ', Azerbaijan';
} elseif ($country === 'tr' && $ilce) {
    $cityName = $ilce . ', ' . $il . ', Turkey';
}

$geoUrl = "https://nominatim.openstreetmap.org/search?q=" . urlencode($cityName) . "&format=json&limit=1";
$opts = ['http' => ['header' => "User-Agent: SkayAPI/1.0\r\n", 'timeout' => 5]];
$context = stream_context_create($opts);
$geoResponse = @file_get_contents($geoUrl, false, $context);

$lat = null;
$lng = null;

if ($geoResponse) {
    $geoData = json_decode($geoResponse, true);
    if (isset($geoData[0]['lat']) && isset($geoData[0]['lon'])) {
        $lat = $geoData[0]['lat'];
        $lng = $geoData[0]['lon'];
    }
}

if (!$lat || !$lng) {
    // Fallback: Şehir merkezi koordinatları
    if ($country === 'az') {
        $azCoords = ['baki' => [40.4093, 49.8671], 'bakü' => [40.4093, 49.8671], 'baku' => [40.4093, 49.8671],
            'gence' => [40.6828, 46.3606], 'gencə' => [40.6828, 46.3606], 'sumqayit' => [40.5897, 49.6680],
            'nahçivan' => [39.2089, 45.4122], 'naxçıvan' => [39.2089, 45.4122]];
        if (isset($azCoords[$il])) { $lat = $azCoords[$il][0]; $lng = $azCoords[$il][1]; }
    }
    if (!$lat || !$lng) {
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Konum bulunamadı'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$method = $country === 'az' ? 4 : 13;
$apiUrl = "https://api.aladhan.com/v1/timings?latitude=$lat&longitude=$lng&method=$method";
$response = @file_get_contents($apiUrl);

if (!$response) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Namaz vakitleri alınamadı'], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode($response, true);
if (!isset($data['data']['timings'])) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Geçersiz veri'], JSON_UNESCAPED_UNICODE);
    exit;
}

$timings = $data['data']['timings'];
echo json_encode([
    'durum' => 'başarı',
    'il' => $il,
    'ilce' => $ilce,
    'ulke' => $country === 'az' ? 'Azerbaycan' : 'Türkiye',
    'tarih' => $data['data']['date']['readable'] ?? '',
    'vakitler' => [
        'imsak' => $timings['Fajr'],
        'gunes' => $timings['Sunrise'],
        'ogle' => $timings['Dhuhr'],
        'ikindi' => $timings['Asr'],
        'aksam' => $timings['Maghrib'],
        'yatsi' => $timings['Isha']
    ],
    'Developer' => '@TuncaySkay'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
