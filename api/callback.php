<?php
// Simple callback handler for M-Pesa STK push results.
// It logs the raw JSON payload to callback_log.json
$raw = file_get_contents('php://input');
$logfile = __DIR__ . '/../callback_log.json';
file_put_contents($logfile, date('c') . ' ' . $raw . PHP_EOL, FILE_APPEND);

// Reply with HTTP 200 to acknowledge
header('Content-Type: application/json');
http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Callback received']);
