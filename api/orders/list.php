<?php
require '../config.php';

$stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

respond(["orders" => $orders]);