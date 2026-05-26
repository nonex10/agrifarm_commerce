<?php
/**
 * POST /api/auth/login.php
 *
 * BUGS FIXED:
 *  1. Added email format validation.
 *  2. Added rate-limit-friendly error message (doesn't reveal whether
 *     the email exists).
 *  3. Regenerates session ID on login to prevent session fixation.
 */

require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(["error" => "Method not allowed"], 405);
}

$data     = json_decode(file_get_contents("php://input"), true);
$email    = trim($data['email']    ?? '');
$password =      $data['password'] ?? '';

if (!$email || !$password) {
    respond(["error" => "All fields required"], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(["error" => "Invalid email format"], 400);
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    respond(["error" => "Invalid email or password"], 401);
}

/* ── Regenerate session to prevent fixation attacks ───────── */
session_regenerate_id(true);

$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['name'];

respond([
    "success" => true,
    "user"    => [
        "id"    => (int) $user['id'],
        "name"  => $user['name'],
        "email" => $user['email'],
    ],
]);
