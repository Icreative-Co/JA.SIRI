<?php
// callback.php - place at PUBLIC CALLBACK_URL, ensure Render route accepts POST
require_once 'db.php';
@error_log("[MPESA_CALLBACK] Received callback at " . date('c'));
$raw = file_get_contents('php://input');
@error_log("[MPESA_CALLBACK_RAW] " . $raw);
if (empty($raw)) {
    http_response_code(400);
    echo json_encode(['result'=>'empty body']);
    exit;
}
$data = json_decode($raw, true);
if (!$data) {
    @error_log("[MPESA_CALLBACK] Invalid JSON");
    http_response_code(400);
    echo json_encode(['result'=>'invalid json']);
    exit;
}

// Safaricom sends Body.stkCallback for STK pushes
$body = $data['Body'] ?? $data;
$stk = $body['stkCallback'] ?? null;
if (!$stk) {
    @error_log("[MPESA_CALLBACK] Not STK callback: " . json_encode(array_keys($body)));
    // respond 200 so Safaricom stops retries
    http_response_code(200);
    echo json_encode(['Result'=>'OK']);
    exit;
}

$merchantRequestID = $stk['MerchantRequestID'] ?? null;
$checkoutRequestID = $stk['CheckoutRequestID'] ?? null;
$resultCode = intval($stk['ResultCode'] ?? -1);
$resultDesc = $stk['ResultDesc'] ?? '';
$meta = $stk['CallbackMetadata']['Item'] ?? null;

$amount = null; $mpesaReceipt = null; $transDate = null; $phone = null;
if (is_array($meta)) {
    foreach ($meta as $item) {
        $name = $item['Name'] ?? null;
        if ($name === 'Amount') $amount = $item['Value'] ?? $amount;
        if ($name === 'MpesaReceiptNumber') $mpesaReceipt = $item['Value'] ?? $mpesaReceipt;
        if ($name === 'TransactionDate') $transDate = $item['Value'] ?? $transDate;
        if ($name === 'PhoneNumber') $phone = $item['Value'] ?? $phone;
    }
}

try {
    // update mpesa_logs if we have a checkoutRequestID
    if ($checkoutRequestID) {
        $stmt = $db->prepare("UPDATE mpesa_logs SET checkout_request_id = ?, merchant_request_id = ?, response = ?, status = ?, mpesa_receipt = ?, amount = ?, phone = ?, updated_at = NOW() WHERE checkout_request_id = ? OR merchant_request_id = ?");
        $stmt->execute([$checkoutRequestID, $merchantRequestID, json_encode($stk), ($resultCode===0?'success':'failed'), $mpesaReceipt, $amount, $phone, $checkoutRequestID, $merchantRequestID]);
        // attempt to find order by checkout_request_id
        $find = $db->prepare("SELECT order_id FROM mpesa_logs WHERE checkout_request_id = ? LIMIT 1");
        $find->execute([$checkoutRequestID]);
        $row = $find->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['order_id']) {
            $order_id = intval($row['order_id']);
            if ($resultCode === 0) {
                $db->prepare("UPDATE orders SET status = ?, mpesa_response = ?, paid_at = NOW(), mpesa_receipt = ? WHERE id = ?")
                   ->execute(['paid', json_encode($stk), $mpesaReceipt, $order_id]);
            } else {
                $db->prepare("UPDATE orders SET status = ?, mpesa_response = ? WHERE id = ?")
                   ->execute(['failed', json_encode($stk), $order_id]);
            }
        }
    } else {
        // fallback: insert log record
        $ins = $db->prepare("INSERT INTO mpesa_logs (order_id, phone, amount, merchant_request_id, checkout_request_id, response, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $ins->execute([null, $phone, $amount, $merchantRequestID, $checkoutRequestID, json_encode($stk), ($resultCode===0?'success':'failed')]);
    }
    http_response_code(200);
    echo json_encode(['Result'=>'OK']);
    exit;
} catch (Exception $e) {
    @error_log("[MPESA_CALLBACK_ERROR] " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['Result'=>'ERROR']);
    exit;
}
