<?php
require '../config.php';
session_destroy();
respond(["message" => "Logged out"]);