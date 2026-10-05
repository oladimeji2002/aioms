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

try {

    $stmt = $conn->prepare(
        "SELECT
            fe.id,
            fe.placement_id,
            fe.student_id,
            fe.supervisor_score,
            fe.ai_score,
            fe.overall_score,
            fe.skills_developed,
            fe.strengths,
            fe.weaknesses,
            fe.ai_summary,
            fe.recommendation,

            p.start_date,
            p.end_date,
            p.status AS placement_status,

            o.name AS organization_name,
            o.industry AS organization_industry,
            o.location AS organization_location,

            s.full_name,
            s.matric_no,
            s.department,
            s.programme

        FROM final_evaluations fe

        INNER JOIN placements p
            ON fe.placement_id = p.id

        INNER JOIN students s
            ON fe.student_id = s.id

        INNER JOIN organizations o
            ON p.organization_id = o.id

        WHERE s.user_id = ?

        ORDER BY fe.id DESC

        LIMIT 1"
    );

    $stmt->execute([
        $_SESSION["user_id"]
    ]);

    $evaluation =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$evaluation) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" =>
                "Final evaluation is not available yet."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    echo json_encode([

        "success" => true,

        "evaluation" => [

            "id" =>
                (int) $evaluation["id"],

            "placement_id" =>
                (int) $evaluation["placement_id"],

            "student_id" =>
                (int) $evaluation["student_id"],

            "student_name" =>
                $evaluation["full_name"],

            "matric_no" =>
                $evaluation["matric_no"],

            "department" =>
                $evaluation["department"],

            "programme" =>
                $evaluation["programme"],

            "supervisor_score" =>
                (float) $evaluation["supervisor_score"],

            "ai_score" =>
                $evaluation["ai_score"] !== null
                    ? (float) $evaluation["ai_score"]
                    : null,

            "overall_score" =>
                $evaluation["overall_score"] !== null
                    ? (float) $evaluation["overall_score"]
                    : null,

            "skills_developed" =>
                $evaluation["skills_developed"],

            "strengths" =>
                $evaluation["strengths"],

            "weaknesses" =>
                $evaluation["weaknesses"],

            "ai_summary" =>
                $evaluation["ai_summary"],

            "recommendation" =>
                $evaluation["recommendation"],

            "organization_name" =>
                $evaluation["organization_name"],

            "organization_industry" =>
                $evaluation["organization_industry"],

            "organization_location" =>
                $evaluation["organization_location"],

            "start_date" =>
                $evaluation["start_date"],

            "end_date" =>
                $evaluation["end_date"],

            "placement_status" =>
                $evaluation["placement_status"]

        ]

    ], JSON_PRETTY_PRINT);


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to retrieve final evaluation.",
        "error" =>
            $e->getMessage()
    ], JSON_PRETTY_PRINT);
}