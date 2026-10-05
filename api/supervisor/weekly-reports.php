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

    /*
    |--------------------------------------------------------------------------
    | GET SUPERVISOR
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | GET ASSIGNED STUDENTS' REPORTS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            wr.id AS report_id,
            wr.placement_id,
            wr.week_number,
            wr.activities,
            wr.skills_learned,
            wr.challenges,
            wr.student_comment,
            wr.supervisor_comment,
            wr.status,
            wr.submitted_at,
            wr.reviewed_at,

            s.id AS student_id,
            s.full_name,
            s.matric_no,

            p.start_date,
            p.end_date,
            p.status AS placement_status,

            o.id AS organization_id,
            o.name AS organization_name,

            ara.id AS ai_analysis_id,
            ara.summary AS ai_summary,
            ara.skills_identified AS ai_skills_identified,
            ara.strengths AS ai_strengths,
            ara.weaknesses AS ai_weaknesses,
            ara.recommendations AS ai_recommendations,
            ara.performance_score AS ai_performance_score

        FROM weekly_reports wr

        INNER JOIN students s
            ON wr.student_id = s.id

        INNER JOIN placements p
            ON wr.placement_id = p.id

        LEFT JOIN organizations o
            ON p.organization_id = o.id

        LEFT JOIN ai_report_analysis ara
            ON wr.id = ara.report_id

        WHERE p.supervisor_id = ?

        ORDER BY wr.submitted_at DESC, wr.week_number DESC"
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
        "message" => "Unable to retrieve weekly reports.",
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}