<?php

ini_set('display_errors', 0);
error_reporting(0);


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

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

header('Access-Control-Allow-Origin: *');
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
    echo json_encode(array(
        'debug_error'    => 'Metode request tidak diizinkan. Gunakan POST.',
        'request_method' => $_SERVER['REQUEST_METHOD']
    ));
    exit;
}

// RATE LIMITER
$userIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
$cacheFile = sys_get_temp_dir() . '/rate_' . md5($userIp);
$timeWindow = 60;
$maxRequests = 15;

$rateData = file_exists($cacheFile) ? json_decode(file_get_contents($cacheFile), true) : array('count' => 0, 'start_time' => time());

if (time() - $rateData['start_time'] < $timeWindow) {
    if ($rateData['count'] >= $maxRequests) {
        set_response_code(429);
        echo json_encode(array(
            'debug_error' => 'Terlalu banyak permintaan (Rate Limit Exceeded). Coba lagi nanti.',
            'user_ip'     => $userIp,
            'total_req'   => $rateData['count']
        ));
        exit;
    }
    $rateData['count']++;
} else {
    $rateData['start_time'] = time();
    $rateData['count'] = 1;
}
file_put_contents($cacheFile, json_encode($rateData));

// VALIDASI EXTENSION CURL
if (!function_exists('curl_init')) {
    set_response_code(500);
    echo json_encode(array('debug_error' => 'Ekstensi cURL belum aktif di server PHP ini.'));
    exit;
}

// BACA & SANITASI INPUT BODY
$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true);

$hawbNo = isset($inputData['HAWBNo']) ? trim($inputData['HAWBNo']) : '';

if (empty($hawbNo)) {
    set_response_code(400);
    echo json_encode(array(
        'debug_error' => 'Nomor AWB / Resi tidak boleh kosong.',
        'raw_input'   => $inputRaw
    ));
    exit;
}

if (strlen($hawbNo) > 15) {
    set_response_code(400);
    echo json_encode(array(
        'debug_error' => 'Format nomor AWB terlalu panjang (>15 karakter).',
        'hawb_len'    => strlen($hawbNo)
    ));
    exit;
}

if (!preg_match('/^[a-zA-Z0-9\-]+$/', $hawbNo)) {
    set_response_code(400);
    echo json_encode(array(
        'debug_error' => 'Nomor AWB mengandung karakter tidak valid.',
        'hawb_input'  => $hawbNo
    ));
    exit;
}

$hawbNo = strtoupper($hawbNo);

// PROXY CALL KE API CENTRAL
$apiUrl = 'http://59.153.83.135/api/best/HAWBStatus';
$payload = json_encode(array('HAWBNo' => $hawbNo));

$ch = curl_init($apiUrl);
curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    CURLOPT_HTTPHEADER     => array(
        'Content-Type: application/json',
        'Accept: application/json'
    ),
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_CONNECTTIMEOUT => 10,
));

$response = curl_exec($ch);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// BILA GAGAL TERHUBUNG
if ($curlError) {
    set_response_code(502);
    echo json_encode(array(
        'debug_error' => 'Gagal terhubung ke API Pusat (cURL Error)',
        'curl_errno'  => $curlErrno,
        'curl_error'  => $curlError,
        'target_url'  => $apiUrl,
        'hawb_no'     => $hawbNo
    ));
    exit;
}

// VALIDASI & RE-ENCODE RESPONSE (FULL DEBUG DETAIL)
if ($httpCode === 200 && $response) {
    $trimmedResponse = trim($response);

    // Jika API Pusat mengembalikan XML (diawali karakter '<')
    if (substr($trimmedResponse, 0, 1) === '<') {
        $xml = simplexml_load_string($trimmedResponse);
        if ($xml !== false) {
            $innerContent = trim((string)$xml);
            $innerDecoded = json_decode($innerContent, true);

            // Jika di dalam tag XML ada string JSON valid
            if (json_last_error() === JSON_ERROR_NONE) {
                set_response_code(200);
                echo json_encode($innerDecoded);
            } else {
                set_response_code(200);
                echo json_encode($xml);
            }
            exit;
        } else {
            set_response_code(502);
            echo json_encode(array(
                'debug_error'  => 'Format XML dari server pusat tidak valid / gagal diparsing',
                'raw_response' => $trimmedResponse
            ));
            exit;
        }
    }

    // Jika API Pusat mengembalikan JSON murni
    $decodedResponse = json_decode($trimmedResponse, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        set_response_code(200);
        echo json_encode($decodedResponse);
    } else {
        set_response_code(502);
        echo json_encode(array(
            'debug_error'  => 'Respon server pusat bukan format JSON/XML valid',
            'raw_response' => $trimmedResponse
        ));
    }
} else {
    set_response_code(502);
    echo json_encode(array(
        'debug_error'  => 'API Pusat mengembalikan status HTTP non-200',
        'http_status'  => $httpCode,
        'target_url'   => $apiUrl,
        'raw_response' => $response ? $response : '(KOSONG / UNRESPONSIVE)'
    ));
}
