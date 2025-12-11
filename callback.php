<?php
require_once 'db.php';
file_put_contents('log.txt', date('Y-m-d H:i:s')." RAW: ".file_get_contents('php://input')."\n\n", FILE_APPEND);

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || ($data['Body']['stkCallback']['ResultCode'] ?? 1) != 0) {
    http_response_code(400); exit;
}

$items = $data['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];
$amount = $receipt = $phone = null;

foreach ($items as $i) {
    if ($i['Name'] === 'Amount') $amount = $i['Value'];
    if ($i['Name'] === 'MpesaReceiptNumber') $receipt = $i['Value'];
    if ($i['Name'] === 'PhoneNumber') $phone = $i['Value'];
}

if ($amount && $phone) {
    // Save payment
    $db->prepare("INSERT INTO payments (phone, amount, receipt) VALUES (?, ?, ?)")->execute([$phone, $amount, $receipt]);

    // Update or create customer total
    $db->prepare("INSERT INTO customers (phone, total_paid) VALUES (?, ?) 
                  ON DUPLICATE KEY UPDATE total_paid = total_paid + ?")->execute([$phone, $amount, $amount]);
}

echo json_encode(['ResultCode'=>0, 'ResultDesc'=>'Accepted']);