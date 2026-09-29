<?php

ini_set('display_errors', 0);
error_reporting(0);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

if (!function_exists('curl_init')) {
    echo json_encode(array('error' => 'Ekstensi PHP cURL belum diaktifkan di server produksi.'));
    exit;
}

$inputData = json_decode(file_get_contents('php://input'), true);
$hawbNo = isset($inputData['HAWBNo']) ? trim($inputData['HAWBNo']) : '';

if (empty($hawbNo)) {
    echo json_encode(array('error' => 'Nomor AWB tidak boleh kosong'));
    exit;
}

$apiUrl = 'http://59.153.83.135/api/best/HAWBStatus';
$payload = json_encode(array('HAWBNo' => $hawbNo));

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Tangkap error cURL jika koneksi diblokir / timeout
if ($curlError) {
    echo json_encode(array('error' => 'Gagal melakukan cURL ke API Pusat: ' . $curlError));
    exit;
}

if ($httpCode === 200 && $response) {
    echo $response;
} else {
    echo json_encode(array('error' => 'API Pusat mengembalikan HTTP Status: ' . $httpCode));
}