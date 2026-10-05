<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("supervisor");

try {

    $stmt = $conn->prepare(
        "SELECT
            wr.id AS report_id,
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
            s.department,
            s.programme,

            o.name AS organization_name,
            o.industry,
            o.location

        FROM weekly_reports wr

        INNER JOIN placements p
            ON wr.placement_id = p.id

        INNER JOIN students s
            ON wr.student_id = s.id

        INNER JOIN organizations o
            ON p.organization_id = o.id

        INNER JOIN supervisors sup
            ON p.supervisor_id = sup.id

        WHERE sup.user_id = ?

        ORDER BY wr.week_number ASC, wr.submitted_at DESC"
    );

    $stmt->execute([
        $_SESSION["user_id"]
    ]);

    $reports = $stmt->fetchAll();

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