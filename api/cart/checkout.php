<?php
require '../config.php';
session_start();

$data    = json_decode(file_get_contents("php://input"), true);

$items   = $data['items'] ?? [];
$total   = $data['total'] ?? 0;
$address = $data['address'] ?? '';
$payment = $data['payment'] ?? 'cod';

$userId  = $_SESSION['user_id'] ?? null;

if (empty($items)) {
  respond(["error" => "No items in cart"], 400);
}

try {
  // 1. Insert order
  $stmt = $pdo->prepare(
    "INSERT INTO orders (user_id, total, status, payment_method, address)
     VALUES (?, ?, 'Confirmed', ?, ?)"
  );

  $stmt->execute([$userId, $total, $payment, $address]);
  $orderId = $pdo->lastInsertId();

  // 2. Insert order items
  $itemStmt = $pdo->prepare(
    "INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
     VALUES (?, ?, ?, ?, ?)"
  );

  foreach ($items as $item) {
    $itemStmt->execute([
      $orderId,
      $item['id'],
      $item['name'],
      $item['price'],
      $item['quantity']
    ]);
  }

  respond([
    "order_id" => "ORD-$orderId",
    "message" => "Order placed successfully"
  ]);

} catch (Exception $e) {
  respond([
    "error" => "Checkout failed",
    "details" => $e->getMessage()
  ], 500);
}