<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("student");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

try {

    /*
    |--------------------------------------------------------------------------
    | GET STUDENT REPORTS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT

            wr.id,
            wr.placement_id,
            wr.student_id,
            wr.week_number,
            wr.activities,
            wr.skills_learned,
            wr.challenges,
            wr.student_comment,
            wr.supervisor_comment,
            wr.status,
            wr.submitted_at,
            wr.reviewed_at,

            ara.id AS ai_analysis_id,
            ara.summary AS ai_summary,
            ara.skills_identified AS ai_skills_identified,
            ara.strengths AS ai_strengths,
            ara.weaknesses AS ai_weaknesses,
            ara.recommendations AS ai_recommendations,
            ara.performance_score AS ai_performance_score,
            ara.created_at AS ai_analyzed_at

        FROM weekly_reports wr

        INNER JOIN students s
            ON wr.student_id = s.id

        LEFT JOIN ai_report_analysis ara
            ON wr.id = ara.report_id

        WHERE s.user_id = ?

        ORDER BY wr.week_number ASC, wr.id ASC"
    );

    $stmt->execute([
        $_SESSION["user_id"]
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