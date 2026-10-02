<?php

ini_set('display_errors', 0);
error_reporting(0);

// Helper function pengganti http_response_code() khusus PHP 5.3
function set_response_code($code)
{
    switch ($code) {
        case 200:
            header('HTTP/1.1 200 OK');
            break;
        case 400:
            header('HTTP/1.1 400 Bad Request');
            break;
        case 405:
            header('HTTP/1.1 405 Method Not Allowed');
            break;
        case 429:
            header('HTTP/1.1 429 Too Many Requests');
            break;
        case 500:
            header('HTTP/1.1 500 Internal Server Error');
            break;
        case 502:
            header('HTTP/1.1 502 Bad Gateway');
            break;
        default:
            header('HTTP/1.1 ' . $code);
            break;
    }
}

// --- 1. SECURITY RESPONSE HEADERS ---
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// --- 2. CORS MANAGEMENT ---
$allowedOrigins = array(
    'https://www.bestranspor.com',
    'https://bestranspor.com'
);

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
} else {
    header('Access-Control-Allow-Origin: https://www.bestranspor.com');
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight request handler
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    set_response_code(200);
    exit;
}

// Validasi Method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_response_code(405);
    echo json_encode(array('error' => 'Metode request tidak diizinkan. Gunakan POST.'));
    exit;
}

// --- 3. RATE LIMITER (Max 15 Req / Min / IP) ---
$userIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
$cacheFile = sys_get_temp_dir() . '/rate_' . md5($userIp);
$timeWindow = 60;
$maxRequests = 15;

$rateData = file_exists($cacheFile) ? json_decode(file_get_contents($cacheFile), true) : array('count' => 0, 'start_time' => time());

if (time() - $rateData['start_time'] < $timeWindow) {
    if ($rateData['count'] >= $maxRequests) {
        set_response_code(429);
        echo json_encode(array('error' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.'));
        exit;
    }
    $rateData['count']++;
} else {
    $rateData['start_time'] = time();
    $rateData['count'] = 1;
}
file_put_contents($cacheFile, json_encode($rateData));

// --- 4. VALIDASI EXTENSION CURL ---
if (!function_exists('curl_init')) {
    error_log('cURL Extension Error: Ekstensi cURL belum aktif di server.');
    set_response_code(500);
    echo json_encode(array('error' => 'Layanan server sedang mengalami kendala teknis.'));
    exit;
}

// --- 5. BACA & SANITASI INPUT BODY ---
$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true);

$hawbNo = isset($inputData['HAWBNo']) ? trim($inputData['HAWBNo']) : '';

if (empty($hawbNo)) {
    set_response_code(400);
    echo json_encode(array('error' => 'Nomor AWB / Resi tidak boleh kosong.'));
    exit;
}

if (strlen($hawbNo) > 15) {
    set_response_code(400);
    echo json_encode(array('error' => 'Format nomor AWB terlalu panjang.'));
    exit;
}

if (!preg_match('/^[a-zA-Z0-9\-]+$/', $hawbNo)) {
    set_response_code(400);
    echo json_encode(array('error' => 'Nomor AWB mengandung karakter tidak valid.'));
    exit;
}

$hawbNo = strtoupper($hawbNo);

// --- 6. PROXY CALL KE API CENTRAL ---
$apiUrl = 'http://59.153.83.135/api/best/HAWBStatus';
$payload = json_encode(array('HAWBNo' => $hawbNo));

$ch = curl_init($apiUrl);
curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
));

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError) {
    error_log("Tracking API cURL Error for HAWB [{$hawbNo}]: " . $curlError);
    set_response_code(502);
    echo json_encode(array('error' => 'Gagal terhubung ke server pusat tracking. Silakan coba beberapa saat lagi.'));
    exit;
}

// --- 7. VALIDASI & RE-ENCODE JSON RESPONSE ---
if ($httpCode === 200 && $response) {
    $decodedResponse = json_decode($response, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        set_response_code(200);
        echo json_encode($decodedResponse);
    } else {
        error_log("Tracking API Central returned non-JSON response for HAWB [{$hawbNo}]. Preview: " . substr($response, 0, 100));
        set_response_code(502);
        echo json_encode(array('error' => 'Format respons dari server pusat tidak sesuai.'));
    }
} else {
    error_log("Tracking API Central returned HTTP Status [{$httpCode}] for HAWB [{$hawbNo}]");
    set_response_code(502);
    echo json_encode(array('error' => 'Layanan API Pusat sedang tidak dapat memproses permintaan.'));
}
