<?php
/**
 * POST /api/contact/submit.php
 * No login required - open to all visitors.
 */

require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(["error" => "Method not allowed"], 405);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    respond(["error" => "Invalid JSON body"], 400);
}

$name    = trim($data['name']    ?? '');
$email   = trim($data['email']   ?? '');
$reason  = trim($data['reason']  ?? '');
$subject = trim($data['subject'] ?? '');
$message = trim($data['message'] ?? '');

if (!$name || !$email || !$reason || !$subject || !$message) {
    respond(["error" => "All fields are required"], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(["error" => "Invalid email format"], 400);
}

if (strlen($message) > 5000) {
    respond(["error" => "Message too long (max 5000 characters)"], 400);
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO contact_messages (name, email, reason, subject, message)
            VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$name, $email, $reason, $subject, $message]);

    respond([
        "success" => true,
        "message" => "Message received. We'll get back to you soon!",
    ]);

} catch (Exception $e) {
    respond(["error" => $e->getMessage()], 500);
}