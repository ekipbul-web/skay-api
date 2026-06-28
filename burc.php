<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$burc = isset($_GET['burc']) ? mb_strtolower(trim($_GET['burc']), 'UTF-8') : null;
$dil = isset($_GET['dil']) ? mb_strtolower(trim($_GET['dil']), 'UTF-8') : 'tr';

if (!$burc) {
    echo json_encode([
        'durum' => 'hata',
        'mesaj' => 'burc parametresi gerekli. Örnek: ?burc=koc&dil=tr veya ?burc=qoc&dil=az'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Dil kontrolü
if (!in_array($dil, ['tr', 'az'])) {
    $dil = 'tr';
}

// Burç listesi (TR ve AZ anahtarları)
$BURCLAR = [
    // TR anahtar => [TR isim, AZ isim]
    'koc'     => ['tr' => 'Koç',    'az' => 'Qoç',     'emoji' => '♈', 'tarih_tr' => '21 Mart - 19 Nisan',     'tarih_az' => '21 Mart - 19 Aprel'],
    'boga'    => ['tr' => 'Boğa',   'az' => 'Buğa',    'emoji' => '♉', 'tarih_tr' => '20 Nisan - 20 Mayıs',     'tarih_az' => '20 Aprel - 20 May'],
    'ikizler' => ['tr' => 'İkizler', 'az' => 'Əkizlər', 'emoji' => '♊', 'tarih_tr' => '21 Mayıs - 20 Haziran',   'tarih_az' => '21 May - 20 İyun'],
    'yengec'  => ['tr' => 'Yengeç', 'az' => 'Xərçəng', 'emoji' => '♋', 'tarih_tr' => '21 Haziran - 22 Temmuz',  'tarih_az' => '21 İyun - 22 İyul'],
    'aslan'   => ['tr' => 'Aslan',  'az' => 'Şir',      'emoji' => '♌', 'tarih_tr' => '23 Temmuz - 22 Ağustos',  'tarih_az' => '23 İyul - 22 Avqust'],
    'basak'   => ['tr' => 'Başak',  'az' => 'Qız',      'emoji' => '♍', 'tarih_tr' => '23 Ağustos - 22 Eylül',   'tarih_az' => '23 Avqust - 22 Sentyabr'],
    'terazi'  => ['tr' => 'Terazi', 'az' => 'Tərəzi',   'emoji' => '♎', 'tarih_tr' => '23 Eylül - 22 Ekim',      'tarih_az' => '23 Sentyabr - 22 Oktyabr'],
    'akrep'   => ['tr' => 'Akrep',  'az' => 'Əqrəb',    'emoji' => '♏', 'tarih_tr' => '23 Ekim - 21 Kasım',      'tarih_az' => '23 Oktyabr - 21 Noyabr'],
    'yay'     => ['tr' => 'Yay',    'az' => 'Oxatan',   'emoji' => '♐', 'tarih_tr' => '22 Kasım - 21 Aralık',    'tarih_az' => '22 Noyabr - 21 Dekabr'],
    'oglak'   => ['tr' => 'Oğlak',  'az' => 'Oğlaq',    'emoji' => '♑', 'tarih_tr' => '22 Aralık - 19 Ocak',     'tarih_az' => '22 Dekabr - 19 Yanvar'],
    'kova'    => ['tr' => 'Kova',   'az' => 'Dolça',    'emoji' => '♒', 'tarih_tr' => '20 Ocak - 18 Şubat',      'tarih_az' => '20 Yanvar - 18 Fevral'],
    'balik'   => ['tr' => 'Balık',  'az' => 'Balıqlar', 'emoji' => '♓', 'tarih_tr' => '19 Şubat - 20 Mart',       'tarih_az' => '19 Fevral - 20 Mart'],
];

// AZ anahtarları için eşleştirme
$AZ_KEYS = [
    'qoc'     => 'koc',
    'buga'    => 'boga',
    'ekizler' => 'ikizler', 'əkizlər' => 'ikizler',
    'xerceng' => 'yengec', 'xərçəng' => 'yengec',
    'sir'     => 'aslan', 'şir' => 'aslan',
    'qiz'     => 'basak', 'qız' => 'basak',
    'terezi'  => 'terazi', 'tərəzi' => 'terazi',
    'eqreb'   => 'akrep', 'əqrəb' => 'akrep',
    'oxatan'  => 'yay',
    'oglaq'   => 'oglak', 'oğlaq' => 'oglak',
    'dolca'   => 'kova', 'dolça' => 'kova',
    'baliqlar'=> 'balik', 'balıqlar' => 'balik',
];

// AZ anahtarıyla gelmişse TR anahtara çevir
if (isset($AZ_KEYS[$burc])) {
    $burc = $AZ_KEYS[$burc];
}

if (!isset($BURCLAR[$burc])) {
    echo json_encode([
        'durum' => 'hata',
        'mesaj_tr' => 'Geçersiz burç. Kullan: koc, boga, ikizler, yengec, aslan, basak, terazi, akrep, yay, oglak, kova, balik',
        'mesaj_az' => 'Yanlış bürc. İstifadə: qoc, buga, ekizler, xerceng, sir, qiz, terezi, eqreb, oxatan, oglaq, dolca, baliqlar'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$b = $BURCLAR[$burc];
$burcIsim = $b[$dil]; // dile göre isim

/**
 * AI ile burç yorumu
 */
function burcYorumu($burcIsim, $dil) {
    if ($dil === 'az') {
        $mesaj = urlencode("$burcIsim bürcü üçün bu günün qısa gündəlik bürc yorumu yaz. Sadəcə yorumu yaz. Maksimum 2 cümlə. Azərbaycan dilində.");
    } else {
        $mesaj = urlencode("$burcIsim burcu için bugünün kısa günlük burç yorumu yaz. Sadece yorumu yaz. Max 2 cümle.");
    }
    
    $url = "https://text.pollinations.ai/$mesaj";
    $ctx = stream_context_create(['http' => ['timeout' => 20]]);
    $response = @file_get_contents($url, false, $ctx);
    
    if ($response && strlen($response) > 5) {
        // Temizle
        $response = trim($response);
        $response = str_replace(['"', '\\'], '', $response);
        return $response;
    }
    return null;
}

/**
 * Yedek statik yorumlar
 */
function yedekYorum($burcKey, $dil) {
    $yorumlar = [
        'tr' => [
            'koc' => 'Bugün enerjiniz yüksek. Yeni başlangıçlar için harika bir gün.',
            'boga' => 'Finansal konularda şanslısınız. Harcamalarınıza dikkat edin.',
            'ikizler' => 'İletişim becerileriniz zirvede. Önemli görüşmeler yapabilirsiniz.',
            'yengec' => 'Duygusal olarak hassas olabilirsiniz. Ailenizle vakit geçirin.',
            'aslan' => 'İlgi odağı olacaksınız. Yaratıcılığınızı kullanın.',
            'basak' => 'Detaylara odaklanın. İş hayatında başarı sizi bekliyor.',
            'terazi' => 'İlişkilerde dengeyi bulacaksınız. Uzlaşmacı olun.',
            'akrep' => 'Sezgileriniz güçlü. Gizli kalmış gerçekler ortaya çıkabilir.',
            'yay' => 'Macera ruhunuz kabarıyor. Seyahat planları yapabilirsiniz.',
            'oglak' => 'Kariyer hedeflerinize odaklanın. Disiplinli çalışın.',
            'kova' => 'Yenilikçi fikirler üreteceksiniz. Arkadaşlarınızla paylaşın.',
            'balik' => 'Sanatsal yetenekleriniz öne çıkıyor. Yaratıcı olun.',
        ],
        'az' => [
            'koc' => 'Bu gün enerjiniz yüksəkdir. Yeni başlanğıclar üçün əla gündür.',
            'boga' => 'Maliyyə məsələlərində şanslısınız. Xərclərinizə diqqət edin.',
            'ikizler' => 'Ünsiyyət bacarığınız zirvədədir. Vacib görüşlər keçirə bilərsiniz.',
            'yengec' => 'Duygusal olaraq həssas ola bilərsiniz. Ailənizlə vaxt keçirin.',
            'aslan' => 'Diqqət mərkəzində olacaqsınız. Yaradıcılığınızı istifadə edin.',
            'basak' => 'Detallara fokuslanın. İş həyatında uğur sizi gözləyir.',
            'terazi' => 'Münasibətlərdə tarazlığı tapacaqsınız. Barışdırıcı olun.',
            'akrep' => 'Önseziləriniz güclüdür. Gizli qalmış həqiqətlər üzə çıxa bilər.',
            'yay' => 'Macəra ruhunuz coşur. Səyahət planları qura bilərsiniz.',
            'oglak' => 'Karyera hədəflərinizə fokuslanın. İntizamlı çalışın.',
            'kova' => 'Yenilikçi fikirlər ürətəcəksiniz. Dostlarınızla bölüşün.',
            'balik' => 'Bədii istedadınız önə çıxır. Yaradıcı olun.',
        ]
    ];
    
    return $yorumlar[$dil][$burcKey] ?? null;
}

// Önce AI dene
$yorum = burcYorumu($burcIsim, $dil);

// AI çalışmazsa yedek
if (!$yorum) {
    $yorum = yedekYorum($burc, $dil);
}

if ($yorum) {
    $tarihKey = ($dil === 'az') ? 'tarih_az' : 'tarih_tr';
    
    echo json_encode([
        'durum' => 'başarı',
        'tur' => $dil === 'az' ? 'bürc yorumu' : 'burç yorumu',
        'dil' => $dil === 'az' ? 'Azərbaycan' : 'Türkçe',
        'imza' => [
            'anahtar' => $burc,
            'isim' => $b[$dil],
            'emoji' => $b['emoji'],
            'tarih_araligi' => $b[$tarihKey]
        ],
        'kategori' => 'günlük',
        'yorum' => $yorum,
        'tarih' => date('d.m.Y'),
        'Developer' => '@TuncaySkay'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'durum' => 'hata',
        'mesaj' => $dil === 'az' ? 'Yorum alına bilmədi' : 'Yorum alınamadı'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
