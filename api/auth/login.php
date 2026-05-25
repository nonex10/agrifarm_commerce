<?php
require '../config.php';

$data     = json_decode(file_get_contents("php://input"), true);
$email    = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password)
  respond(["error" => "All fields required"], 400);

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password']))
  respond(["error" => "Invalid email or password"], 401);

$_SESSION['user_id'] = $user['id'];
respond(["user" => ["id" => $user['id'], "name" => $user['name'], "email" => $user['email']]]);