<?php

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Jika sudah produksi, ganti '*' dengan domain websitemu (misal: https://ptbest.co.id)
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Validasi Metode Request (Hanya POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metode request tidak diizinkan. Gunakan POST.']);
    exit;
}

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

if (strlen($hawbNo) > 30) {
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
    CURLOPT_CONNECTTIMEOUT => 5, // Timeout batas waktu percobaan koneksi awal
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError) {
    // Catat detail error teknis asli ke log internal server (Aman, tidak terkespesos ke browser/Inspect Element)
    error_log("Tracking API cURL Error for HAWB [{$hawbNo}]: " . $curlError);

    // Kirim pesan umum yang aman ke frontend
    http_response_code(502);
    echo json_encode(['error' => 'Gagal terhubung ke server pusat tracking. Silakan coba beberapa saat lagi.']);
    exit;
}

if ($httpCode === 200 && $response) {
    http_response_code(200);
    echo $response;
} else {
    // Log error HTTP dari API Pusat ke log internal server
    error_log("Tracking API Central returned HTTP Status [{$httpCode}] for HAWB [{$hawbNo}]");

    http_response_code(502);
    echo json_encode(['error' => 'Layanan API Pusat sedang tidak dapat memproses permintaan.']);
}
