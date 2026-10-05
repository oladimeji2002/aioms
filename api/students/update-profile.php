<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("student");

if ($_SERVER["REQUEST_METHOD"] !== "PUT" && $_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$allowed = [
    "phone",
    "skills",
    "career_interest",
    "industry_interest",
    "location_preference"
];

$updates = [];
$values = [];

foreach ($allowed as $field) {
    if (array_key_exists($field, $data)) {
        $updates[] = "$field = ?";
        $values[] = trim($data[$field]);
    }
}

if (empty($updates)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "No profile information provided."
    ]);

    exit;
}

try {

    $values[] = $_SESSION["user_id"];

    $sql = "UPDATE students SET "
         . implode(", ", $updates)
         . " WHERE user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute($values);

    echo json_encode([
        "success" => true,
        "message" => "Profile updated successfully."
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to update profile."
    ]);
}