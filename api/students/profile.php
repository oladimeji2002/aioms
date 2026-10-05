<?php

require_once __DIR__ . "/../auth/auth-helper.php";
header("Content-Type: application/json");

requireRole("student");

try {

    $stmt = $conn->prepare(
        "SELECT
            s.id,
            s.matric_no,
            s.full_name,
            s.department,
            s.programme,
            s.level,
            s.phone,
            s.skills,
            s.career_interest,
            s.industry_interest,
            s.location_preference,
            s.profile_image,
            u.email,
            u.status
         FROM students s
         INNER JOIN users u ON s.user_id = u.id
         WHERE s.user_id = ?
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

    echo json_encode([
        "success" => true,
        "student" => $student
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load student profile."
    ]);
}