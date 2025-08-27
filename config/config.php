<?php
/**
 * M-Pesa Daraja API Configuration
 * 
 * Copy this file to config.php and update with your actual credentials
 * NEVER commit actual credentials to version control
 */

// Sandbox mode (true for testing, false for production)
$sandbox = true;

// Daraja API credentials
$consumerKey = 'YOUR_CONSUMER_KEY_HERE';
$consumerSecret = 'YOUR_CONSUMER_SECRET_HERE';

// Shortcode (Till or Paybill number)
$shortcode = '6434270';

// Passkey
$passkey = 'YOUR_PASSKEY_HERE';

// Callback URL (must be HTTPS)
$callbackURL = 'https://yourdomain.com/api/callback.php';

// Account reference
$accountRef = 'HELB Disbursement';

// Log directory
$logDir = __DIR__ . '/../logs';
?>
