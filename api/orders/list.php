<?php
require '../config.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) respond(["error" => "Not logged in"], 401);

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($orders as &$order) {
  $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
  $itemStmt->execute([$order['id']]);
  $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
}

respond(["orders" => $orders]);