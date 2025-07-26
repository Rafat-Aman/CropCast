<?php
header('Content-Type: application/json');

// 🔽 Load API key securely
require '../.gitignore/config.php'; // adjust path if needed

$lat = $_GET['lat'] ?? '23.8103';
$lon = $_GET['lon'] ?? '90.4125';

$url = "https://api.agromonitoring.com/agro/1.0/weather?lat={$lat}&lon={$lon}&appid={$AGRO_API_KEY}";

$response = file_get_contents($url);

if ($response === FALSE) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch from Agro API']);
    exit;
}

echo $response;
