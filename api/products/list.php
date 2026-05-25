<?php
require '../config.php';

$category = $_GET['category'] ?? '';
$search   = $_GET['search'] ?? '';
$limit    = (int)($_GET['limit'] ?? 100);

$sql    = "SELECT * FROM products WHERE 1=1";
$params = [];

if ($category) { $sql .= " AND category = ?"; $params[] = $category; }
if ($search)   { $sql .= " AND (name LIKE ? OR description LIKE ? OR farmer LIKE ?)";
                 $s = "%$search%"; $params = array_merge($params, [$s, $s, $s]); }

$limit = (int)($_GET['limit'] ?? 100);
$sql .= " LIMIT $limit";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
respond(["products" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);