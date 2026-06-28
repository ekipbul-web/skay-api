<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';
$usersFile = __DIR__ . '/users.json';
$logsFile = __DIR__ . '/logs.json';
$msgsFile = __DIR__ . '/messages.json';

// Dosyalar yoksa oluştur
if (!file_exists($usersFile)) {
    file_put_contents($usersFile, json_encode([
        'TuncaySkay' => ['password' => '123456&Hack', 'telegram' => '@TuncaySkay', 'role' => 'owner', 'banned' => false]
    ]));
}
if (!file_exists($logsFile)) {
    file_put_contents($logsFile, json_encode([]));
}
if (!file_exists($msgsFile)) {
    file_put_contents($msgsFile, json_encode([]));
}

$users = json_decode(file_get_contents($usersFile), true);
$logs = json_decode(file_get_contents($logsFile), true);
$msgs = json_decode(file_get_contents($msgsFile), true);

function addLog($username, $action) {
    global $logsFile, $logs;
    array_unshift($logs, ['user' => $username, 'action' => $action, 'time' => date('Y-m-d H:i:s')]);
    if (count($logs) > 500) $logs = array_slice($logs, 0, 500);
    file_put_contents($logsFile, json_encode($logs, JSON_PRETTY_PRINT));
}

// ============ KAYIT ============
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
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    addLog($u, 'Yeni kayıt');
    echo json_encode(['success' => true, 'message' => 'Kayıt başarılı']);
}

// ============ GİRİŞ ============
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
    
    addLog($u, 'Giriş yaptı');
    echo json_encode(['success' => true, 'user' => ['username' => $u, 'telegram' => $users[$u]['telegram'], 'role' => $users[$u]['role']]]);
}

// ============ KULLANICI KONTROL ============
elseif ($action === 'checkuser') {
    $u = $_POST['username'] ?? '';
    if (isset($users[$u])) {
        echo json_encode(['success' => true, 'telegram' => $users[$u]['telegram']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
    }
}

// ============ ŞİFRE DEĞİŞTİR ============
elseif ($action === 'changepass') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['newpass'] ?? '';
    if (isset($users[$u]) && $p) {
        $users[$u]['password'] = $p;
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        addLog($u, 'Şifre değiştirdi');
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

// ============ KULLANICI LİSTESİ ============
elseif ($action === 'list') {
    $list = [];
    foreach ($users as $username => $data) {
        $list[] = ['username' => $username, 'role' => $data['role'], 'telegram' => $data['telegram'], 'banned' => $data['banned']];
    }
    echo json_encode($list);
}

// ============ BAN ============
elseif ($action === 'ban') {
    $u = $_POST['username'] ?? '';
    if (isset($users[$u]) && $u !== 'TuncaySkay') {
        $users[$u]['banned'] = true;
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        addLog($u, 'Banlandı');
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

// ============ BAN AÇ ============
elseif ($action === 'unban') {
    $u = $_POST['username'] ?? '';
    if (isset($users[$u])) {
        $users[$u]['banned'] = false;
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        addLog($u, 'Ban açıldı');
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

// ============ ADMIN EKLE ============
elseif ($action === 'addadmin') {
    $u = $_POST['username'] ?? '';
    if (isset($users[$u])) {
        $users[$u]['role'] = 'admin';
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        addLog($u, 'Admin yapıldı');
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

// ============ LOG LİSTESİ ============
elseif ($action === 'logs') {
    echo json_encode($logs);
}

// ============ DUYURU GÖNDER ============
elseif ($action === 'announce') {
    $msg = $_POST['message'] ?? '';
    $from = $_POST['from'] ?? 'Admin';
    if ($msg) {
        foreach ($users as $username => $data) {
            if (!isset($msgs[$username])) $msgs[$username] = [];
            $msgs[$username][] = ['msg' => $msg, 'from' => $from, 'time' => date('Y-m-d H:i:s'), 'type' => 'announce'];
        }
        file_put_contents($msgsFile, json_encode($msgs, JSON_PRETTY_PRINT));
        addLog($from, 'Duyuru gönderdi');
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

// ============ ÖZEL MESAJ ============
elseif ($action === 'sendpm') {
    $to = $_POST['to'] ?? '';
    $msg = $_POST['message'] ?? '';
    $from = $_POST['from'] ?? '';
    if ($to && $msg && $from && isset($users[$to])) {
        if (!isset($msgs[$to])) $msgs[$to] = [];
        $msgs[$to][] = ['msg' => $msg, 'from' => $from, 'time' => date('Y-m-d H:i:s'), 'type' => 'pm'];
        file_put_contents($msgsFile, json_encode($msgs, JSON_PRETTY_PRINT));
        addLog($from, 'Mesaj gönderdi → ' . $to);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

// ============ MESAJLARI GETİR ============
elseif ($action === 'getmsgs') {
    $u = $_GET['username'] ?? '';
    echo json_encode($msgs[$u] ?? []);
}
?>
