<?php
/**
 * GET /api/products/single.php?id=N
 *
 * BUGS FIXED:
 *  1. Removed redundant header() call (config.php already sets it).
 *  2. Cast numeric fields to proper types.
 */

require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(["error" => "Method not allowed"], 405);
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    respond(["error" => "Valid product ID required"], 400);
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    respond(["error" => "Product not found"], 404);
}

$product['id']      = (int)   $product['id'];
$product['price']   = (float) $product['price'];
$product['rating']  = (float) $product['rating'];
$product['reviews'] = (int)   $product['reviews'];

respond(["product" => $product]);
