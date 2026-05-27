<?php
/**
 * POST /api/admin/add_product.php
 * Adds a new product. Admin only.
 */
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(["error" => "Method not allowed"], 405);
}

$data        = json_decode(file_get_contents("php://input"), true);
$name        = trim($data['name']        ?? '');
$category    = trim($data['category']    ?? 'Other');
$price       = (float)($data['price']    ?? 0);
$farmer      = trim($data['farmer']      ?? '');
$description = trim($data['description'] ?? '');
$image       = trim($data['image']       ?? '');

if (!$name || $price <= 0) {
    respond(["error" => "Name and a valid price are required"], 400);
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO products (name, category, price, farmer, description, image)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$name, $category, $price, $farmer, $description, $image]);

    respond([
        "success" => true,
        "id"      => (int) $pdo->lastInsertId(),
        "message" => "Product added successfully",
    ]);
} catch (Exception $e) {
    respond(["error" => $e->getMessage()], 500);
}
