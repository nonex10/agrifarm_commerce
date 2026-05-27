<?php
/**
 * GET /api/admin/orders.php
 * Returns all orders with item count. Admin only.
 */
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(["error" => "Method not allowed"], 405);
}

try {
    $stmt = $pdo->query(
        "SELECT o.*,
                COUNT(oi.id) AS item_count
         FROM orders o
         LEFT JOIN order_items oi ON oi.order_id = o.id
         GROUP BY o.id
         ORDER BY o.created_at DESC"
    );
    respond(["orders" => $stmt->fetchAll()]);
} catch (Exception $e) {
    respond(["error" => $e->getMessage()], 500);
}
