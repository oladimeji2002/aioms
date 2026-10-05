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


/*
|--------------------------------------------------------------------------
| METHOD
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ], JSON_PRETTY_PRINT);

    exit;
}


$data =
    json_decode(
        file_get_contents("php://input"),
        true
    );


$notificationId =
    isset($data["notification_id"])
        ? (int) $data["notification_id"]
        : 0;


if ($notificationId <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Valid notification ID is required."
    ], JSON_PRETTY_PRINT);

    exit;
}


try {

    $stmt = $conn->prepare(
        "UPDATE notifications
         SET is_read = 1
         WHERE id = ?
         AND user_id = ?"
    );


    $stmt->execute([

        $notificationId,

        (int) $_SESSION["user_id"]

    ]);


    if ($stmt->rowCount() === 0) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" =>
                "Notification not found."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    echo json_encode([

        "success" => true,

        "message" =>
            "Notification marked as read."

    ], JSON_PRETTY_PRINT);


} catch (PDOException $e) {

    error_log(
        "Mark notification read error: " .
        $e->getMessage()
    );


    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to mark notification as read."
    ], JSON_PRETTY_PRINT);
}