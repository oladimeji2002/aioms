<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");


/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    !in_array(
        $_SESSION["role"],
        ["student", "supervisor"],
        true
    )
) {

    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized."
    ], JSON_PRETTY_PRINT);

    exit;
}


try {

    $userId =
        (int) $_SESSION["user_id"];


    $stmt = $conn->prepare(
        "SELECT
            id,
            title,
            message,
            type,
            is_read,
            created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT 50"
    );


    $stmt->execute([
        $userId
    ]);


    $notifications =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([

        "success" => true,

        "count" =>
            count($notifications),

        "notifications" =>
            $notifications

    ], JSON_PRETTY_PRINT);


} catch (PDOException $e) {

    error_log(
        "Notification list error: " .
        $e->getMessage()
    );


    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to load notifications."
    ], JSON_PRETTY_PRINT);
}