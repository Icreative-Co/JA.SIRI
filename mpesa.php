<?php
// mpesa.php - M-Pesa helper (use require_once 'mpesa.php';)
require_once 'db.php';

function mpesaConfig() {
    $c = @parse_ini_file(__DIR__.'/.env');
    if ($c === false) $c = null;
    // prefer getenv if running on Render where .env file may not exist
    $cfg = [];
    $cfg['MPESA_CONSUMER_KEY'] = $c['MPESA_CONSUMER_KEY'] ?? getenv('MPESA_CONSUMER_KEY');
    $cfg['MPESA_CONSUMER_SECRET'] = $c['MPESA_CONSUMER_SECRET'] ?? getenv('MPESA_CONSUMER_SECRET');
    $cfg['MPESA_PASSKEY'] = $c['MPESA_PASSKEY'] ?? getenv('MPESA_PASSKEY');
    $cfg['MPESA_SHORTCODE'] = $c['MPESA_SHORTCODE'] ?? getenv('MPESA_SHORTCODE') ?? '174379';
    $cfg['CALLBACK_URL'] = $c['CALLBACK_URL'] ?? getenv('CALLBACK_URL');
    $cfg['ENV'] = $c['ENV'] ?? getenv('ENV') ?? 'sandbox';
    if (empty($cfg['MPESA_CONSUMER_KEY']) || empty($cfg['MPESA_CONSUMER_SECRET']) || empty($cfg['MPESA_PASSKEY']) || empty($cfg['CALLBACK_URL'])) {
        return [null, "Missing M-Pesa config; set MPESA_CONSUMER_KEY/MPESA_CONSUMER_SECRET/MPESA_PASSKEY/CALLBACK_URL"];
    }
    $cfg['BASE_URL'] = ($cfg['ENV'] === 'live') ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
    return [$cfg, null];
}

function getToken() {
    list($cfg, $err) = mpesaConfig();
    if ($err) return [null, $err];
    $cred = base64_encode($cfg['MPESA_CONSUMER_KEY'] . ':' . $cfg['MPESA_CONSUMER_SECRET']);
    $url = $cfg['BASE_URL'] . '/oauth/v1/generate?grant_type=client_credentials';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ["Authorization: Basic $cred"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($res === false) {
        $errMsg = curl_error($ch);
        curl_close($ch);
        return [null, "cURL error: $errMsg"];
    }
    curl_close($ch);
    $json = json_decode($res, true);
    if ($code >= 200 && $code < 300 && !empty($json['access_token'])) return [$json['access_token'], null];
    return [null, "Failed to obtain token (HTTP $code)"];
}

/**
 * Initiate STK Push
 * returns associative array with ResponseCode, CustomerMessage and raw response
 */
function stkPush($phone, $amount, $reference = "KSA-ORDER") {
    list($cfg, $err) = mpesaConfig();
    if ($err) return ['ResponseCode'=>'-1','CustomerMessage'=>$err,'raw'=>null];

    list($token, $tokenErr) = getToken();
    if ($tokenErr) return ['ResponseCode'=>'-1','CustomerMessage'=>$tokenErr,'raw'=>null];

    // normalize Kenya phone to 2547XXXXXXXX
    $phone = preg_replace('/\D/','',$phone);
    if (substr($phone,0,1) === '0') $phone = '254'.substr($phone,1);
    if (!preg_match('/^2547\d{8}$/', $phone)) return ['ResponseCode'=>'-1','CustomerMessage'=>'Invalid phone format','raw'=>null];

    $timestamp = date('YmdHis');
    $password = base64_encode($cfg['MPESA_SHORTCODE'] . $cfg['MPESA_PASSKEY'] . $timestamp);

    $payload = [
        "BusinessShortCode" => $cfg['MPESA_SHORTCODE'],
        "Password" => $password,
        "Timestamp" => $timestamp,
        "TransactionType" => "CustomerPayBillOnline",
        "Amount" => intval($amount),
        "PartyA" => $phone,
        "PartyB" => $cfg['MPESA_SHORTCODE'],
        "PhoneNumber" => $phone,
        "CallBackURL" => rtrim($cfg['CALLBACK_URL'],'/'),
        "AccountReference" => substr($reference, 0, 12),
        "TransactionDesc" => $reference
    ];

    $url = $cfg['BASE_URL'] . '/mpesa/stkpush/v1/processrequest';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $token","Content-Type: application/json"],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($res === false) {
        $errMsg = curl_error($ch);
        curl_close($ch);
        return ['ResponseCode'=>'-1','CustomerMessage'=>"cURL error: $errMsg",'raw'=>null];
    }
    curl_close($ch);
    $json = json_decode($res, true);
    @error_log("[MPESA_STK_PUSH] HTTP $code => " . json_encode($json));
    if (!$json) return ['ResponseCode'=>'-1','CustomerMessage'=>'Invalid response from M-Pesa','raw'=>$res];

    // return the DAR response as-is and normalized common fields
    return [
        'ResponseCode' => $json['ResponseCode'] ?? ($json['errorCode'] ?? '-1'),
        'CustomerMessage' => $json['CustomerMessage'] ?? $json['errorMessage'] ?? 'Unknown response',
        'MerchantRequestID' => $json['MerchantRequestID'] ?? null,
        'CheckoutRequestID' => $json['CheckoutRequestID'] ?? null,
        'raw' => $json
    ];
}

/**
 * Query STK push by CheckoutRequestID
 */
function stkQuery($checkoutRequestID) {
    list($cfg, $err) = mpesaConfig();
    if ($err) return ['ResponseCode'=>'-1','CustomerMessage'=>$err];

    list($token, $tokenErr) = getToken();
    if ($tokenErr) return ['ResponseCode'=>'-1','CustomerMessage'=>$tokenErr];

    $timestamp = date('YmdHis');
    $password = base64_encode($cfg['MPESA_SHORTCODE'] . $cfg['MPESA_PASSKEY'] . $timestamp);

    $payload = [
        "BusinessShortCode" => $cfg['MPESA_SHORTCODE'],
        "Password" => $password,
        "Timestamp" => $timestamp,
        "CheckoutRequestID" => $checkoutRequestID
    ];

    $url = $cfg['BASE_URL'] . '/mpesa/stkpushquery/v1/query';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $token","Content-Type: application/json"],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $res = curl_exec($ch);
    if ($res === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['ResponseCode'=>'-1','CustomerMessage'=>"cURL error: $err"];
    }
    curl_close($ch);
    $json = json_decode($res, true);
    return $json ?: ['ResponseCode'=>'-1','CustomerMessage'=>'Invalid response from M-Pesa'];
}
