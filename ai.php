<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$mesaj = isset($_GET['mesaj']) ? trim($_GET['mesaj']) : null;
$model = isset($_GET['model']) ? trim($_GET['model']) : 'pollinations';

if (!$mesaj) {
    echo json_encode([
        'durum' => 'hata',
        'mesaj' => 'mesaj parametresi gerekli'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function ai_pollinations($mesaj) {
    $url = 'https://text.pollinations.ai/' . urlencode($mesaj);
    $ctx = stream_context_create(['http' => ['timeout' => 30]]);
    $response = @file_get_contents($url, false, $ctx);
    return ($response && strlen($response) > 5) ? trim($response) : null;
}

function ai_gemini($mesaj) {
    // Ücretsiz Gemini (OpenRouter üzerinden)
    $url = 'https://openrouter.ai/api/v1/chat/completions';
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAuthorization: Bearer sk-or-v1-free\r\n",
            'content' => json_encode([
                'model' => 'google/gemini-2.0-flash-lite-001',
                'messages' => [['role' => 'user', 'content' => $mesaj]]
            ]),
            'timeout' => 30
        ]
    ]);
    $response = @file_get_contents($url, false, $ctx);
    if ($response) {
        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }
    return null;
}

// Model seçimi
switch ($model) {
    case 'gemini':
        $cevap = ai_gemini($mesaj);
        $model_adi = 'gemini-2.0-flash';
        break;
    default:
        $cevap = ai_pollinations($mesaj);
        $model_adi = 'pollinations';
        break;
}

if ($cevap) {
    echo json_encode([
        'durum' => 'başarı',
        'mesaj' => $mesaj,
        'cevap' => $cevap,
        'model' => $model_adi,
        'Developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'durum' => 'hata',
        'mesaj' => 'Cevap alınamadı'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
