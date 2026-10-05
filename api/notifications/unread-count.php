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
        "SELECT COUNT(*) AS unread_count
         FROM notifications
         WHERE user_id = ?
         AND is_read = 0"
    );


    $stmt->execute([
        $userId
    ]);


    $result =
        $stmt->fetch(PDO::FETCH_ASSOC);


    echo json_encode([

        "success" => true,

        "unread_count" =>
            (int) $result["unread_count"]

    ], JSON_PRETTY_PRINT);


} catch (PDOException $e) {

    error_log(
        "Unread notification count error: " .
        $e->getMessage()
    );


    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to retrieve unread notification count."
    ], JSON_PRETTY_PRINT);
}