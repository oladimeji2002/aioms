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

            wr.id AS report_id,
            wr.week_number,
            wr.activities,
            wr.skills_learned,
            wr.challenges,
            wr.student_comment,
            wr.status,
            wr.submitted_at,

            s.id AS student_id,
            s.full_name,
            s.matric_no,
            s.department,
            s.programme,

            p.id AS placement_id,
            p.status AS placement_status

        FROM weekly_reports wr

        INNER JOIN students s
            ON wr.student_id = s.id

        INNER JOIN placements p
            ON wr.placement_id = p.id

        WHERE p.supervisor_id = ?
        AND wr.status = 'pending'

        ORDER BY wr.submitted_at ASC"
    );

    $stmt->execute([
        $supervisorId
    ]);

    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "count" => count($reports),
        "reports" => $reports
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve pending reports.",
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}