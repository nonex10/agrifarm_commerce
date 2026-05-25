<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

$host = "localhost";
$db   = "agrifresh";
$user = "root";
$pass = "";          // XAMPP default is no password

try {
  $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  echo json_encode(["error" => "DB connection failed: " . $e->getMessage()]);
  exit;
}

// function respond($data, $code = 200) {
//   http_response_code($code);
//   echo json_encode($data);
//   exit;
// }
function respond($data, $code = 200) {
  http_response_code($code);
  header("Content-Type: application/json");
  echo json_encode($data);
  exit;
}