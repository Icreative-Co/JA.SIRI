<?php
require_once 'db.php';

/**
 * Load and validate M-Pesa configuration from .env
 */
function mpesaConfig() {
    $c = @parse_ini_file('.env');
    if (!$c) {
        return [null, "M-Pesa configuration file (.env) missing."];
    }

    $required = [
        'MPESA_CONSUMER_KEY',
        'MPESA_CONSUMER_SECRET',
        'MPESA_PASSKEY',
        'CALLBACK_URL',
        'ENV'
    ];

    foreach ($required as $key) {
        if (empty($c[$key])) {
            return [null, "Missing M-Pesa setting: $key"];
        }
    }

    // Normalize host
    $c['BASE_URL'] = ($c['ENV'] === 'live')
        ? 'https://api.safaricom.co.ke'
        : 'https://sandbox.safaricom.co.ke';

    // Shortcode fallback
    $c['MPESA_SHORTCODE'] = $c['MPESA_SHORTCODE'] ?? '174379';

    return [$c, null];
}



/**
 * Get OAuth Token
 */
function getToken() {
    list($c, $err) = mpesaConfig();
    if ($err) return [null, $err];

    $cred = base64_encode($c['MPESA_CONSUMER_KEY'] . ':' . $c['MPESA_CONSUMER_SECRET']);

    $ch = curl_init($c['BASE_URL'].'/oauth/v1/generate?grant_type=client_credentials');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER      => ['Authorization: Basic '.$cred],
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 20,
        CURLOPT_SSL_VERIFYPEER  => true
    ]);

    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($res === false) {
        $errMsg = curl_error($ch);
        curl_close($ch);
        return [null, "Connection error: $errMsg"];
    }

    curl_close($ch);
    $json = json_decode($res, true);

    if ($code >= 200 && $code < 300 && isset($json['access_token'])) {
        return [$json['access_token'], null];
    }

    return [null, "Token request failed. HTTP: $code"];
}



/**
 * STK Push
 */
function stkPush($phone, $amount, $reference = "KSA-ORDER") {
    list($c, $err) = mpesaConfig();
    if ($err) return ['ResponseCode' => '-1', 'CustomerMessage' => $err];

    list($token, $tokenErr) = getToken();
    if ($tokenErr) {
        return ['ResponseCode' => '-1', 'CustomerMessage' => $tokenErr];
    }

    // Clean phone number
    $phone = preg_replace('/\D/', '', $phone);
    if (substr($phone, 0, 1) === '0') $phone = '254'.substr($phone, 1);

    if (strlen($phone) < 12) {
        return ['ResponseCode' => '-1', 'CustomerMessage' => "Invalid phone number"];
    }

    // Prepare password
    $timestamp = date('YmdHis');
    $password = base64_encode(
        $c['MPESA_SHORTCODE'] .
        $c['MPESA_PASSKEY'] .
        $timestamp
    );

    $payload = [
        "BusinessShortCode" => $c['MPESA_SHORTCODE'],
        "Password"          => $password,
        "Timestamp"         => $timestamp,
        "TransactionType"   => "CustomerPayBillOnline",
        "Amount"            => $amount,
        "PartyA"            => $phone,
        "PartyB"            => $c['MPESA_SHORTCODE'],
        "PhoneNumber"       => $phone,
        "CallBackURL"       => rtrim($c['CALLBACK_URL'], '/'),
        "AccountReference"  => substr($reference, 0, 12),
        "TransactionDesc"   => "KSA Store Payment"
    ];

    $ch = curl_init($c['BASE_URL'].'/mpesa/stkpush/v1/processRequest');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER      => [
            'Authorization: Bearer '.$token,
            'Content-Type: application/json'
        ],
        CURLOPT_POST            => true,
        CURLOPT_POSTFIELDS      => json_encode($payload),
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 20,
        CURLOPT_SSL_VERIFYPEER  => true
    ]);

    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($res === false) {
        $errMsg = curl_error($ch);
        curl_close($ch);
        return [
            'ResponseCode'    => '-1',
            'CustomerMessage' => "Connection error: $errMsg"
        ];
    }

    curl_close($ch);

    $json = json_decode($res, true);

    // Log all JSON responses for debugging
    @error_log("[MPESA_STK] HTTP $code => " . json_encode($json));

    if (!$json) {
        return [
            'ResponseCode' => '-1',
            'CustomerMessage' => "Invalid response from M-Pesa"
        ];
    }

    // **Uniform responses checkout.php can use**
    if (isset($json['ResponseCode']) && $json['ResponseCode'] == "0") {
        return [
            'ResponseCode'    => 0,
            'CustomerMessage' => "Check your phone and enter your M-Pesa PIN to complete payment."
        ];
    }

    // Error message fallback
    $msg = $json['errorMessage'] ?? $json['CustomerMessage'] ?? "Payment request failed.";

    return [
        'ResponseCode'    => $json['errorCode'] ?? "-1",
        'CustomerMessage' => $msg
    ];
}
?>
