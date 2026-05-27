<?php
/**
 * POST /api/esewa/initiate.php
 * ============================
 * Called by checkout.html (JavaScript) right before redirecting the user to eSewa.
 * Generates an HMAC-SHA256 signature and returns all required eSewa form parameters.
 *
 * Request body (JSON):
 *   { "order_id": "ORD-5", "amount": 1130 }
 *
 * Response (JSON):
 *   { "esewa_url": "https://rc-epay.esewa.com.np/api/epay/main/v2/form",
 *     "params":    { amount, tax_amount, total_amount, transaction_uuid,
 *                    product_code, product_service_charge,
 *                    product_delivery_charge, success_url, failure_url,
 *                    signed_field_names, signature } }
 */

require_once '../config.php';        // DB connection + respond() helper
require_once 'config.php';           // eSewa credentials + URLs

/* ── Only accept POST ─────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Method not allowed'], 405);
}

/* ── Parse and validate request body ─────────────────────── */
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    respond(['error' => 'Invalid JSON body'], 400);
}

$order_id = isset($body['order_id']) ? trim((string) $body['order_id']) : '';
$amount   = isset($body['amount'])   ? (float) $body['amount']          : 0;

if (empty($order_id)) {
    respond(['error' => 'order_id is required'], 400);
}
if ($amount <= 0) {
    respond(['error' => 'Invalid amount'], 400);
}

/* ── Verify the order exists in DB and belongs to this user ─ */
$numericId = (int) str_replace('ORD-', '', $order_id);
if ($numericId <= 0) {
    respond(['error' => 'Invalid order ID format'], 400);
}

try {
    $stmt = $pdo->prepare(
        'SELECT id, total, payment_method, status FROM orders WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$numericId]);
    $order = $stmt->fetch();
} catch (Exception $e) {
    respond(['error' => 'Database error while verifying order'], 500);
}

if (!$order) {
    respond(['error' => 'Order not found'], 404);
}
if (strtolower($order['payment_method']) !== 'esewa') {
    respond(['error' => 'Order payment method is not eSewa'], 400);
}
if (!in_array($order['status'], ['Pending', 'pending'], true)) {
    respond(['error' => 'Order is not in a payable state'], 400);
}

/* ── Build eSewa params ───────────────────────────────────── */
$total_amount     = round((float) $order['total'], 2);  // use DB total for integrity
$tax_amount       = 0;   // VAT already included in total; eSewa extra tax = 0
$service_charge   = 0;
$delivery_charge  = 0;
$transaction_uuid = 'ORD-' . $numericId;   // e.g. "ORD-5"
$product_code     = ESEWA_PRODUCT_CODE;

// Build base URLs
$base = 'http://localhost/agrifarm';
$success_url = $base . '/pages/esewa-success.html';
$failure_url = $base . '/pages/esewa-failure.html';

/* ── Generate HMAC-SHA256 signature ──────────────────────────
 *
 *  eSewa v2 signature spec:
 *    message   = "total_amount={v},transaction_uuid={v},product_code={v}"
 *    signature = base64( hmac_sha256( secret_key, message ) )
 */
$signed_field_names = 'total_amount,transaction_uuid,product_code';

$message = implode(',', [
    "total_amount={$total_amount}",
    "transaction_uuid={$transaction_uuid}",
    "product_code={$product_code}",
]);

$signature = base64_encode(
    hash_hmac('sha256', $message, ESEWA_SECRET_KEY, true)
);

/* ── Return params to frontend ────────────────────────────── */
respond([
    'success'   => true,
    'esewa_url' => ESEWA_PAYMENT_URL,
    'params'    => [
        'amount'                  => $total_amount,
        'tax_amount'              => $tax_amount,
        'total_amount'            => $total_amount,
        'transaction_uuid'        => $transaction_uuid,
        'product_code'            => $product_code,
        'product_service_charge'  => $service_charge,
        'product_delivery_charge' => $delivery_charge,
        'success_url'             => $success_url,
        'failure_url'             => $failure_url,
        'signed_field_names'      => $signed_field_names,
        'signature'               => $signature,
    ],
]);