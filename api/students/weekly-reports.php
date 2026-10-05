<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("student");

$method = $_SERVER["REQUEST_METHOD"];

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
    | GET WEEKLY REPORTS
    |--------------------------------------------------------------------------
    */

    if ($method === "GET") {

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

            LEFT JOIN ai_report_analysis ara
                ON wr.id = ara.report_id

            WHERE wr.student_id = ?

            ORDER BY wr.week_number ASC, wr.id ASC"
        );

        $stmt->execute([
            $studentId
        ]);

        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "count" => count($reports),
            "reports" => $reports
        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE WEEKLY REPORT
    |--------------------------------------------------------------------------
    */

    if ($method === "POST") {

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
                "message" =>
                    "A report for week " .
                    $weekNumber .
                    " already exists."
            ]);

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | INSERT REPORT
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


        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        echo json_encode([
            "success" => true,
            "message" => "Weekly report submitted successfully.",
            "report_id" => (int) $reportId,
            "week_number" => $weekNumber,
            "status" => "pending"
        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | METHOD NOT ALLOWED
    |--------------------------------------------------------------------------
    */

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to process weekly report.",
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}