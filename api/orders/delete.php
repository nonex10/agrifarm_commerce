<?php
/**
 * DELETE /api/orders/delete.php
 * Deletes a single order belonging to the logged-in user.
 *
 * NEW FILE – this endpoint did not exist; the delete button on the
 * orders page had no backend support.
 *
 * Security:
 *  • Requires an active session (logged-in user only).
 *  • Verifies the order belongs to the requesting user before deleting.
 *  • Uses prepared statements – no SQL injection possible.
 *  • order_items rows are removed automatically via ON DELETE CASCADE.
 *
 * Request body (JSON):
 *  { "order_id": 42 }          — numeric DB id (not "ORD-42")
 *
 * Responses:
 *  200 { "success": true, "message": "Order deleted" }
 *  400 { "error": "Missing order_id" }
 *  401 { "error": "Not authenticated" }
 *  403 { "error": "Unauthorised" }
 *  404 { "error": "Order not found" }
 */

require '../config.php';   // opens $pdo, starts session, sets headers

/* ── Accept DELETE or POST (some browsers/fetch configs use POST) */
if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'], true)) {
    respond(["error" => "Method not allowed"], 405);
}

/* ── Require a logged-in session ─────────────────────────── */
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    respond(["error" => "Not authenticated. Please sign in."], 401);
}

/* ── Parse JSON body ──────────────────────────────────────── */
$data     = json_decode(file_get_contents("php://input"), true);
$orderId  = isset($data['order_id']) ? (int) $data['order_id'] : 0;

if ($orderId <= 0) {
    respond(["error" => "Missing or invalid order_id"], 400);
}

/* ── Verify the order exists AND belongs to this user ────── */
try {
    $checkStmt = $pdo->prepare(
        "SELECT id FROM orders WHERE id = ? AND user_id = ?"
    );
    $checkStmt->execute([$orderId, $userId]);
    $order = $checkStmt->fetch();

    if (!$order) {
        // Either doesn't exist or belongs to someone else – same response
        respond(["error" => "Order not found or access denied."], 404);
    }

    /* ── Delete (CASCADE removes order_items automatically) ── */
    $deleteStmt = $pdo->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?");
    $deleteStmt->execute([$orderId, $userId]);

    respond([
        "success" => true,
        "message" => "Order deleted successfully",
    ]);

} catch (Exception $e) {
    respond(["error" => "Delete failed: " . $e->getMessage()], 500);
}
