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

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON payload."
    ]);

    exit;
}

$weekNumber = isset($data["week_number"])
    ? (int) $data["week_number"]
    : 0;

$activities = isset($data["activities"])
    ? trim($data["activities"])
    : "";

$skillsLearned = isset($data["skills_learned"])
    ? trim($data["skills_learned"])
    : "";

$challenges = isset($data["challenges"])
    ? trim($data["challenges"])
    : "";

$studentComment = isset($data["student_comment"])
    ? trim($data["student_comment"])
    : "";

/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if ($weekNumber < 1 || $weekNumber > 52) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid week number."
    ]);

    exit;
}

if ($activities === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Activities are required."
    ]);

    exit;
}

try {

    /*
    |--------------------------------------------------------------------------
    | GET STUDENT
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT id
         FROM students
         WHERE user_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $_SESSION["user_id"]
    ]);

    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Student not found."
        ]);

        exit;
    }

    $studentId = $student["id"];

    /*
    |--------------------------------------------------------------------------
    | GET ACTIVE PLACEMENT
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT id
         FROM placements
         WHERE student_id = ?
         AND status = 'active'
         LIMIT 1"
    );

    $stmt->execute([
        $studentId
    ]);

    $placement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$placement) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "No active industrial training placement found."
        ]);

        exit;
    }

    $placementId = $placement["id"];

    /*
    |--------------------------------------------------------------------------
    | CHECK DUPLICATE WEEK
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT id
         FROM weekly_reports
         WHERE placement_id = ?
         AND week_number = ?
         LIMIT 1"
    );

    $stmt->execute([
        $placementId,
        $weekNumber
    ]);

    if ($stmt->fetch()) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "A report for week " . $weekNumber . " already exists."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE REPORT
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "INSERT INTO weekly_reports
        (
            placement_id,
            student_id,
            week_number,
            activities,
            skills_learned,
            challenges,
            student_comment,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
    );

    $stmt->execute([
        $placementId,
        $studentId,
        $weekNumber,
        $activities,
        $skillsLearned,
        $challenges,
        $studentComment
    ]);

    $reportId = $conn->lastInsertId();

    echo json_encode([
        "success" => true,
        "message" => "Weekly report submitted successfully.",
        "report_id" => (int) $reportId,
        "week_number" => $weekNumber,
        "status" => "pending"
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to submit weekly report.",
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}