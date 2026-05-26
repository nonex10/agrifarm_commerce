<?php
/**
 * AgriFresh – Shared API configuration
 */

// Define the base URL of your application
define('BASE_URL', 'http://localhost/agrifarm/');

/* ── Buffer ALL output so stray warnings never corrupt JSON ── */
ob_start();

ini_set('display_errors', 0);
error_reporting(0);

/* ── CORS headers ─────────────────────────────────────────── */
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Content-Type: application/json");

/* ── Pre-flight (OPTIONS) ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit;
}

/* ── Session ──────────────────────────────────────────────── */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ── Database connection ──────────────────────────────────── */
$host = "localhost";
$db   = "agrifresh";
$user = "root";
$pass = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
        ]
    );
} catch (PDOException $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed: " . $e->getMessage()]);
    exit;
}

/**
 * Discard any buffered stray output, then send clean JSON.
 */
function respond($data, int $code = 200): void
{
    while (ob_get_level()) {
        ob_end_clean();
    }

    http_response_code($code);
    header("Content-Type: application/json");

    echo json_encode($data, JSON_UNESCAPED_UNICODE);

    exit;
}