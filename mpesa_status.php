<?php
require_once 'mpesa.php';
if (empty($_GET['id'])) { echo "pass ?id=CheckoutRequestID"; exit; }
$id = $_GET['id'];
$res = stkQuery($id);
header('Content-Type: application/json');
echo json_encode($res, JSON_PRETTY_PRINT);
