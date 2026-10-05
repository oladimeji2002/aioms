<?php

require_once __DIR__ . "/../../config/config.php";

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "authenticated" => false,
        "message" => "Not authenticated."
    ]);

    exit;
}

echo json_encode([
    "success" => true,
    "authenticated" => true,
    "user" => [
        "id" => $_SESSION["user_id"],
        "email" => $_SESSION["email"],
        "role" => $_SESSION["role"]
    ]
]);