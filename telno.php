<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$phone = isset($_GET['phone']) ? trim($_GET['phone']) : null;

if (!$phone) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'phone parametresi gerekli'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Temizle - sadece rakamları al
$phone = preg_replace('/[^\d]/', '', $phone);
$originalPhone = $phone;

// Ülke kodu kontrolü
$countryCode = '';
$isAz = false;
$isTr = false;

// AZ formatı: 994XX XXXXXX (12 hane) veya 0XX XXXXXX (9 hane) veya XX XXXXXX (7-8 hane)
// TR formatı: 90XXX XXXXXXX (12 hane) veya 0XXX XXXXXXX (10 hane)

// 994 ile başlıyorsa AZ
if (str_starts_with($phone, '994') && strlen($phone) >= 12) {
    $countryCode = '994';
    $phone = substr($phone, 3);
    $isAz = true;
}
// 90 ile başlıyorsa TR
elseif (str_starts_with($phone, '90') && strlen($phone) >= 12) {
    $countryCode = '90';
    $phone = substr($phone, 2);
    $isTr = true;
}
// 0 ile başlıyorsa
elseif (str_starts_with($phone, '0')) {
    $phone = substr($phone, 1);
    // AZ operatör prefixleri 2 haneli (50, 51, 55, 60, 70, 77, 99)
    $azPrefix = substr($phone, 0, 2);
    $trPrefix = substr($phone, 0, 3);
    
    if (in_array($azPrefix, ['50', '51', '55', '60', '70', '77', '99'])) {
        $countryCode = '994';
        $isAz = true;
    } else {
        $countryCode = '90';
        $phone = substr($phone, 0, 10);
        $isTr = true;
    }
}
// Başında bir şey yoksa uzunluğa bak
else {
    if (strlen($phone) <= 9) {
        $azPrefix = substr($phone, 0, 2);
        if (in_array($azPrefix, ['50', '51', '55', '60', '70', '77', '99'])) {
            $countryCode = '994';
            $isAz = true;
        }
    }
    if (!$isAz) {
        $countryCode = '90';
        $isTr = true;
    }
}

// ============ AZERBAYCAN OPERATÖRLERİ ============
$azOperators = [
    '50' => ['name' => 'Azercell', 'type' => 'GSM', 'website' => 'azercell.com', 'color' => '#8B0000'],
    '51' => ['name' => 'Azercell', 'type' => 'GSM', 'website' => 'azercell.com', 'color' => '#8B0000'],
    '10' => ['name' => 'Azercell (Nomre Daşınma)', 'type' => 'GSM', 'website' => 'azercell.com'],
    '55' => ['name' => 'Bakcell', 'type' => 'GSM', 'website' => 'bakcell.com', 'color' => '#FF6600'],
    '60' => ['name' => 'Naxtel', 'type' => 'CDMA/WiMAX', 'website' => 'naxtel.az', 'color' => '#0066CC'],
    '70' => ['name' => 'Nar (Azerfon)', 'type' => 'GSM/4G', 'website' => 'nar.az', 'color' => '#E6007E'],
    '77' => ['name' => 'Nar (Azerfon)', 'type' => 'GSM/4G', 'website' => 'nar.az', 'color' => '#E6007E'],
    '99' => ['name' => 'Azercell (Prepaid)', 'type' => 'GSM', 'website' => 'azercell.com', 'color' => '#8B0000'],
];

// ============ TÜRKİYE OPERATÖRLERİ ============
$trOperators = [
    '530'=>'Turkcell','531'=>'Turkcell','532'=>'Turkcell','533'=>'Turkcell',
    '534'=>'Turkcell','535'=>'Turkcell','536'=>'Turkcell','537'=>'Turkcell',
    '538'=>'Turkcell','539'=>'Turkcell',
    '540'=>'Vodafone','541'=>'Vodafone','542'=>'Vodafone','543'=>'Vodafone',
    '544'=>'Vodafone','545'=>'Vodafone','546'=>'Vodafone','547'=>'Vodafone',
    '548'=>'Vodafone','549'=>'Vodafone',
    '550'=>'Türk Telekom','551'=>'Türk Telekom','552'=>'Türk Telekom',
    '553'=>'Türk Telekom','554'=>'Türk Telekom','555'=>'Türk Telekom',
    '556'=>'Türk Telekom','557'=>'Türk Telekom','558'=>'Türk Telekom',
    '559'=>'Türk Telekom',
    '501'=>'Türk Telekom','505'=>'Türk Telekom','506'=>'Türk Telekom','507'=>'Türk Telekom',
];

// ============ AZ ŞEHİR KODLARI (Sabit Hat) ============
$azCities = [
    '12' => 'Bakı', '012' => 'Bakı',
    '18' => 'Sumqayıt', '018' => 'Sumqayıt',
    '20' => 'Gəncə', '020' => 'Gəncə',
    '21' => 'Mingəçevir', '021' => 'Mingəçevir',
    '22' => 'Naxçıvan', '022' => 'Naxçıvan',
    '23' => 'Lənkəran', '023' => 'Lənkəran',
    '24' => 'Şəki', '024' => 'Şəki',
    '25' => 'Yevlax', '025' => 'Yevlax',
    '26' => 'Şamaxı', '026' => 'Şamaxı',
    '27' => 'Quba', '027' => 'Quba',
    '28' => 'Qazax', '028' => 'Qazax',
    '29' => 'Zəngilan', '029' => 'Zəngilan',
    '30' => 'Füzuli', '030' => 'Füzuli',
    '31' => 'Ağdam', '031' => 'Ağdam',
    '32' => 'Ağdaş', '032' => 'Ağdaş',
    '33' => 'Bərdə', '033' => 'Bərdə',
    '34' => 'Astara', '034' => 'Astara',
    '35' => 'İmişli', '035' => 'İmişli',
    '36' => 'Kəlbəcər', '036' => 'Kəlbəcər',
    '37' => 'Laçın', '037' => 'Laçın',
    '38' => 'Masallı', '038' => 'Masallı',
    '39' => 'Neftçala', '039' => 'Neftçala',
    '40' => 'Oğuz', '040' => 'Oğuz',
    '41' => 'Saatlı', '041' => 'Saatlı',
    '42' => 'Sabirabad', '042' => 'Sabirabad',
    '43' => 'Salyan', '043' => 'Salyan',
    '44' => 'Tərtər', '044' => 'Tərtər',
    '45' => 'Ucar', '045' => 'Ucar',
    '46' => 'Xaçmaz', '046' => 'Xaçmaz',
    '47' => 'Xızı', '047' => 'Xızı',
    '48' => 'Cəbrayıl', '048' => 'Cəbrayıl',
    '49' => 'Qubadlı', '049' => 'Qubadlı',
    '50' => 'Şuşa', '050' => 'Şuşa',
];

// ============ TR ŞEHİR KODLARI ============
$trCities = [
    '212'=>'İstanbul (Avrupa)','216'=>'İstanbul (Anadolu)','312'=>'Ankara','232'=>'İzmir',
    '224'=>'Bursa','242'=>'Antalya','322'=>'Adana','332'=>'Konya','342'=>'Gaziantep',
    '352'=>'Kayseri','412'=>'Diyarbakır','324'=>'Mersin','236'=>'Manisa',
    '222'=>'Eskişehir','256'=>'Aydın','252'=>'Muğla','274'=>'Kütahya',
    '258'=>'Denizli','262'=>'Kocaeli','264'=>'Sakarya','266'=>'Balıkesir',
    '272'=>'Afyon','276'=>'Uşak','284'=>'Edirne','286'=>'Çanakkale',
    '288'=>'Kırklareli','318'=>'Kırıkkale','326'=>'Hatay','328'=>'Osmaniye',
    '338'=>'Karaman','344'=>'Kahramanmaraş','346'=>'Sivas','348'=>'Kilis',
    '354'=>'Yozgat','356'=>'Tokat','358'=>'Amasya','362'=>'Samsun',
    '364'=>'Çorum','366'=>'Kastamonu','368'=>'Sinop','370'=>'Karabük',
    '372'=>'Zonguldak','374'=>'Bolu','376'=>'Bartın','378'=>'Düzce',
    '380'=>'Kırşehir','382'=>'Nevşehir','384'=>'Niğde','386'=>'Aksaray',
    '388'=>'Kayseri','392'=>'Elazığ','394'=>'Malatya','416'=>'Adıyaman',
    '422'=>'Siirt','424'=>'Batman','426'=>'Bingöl','428'=>'Tunceli',
    '432'=>'Van','434'=>'Hakkari','436'=>'Muş','438'=>'Bitlis',
    '442'=>'Erzurum','446'=>'Erzincan','452'=>'Ordu','454'=>'Giresun',
    '456'=>'Gümüşhane','458'=>'Bayburt','462'=>'Trabzon','464'=>'Rize',
    '466'=>'Artvin','472'=>'Kars','474'=>'Iğdır','476'=>'Ağrı',
    '478'=>'Ardahan','482'=>'Mardin','484'=>'Şırnak','486'=>'Şanlıurfa',
    '488'=>'Diyarbakır',
];

// Sonuç oluştur
$result = [
    'number' => $originalPhone,
    'formatted' => '+' . $countryCode . ' ' . substr($phone, 0, ($isAz ? 2 : 3)) . ' ' . substr($phone, ($isAz ? 2 : 3), 3) . ' ' . substr($phone, ($isAz ? 5 : 6), 2) . ' ' . substr($phone, ($isAz ? 7 : 8)),
    'valid' => false,
    'country' => null,
    'country_code' => '+' . $countryCode,
    'operator' => null,
    'operator_detail' => null,
    'city' => null,
    'type' => null,
    'social' => [],
];

if ($isAz && strlen($phone) >= 9) {
    $prefix = substr($phone, 0, 2);
    if (isset($azOperators[$prefix])) {
        $result['operator'] = $azOperators[$prefix]['name'];
        $result['operator_detail'] = $azOperators[$prefix];
        $result['country'] = 'Azerbaycan';
        $result['type'] = 'Mobil';
        $result['valid'] = true;
    }
    // Sabit hat kontrolü
    $areaCode2 = substr($phone, 0, 2);
    $areaCode3 = substr($phone, 0, 3);
    if (isset($azCities[$areaCode3])) {
        $result['city'] = $azCities[$areaCode3];
        $result['type'] = 'Sabit Hat';
        if (!$result['operator']) $result['operator'] = 'AzTelekom';
        $result['valid'] = true;
    } elseif (isset($azCities[$areaCode2])) {
        $result['city'] = $azCities[$areaCode2];
        $result['type'] = 'Sabit Hat';
        if (!$result['operator']) $result['operator'] = 'AzTelekom';
        $result['valid'] = true;
    }
} elseif ($isTr && strlen($phone) >= 10) {
    $prefix = substr($phone, 0, 3);
    if (isset($trOperators[$prefix])) {
        $result['operator'] = $trOperators[$prefix];
        $result['country'] = 'Türkiye';
        $result['type'] = 'Mobil';
        $result['valid'] = true;
    }
    // Sabit hat
    $area = substr($phone, 0, 3);
    if (isset($trCities[$area])) {
        $result['city'] = $trCities[$area];
        $result['type'] = $result['type'] ?: 'Sabit Hat';
        if (!$result['operator']) $result['operator'] = 'Türk Telekom';
        $result['valid'] = true;
    }
}

// Formatla
if ($isAz) {
    $result['formatted'] = '+994 ' . substr($phone, 0, 2) . ' ' . substr($phone, 2, 3) . ' ' . substr($phone, 5, 2) . ' ' . substr($phone, 7, 2);
} else {
    $result['formatted'] = '+90 ' . substr($phone, 0, 3) . ' ' . substr($phone, 3, 3) . ' ' . substr($phone, 6, 2) . ' ' . substr($phone, 8, 2);
}

// Sosyal medya kontrolü
$ctx = stream_context_create(['http' => ['timeout' => 3]]);

// WhatsApp
$fullPhone = $countryCode . $phone;
$wa = @file_get_contents("https://wa.me/$fullPhone", false, $ctx);
if ($wa !== false) $result['social'][] = 'WhatsApp';

// Telegram
$tg = @file_get_contents("https://t.me/+$fullPhone", false, $ctx);
if ($tg !== false) $result['social'][] = 'Telegram';

echo json_encode([
    'durum' => 'başarı',
    'data' => $result,
    'Developer' => '@TuncaySkay'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
