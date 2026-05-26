<?php
/**
 * GET /api/products/list.php
 *
 * BUGS FIXED:
 *  1. $limit was declared twice (shadowing the first declaration).
 *  2. $limit is now cast to int once and capped at 200 to prevent
 *     accidental full-table dumps.
 *  3. search LIKE uses PDO params properly (was already fine, kept clean).
 */

require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(["error" => "Method not allowed"], 405);
}

$category = trim($_GET['category'] ?? '');
$search   = trim($_GET['search']   ?? '');
$limit    = min(200, max(1, (int) ($_GET['limit'] ?? 100)));

$sql    = "SELECT * FROM products WHERE 1=1";
$params = [];

if ($category !== '') {
    $sql      .= " AND category = ?";
    $params[]  = $category;
}

if ($search !== '') {
    $sql      .= " AND (name LIKE ? OR description LIKE ? OR farmer LIKE ?)";
    $s         = "%$search%";
    $params    = array_merge($params, [$s, $s, $s]);
}

$sql .= " LIMIT $limit";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

/* ── Cast numeric fields so JS doesn't get strings ───────── */
foreach ($products as &$p) {
    $p['id']      = (int)   $p['id'];
    $p['price']   = (float) $p['price'];
    $p['rating']  = (float) $p['rating'];
    $p['reviews'] = (int)   $p['reviews'];
}
unset($p);

respond(["products" => $products]);
