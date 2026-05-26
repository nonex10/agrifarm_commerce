<?php
/**
 * POST /api/cart/checkout.php
 * Creates a new order and its line-items in the database.
 */

header('Content-Type: application/json');

require_once '../config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(["error" => "Method not allowed"], 405);
}

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    respond(["error" => "Invalid JSON body"], 400);
}

/* ── Extract & sanitise fields ────────────────────────────── */
$items         = $data['items']        ?? [];
$total         = (float)  ($data['total']         ?? 0);
$address       = trim(    ($data['address']       ?? ''));
$payment       = trim(    ($data['payment']       ?? 'cod'));
$customer_name = trim(    ($data['customer_name'] ?? 'Guest'));

$allowed_payments = ['cod', 'esewa', 'khalti', 'bank'];
if (!in_array($payment, $allowed_payments, true)) {
    $payment = 'cod';
}

/* ── Validate ─────────────────────────────────────────────── */
if (empty($items) || !is_array($items)) {
    respond(["error" => "No items in cart"], 400);
}
if ($total <= 0) {
    respond(["error" => "Invalid total amount"], 400);
}
if (empty($address)) {
    respond(["error" => "Delivery address is required"], 400);
}

/* ── User from session (NULL for guests) ──────────────────── */
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

/* Set order status conditionally */
$initial_status = ($payment === 'esewa') ? 'Pending' : 'Confirmed';

/* ── Insert wrapped in a transaction ─────────────────────── */
try {
    $pdo->beginTransaction();

    /* 1. Insert order row */
    $orderStmt = $pdo->prepare(
        "INSERT INTO orders
            (user_id, customer_name, total, status, payment_method, address)
            VALUES
            (:user_id, :customer_name, :total, :status, :payment, :address)"
    );

    $orderStmt->bindValue(':user_id',       $userId,        $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $orderStmt->bindValue(':customer_name', $customer_name, PDO::PARAM_STR);
    $orderStmt->bindValue(':total',         $total,         PDO::PARAM_STR); 
    $orderStmt->bindValue(':status',        $initial_status,PDO::PARAM_STR);
    $orderStmt->bindValue(':payment',       $payment,       PDO::PARAM_STR);
    $orderStmt->bindValue(':address',       $address,       PDO::PARAM_STR);
    $orderStmt->execute();

    $orderId = (int) $pdo->lastInsertId();
    $transaction_uuid = "ORD-" . $orderId;

    /* 2. Insert order_items */
    $itemStmt = $pdo->prepare(
        "INSERT INTO order_items
            (order_id, product_id, product_name, price, quantity, image)
            VALUES
            (:order_id, :product_id, :product_name, :price, :quantity, :image)"
    );

    foreach ($items as $item) {
        if (empty($item['name']) || !isset($item['price']) || !isset($item['quantity'])) {
            $pdo->rollBack();
            respond(["error" => "Invalid item data"], 400);
        }

        $productId = isset($item['id']) ? (int) $item['id'] : null;

        $itemStmt->bindValue(':order_id',     $orderId,            PDO::PARAM_INT);
        $itemStmt->bindValue(':product_id',   $productId,          $productId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $itemStmt->bindValue(':product_name', trim($item['name']), PDO::PARAM_STR);
        $itemStmt->bindValue(':price',        (float) $item['price'], PDO::PARAM_STR);
        $itemStmt->bindValue(':quantity',     max(1, (int) $item['quantity']), PDO::PARAM_INT);
        $itemStmt->bindValue(':image',        trim($item['image'] ?? ''), PDO::PARAM_STR);
        $itemStmt->execute();
    }

    $pdo->commit();

    /* 3. Prepare response JSON */
    $response = [
        "success"  => true,
        "order_id" => $transaction_uuid,
        "message"  => "Order recorded successfully",
    ];

    if ($payment === 'esewa') {
        // eSewa HMAC-SHA256 Configuration (Sandbox)
        $product_code = "EPAYTEST";
        $secret_key = "8gBm/:&EnhH.1/q";
        
        $formatted_total = number_format($total, 2, '.', '');
        
        // Formulate Base Signature String
        $signature_string = "total_amount={$formatted_total},transaction_uuid={$transaction_uuid},product_code={$product_code}";
        $signature = base64_encode(hash_hmac('sha256', $signature_string, $secret_key, true));

        // Dynamically locate root host to supply absolute URLs
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));

        $response["esewa"] = [
            "amount" => $formatted_total,
            "tax_amount" => "0.00",
            "total_amount" => $formatted_total,
            "transaction_uuid" => $transaction_uuid,
            "product_code" => $product_code,
            "product_service_charge" => "0.00",
            "product_delivery_charge" => "0.00",
            "success_url" => $base_url . "/api/orders/payment-verify.php",
            "failure_url" => $base_url . "/api/orders/payment-fail.php?transaction_uuid=" . urlencode($transaction_uuid),
            "signed_field_names" => "total_amount,transaction_uuid,product_code",
            "signature" => $signature
        ];
    }

    respond($response);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond([
        "error"   => "Checkout failed: " . $e->getMessage(),
    ], 500);
}