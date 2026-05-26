<?php
require_once '../config.php';
session_start();

$encoded_data = $_GET['data'] ?? null;

if (!$encoded_data) {
    die("Access Denied: Payment data payload token missing.");
}

// Decode base64 callback response
$decoded_json = base64_decode($encoded_data);
$response_data = json_decode($decoded_json, true);

$status = $response_data['status'] ?? '';
$total_amount = $response_data['total_amount'] ?? '';
$transaction_uuid = $response_data['transaction_uuid'] ?? '';
$ref_id = $response_data['ref_id'] ?? '';
$response_signature = $response_data['signature'] ?? '';

// If the response from eSewa is not strictly Complete, bounce them to failure
if ($status !== 'COMPLETE') {
    header("Location: payment-fail.php?transaction_uuid=" . urlencode($transaction_uuid));
    exit;
}

// Verify HMAC-SHA256 Authenticity
$secret_key = "8gBm/:&EnhH.1/q"; 
$product_code = "EPAYTEST";

$expected_string = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code={$product_code}";
$generated_raw = hash_hmac('sha256', $expected_string, $secret_key, true);
$generated_signature = base64_encode($generated_raw);

if (!hash_equals($generated_signature, $response_signature)) {
    die("Security Integrity Error: Digital verification signature does not match expected result.");
}

// Extract exact numeric Order ID from "ORD-xx"
$clean_order_id = (int) str_replace("ORD-", "", $transaction_uuid);

// Update database status from Pending to Confirmed
try {
    $updateStmt = $pdo->prepare("UPDATE orders SET status = 'Confirmed' WHERE id = :id");
    $updateStmt->bindValue(':id', $clean_order_id, PDO::PARAM_INT);
    $updateStmt->execute();
} catch (Exception $e) {
    die("Error updating database record.");
}

// Dynamically construct redirect back to the static UI pages layout
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
header("Location: " . $base_url . "/pages/esewa-success.html?oid=" . urlencode($transaction_uuid));
exit;