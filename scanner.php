<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$domain = isset($_GET['domain']) ? trim($_GET['domain']) : null;

if (!$domain) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'domain parametresi gerekli'], JSON_UNESCAPED_UNICODE);
    exit;
}

$domain = str_replace(['https://', 'http://'], '', $domain);
$domain = rtrim($domain, '/');

$result = [
    'domain' => $domain,
    'ip' => null,
    'country' => null,
    'city' => null,
    'isp' => null,
    'cloudflare' => false,
    'ssl' => null,
    'technologies' => [],
    'security' => [],
    'ports' => [],
    'dns' => [],
    'reverse' => [],
    'subdomains' => [],
    'emails' => [],
    'whois' => [],
];

// IP + Konum
$ip = @gethostbyname($domain);
if ($ip && $ip != $domain) {
    $result['ip'] = $ip;
    $geo = @file_get_contents("http://ip-api.com/json/$ip");
    if ($geo) {
        $geoData = json_decode($geo, true);
        $result['country'] = $geoData['country'] ?? null;
        $result['city'] = $geoData['city'] ?? null;
        $result['isp'] = $geoData['isp'] ?? null;
    }
}

// HTTP Headers + Teknolojiler
$ctx = stream_context_create(['http' => ['timeout' => 5], 'ssl' => ['verify_peer' => false]]);
$html = @file_get_contents("https://$domain", false, $ctx);

if ($html !== false) {
    // Cloudflare
    $result['cloudflare'] = stripos($html, 'cloudflare') !== false;
    
    // WordPress
    if (stripos($html, 'wp-content') !== false) $result['technologies'][] = 'WordPress';
    if (stripos($html, 'react') !== false) $result['technologies'][] = 'React';
    if (stripos($html, 'next') !== false) $result['technologies'][] = 'Next.js';
    if (stripos($html, 'google-analytics') !== false || stripos($html, 'gtag') !== false) 
        $result['technologies'][] = 'Google Analytics';
    
    // Emails
    preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $html, $emails);
    $result['emails'] = array_slice(array_unique($emails[0]), 0, 5);
}

// SSL
$ssl = @stream_socket_client("ssl://$domain:443", $errno, $errstr, 5);
if ($ssl) {
    $cert = stream_context_get_params($ssl);
    @fclose($ssl);
    $result['ssl'] = 'Var';
} else {
    $result['ssl'] = 'Yok';
}

// Güvenlik kontrolleri
$checks = ['robots.txt', 'sitemap.xml', '.git/HEAD', '.env', 'wp-admin'];
foreach ($checks as $check) {
    $ch = @file_get_contents("https://$domain/$check", false, $ctx);
    $result['security'][$check] = $ch !== false ? '⚠️ Açık' : '✅ Güvenli';
}

// Port taraması (basit)
$ports = [80, 443, 8080, 8443];
foreach ($ports as $port) {
    $fp = @fsockopen($domain, $port, $errno, $errstr, 1);
    if ($fp) {
        $result['ports'][] = $port;
        @fclose($fp);
    }
}

// DNS
$dnsTypes = ['A', 'MX', 'NS'];
foreach ($dnsTypes as $type) {
    $dns = dns_get_record($domain, constant("DNS_$type"));
    if ($dns) {
        $result['dns'][$type] = array_slice(array_column($dns, 'target' ?: 'ip'), 0, 3);
    }
}

// Reverse IP + Whois
if ($ip) {
    $reverse = @file_get_contents("https://api.hackertarget.com/reverseiplookup/?q=$ip");
    if ($reverse) $result['reverse'] = array_slice(explode("\n", trim($reverse)), 0, 10);
    
    $whois = @file_get_contents("https://api.hackertarget.com/whois/?q=$domain");
    if ($whois) {
        foreach (['Registrar', 'Creation Date', 'Registry Expiry Date'] as $key) {
            if (preg_match("/$key[:\s]+(.+)/i", $whois, $m))
                $result['whois'][$key] = trim($m[1]);
        }
    }
}

// Subdomainler
$crt = @file_get_contents("https://crt.sh/?q=%25.$domain&output=json");
if ($crt) {
    $crtData = json_decode($crt, true);
    $result['subdomains'] = array_slice(array_unique(array_column($crtData, 'name_value')), 0, 10);
}

echo json_encode([
    'durum' => 'başarı',
    'data' => $result,
    'Developer' => '@TuncaySkay'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>