<?php
/**
 * GET /api/admin/messages.php
 * Returns all contact messages. Admin only.
 */
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(["error" => "Method not allowed"], 405);
}

try {
    $stmt = $pdo->query(
        "SELECT * FROM contact_messages ORDER BY created_at DESC"
    );
    respond(["messages" => $stmt->fetchAll()]);
} catch (Exception $e) {
    respond(["error" => $e->getMessage()], 500);
}
