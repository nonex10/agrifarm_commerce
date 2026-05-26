<?php
require '../config.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) respond(["error" => "Not logged in"], 401);

$data      = json_decode(file_get_contents("php://input"), true);
$productId = (int)($data['product_id'] ?? 0);
if (!$productId) respond(["error" => "product_id required"], 400);

$stmt = $pdo->prepare(
    "DELETE FROM wishlists WHERE user_id = ? AND product_id = ?"
);
$stmt->execute([$userId, $productId]);

if ($stmt->rowCount() === 0)
    respond(["error" => "Item not in wishlist"], 404);

respond(["message" => "Removed from wishlist", "product_id" => $productId]);