<?php
header('Content-Type: application/json');

// TODO: You can enable this for real session use
// session_start();
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
//     exit;
// }

// Simulated weather data (replace with real API later)
echo json_encode([
    'success' => true,
    'temp' => 32,
    'condition' => 'Sunny with light breeze'
]);
