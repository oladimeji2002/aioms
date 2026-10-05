<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("student");

try {

    /*
    |--------------------------------------------------------------------------
    | GET STUDENT
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            s.id,
            s.full_name,
            s.matric_no,
            s.department,
            s.programme
         FROM students s
         WHERE s.user_id = ?
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
            "message" => "Student profile not found."
        ]);

        exit;
    }

    $studentId = $student["id"];

    /*
    |--------------------------------------------------------------------------
    | GET LATEST PLACEMENT
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            p.id AS placement_id,
            p.start_date,
            p.end_date,
            p.status,

            o.id AS organization_id,
            o.name AS organization_name,
            o.industry,
            o.location,

            sp.id AS supervisor_id,
            sp.full_name AS supervisor_name,
            sp.department AS supervisor_department,
            sp.phone AS supervisor_phone

         FROM placements p

         INNER JOIN organizations o
            ON p.organization_id = o.id

         LEFT JOIN supervisors sp
            ON p.supervisor_id = sp.id

         WHERE p.student_id = ?

         ORDER BY p.id DESC

         LIMIT 1"
    );

    $stmt->execute([
        $studentId
    ]);

    $placement = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | WEEKLY REPORT STATISTICS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            COUNT(*) AS total_reports,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_reports,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_reports,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_reports
         FROM weekly_reports
         WHERE student_id = ?"
    );

    $stmt->execute([
        $studentId
    ]);

    $reportStats = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | LATEST AI PERFORMANCE
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            ara.performance_score,
            ara.summary,
            ara.created_at,
            wr.week_number

         FROM ai_report_analysis ara

         INNER JOIN weekly_reports wr
            ON ara.report_id = wr.id

         WHERE wr.student_id = ?

         ORDER BY ara.id DESC

         LIMIT 1"
    );

    $stmt->execute([
        $studentId
    ]);

    $latestAIAnalysis = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | FINAL EVALUATION
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            id,
            supervisor_score,
            ai_score,
            overall_score,
            skills_developed,
            strengths,
            weaknesses,
            ai_summary,
            recommendation,
            created_at

         FROM final_evaluations

         WHERE student_id = ?

         ORDER BY id DESC

         LIMIT 1"
    );

    $stmt->execute([
        $studentId
    ]);

    $finalEvaluation = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "student" => $student,
        "placement" => $placement ?: null,
        "report_statistics" => [
            "total_reports" => (int) ($reportStats["total_reports"] ?? 0),
            "approved_reports" => (int) ($reportStats["approved_reports"] ?? 0),
            "pending_reports" => (int) ($reportStats["pending_reports"] ?? 0),
            "rejected_reports" => (int) ($reportStats["rejected_reports"] ?? 0)
        ],
        "latest_ai_analysis" => $latestAIAnalysis ?: null,
        "final_evaluation" => $finalEvaluation ?: null
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load student dashboard.",
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}