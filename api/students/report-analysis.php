<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole(["student", "supervisor"]);

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

$reportId = isset($_GET["report_id"])
    ? (int) $_GET["report_id"]
    : 0;

if ($reportId <= 0) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Report ID is required."
    ]);

    exit;
}

try {

    $stmt = $conn->prepare(
        "SELECT
            ara.id,
            ara.report_id,
            ara.summary,
            ara.skills_identified,
            ara.strengths,
            ara.weaknesses,
            ara.recommendations,
            ara.performance_score,
            ara.created_at,

            wr.week_number,
            wr.status,

            s.id AS student_id,
            s.full_name,
            s.matric_no,
            s.department,
            s.programme,

            o.name AS organization_name,
            o.industry AS organization_industry

        FROM ai_report_analysis ara

        INNER JOIN weekly_reports wr
            ON ara.report_id = wr.id

        INNER JOIN students s
            ON wr.student_id = s.id

        INNER JOIN placements p
            ON wr.placement_id = p.id

        INNER JOIN organizations o
            ON p.organization_id = o.id

        WHERE ara.report_id = ?
        AND s.user_id = ?

        LIMIT 1"
    );

    $stmt->execute([
        $reportId,
        $_SESSION["user_id"]
    ]);

    $analysis = $stmt->fetch();

    if (!$analysis) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "AI analysis not found for this report."
        ]);

        exit;
    }

    echo json_encode([
        "success" => true,
        "analysis" => $analysis
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve AI report analysis.",
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}