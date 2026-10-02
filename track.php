<?php

ini_set('display_errors', 0);
error_reporting(0);

$allowedOrigins = [
    'https://www.bestranspor.com',
    'https://bestranspor.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
} else {
    header('Access-Control-Allow-Origin: https://www.bestranspor.com');
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Validasi Metode (POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metode request tidak diizinkan. Gunakan POST.']);
    exit;
}

$userIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$cacheFile = sys_get_temp_dir() . '/rate_' . md5($userIp);
$timeWindow = 60;
$maxRequests = 15;

$rateData = file_exists($cacheFile) ? json_decode(file_get_contents($cacheFile), true) : ['count' => 0, 'start_time' => time()];

if (time() - $rateData['start_time'] < $timeWindow) {
    if ($rateData['count'] >= $maxRequests) {
        http_response_code(429);
        echo json_encode(['error' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.']);
        exit;
    }
    $rateData['count']++;
} else {
    $rateData['start_time'] = time();
    $rateData['count'] = 1;
}
file_put_contents($cacheFile, json_encode($rateData));

// Ekstensi cURL Aktif
if (!function_exists('curl_init')) {
    error_log('cURL Extension Error: Ekstensi cURL belum aktif di server.');
    http_response_code(500);
    echo json_encode(['error' => 'Layanan server sedang mengalami kendala teknis.']);
    exit;
}

// Baca & Sanitasi Input Body
$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true);

$hawbNo = isset($inputData['HAWBNo']) ? trim($inputData['HAWBNo']) : '';

if (empty($hawbNo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nomor AWB / Resi tidak boleh kosong.']);
    exit;
}

if (strlen($hawbNo) > 15) {
    http_response_code(400);
    echo json_encode(['error' => 'Format nomor AWB terlalu panjang.']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9\-]+$/', $hawbNo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nomor AWB mengandung karakter tidak valid.']);
    exit;
}

$hawbNo = strtoupper($hawbNo);

$apiUrl = 'http://59.153.83.135/api/best/HAWBStatus';
$payload = json_encode(['HAWBNo' => $hawbNo]);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_CONNECTTIMEOUT => 5, // Timeout untuk koneksi
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError) {
    // Catat detail error ke log internal server
    error_log("Tracking API cURL Error for HAWB [{$hawbNo}]: " . $curlError);

    // Kirim pesan umum yang aman ke frontend
    http_response_code(502);
    echo json_encode(['error' => 'Gagal terhubung ke server pusat tracking. Silakan coba beberapa saat lagi.']);
    exit;
}

if ($httpCode === 200 && $response) {
    $decodedResponse = json_decode($response, true);

    // Cek apakah string benar-benar berformat JSON valid
    if (json_last_error() === JSON_ERROR_NONE) {
        http_response_code(200);
        echo json_encode($decodedResponse);
    } else {
        // Catat di log bahwa server pusat mengirim respons yang rusak/HTML
        error_log("Tracking API Central returned non-JSON response for HAWB [{$hawbNo}]. Raw response preview: " . substr($response, 0, 100));

        http_response_code(502);
        echo json_encode(['error' => 'Format respons dari server pusat tidak sesuai.']);
    }
} else {
    error_log("Tracking API Central returned HTTP Status [{$httpCode}] for HAWB [{$hawbNo}]");

    http_response_code(502);
    echo json_encode(['error' => 'Layanan API Pusat sedang tidak dapat memproses permintaan.']);
}
