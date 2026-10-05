<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("student");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$trainingDuration = isset($data["training_duration"])
    ? (int) $data["training_duration"]
    : 10;

$preferredLocation = isset($data["preferred_location"])
    ? trim($data["preferred_location"])
    : null;

if ($trainingDuration <= 0) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid training duration."
    ]);

    exit;
}

try {

    // Get student ID
    $stmt = $conn->prepare(
        "SELECT id FROM students
         WHERE user_id = ?
         LIMIT 1"
    );

    $stmt->execute([$_SESSION["user_id"]]);

    $student = $stmt->fetch();

    if (!$student) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Student profile not found."
        ]);

        exit;
    }

    $studentId = $student["id"];

    // Check existing active/pending application
    $stmt = $conn->prepare(
        "SELECT id, status
         FROM applications
         WHERE student_id = ?
         AND status IN ('pending', 'recommended', 'placed')
         LIMIT 1"
    );

    $stmt->execute([$studentId]);

    if ($stmt->fetch()) {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "You already have an active training application."
        ]);

        exit;
    }

    // Create application
    $stmt = $conn->prepare(
        "INSERT INTO applications
        (
            student_id,
            training_duration,
            preferred_location,
            status
        )
        VALUES (?, ?, ?, 'pending')"
    );

    $stmt->execute([
        $studentId,
        $trainingDuration,
        $preferredLocation
    ]);

    $applicationId = $conn->lastInsertId();

    echo json_encode([
        "success" => true,
        "message" => "Industrial training application submitted successfully.",
        "application_id" => $applicationId,
        "status" => "pending"
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to submit application."
    ]);
}