<?php
require '../config.php';

$data    = json_decode(file_get_contents("php://input"), true);
$items   = $data['items'] ?? [];
$total   = $data['total'] ?? 0;
$address = $data['address'] ?? '';
$payment = $data['payment'] ?? 'cod';
$userId  = $_SESSION['user_id'] ?? null;

if (empty($items)) respond(["error" => "No items"], 400);

$stmt = $pdo->prepare(
  "INSERT INTO orders (user_id, total, status, payment_method, address) VALUES (?, ?, 'Confirmed', ?, ?)"
);
$stmt->execute([$userId, $total, $payment, $address]);
$orderId = $pdo->lastInsertId();

$itemStmt = $pdo->prepare(
  "INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)"
);
foreach ($items as $item) {
  $itemStmt->execute([$orderId, $item['id'], $item['name'], $item['price'], $item['quantity']]);
}

respond(["order_id" => "ORD-$orderId", "message" => "Order placed successfully"]);