<?php
require '../config.php';

$data = json_decode(file_get_contents("php://input"), true);
$stmt = $pdo->prepare(
  "INSERT INTO contact_messages (name, email, reason, subject, message) VALUES (?, ?, ?, ?, ?)"
);
$stmt->execute([
  $data['name'] ?? '', $data['email'] ?? '',
  $data['reason'] ?? '', $data['subject'] ?? '', $data['message'] ?? ''
]);
respond(["message" => "Message received"]);