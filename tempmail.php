<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$action = isset($_GET['action']) ? trim($_GET['action']) : 'create';

if ($action == 'create') {
    // Yeni email oluştur
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $response = @file_get_contents('https://api.guerrillamail.com/ajax.php?f=get_email_address&ip=127.0.0.1&agent=Mozilla', false, $ctx);
    
    if ($response) {
        $data = json_decode($response, true);
        echo json_encode([
            'durum' => 'başarı',
            'email' => $data['email_addr'],
            'token' => $data['sid_token'],
            'Developer' => '@TuncaySkay'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Email oluşturulamadı'], JSON_UNESCAPED_UNICODE);
    }
    
} elseif ($action == 'inbox') {
    // Gelen kutusu
    $token = isset($_GET['token']) ? trim($_GET['token']) : null;
    if (!$token) {
        echo json_encode(['durum' => 'hata', 'mesaj' => 'token gerekli'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $response = @file_get_contents("https://api.guerrillamail.com/ajax.php?f=get_email_list&offset=0&sid_token=$token", false, $ctx);
    
    if ($response) {
        $data = json_decode($response, true);
        $messages = [];
        foreach ($data['list'] ?? [] as $msg) {
            $messages[] = [
                'id' => $msg['mail_id'],
                'from' => $msg['mail_from'],
                'subject' => $msg['mail_subject'],
                'date' => $msg['mail_date'],
            ];
        }
        
        echo json_encode([
            'durum' => 'başarı',
            'toplam' => count($messages),
            'mesajlar' => $messages,
            'Developer' => '@TuncaySkay'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Gelen kutusu alınamadı'], JSON_UNESCAPED_UNICODE);
    }
    
} elseif ($action == 'read') {
    // Mesaj oku
    $token = isset($_GET['token']) ? trim($_GET['token']) : null;
    $id = isset($_GET['id']) ? trim($_GET['id']) : null;
    
    if (!$token || !$id) {
        echo json_encode(['durum' => 'hata', 'mesaj' => 'token ve id gerekli'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $response = @file_get_contents("https://api.guerrillamail.com/ajax.php?f=fetch_email&email_id=$id&sid_token=$token", false, $ctx);
    
    if ($response) {
        $data = json_decode($response, true);
        echo json_encode([
            'durum' => 'başarı',
            'mesaj' => [
                'from' => $data['mail_from'] ?? '',
                'subject' => $data['mail_subject'] ?? '',
                'body' => $data['mail_body'] ?? '',
                'date' => $data['mail_date'] ?? '',
            ],
            'Developer' => '@TuncaySkay'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Mesaj okunamadı'], JSON_UNESCAPED_UNICODE);
    }
}
?>