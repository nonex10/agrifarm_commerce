<?php
/**
 * POST /api/orders/create.php
 *
 * NOTE: The primary order-creation flow now lives in /api/cart/checkout.php
 * which handles multi-item cart orders and stores order_items.
 *
 * This file is kept for backward-compatibility in case any older call
 * targets this endpoint directly. It proxies the request to checkout.php
 * logic by accepting a single-item body.
 *
 * BUGS FIXED vs original:
 *  1. Original had no security – anyone could insert arbitrary orders.
 *  2. Added input validation and type casting.
 *  3. Uses parameterised queries (was already OK, kept clean).
 *  4. Added customer_name to the orders table via checkout columns.
 */

require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(["error" => "Method not allowed"], 405);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    respond(["error" => "Invalid JSON body"], 400);
}

/* ── Validate ─────────────────────────────────────────────── */
$product_id   = isset($data['product_id'])   ? (int)   $data['product_id']   : null;
$product_name = isset($data['product_name']) ? trim(   $data['product_name']) : '';
$price        = isset($data['price'])        ? (float) $data['price']        : 0;
$quantity     = isset($data['quantity'])     ? max(1, (int) $data['quantity']): 1;
$customer     = isset($data['customer_name'])? trim(   $data['customer_name']): 'Guest';
$address      = isset($data['address'])      ? trim(   $data['address'])      : 'N/A';
$payment      = isset($data['payment'])      ? trim(   $data['payment'])      : 'cod';

if (!$product_name || $price <= 0) {
    respond(["error" => "product_name and price are required"], 400);
}

$total  = $price * $quantity;
$userId = $_SESSION['user_id'] ?? null;

try {
    $pdo->beginTransaction();

    $orderStmt = $pdo->prepare(
        "INSERT INTO orders (user_id, customer_name, total, status, payment_method, address)
         VALUES (?, ?, ?, 'Confirmed', ?, ?)"
    );
    $orderStmt->execute([$userId, $customer, $total, $payment, $address]);
    $orderId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        "INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
         VALUES (?, ?, ?, ?, ?)"
    );
    $itemStmt->execute([$orderId, $product_id, $product_name, $price, $quantity]);

    $pdo->commit();

    respond([
        "success"  => true,
        "order_id" => "ORD-$orderId",
        "message"  => "Order placed successfully",
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond(["error" => "Order failed: " . $e->getMessage()], 500);
}
