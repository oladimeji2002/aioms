<?php

require_once __DIR__ . "/../../config/config.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$email = isset($data["email"]) ? trim($data["email"]) : "";
$password = $data["password"] ?? "";

if (empty($email) || empty($password)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Email and password are required."
    ]);

    exit;
}

try {

    $stmt = $conn->prepare(
        "SELECT id, email, password, role, status
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user["password"])) {
        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Invalid email or password."
        ]);

        exit;
    }

    if ($user["status"] !== "active") {
        http_response_code(403);

        echo json_encode([
            "success" => false,
            "message" => "Your account is inactive."
        ]);

        exit;
    }

    $_SESSION["user_id"] = $user["id"];
    $_SESSION["role"] = $user["role"];
    $_SESSION["email"] = $user["email"];

    $response = [
        "success" => true,
        "message" => "Login successful.",
        "user" => [
            "id" => $user["id"],
            "email" => $user["email"],
            "role" => $user["role"]
        ]
    ];

    // Get student information
    if ($user["role"] === "student") {

        $stmt = $conn->prepare(
            "SELECT id, matric_no, full_name, department,
                    programme, level, phone
             FROM students
             WHERE user_id = ?
             LIMIT 1"
        );

        $stmt->execute([$user["id"]]);

        $student = $stmt->fetch();

        if ($student) {
            $_SESSION["student_id"] = $student["id"];
            $response["student"] = $student;
        }
    }

    // Get supervisor information
    if ($user["role"] === "supervisor") {

        $stmt = $conn->prepare(
            "SELECT id, staff_id, full_name, department, phone
             FROM supervisors
             WHERE user_id = ?
             LIMIT 1"
        );

        $stmt->execute([$user["id"]]);

        $supervisor = $stmt->fetch();

        if ($supervisor) {
            $_SESSION["supervisor_id"] = $supervisor["id"];
            $response["supervisor"] = $supervisor;
        }
    }

    echo json_encode($response);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Login failed."
    ]);
}