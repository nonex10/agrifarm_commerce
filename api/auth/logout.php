<?php
/**
 * POST /api/auth/logout.php
 *
 * BUGS FIXED:
 *  1. Original was only 90 bytes and did nothing useful.
 *     Now properly destroys the session.
 */

require '../config.php';

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $p["path"], $p["domain"], $p["secure"], $p["httponly"]
    );
}

session_destroy();

respond(["success" => true, "message" => "Logged out"]);
