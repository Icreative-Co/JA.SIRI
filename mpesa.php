<?php
require_once 'db.php';

function getToken() {
    $c = @parse_ini_file('.env');
    if (!$c || !isset($c['MPESA_CONSUMER_KEY'], $c['MPESA_CONSUMER_SECRET'], $c['ENV'])) {
        return null;
    }
    $url = $c['ENV'] === 'live' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
    $cred = base64_encode($c['MPESA_CONSUMER_KEY'].':'.$c['MPESA_CONSUMER_SECRET']);
    $ch = curl_init($url.'/oauth/v1/generate?grant_type=client_credentials');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Authorization: Basic '.$cred],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FAILONERROR => false
    ]);
    $res = curl_exec($ch);
    if ($res === false) {
        curl_close($ch);
        return null;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($res);
    if ($httpCode >= 200 && $httpCode < 300 && isset($data->access_token)) {
        return $data->access_token;
    }
    return null;
}

function stkPush($phone, $amount, $reference = "KSA-ORDER") {
    $c = @parse_ini_file('.env');
    if (!$c) return ['CustomerMessage' => 'MPesa configuration missing'];
    $token = getToken();
    if (!$token) return ['CustomerMessage' => 'Token failed'];

    $url = $c['ENV'] === 'live' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
    // Use configured shortcode or fall back to sandbox shortcode
    $shortcode = (!empty($c['MPESA_SHORTCODE'])) ? $c['MPESA_SHORTCODE'] : '174379';
    if (empty($c['MPESA_SHORTCODE'])) {
        @error_log("[MPESA_STK_PUSH] MPESA_SHORTCODE not set in .env, falling back to sandbox shortcode $shortcode");
    }
    $ts = date('YmdHis');
    $pass = base64_encode($shortcode . ($c['MPESA_PASSKEY'] ?? '') . $ts);

    $payload = [
        "BusinessShortCode" => $shortcode,
        "Password" => $pass,
        "Timestamp" => $ts,
        "TransactionType" => "CustomerPayBillOnline",
        "Amount" => $amount,
        "PartyA" => $phone,
        "PartyB" => $shortcode,
        "PhoneNumber" => $phone,
        "CallBackURL" => rtrim($c['CALLBACK_URL'], '/'),
        "AccountReference" => substr($reference, 0, 12),
        "TransactionDesc" => "KSA Store Payment"
    ];

    $ch = curl_init($url.'/mpesa/stkpush/v1/processRequest');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$token, 'Content-Type: application/json'],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FAILONERROR => false,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        @error_log("[MPESA_STK_PUSH] Curl error: $err");
        return ['ResponseCode' => '-1', 'CustomerMessage' => 'Curl error: '.$err];
    }
    curl_close($ch);
    $result = json_decode($response, true);
    if ($httpCode >= 400) {
        @error_log("[MPESA_STK_PUSH] HTTP $httpCode: " . json_encode($result));
    }
    return $result ?: ['ResponseCode' => '-1', 'CustomerMessage' => 'Empty response from M-Pesa'];
}
?>