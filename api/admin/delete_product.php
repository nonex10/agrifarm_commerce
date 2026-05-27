<?php
/**
 * POST /api/admin/delete_product.php
 * Deletes a product by ID. Admin only.
 */
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(["error" => "Method not allowed"], 405);
}

$data = json_decode(file_get_contents("php://input"), true);
$id   = (int)($data['id'] ?? 0);

if (!$id) {
    respond(["error" => "Product ID required"], 400);
}

try {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        respond(["error" => "Product not found"], 404);
    }

    respond(["success" => true, "message" => "Product deleted"]);
} catch (Exception $e) {
    respond(["error" => $e->getMessage()], 500);
}
