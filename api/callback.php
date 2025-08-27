<?php
/**
 * M-Pesa STK Push Callback Handler
 * Logs callback data from Safaricom Daraja API
 */

// Set content type
header('Content-Type: application/json');

// Get the raw POST data
$rawData = file_get_contents('php://input');

// Validate we received data
if (empty($rawData)) {
    http_response_code(400);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Empty callback data']);
    exit;
}

// Decode the JSON data
$callbackData = json_decode($rawData, true);

// Prepare log entry
$logEntry = [
    'timestamp' => date('c'),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'data' => $callbackData
];

// Write to log file (ensure logs directory exists and is writable)
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logFile = $logDir . '/callback_log.json';
file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND);

// Send success response
http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Callback received successfully']);
?>
