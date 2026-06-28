<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$cinsiyet = isset($_GET['cinsiyet']) ? mb_strtolower(trim($_GET['cinsiyet']), 'UTF-8') : 'rastgele';
$ulke = isset($_GET['ulke']) ? mb_strtolower(trim($_GET['ulke']), 'UTF-8') : 'rastgele';
$adet = isset($_GET['adet']) ? intval($_GET['adet']) : 1;
$adet = max(1, min(10, $adet));

// Cinsiyet normalizasyonu
$erkekList = ['erkek', 'kisi', 'male', 'man', 'k', 'm', '1', 'e'];
$kadinList = ['kadin', 'kadın', 'qadin', 'qadın', 'female', 'woman', 'q', 'w', 'f', '2', 'kz'];

if (in_array($cinsiyet, $erkekList)) {
    $isMale = true;
} elseif (in_array($cinsiyet, $kadinList)) {
    $isMale = false;
} else {
    $isMale = rand(0, 1) === 1;
}

// Ülke normalizasyonu
$azList = ['az', 'azerbaycan', 'azərbaycan', 'aze', 'azeri', 'azəri'];
$trList = ['tr', 'türkiye', 'turkiye', 'turk', 'türk', 'turkey'];

if (in_array($ulke, $azList)) {
    $ulke = 'az';
} elseif (in_array($ulke, $trList)) {
    $ulke = 'tr';
} else {
    $ulke = rand(0, 1) ? 'az' : 'tr';
}

// ============ VERİTABANLARI ============

// AZERBAYCAN
$azMaleNames = ['Ali', 'Hüseyn', 'Murad', 'Orxan', 'Elçin', 'Rəşad', 'Rauf', 'Ramil', 'Namiq', 'Eldar',
    'Fuad', 'Rövşən', 'Rəşid', 'Vüqar', 'Tural', 'Səbuhi', 'Kənan', 'Emin', 'Sənan', 'Zaur',
    'İlkin', 'Rəvan', 'Cavid', 'Toğrul', 'Araz', 'Tərlan', 'İlham', 'Zakir', 'Müşfiq', 'Fərid'];

$azFemaleNames = ['Aygün', 'Leyla', 'Nərmin', 'Aysel', 'Günay', 'Sevinc', 'Könül', 'Nigar', 'Firuzə', 'Züleyxa',
    'Rəna', 'Aydan', 'Günel', 'Mehriban', 'Ülviyyə', 'Şəbnəm', 'Səbinə', 'Dilarə', 'Aytən', 'Nurlanə',
    'Aygül', 'Lalə', 'Nərgiz', 'Səidə', 'Arzu', 'Xəyalə', 'Fidan', 'Çiçək', 'Bahar', 'Gülşən'];

$azSurnames = ['Əliyev', 'Hüseynov', 'Məmmədov', 'Həsənov', 'Quliyev', 'İsmayılov', 'Rzayev', 'Əhmədov',
    'Musayev', 'Babayev', 'Nəbiyev', 'Cəfərov', 'İbrahimov', 'Orucov', 'Səfərov', 'Əsədov',
    'Qasımov', 'Mehdiyev', 'Abbasov', 'Kazımov', 'Ağayev', 'Qəhrəmanov', 'Əkbərov', 'Şükürov', 'Mirzəyev'];

$azFatherNames = ['Əli', 'Hüseyn', 'Murad', 'Orxan', 'Rəşad', 'Elçin', 'Rauf', 'Namiq', 'Eldar', 'Vüqar',
    'Fuad', 'Rövşən', 'Zakir', 'İlham', 'Məhərrəm', 'Sərvər', 'Arif', 'Qədir', 'Vaqif', 'Yusif',
    'Cəmil', 'Fikrət', 'Süleyman', 'İsmayıl', 'Rafiq'];

$azCities = ['Bakı', 'Gəncə', 'Sumqayıt', 'Mingəçevir', 'Naxçıvan', 'Lənkəran', 'Şəki', 'Yevlax',
    'Şirvan', 'Xırdalan', 'Şamaxı', 'Quba', 'Qazax', 'Ağdam', 'Bərdə', 'İmişli', 'Salyan',
    'Sabirabad', 'Xaçmaz', 'Cəlilabad', 'Masallı', 'Neftçala', 'Tərtər', 'Ucar', 'Astara'];

$azDistricts = [
    'Bakı' => ['Nərimanov', 'Nəsimi', 'Yasamal', 'Səbail', 'Xətai', 'Nizami', 'Sabunçu', 'Binəqədi'],
    'Gəncə' => ['Kəpəz', 'Nizami'],
    'Sumqayıt' => ['Mərkəz', 'Kimyaçılar', 'Corat'],
    'Naxçıvan' => ['Mərkəz', 'Qarabağlar', 'Babək'],
    'Lənkəran' => ['Mərkəz', 'Limani', 'Kənarmeşə'],
    'Şəki' => ['Mərkəz', 'Oxud', 'Kiş'],
];

$azStreets = ['Nizami', 'Azadlıq', '28 May', 'Neftçilər', 'Dilarə Əliyeva', 'İstiqlaliyyət',
    'Təbriz', 'Xətai', 'Səməd Vurğun', 'Nəriman Nərimanov', 'Rəşid Behbudov', 'Üzeyir Hacıbəyov',
    'Mikayıl Müşfiq', 'Cəfər Cabbarlı', 'Bülbül', 'Zərifə Əliyeva', 'Hüseyn Cavid', 'Füzuli'];

$azJobs = ['Mühəndis', 'Müəllim', 'Həkim', 'Vəkil', 'Memar', 'Proqramçı', 'Dizayner', 'Mühasib',
    'İqtisadçı', 'Jurnalist', 'Hərbçi', 'Polis', 'Sahibkar', 'Dövlət Qulluqçusu', 'Tələbə'];

$azDomains = ['gmail.com', 'mail.ru', 'yahoo.com', 'outlook.com', 'yandex.ru'];

// TÜRKİYE
$trMaleNames = ['Ahmet', 'Mehmet', 'Mustafa', 'Ali', 'Hüseyin', 'Hasan', 'İbrahim', 'İsmail', 'Yusuf', 'Murat',
    'Ömer', 'Kadir', 'Berk', 'Can', 'Deniz', 'Emre', 'Furkan', 'Gökhan', 'Hakan', 'Kerem',
    'Mert', 'Onur', 'Özgür', 'Serkan', 'Tolga', 'Umut', 'Volkan', 'Yasin', 'Zafer', 'Burak'];

$trFemaleNames = ['Fatma', 'Ayşe', 'Emine', 'Hatice', 'Zeynep', 'Elif', 'Merve', 'Büşra', 'Esra', 'Selin',
    'İrem', 'Gizem', 'Derya', 'Pınar', 'Seda', 'Tuğba', 'Yasemin', 'Ceren', 'Ebru', 'Sude',
    'Melis', 'Ece', 'Nisa', 'Beyza', 'Damla', 'Eylül', 'Lale', 'Menekşe', 'Sümbül', 'Nergis'];

$trSurnames = ['Yılmaz', 'Kaya', 'Demir', 'Şahin', 'Çelik', 'Yıldız', 'Yıldırım', 'Öztürk', 'Aydın', 'Özdemir',
    'Arslan', 'Doğan', 'Kılıç', 'Aslan', 'Çetin', 'Kara', 'Koç', 'Kurt', 'Özkan', 'Şimşek',
    'Polat', 'Akın', 'Korkmaz', 'Çakır', 'Erdoğan', 'Avcı', 'Şen', 'Taş', 'Tekin', 'Bulut'];

$trCities = [
    'İstanbul' => ['Kadıköy', 'Beşiktaş', 'Üsküdar', 'Maltepe', 'Ataşehir'],
    'Ankara' => ['Çankaya', 'Keçiören', 'Mamak', 'Yenimahalle', 'Etimesgut'],
    'İzmir' => ['Karşıyaka', 'Bornova', 'Buca', 'Konak', 'Çiğli'],
    'Bursa' => ['Osmangazi', 'Nilüfer', 'Yıldırım', 'Mudanya', 'Gemlik'],
    'Antalya' => ['Muratpaşa', 'Konyaaltı', 'Kepez', 'Alanya', 'Manavgat'],
    'Adana' => ['Seyhan', 'Çukurova', 'Yüreğir', 'Sarıçam', 'Karaisalı'],
    'Gaziantep' => ['Şahinbey', 'Şehitkamil', 'Nizip', 'İslahiye', 'Nurdağı'],
];

$trStreets = ['Atatürk', 'Cumhuriyet', 'İnönü', 'Bağdat', 'İstiklal', 'Lale', 'Menekşe', 'Gül', 'Papatya',
    'Zambak', 'Çınar', 'Zafer', 'Barış', 'Fatih', 'Süleyman', 'Yunus', 'Mehmet Akif'];

$trJobs = ['Mühendis', 'Öğretmen', 'Doktor', 'Avukat', 'Mimar', 'Yazılımcı', 'Serbest', 'Esnaf', 'Memur', 'İşçi'];

$trDomains = ['gmail.com', 'hotmail.com', 'yahoo.com', 'outlook.com', 'icloud.com'];

// ============ FONKSİYONLAR ============

function generateTC() {
    $digits = [rand(1, 9)];
    for ($i = 0; $i < 8; $i++) $digits[] = rand(0, 9);
    $oddSum = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
    $evenSum = $digits[1] + $digits[3] + $digits[5] + $digits[7];
    $digit10 = ($oddSum * 7 - $evenSum) % 10;
    if ($digit10 < 0) $digit10 += 10;
    $digits[] = $digit10;
    $digit11 = array_sum($digits) % 10;
    $digits[] = $digit11;
    return implode('', $digits);
}

function generateFIN() {
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    return $letters[rand(0, 25)] . $letters[rand(0, 25)] . $letters[rand(0, 25)] . rand(1000, 9999);
}

function generateSeria() {
    return 'AZE' . rand(1000000, 9999999);
}

function generateTRPhone() {
    $prefixes = ['530','531','532','533','534','535','536','537','538','539',
                 '540','541','542','543','544','545','546','547','548','549',
                 '550','551','552','553','554','555','556','557','558','559'];
    return '0' . $prefixes[array_rand($prefixes)] . str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
}

function generateAZPhone() {
    $prefixes = ['050', '051', '055', '060', '070', '077', '099'];
    return $prefixes[array_rand($prefixes)] . str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
}

function generateEmail($first, $last, $domains) {
    $first = transliterate($first);
    $last = transliterate($last);
    $patterns = [$first . '.' . $last, $first . $last, $first . '_' . $last, $first . rand(1, 999)];
    return strtolower($patterns[array_rand($patterns)]) . '@' . $domains[array_rand($domains)];
}

function transliterate($text) {
    $tr = ['ü','ö','ı','ş','ğ','ç','Ü','Ö','İ','Ş','Ğ','Ç','ə','Ə'];
    $en = ['u','o','i','s','g','c','U','O','I','S','G','C','e','E'];
    return str_replace($tr, $en, $text);
}

// ============ ID ÜRET ============
$data = [];

for ($i = 0; $i < $adet; $i++) {
    if ($ulke === 'az') {
        $firstName = $isMale ? $azMaleNames[array_rand($azMaleNames)] : $azFemaleNames[array_rand($azFemaleNames)];
        $lastName = $azSurnames[array_rand($azSurnames)];
        $fatherName = $azFatherNames[array_rand($azFatherNames)];
        $cityName = $azCities[array_rand($azCities)];
        $district = isset($azDistricts[$cityName]) ? $azDistricts[$cityName][array_rand($azDistricts[$cityName])] : 'Mərkəz';
        $street = $azStreets[array_rand($azStreets)];
        
        $year = rand(1960, 2005);
        $month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
        $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        
        $data[] = [
            'ulke' => 'Azerbaycan',
            'ad' => $firstName,
            'soyad' => $lastName,
            'ata_adi' => $fatherName,
            'cinsiyet' => $isMale ? 'Kişi' : 'Qadın',
            'fin_kod' => generateFIN(),
            'seri_no' => generateSeria(),
            'dogum_tarihi' => "$year-$month-$day",
            'dogum_yeri' => $cityName,
            'telefon' => generateAZPhone(),
            'email' => generateEmail($firstName, $lastName, $azDomains),
            'adres' => "$street küçəsi, " . rand(1, 300) . ', ' . $district . ', ' . $cityName,
            'sehir' => $cityName,
            'ilce' => $district,
            'posta_kodu' => 'AZ' . rand(1000, 9999),
            'meslek' => $azJobs[array_rand($azJobs)],
            'kan_grubu' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', '0+', '0-'][rand(0, 7)],
            'medeni_durum' => rand(0, 1) ? 'Subay' : 'Evli',
        ];
    } else {
        $firstName = $isMale ? $trMaleNames[array_rand($trMaleNames)] : $trFemaleNames[array_rand($trFemaleNames)];
        $lastName = $trSurnames[array_rand($trSurnames)];
        $cityName = array_rand($trCities);
        $district = $trCities[$cityName][array_rand($trCities[$cityName])];
        $street = $trStreets[array_rand($trStreets)];
        
        $year = rand(1960, 2005);
        $month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
        $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        
        $data[] = [
            'ulke' => 'Türkiye',
            'ad' => $firstName,
            'soyad' => $lastName,
            'cinsiyet' => $isMale ? 'Erkek' : 'Kadın',
            'tc_kimlik' => generateTC(),
            'dogum_tarihi' => "$year-$month-$day",
            'dogum_yeri' => $cityName,
            'telefon' => generateTRPhone(),
            'email' => generateEmail($firstName, $lastName, $trDomains),
            'adres' => "$street Mah. " . rand(1, 500) . ". Sk. No:" . rand(1, 200) . " D:" . rand(1, 20) . " $district/$cityName",
            'sehir' => $cityName,
            'ilce' => $district,
            'posta_kodu' => str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT),
            'meslek' => $trJobs[array_rand($trJobs)],
            'kan_grubu' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', '0+', '0-'][rand(0, 7)],
            'medeni_durum' => rand(0, 1) ? 'Bekar' : 'Evli',
        ];
    }
}

$result = [
    'durum' => 'başarı',
    'toplam' => $adet,
    'data' => $adet === 1 ? $data[0] : $data,
    'Developer' => '@TuncaySkay'
];

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
