<?php

require_once __DIR__ . "/../../config/config.php";

function requireLogin()
{
    if (!isset($_SESSION["user_id"])) {
        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Please login first."
        ]);

        exit;
    }
}

function requireRole($role)
{
    requireLogin();

    if ($_SESSION["role"] !== $role) {
        http_response_code(403);

        echo json_encode([
            "success" => false,
            "message" => "Access denied."
        ]);

        exit;
    }
}