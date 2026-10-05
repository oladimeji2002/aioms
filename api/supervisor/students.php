<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("supervisor");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ], JSON_PRETTY_PRINT);

    exit;
}

try {

    $stmt = $conn->prepare(
        "SELECT id
         FROM supervisors
         WHERE user_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $_SESSION["user_id"]
    ]);

    $supervisor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$supervisor) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Supervisor not found."
        ], JSON_PRETTY_PRINT);

        exit;
    }

    $supervisorId = $supervisor["id"];


    $stmt = $conn->prepare(
        "SELECT
            p.id AS placement_id,
            p.status AS placement_status,
            p.start_date,
            p.end_date,

            s.id AS student_id,
            s.full_name,
            s.matric_no,
            s.department,
            s.programme

        FROM placements p

        INNER JOIN students s
            ON p.student_id = s.id

        WHERE p.supervisor_id = ?

        ORDER BY p.id DESC"
    );

    $stmt->execute([
        $supervisorId
    ]);

    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "count" => count($students),
        "students" => $students
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve assigned students.",
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}