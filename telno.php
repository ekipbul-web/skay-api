<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$phone = isset($_GET['phone']) ? trim($_GET['phone']) : null;

if (!$phone) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'phone parametresi gerekli'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Temizle
$phone = preg_replace('/[^\d]/', '', $phone);
if (!str_starts_with($phone, '90')) {
    if (str_starts_with($phone, '0')) $phone = '90' . substr($phone, 1);
    elseif (strlen($phone) == 10) $phone = '90' . $phone;
}

$result = [
    'number' => $phone,
    'formatted' => '+' . substr($phone, 0, 2) . ' ' . substr($phone, 2, 3) . ' ' . substr($phone, 5, 3) . ' ' . substr($phone, 8, 2) . ' ' . substr($phone, 10),
    'valid' => false,
    'country' => null,
    'operator' => null,
    'city' => null,
    'social' => [],
];

// TR Operatör
$operators = [
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
];

if (str_starts_with($phone, '90') && strlen($phone) >= 12) {
    $prefix = substr($phone, 2, 3);
    if (isset($operators[$prefix])) {
        $result['operator'] = $operators[$prefix];
        $result['country'] = 'Türkiye';
        $result['valid'] = true;
    }
}

// TR Şehirler (sabit hat)
$cities = [
    '212'=>'İstanbul (Avrupa)','216'=>'İstanbul (Anadolu)','312'=>'Ankara','232'=>'İzmir',
    '224'=>'Bursa','242'=>'Antalya','322'=>'Adana','332'=>'Konya','342'=>'Gaziantep',
    '352'=>'Kayseri','412'=>'Diyarbakır','324'=>'Mersin','236'=>'Manisa',
];

if (str_starts_with($phone, '90')) {
    $area = substr($phone, 2, 3);
    if (isset($cities[$area])) $result['city'] = $cities[$area];
}

// Sosyal medya
$ctx = stream_context_create(['http' => ['timeout' => 3]]);

// WhatsApp
$wa = @file_get_contents("https://wa.me/$phone", false, $ctx);
if ($wa !== false) $result['social'][] = 'WhatsApp';

// Telegram
$tg = @file_get_contents("https://t.me/+$phone", false, $ctx);
if ($tg !== false) $result['social'][] = 'Telegram';

echo json_encode([
    'durum' => 'başarı',
    'data' => $result,
    'Developer' => '@TuncaySkay'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>