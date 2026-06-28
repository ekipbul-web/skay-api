<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';
$file = __DIR__ . '/users.json';

// Dosya yoksa oluştur
if (!file_exists($file)) {
    file_put_contents($file, json_encode(['TuncaySkay' => ['password' => '123456&Hack', 'telegram' => '@TuncaySkay', 'role' => 'owner', 'banned' => false]]));
}

$users = json_decode(file_get_contents($file), true);

if ($action === 'register') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    $t = $_POST['telegram'] ?? '';
    
    if (!$u || !$p || !$t) {
        echo json_encode(['success' => false, 'message' => 'Tüm alanlar gerekli']);
        exit;
    }
    if (isset($users[$u])) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı adı kayıtlı']);
        exit;
    }
    
    $users[$u] = ['password' => $p, 'telegram' => $t, 'role' => 'free', 'banned' => false];
    file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true, 'message' => 'Kayıt başarılı']);
}

elseif ($action === 'login') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    
    if (!isset($users[$u])) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
        exit;
    }
    if ($users[$u]['banned']) {
        echo json_encode(['success' => false, 'message' => 'Hesap banlandı']);
        exit;
    }
    if ($users[$u]['password'] !== $p) {
        echo json_encode(['success' => false, 'message' => 'Şifre yanlış']);
        exit;
    }
    
    echo json_encode(['success' => true, 'user' => ['username' => $u, 'telegram' => $users[$u]['telegram'], 'role' => $users[$u]['role']]]);
}

elseif ($action === 'list') {
    // Admin için kullanıcı listesi
    $list = [];
    foreach ($users as $username => $data) {
        $list[] = ['username' => $username, 'role' => $data['role'], 'telegram' => $data['telegram'], 'banned' => $data['banned']];
    }
    echo json_encode($list);
}

elseif ($action === 'ban') {
    $u = $_POST['username'] ?? '';
    if (isset($users[$u]) && $u !== 'TuncaySkay') {
        $users[$u]['banned'] = true;
        file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

elseif ($action === 'unban') {
    $u = $_POST['username'] ?? '';
    if (isset($users[$u])) {
        $users[$u]['banned'] = false;
        file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

elseif ($action === 'addadmin') {
    $u = $_POST['username'] ?? '';
    if (isset($users[$u])) {
        $users[$u]['role'] = 'admin';
        file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>
