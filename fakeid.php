import requests
from colorama import Fore
import random
import string
from datetime import datetime, timedelta

F = '\033[1;92m'
R = '\033[1;91m'
C = '\033[1;96m'
Y = '\033[1;33m'
M = '\033[1;95m'
B = '\033[1;94m'
W = '\033[1;97m'
RESET = '\033[0m'

banner = """
╔══════════════════════════════════╗
║   🎭 FAKE ID GENERATOR          ║
║   Gerçekçi Sahte Kimlik         ║
╚══════════════════════════════════╝
"""
print(F + banner)
print(C + '  Seçenekleri belirle, ID üret')
print('─' * 50)

# Veritabanları
FIRST_NAMES_TR = [
    'Ahmet', 'Mehmet', 'Mustafa', 'Ali', 'Hüseyin', 'Hasan', 'İbrahim', 'İsmail', 'Yusuf', 'Murat',
    'Ömer', 'Fatma', 'Ayşe', 'Emine', 'Hatice', 'Zeynep', 'Elif', 'Merve', 'Büşra', 'Esra',
    'Kadir', 'Berk', 'Can', 'Deniz', 'Emre', 'Furkan', 'Gökhan', 'Hakan', 'Kerem', 'Levent',
    'Mert', 'Onur', 'Özgür', 'Serkan', 'Tolga', 'Umut', 'Volkan', 'Yasin', 'Zafer', 'Burak',
    'Selin', 'İrem', 'Gizem', 'Derya', 'Pınar', 'Seda', 'Tuğba', 'Yasemin', 'Ceren', 'Ebru',
]

LAST_NAMES_TR = [
    'Yılmaz', 'Kaya', 'Demir', 'Şahin', 'Çelik', 'Yıldız', 'Yıldırım', 'Öztürk', 'Aydın', 'Özdemir',
    'Arslan', 'Doğan', 'Kılıç', 'Aslan', 'Çetin', 'Kara', 'Koç', 'Kurt', 'Özkan', 'Şimşek',
    'Polat', 'Akın', 'Korkmaz', 'Çakır', 'Erdoğan', 'Avcı', 'Şen', 'Taş', 'Tekin', 'Bulut',
]

CITIES_TR = [
    ('İstanbul', ['Kadıköy', 'Beşiktaş', 'Üsküdar', 'Maltepe', 'Ataşehir']),
    ('Ankara', ['Çankaya', 'Keçiören', 'Mamak', 'Yenimahalle', 'Etimesgut']),
    ('İzmir', ['Karşıyaka', 'Bornova', 'Buca', 'Konak', 'Çiğli']),
    ('Bursa', ['Osmangazi', 'Nilüfer', 'Yıldırım', 'Mudanya', 'Gemlik']),
    ('Antalya', ['Muratpaşa', 'Konyaaltı', 'Kepez', 'Lara', 'Alanya']),
    ('Adana', ['Seyhan', 'Çukurova', 'Yüreğir', 'Sarıçam', 'Karaisalı']),
    ('Gaziantep', ['Şahinbey', 'Şehitkamil', 'Nizip', 'İslahiye', 'Nurdağı']),
]

DOMAINS = ['gmail.com', 'hotmail.com', 'yahoo.com', 'outlook.com', 'icloud.com']
JOBS = ['Mühendis', 'Öğretmen', 'Doktor', 'Avukat', 'Mimar', 'Yazılımcı', 'Serbest', 'Esnaf', 'Memur', 'İşçi']

def generate_tc():
    """Gerçek algoritmaya uygun TC Kimlik No"""
    digits = [random.randint(1, 9)]
    for _ in range(8):
        digits.append(random.randint(0, 9))
    
    # 10. hane
    odd_sum = sum(digits[0:9:2])
    even_sum = sum(digits[1:8:2])
    digit10 = (odd_sum * 7 - even_sum) % 10
    digits.append(digit10)
    
    # 11. hane
    digit11 = sum(digits[:10]) % 10
    digits.append(digit11)
    
    return ''.join(map(str, digits))

def generate_phone():
    prefixes = ['530', '531', '532', '533', '534', '535', '536', '537', '538', '539',
                '540', '541', '542', '543', '544', '545', '546', '547', '548', '549',
                '550', '551', '552', '553', '554', '555', '556', '557', '558', '559']
    prefix = random.choice(prefixes)
    suffix = ''.join(random.choices(string.digits, k=7))
    return f'0{prefix}{suffix}'

def generate_email(first, last):
    first_lower = first.lower().replace('ü','u').replace('ö','o').replace('ı','i').replace('ş','s').replace('ğ','g').replace('ç','c')
    last_lower = last.lower().replace('ü','u').replace('ö','o').replace('ı','i').replace('ş','s').replace('ğ','g').replace('ç','c')
    
    patterns = [
        f'{first_lower}.{last_lower}',
        f'{first_lower}{last_lower}',
        f'{first_lower}_{last_lower}',
        f'{first_lower}{random.randint(1,999)}',
    ]
    
    return f'{random.choice(patterns)}@{random.choice(DOMAINS)}'

def generate_address(city_info):
    city, districts = city_info
    district = random.choice(districts)
    streets = ['Atatürk', 'Cumhuriyet', 'İnönü', 'Bağdat', 'İstiklal', 'Lale', 'Menekşe', 'Gül', 'Papatya', 'Zambak']
    street = random.choice(streets)
    no = random.randint(1, 500)
    return f'{street} Cad. No:{no} D:{random.randint(1,20)} {district}/{city}'

def generate_fake_id(gender='random'):
    if gender == 'random':
        gender = random.choice(['male', 'female'])
    
    if gender == 'male':
        name_pool = FIRST_NAMES_TR[:25]  # Erkek isimleri
    else:
        name_pool = FIRST_NAMES_TR[25:]  # Kadın isimleri
    
    first_name = random.choice(name_pool)
    last_name = random.choice(LAST_NAMES_TR)
    city_info = random.choice(CITIES_TR)
    
    # Doğum tarihi (18-65 yaş)
    today = datetime.now()
    age = random.randint(18, 65)
    birth_date = today - timedelta(days=age*365 + random.randint(0, 364))
    birth_str = birth_date.strftime('%d.%m.%Y')
    
    return {
        'ad_soyad': f'{first_name} {last_name}',
        'ad': first_name,
        'soyad': last_name,
        'cinsiyet': 'Erkek' if gender == 'male' else 'Kadın',
        'tc_kimlik': generate_tc(),
        'dogum_tarihi': birth_str,
        'dogum_yeri': random.choice(CITIES_TR)[0],
        'telefon': generate_phone(),
        'email': generate_email(first_name, last_name),
        'adres': generate_address(city_info),
        'sehir': city_info[0],
        'ilce': random.choice(city_info[1]),
        'posta_kodu': f'{random.randint(10000, 99999)}',
        'meslek': random.choice(JOBS),
        'kan_grubu': random.choice(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', '0+', '0-']),
        'medeni_durum': random.choice(['Bekar', 'Evli']),
    }


while True:
    print(C + '\n📌 Seçenek: erkek/kadın/rastgele (veya 0=çıkış)' + RESET)
    choice = input(C + '▸ Cinsiyet (0=çıkış): ' + RESET).strip().lower()
    
    if choice == '0': break
    if not choice: choice = 'random'
    
    count = input(C + '▸ Kaç adet? (1-10): ' + RESET).strip()
    try:
        count = int(count) if count else 1
        count = max(1, min(10, count))
    except:
        count = 1
    
    print(F + f'\n🎭 {count} adet Fake ID üretiliyor...\n' + RESET)
    
    for i in range(count):
        person = generate_fake_id(choice)
        
        print(C + '═' * 50 + RESET)
        print(F + f'🪪 KİMLİK #{i+1}' + RESET)
        print(C + '═' * 50 + RESET)
        
        print(W + f'   👤 Ad Soyad:    {person["ad_soyad"]}' + RESET)
        print(W + f'   🆔 TC Kimlik:   {person["tc_kimlik"]}' + RESET)
        print(W + f'   🎂 Doğum:       {person["dogum_tarihi"]} - {person["dogum_yeri"]}' + RESET)
        print(W + f'   ⚧ Cinsiyet:    {person["cinsiyet"]}' + RESET)
        print(W + f'   📱 Telefon:     {person["telefon"]}' + RESET)
        print(W + f'   📧 Email:       {person["email"]}' + RESET)
        print(W + f'   📍 Adres:       {person["adres"]}' + RESET)
        print(W + f'   💼 Meslek:      {person["meslek"]}' + RESET)
        print(W + f'   🩸 Kan Grubu:   {person["kan_grubu"]}' + RESET)
        print(W + f'   💍 Medeni Hal:  {person["medeni_durum"]}' + RESET)
        print(W + f'   📮 Posta Kodu:  {person["posta_kodu"]}' + RESET)
        print()

print(F + '\n✓ Program sonlandı.' + RESET)