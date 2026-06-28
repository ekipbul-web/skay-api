<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$sehir = isset($_GET['sehir']) ? trim(mb_strtolower($_GET['sehir'], 'UTF-8')) : null;

if (!$sehir) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'sehir parametresi gerekli'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Azerbaycan şehirleri listesi
$azCities = [
    'baki', 'bakü', 'baku', 'gence', 'gencə', 'sumqayit', 'sumqayıt', 'sumqayt',
    'mingecevir', 'mingəçevir', 'mingachevir', 'xirdalan', 'xırdalan',
    'siyazen', 'siyəzən', 'şirvan', 'shirvan', 'şəki', 'sheki', 'şeki',
    'yevlax', 'yevlah', 'lenkeran', 'lənkəran', 'lenkoran',
    'şamaxı', 'shamaxi', 'şamahi', 'quba', 'guba', 'qazax', 'gazakh',
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

// Türkiye şehirleri listesi
$trCities = [
    'adana', 'adiyaman', 'afyon', 'afyonkarahisar', 'ağrı', 'agri', 'aksaray',
    'amasya', 'ankara', 'antalya', 'ardahan', 'artvin', 'aydın', 'aydin',
    'balıkesir', 'balikesir', 'bartın', 'bartin', 'batman', 'bayburt', 'bilecik',
    'bingöl', 'bingol', 'bitlis', 'bolu', 'burdur', 'bursa', 'çanakkale', 'canakkale',
    'çankırı', 'cankiri', 'çorum', 'corum', 'denizli', 'diyarbakır', 'diyarbakir',
    'düzce', 'duzce', 'edirne', 'elazığ', 'elazig', 'erzincan', 'erzurum',
    'eskişehir', 'eskisehir', 'gaziantep', 'giresun', 'gümüşhane', 'gumushane',
    'hakkari', 'hatay', 'iğdır', 'igdir', 'isparta', 'istanbul', 'izmir',
    'kahramanmaraş', 'kahramanmaras', 'karabük', 'karabuk', 'karaman', 'kars',
    'kastamonu', 'kayseri', 'kilis', 'kırıkkale', 'kirikkale', 'kırklareli', 'kirklareli',
    'kırşehir', 'kirsehir', 'kocaeli', 'konya', 'kütahya', 'kutahya', 'malatya',
    'manisa', 'mardin', 'mersin', 'muğla', 'mugla', 'muş', 'mus', 'nevşehir', 'nevsehir',
    'niğde', 'nigde', 'ordu', 'osmaniye', 'rize', 'sakarya', 'samsun', 'şanlıurfa',
    'sanliurfa', 'siirt', 'sinop', 'şırnak', 'sirnak', 'sivas', 'tekirdağ', 'tekirdag',
    'tokat', 'trabzon', 'tunceli', 'uşak', 'usak', 'van', 'yalova', 'yozgat', 'zonguldak'
];

$country = null;
$citySlug = $sehir;

if (in_array($sehir, $azCities)) {
    $country = 'az';
} elseif (in_array($sehir, $trCities)) {
    $country = 'tr';
}

if (!$country) {
    echo json_encode([
        'durum' => 'hata', 
        'mesaj' => 'Şehir bulunamadı. Lütfen geçerli bir Türkiye veya Azerbaycan şehri girin.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Azerbaycan için özel eşleştirme
$azCityMap = [
    'baki' => 'baku', 'bakü' => 'baku',
    'gence' => 'ganja', 'gencə' => 'ganja',
    'sumqayit' => 'sumqayit', 'sumqayıt' => 'sumqayit', 'sumqayt' => 'sumqayit',
    'mingecevir' => 'mingachevir', 'mingəçevir' => 'mingachevir',
    'siyazen' => 'siazan', 'siyəzən' => 'siazan',
    'şirvan' => 'shirvan', 'shirvan' => 'shirvan',
    'şəki' => 'shaki', 'sheki' => 'shaki', 'şeki' => 'shaki',
    'yevlax' => 'yevlakh', 'yevlah' => 'yevlakh',
    'lenkeran' => 'lankaran', 'lənkəran' => 'lankaran', 'lenkoran' => 'lankaran',
    'nahçivan' => 'nakhchivan', 'naxçıvan' => 'nakhchivan',
];

if ($country === 'az') {
    $citySlug = isset($azCityMap[$sehir]) ? $azCityMap[$sehir] : $sehir;
    $apiUrl = "https://api.aladhan.com/v1/timingsByCity?city=" . urlencode($citySlug) . "&country=AZ&method=4";
} else {
    $citySlug = str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'],
                            ['i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c'], $sehir);
    $apiUrl = "https://api.aladhan.com/v1/timingsByCity?city=" . urlencode($citySlug) . "&country=TR&method=13";
}

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
    'sehir' => $sehir,
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
