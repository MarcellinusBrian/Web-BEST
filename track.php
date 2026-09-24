<?php
// Set header agar merespons sebagai JSON
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Tangkap request dari JavaScript
$inputData = json_decode(file_get_contents('php://input'), true);
$hawbNo = isset($inputData['HAWBNo']) ? trim($inputData['HAWBNo']) : '';

if (empty($hawbNo)) {
    echo json_encode(['error' => 'Nomor AWB tidak boleh kosong']);
    exit;
}

// Panggil API Pusat via cURL (Server to Server, Bebas CORS)
$apiUrl = 'http://59.153.83.135/api/best/HAWBStatus';
$payload = json_encode(['HAWBNo' => $hawbNo]);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    echo $response;
} else {
    echo json_encode(['error' => 'Gagal terhubung ke API HAWB']);
}