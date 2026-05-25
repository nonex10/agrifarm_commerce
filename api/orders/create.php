<?php
require '../config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
  respond(["error" => "Invalid input"], 400);
}

$product_id = $data['product_id'];
$product_name = $data['product_name'];
$price = $data['price'];
$quantity = $data['quantity'];
$total = $price * $quantity;
$customer_name = $data['customer_name'] ?? 'Guest';

$stmt = $pdo->prepare("
  INSERT INTO orders (product_id, product_name, price, quantity, total, customer_name)
  VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->execute([
  $product_id,
  $product_name,
  $price,
  $quantity,
  $total,
  $customer_name
]);

respond(["message" => "Order placed successfully"]);