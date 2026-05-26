<?php
/**
 * POST /api/auth/signup.php
 *
 * BUGS FIXED:
 *  1. Added email format validation (was missing).
 *  2. Added name length validation.
 *  3. Regenerates session ID on successful registration.
 */

require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(["error" => "Method not allowed"], 405);
}

$data     = json_decode(file_get_contents("php://input"), true);
$name     = trim($data['name']     ?? '');
$email    = trim($data['email']    ?? '');
$password =      $data['password'] ?? '';

if (!$name || !$email || !$password) {
    respond(["error" => "All fields required"], 400);
}

if (strlen($name) < 2 || strlen($name) > 100) {
    respond(["error" => "Name must be between 2 and 100 characters"], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(["error" => "Invalid email format"], 400);
}

if (strlen($password) < 6) {
    respond(["error" => "Password must be at least 6 characters"], 400);
}

/* ── Check duplicate email ────────────────────────────────── */
$check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$check->execute([$email]);
if ($check->fetch()) {
    respond(["error" => "Email already registered"], 409);
}

/* ── Insert new user ──────────────────────────────────────── */
$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->execute([$name, $email, $hash]);
$userId = (int) $pdo->lastInsertId();

/* ── Start authenticated session immediately ──────────────── */
session_regenerate_id(true);
$_SESSION['user_id']   = $userId;
$_SESSION['user_name'] = $name;

respond([
    "success" => true,
    "user"    => [
        "id"    => $userId,
        "name"  => $name,
        "email" => $email,
    ],
]);
