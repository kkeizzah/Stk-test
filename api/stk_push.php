<?php
header('Content-Type: application/json');

// ===== CONFIG =====
// Leave sandbox=true for testing. Switch to false for production (live endpoints & live credentials).
$sandbox = true;

// Put your Daraja credentials here. DO NOT commit live credentials to public repos.
$consumerKey = '';
$consumerSecret = '';
$shortcode = '6434270'; // Till or Paybill
$passkey = '';
$callbackURL = ''; // e.g. https://yourdomain.com/api/callback.php
$accountRef = 'HELB Disbursement';

// ===== END CONFIG =====

// Simple POST validation
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
$amount = isset($_POST['amount']) ? intval($_POST['amount']) : null;

if (!$phone || !$amount) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing phone or amount']);
    exit;
}

if (!preg_match('/^2547\d{8}$/', $phone)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid phone format']);
    exit;
}

// Choose endpoints
if ($sandbox) {
    $authURL = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $stkURL = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
} else {
    $authURL = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $stkURL = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
}

// 1) Get OAuth token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $authURL);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf8']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
$result = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'Auth request failed', 'detail' => $err]);
    exit;
}

$tokenData = json_decode($result, true);
if (!isset($tokenData['access_token'])) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'Failed to obtain access token', 'raw' => $result]);
    exit;
}
$accessToken = $tokenData['access_token'];

// 2) Prepare STK Push payload
$timestamp = date('YmdHis');
$password = base64_encode($shortcode . $passkey . $timestamp);

$payload = [
    'BusinessShortCode' => $shortcode,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerBuyGoodsOnline',
    'Amount' => $amount,
    'PartyA' => $phone,
    'PartyB' => $shortcode,
    'PhoneNumber' => $phone,
    'CallBackURL' => $callbackURL,
    'AccountReference' => $accountRef,
    'TransactionDesc' => 'HELB Disbursement'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $stkURL);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $accessToken
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'STK request failed', 'detail' => $err]);
    exit;
}

// Try to decode response
$respData = json_decode($response, true);
if ($httpCode >= 200 && $httpCode < 300) {
    // Return the raw response; frontend expects { ok: true, checkout_id: ... } on success from your server
    // Some implementations wrap response; we normalize:
    $out = ['ok' => true, 'raw' => $respData];
    if (isset($respData['CheckoutRequestID'])) $out['checkout_id'] = $respData['CheckoutRequestID'];
    if (isset($respData['ResponseCode'])) $out['response_code'] = $respData['ResponseCode'];
    echo json_encode($out);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'http' => $httpCode, 'raw' => $respData]);
}
