<?php
require '../config.php';

$data = json_decode(file_get_contents("php://input"), true);
$name     = trim($data['name'] ?? '');
$email    = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$name || !$email || !$password)
  respond(["error" => "All fields required"], 400);

if (strlen($password) < 6)
  respond(["error" => "Password too short"], 400);

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch())
  respond(["error" => "Email already registered"], 409);

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->execute([$name, $email, $hash]);
$userId = $pdo->lastInsertId();

$_SESSION['user_id'] = $userId;
respond(["user" => ["id" => $userId, "name" => $name, "email" => $email]]);