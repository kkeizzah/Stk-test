<?php
/**
 * M-Pesa STK Push Implementation for HELB Disbursement
 */

// Set content type
header('Content-Type: application/json');

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Validate required parameters
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
$amount = isset($_POST['amount']) ? intval($_POST['amount']) : null;

if (!$phone || !$amount) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing phone or amount']);
    exit;
}

// Validate phone format
if (!preg_match('/^2547\d{8}$/', $phone)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid phone format. Use format: 2547XXXXXXXX']);
    exit;
}

// Validate amount
if ($amount <= 0 || $amount > 150000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid amount. Must be between 1 and 150,000']);
    exit;
}

try {
    // Get access token
    $accessToken = getAccessToken();
    
    // Initiate STK push
    $response = initiateSTKPush($accessToken, $phone, $amount);
    
    // Return success response
    echo json_encode([
        'ok' => true, 
        'checkout_id' => $response['CheckoutRequestID'],
        'response_code' => $response['ResponseCode'],
        'message' => 'STK push initiated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

/**
 * Get OAuth access token from Daraja API
 */
function getAccessToken() {
    global $sandbox, $consumerKey, $consumerSecret;
    
    $authURL = $sandbox 
        ? 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
        : 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $authURL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        throw new Exception('Auth request failed: ' . $err);
    }
    
    $tokenData = json_decode($response, true);
    if (!isset($tokenData['access_token'])) {
        throw new Exception('Failed to obtain access token: ' . $response);
    }
    
    return $tokenData['access_token'];
}

/**
 * Initiate STK Push request
 */
function initiateSTKPush($accessToken, $phone, $amount) {
    global $sandbox, $shortcode, $passkey, $callbackURL, $accountRef;
    
    $stkURL = $sandbox
        ? 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
        : 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    
    // Generate password
    $timestamp = date('YmdHis');
    $password = base64_encode($shortcode . $passkey . $timestamp);
    
    // Prepare payload
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($err) {
        throw new Exception('STK request failed: ' . $err);
    }
    
    $responseData = json_decode($response, true);
    
    if ($httpCode !== 200 || !isset($responseData['ResponseCode'])) {
        throw new Exception('STK push failed: ' . $response);
    }
    
    if ($responseData['ResponseCode'] !== '0') {
        throw new Exception('STK push error: ' . $responseData['ResponseDescription']);
    }
    
    return $responseData;
}
?>
