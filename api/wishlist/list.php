<?php
require '../config.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) respond(["error" => "Not logged in"], 401);

$stmt = $pdo->prepare(
    "SELECT p.*
    FROM wishlists w
    JOIN products p ON p.id = w.product_id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC"
);
$stmt->execute([$userId]);
respond(["wishlist" => $stmt->fetchAll()]);