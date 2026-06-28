<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ============ JSONBIN.IO AYARLARI ============
$MASTER_KEY = '$2a$10$0D/JQ68G.ZvxOxjSxmZ1Gei5cackCaiNRmFDTigNy2rT3JE.zHaaK';
$USERS_BIN_ID = '6a4173b6da38895dfe0cbd58';
$LOGS_BIN_ID = '6a4174a6da38895dfe0cc040';
$MSGS_BIN_ID = '6a4174aeda38895dfe0cc04f';

$action = $_GET['action'] ?? '';

// ============ JSONBIN FONKSİYONLARI ============
function jsonbinGet($binId) {
    global $MASTER_KEY;
    $url = "https://api.jsonbin.io/v3/b/$binId/latest";
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "X-Master-Key: $MASTER_KEY\r\n",
            'timeout' => 10
        ],
        'ssl' => ['verify_peer' => false]
    ];
    $response = @file_get_contents($url, false, stream_context_create($opts));
    if ($response) {
        $data = json_decode($response, true);
        return $data['record'] ?? null;
    }
    return null;
}

function jsonbinPut($binId, $data) {
    global $MASTER_KEY;
    $url = "https://api.jsonbin.io/v3/b/$binId";
    $opts = [
        'http' => [
            'method' => 'PUT',
            'header' => "X-Master-Key: $MASTER_KEY\r\nContent-Type: application/json\r\n",
            'content' => json_encode($data),
            'timeout' => 10
        ],
        'ssl' => ['verify_peer' => false]
    ];
    return @file_get_contents($url, false, stream_context_create($opts)) !== false;
}

function addLog($username, $action) {
    global $LOGS_BIN_ID;
    $logs = jsonbinGet($LOGS_BIN_ID) ?: [];
    // Placeholder temizle
    if (isset($logs['_bos'])) unset($logs['_bos']);
    if (!is_array($logs)) $logs = [];
    array_unshift($logs, ['user' => $username, 'action' => $action, 'time' => date('Y-m-d H:i:s')]);
    if (count($logs) > 500) $logs = array_slice($logs, 0, 500);
    jsonbinPut($LOGS_BIN_ID, $logs);
}

// ============ VERİLERİ YÜKLE ============
$users = jsonbinGet($USERS_BIN_ID) ?: [];
$logs = jsonbinGet($LOGS_BIN_ID) ?: [];
$msgs = jsonbinGet($MSGS_BIN_ID) ?: [];

// Placeholder temizle
if (isset($users['_bos'])) unset($users['_bos']);
if (isset($logs['_bos'])) unset($logs['_bos']);
if (isset($msgs['_bos'])) unset($msgs['_bos']);
if (!is_array($logs)) $logs = [];
if (!is_array($msgs)) $msgs = [];

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
    jsonbinPut($USERS_BIN_ID, $users);
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
        jsonbinPut($USERS_BIN_ID, $users);
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
        if ($username === '_bos') continue;
        $list[] = ['username' => $username, 'role' => $data['role'], 'telegram' => $data['telegram'], 'banned' => $data['banned']];
    }
    echo json_encode($list);
}

// ============ BAN ============
elseif ($action === 'ban') {
    $u = $_POST['username'] ?? '';
    if (isset($users[$u]) && $u !== 'TuncaySkay') {
        $users[$u]['banned'] = true;
        jsonbinPut($USERS_BIN_ID, $users);
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
        jsonbinPut($USERS_BIN_ID, $users);
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
        jsonbinPut($USERS_BIN_ID, $users);
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
            if ($username === '_bos') continue;
            if (!isset($msgs[$username])) $msgs[$username] = [];
            $msgs[$username][] = ['msg' => $msg, 'from' => $from, 'time' => date('Y-m-d H:i:s'), 'type' => 'announce'];
        }
        jsonbinPut($MSGS_BIN_ID, $msgs);
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
        jsonbinPut($MSGS_BIN_ID, $msgs);
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
