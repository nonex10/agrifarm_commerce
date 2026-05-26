<?php
require '../config.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) respond(["error" => "Not logged in"], 401);

$data      = json_decode(file_get_contents("php://input"), true);
$productId = (int)($data['product_id'] ?? 0);
if (!$productId) respond(["error" => "product_id required"], 400);

$check = $pdo->prepare("SELECT id FROM products WHERE id = ?");
$check->execute([$productId]);
if (!$check->fetch()) respond(["error" => "Product not found"], 404);

$stmt = $pdo->prepare(
    "INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)"
);
$stmt->execute([$userId, $productId]);
respond(["message" => "Added to wishlist", "product_id" => $productId]);