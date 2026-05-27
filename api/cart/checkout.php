<?php
/**
 * POST /api/cart/checkout.php  ← MODIFIED for eSewa integration
 * ====================================================
 * CHANGES FROM ORIGINAL (payment-related only):
 *   - eSewa orders are saved with status = 'Pending'  (was 'Confirmed')
 *   - COD orders remain   status = 'Confirmed'
 *   - All other logic, fields, and validations are UNCHANGED
 *
 * Creates a new order and its line-items in the database.
 */

header('Content-Type: application/json');

require_once '../config.php';

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
$address       = trim(    ($data['address']        ?? ''));
$payment       = trim(    ($data['payment']        ?? 'cod'));
$customer_name = trim(    ($data['customer_name']  ?? 'Guest'));

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

/* ── Determine initial order status ──────────────────────────
 *
 *  CHANGE: eSewa orders start as 'Pending' because the customer
 *  hasn't actually paid yet — they are about to be redirected to
 *  eSewa. The status becomes 'Paid' only after verify.php confirms
 *  the payment.  COD remains 'Confirmed' as before.
 */
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

    $orderStmt->bindValue(':user_id',       $userId,         $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $orderStmt->bindValue(':customer_name', $customer_name,  PDO::PARAM_STR);
    $orderStmt->bindValue(':total',         $total,          PDO::PARAM_STR);
    $orderStmt->bindValue(':status',        $initial_status, PDO::PARAM_STR); // ← CHANGED
    $orderStmt->bindValue(':payment',       $payment,        PDO::PARAM_STR);
    $orderStmt->bindValue(':address',       $address,        PDO::PARAM_STR);
    $orderStmt->execute();

    $orderId = (int) $pdo->lastInsertId();

    /* 2. Insert order_items — UNCHANGED */
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

        $itemStmt->bindValue(':order_id',     $orderId,                            PDO::PARAM_INT);
        $itemStmt->bindValue(':product_id',   $productId, $productId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $itemStmt->bindValue(':product_name', trim($item['name']),                 PDO::PARAM_STR);
        $itemStmt->bindValue(':price',        (float) $item['price'],              PDO::PARAM_STR);
        $itemStmt->bindValue(':quantity',     max(1, (int) $item['quantity']),      PDO::PARAM_INT);
        $itemStmt->bindValue(':image',        trim($item['image'] ?? ''),           PDO::PARAM_STR);
        $itemStmt->execute();
    }

    $pdo->commit();

    respond([
        "success"  => true,
        "order_id" => "ORD-$orderId",
        "message"  => "Order placed successfully",
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond([
        "error" => "Checkout failed: " . $e->getMessage(),
    ], 500);
}