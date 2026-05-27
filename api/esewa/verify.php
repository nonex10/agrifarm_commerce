<?php
/**
 * POST /api/esewa/verify.php
 * ==========================
 * Called by esewa-success.html after eSewa redirects back to our success page.
 * eSewa sends an encoded "data" parameter in the URL query string.
 *
 * This file:
 *   1. Decodes the base64 JSON payload from eSewa
 *   2. Verifies the HMAC-SHA256 signature of the response
 *   3. Calls eSewa's Transaction Status API to double-check
 *   4. On success: marks the order as "Paid" and saves transaction details
 *   5. Returns JSON result to the caller
 *
 * Request body (JSON):
 *   { "data": "<base64-encoded-json-from-esewa-query-param>" }
 *
 * Response (JSON):
 *   { "success": true, "order_id": "ORD-5", "message": "Payment verified" }
 *   OR
 *   { "error": "Verification failed: ..." }
 */

require_once '../config.php';   // DB + respond()
require_once 'config.php';      // eSewa credentials

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Method not allowed'], 405);
}

/* ── Parse incoming request ───────────────────────────────── */
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body) || empty($body['data'])) {
    respond(['error' => 'Missing data parameter'], 400);
}

/* ── Step 1: Decode the base64 payload sent by eSewa ─────── */
$decoded = base64_decode($body['data'], true);
if ($decoded === false) {
    respond(['error' => 'Invalid base64 data from eSewa'], 400);
}

$esewaData = json_decode($decoded, true);
if (!is_array($esewaData)) {
    respond(['error' => 'Could not parse eSewa response payload'], 400);
}

/*
 * eSewa v2 decoded payload keys:
 *   transaction_code, status, total_amount, transaction_uuid,
 *   product_code, signed_field_names, signature
 */
$transaction_code  = trim($esewaData['transaction_code']  ?? '');
$status            = trim($esewaData['status']            ?? '');
$total_amount      = $esewaData['total_amount']            ?? 0;
$transaction_uuid  = trim($esewaData['transaction_uuid']  ?? '');
$product_code      = trim($esewaData['product_code']      ?? '');
$signed_fields     = trim($esewaData['signed_field_names'] ?? '');
$received_sig      = trim($esewaData['signature']         ?? '');

/* ── Step 2: Verify the HMAC-SHA256 signature ─────────────── */
$field_keys = explode(',', $signed_fields);
$sig_parts  = [];
foreach ($field_keys as $key) {
    $key = trim($key);
    if (!isset($esewaData[$key])) {
        respond(['error' => "Signed field '{$key}' missing from eSewa payload"], 400);
    }
    $sig_parts[] = "{$key}={$esewaData[$key]}";
}
$message          = implode(',', $sig_parts);
$expected_sig     = base64_encode(hash_hmac('sha256', $message, ESEWA_SECRET_KEY, true));

if (!hash_equals($expected_sig, $received_sig)) {
    // Log tamper attempt silently; never expose details to client
    error_log("[eSewa] Signature mismatch for uuid={$transaction_uuid}");
    respond(['error' => 'Payment verification failed: signature mismatch'], 400);
}

/* ── Step 3: Check payment status field ──────────────────── */
if (strtoupper($status) !== 'COMPLETE') {
    respond(['error' => "Payment not completed. Status: {$status}"], 400);
}

/* ── Step 4: Extract numeric order ID from transaction_uuid  */
// transaction_uuid is "ORD-5" → numeric ID is 5
$numericId = (int) str_replace('ORD-', '', $transaction_uuid);
if ($numericId <= 0) {
    respond(['error' => 'Cannot resolve order ID from transaction_uuid'], 400);
}

/* ── Step 5: Confirm order exists and is in pending/esewa state */
try {
    $stmt = $pdo->prepare(
        'SELECT id, total, status, payment_method FROM orders WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$numericId]);
    $order = $stmt->fetch();
} catch (Exception $e) {
    respond(['error' => 'Database error while fetching order'], 500);
}

if (!$order) {
    respond(['error' => 'Order not found'], 404);
}
if (strtolower($order['payment_method']) !== 'esewa') {
    respond(['error' => 'Order is not an eSewa order'], 400);
}

// Idempotent: if already paid, just return success
if (strtolower($order['status']) === 'paid') {
    respond([
        'success'  => true,
        'order_id' => "ORD-{$numericId}",
        'message'  => 'Payment already verified',
    ]);
}

/* ── Step 6: Cross-verify with eSewa Transaction Status API ─
 *
 *  GET {ESEWA_STATUS_URL}?product_code={}&total_amount={}&transaction_uuid={}
 *  Returns JSON with status "COMPLETE" if legitimate.
 */
$status_url = ESEWA_STATUS_URL . '?' . http_build_query([
    'product_code'     => ESEWA_PRODUCT_CODE,
    'total_amount'     => $total_amount,
    'transaction_uuid' => $transaction_uuid,
]);

$ch = curl_init($status_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_SSL_VERIFYPEER => true,
]);
$api_response = curl_exec($ch);
$curl_error   = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    error_log("[eSewa] cURL error during status check: {$curl_error}");
    respond(['error' => 'Could not reach eSewa verification server'], 502);
}

$api_data = json_decode($api_response, true);
if (!is_array($api_data)) {
    respond(['error' => 'Invalid response from eSewa status API'], 502);
}

$api_status = strtoupper($api_data['status'] ?? '');
if ($api_status !== 'COMPLETE') {
    error_log("[eSewa] Status API returned non-COMPLETE for uuid={$transaction_uuid}: {$api_status}");
    respond(['error' => "eSewa status API reports: {$api_status}"], 400);
}

/* ── Step 7: Update DB – mark order Paid + store transaction ─ */
try {
    $pdo->beginTransaction();

    // Mark order as Paid
    $upd = $pdo->prepare(
        "UPDATE orders SET status = 'Paid' WHERE id = ?"
    );
    $upd->execute([$numericId]);

    // Save transaction details (table created in migration SQL)
    $ins = $pdo->prepare(
        "INSERT INTO esewa_transactions
            (order_id, transaction_code, transaction_uuid, amount, status, raw_response)
         VALUES (?, ?, ?, ?, 'COMPLETE', ?)
         ON DUPLICATE KEY UPDATE
            transaction_code = VALUES(transaction_code),
            status           = 'COMPLETE',
            raw_response     = VALUES(raw_response)"
    );
    $ins->execute([
        $numericId,
        $transaction_code,
        $transaction_uuid,
        $total_amount,
        $api_response,
    ]);

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("[eSewa] DB update failed: " . $e->getMessage());
    respond(['error' => 'Payment verified but order update failed. Contact support.'], 500);
}

/* ── Done ─────────────────────────────────────────────────── */
respond([
    'success'          => true,
    'order_id'         => "ORD-{$numericId}",
    'transaction_code' => $transaction_code,
    'message'          => 'Payment verified and order updated successfully',
]);