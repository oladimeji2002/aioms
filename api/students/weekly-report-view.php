<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("student");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ], JSON_PRETTY_PRINT);

    exit;
}

$reportId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($reportId < 1) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "A valid report ID is required."
    ], JSON_PRETTY_PRINT);

    exit;
}

try {

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
            ara.performance_score AS ai_performance_score

        FROM weekly_reports wr

        INNER JOIN students s
            ON wr.student_id = s.id

        LEFT JOIN ai_report_analysis ara
            ON wr.id = ara.report_id

        WHERE wr.id = ?
        AND s.user_id = ?

        LIMIT 1"
    );

    $stmt->execute([
        $reportId,
        $_SESSION["user_id"]
    ]);

    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Weekly report not found."
        ], JSON_PRETTY_PRINT);

        exit;
    }

    echo json_encode([
        "success" => true,
        "report" => $report
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve weekly report.",
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}