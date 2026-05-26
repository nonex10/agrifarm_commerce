<?php
/**
 * GET /api/orders/list.php
 * Returns the order history for the currently logged-in user.
 *
 * BUGS FIXED:
 *  1. Previously returned ALL orders for all users (no user filter).
 *     Now filters by $_SESSION['user_id'] so each user sees only their own.
 *  2. Now JOINs order_items so the frontend receives the full items array
 *     (including images) – the frontend was unable to display thumbnails.
 *  3. Returns data in the exact shape the orders.html renderOrders()
 *     function expects.
 */

require '../config.php';   // opens $pdo, starts session, sets headers

/* ── Only allow GET ───────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(["error" => "Method not allowed"], 405);
}

/* ── Require a logged-in session ─────────────────────────── */
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    // Guest: the frontend falls back to localStorage – return empty.
    respond(["orders" => []]);
}

/* ── Fetch orders for this user (newest first) ────────────── */
try {
    $orderStmt = $pdo->prepare(
        "SELECT id, customer_name, total, status, payment_method, address, created_at
         FROM   orders
         WHERE  user_id = ?
         ORDER  BY id DESC"
    );
    $orderStmt->execute([$userId]);
    $rows = $orderStmt->fetchAll();

    if (empty($rows)) {
        respond(["orders" => []]);
    }

    /* ── Fetch items for each order in a single query ───────── */
    $orderIds    = array_column($rows, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

    $itemStmt = $pdo->prepare(
        "SELECT order_id, product_id, product_name AS name, price, quantity, image
         FROM   order_items
         WHERE  order_id IN ($placeholders)"
    );
    $itemStmt->execute($orderIds);
    $allItems = $itemStmt->fetchAll();

    /* ── Group items by order_id ─────────────────────────────── */
    $itemsByOrder = [];
    foreach ($allItems as $item) {
        $itemsByOrder[$item['order_id']][] = [
            'id'       => (int) $item['product_id'],
            'name'     => $item['name'],
            'price'    => (float) $item['price'],
            'quantity' => (int) $item['quantity'],
            'image'    => $item['image'],
        ];
    }

    /* ── Build response array in the shape orders.html expects ── */
    $orders = [];
    foreach ($rows as $row) {
        $orders[] = [
            'id'      => 'ORD-' . $row['id'],       // matches frontend format
            'db_id'   => (int) $row['id'],           // used for delete API calls
            'date'    => $row['created_at'],
            'items'   => $itemsByOrder[$row['id']] ?? [],
            'total'   => (float) $row['total'],
            'payment' => $row['payment_method'],
            'address' => $row['address'],
            'status'  => $row['status'],
        ];
    }

    respond(["orders" => $orders]);

} catch (Exception $e) {
    respond(["error" => "Failed to fetch orders: " . $e->getMessage()], 500);
}
