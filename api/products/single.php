<?php
require '../config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) respond(["error" => "ID required"], 400);

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) respond(["error" => "Not found"], 404);

respond(["product" => $product]);