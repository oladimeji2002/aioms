<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("supervisor");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ], JSON_PRETTY_PRINT);

    exit;
}


$data = json_decode(
    file_get_contents("php://input"),
    true
);


$placementId =
    isset($data["placement_id"])
        ? (int) $data["placement_id"]
        : 0;

$supervisorScore =
    isset($data["supervisor_score"])
        ? (float) $data["supervisor_score"]
        : -1;


if ($placementId <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid placement ID is required."
    ], JSON_PRETTY_PRINT);

    exit;
}


if (
    $supervisorScore < 0 ||
    $supervisorScore > 100
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Supervisor score must be between 0 and 100."
    ], JSON_PRETTY_PRINT);

    exit;
}


try {

    /*
    |--------------------------------------------------------------------------
    | GET LOGGED-IN SUPERVISOR
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

    $supervisor =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$supervisor) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Supervisor not found."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    $supervisorId =
        (int) $supervisor["id"];


    /*
    |--------------------------------------------------------------------------
    | GET PLACEMENT
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            p.id,
            p.student_id,
            p.supervisor_id,
            p.start_date,
            p.end_date,
            p.status,

            s.full_name,
            s.matric_no

         FROM placements p

         INNER JOIN students s
            ON p.student_id = s.id

         WHERE p.id = ?
         AND p.supervisor_id = ?

         LIMIT 1"
    );

    $stmt->execute([
        $placementId,
        $supervisorId
    ]);

    $placement =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$placement) {

        http_response_code(403);

        echo json_encode([
            "success" => false,
            "message" =>
                "You are not authorized to evaluate this student."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    $studentId =
        (int) $placement["student_id"];


    /*
    |--------------------------------------------------------------------------
    | CHECK TRAINING COMPLETION
    |--------------------------------------------------------------------------
    */

    $today =
        date("Y-m-d");


    $trainingCompleted =
        (
            $placement["status"] === "completed"
            ||
            (
                !empty($placement["end_date"])
                &&
                $placement["end_date"] <= $today
            )
        );


    /*
    |--------------------------------------------------------------------------
    | COUNT WEEKLY REPORTS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            COUNT(DISTINCT week_number) AS completed_weeks
         FROM weekly_reports
         WHERE placement_id = ?
         AND student_id = ?
         AND week_number BETWEEN 1 AND 10"
    );

    $stmt->execute([
        $placementId,
        $studentId
    ]);

    $reportResult =
        $stmt->fetch(PDO::FETCH_ASSOC);


    $completedWeeks =
        (int) (
            $reportResult["completed_weeks"] ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | CHECK EACH REQUIRED WEEK
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT DISTINCT week_number
         FROM weekly_reports
         WHERE placement_id = ?
         AND student_id = ?
         AND week_number BETWEEN 1 AND 10
         ORDER BY week_number ASC"
    );

    $stmt->execute([
        $placementId,
        $studentId
    ]);

    $existingWeeks =
        $stmt->fetchAll(
            PDO::FETCH_COLUMN
        );


    $existingWeeks =
        array_map(
            "intval",
            $existingWeeks
        );


    $missingWeeks = [];


    for ($week = 1; $week <= 10; $week++) {

        if (
            !in_array(
                $week,
                $existingWeeks,
                true
            )
        ) {
            $missingWeeks[] = $week;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | BLOCK EARLY FINAL EVALUATION
    |--------------------------------------------------------------------------
    */

    if (
        $completedWeeks < 10 ||
        !empty($missingWeeks) ||
        !$trainingCompleted
    ) {

        http_response_code(409);

        $message =
            "Final evaluation is not available yet. ";


        if (
            $completedWeeks < 10 ||
            !empty($missingWeeks)
        ) {

            $message .=
                "The student has not completed all required SIWES weekly reports.";

        } elseif (!$trainingCompleted) {

            $message .=
                "The student's training period has not been completed yet.";
        }


        echo json_encode([

            "success" => false,

            "message" => $message,

            "completed_weeks" =>
                $completedWeeks,

            "required_weeks" =>
                10,

            "missing_weeks" =>
                $missingWeeks,

            "training_completed" =>
                $trainingCompleted

        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK EXISTING FINAL EVALUATION
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT id
         FROM final_evaluations
         WHERE placement_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $placementId
    ]);

    $existing =
        $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | CREATE OR UPDATE SUPERVISOR EVALUATION
    |--------------------------------------------------------------------------
    */

    if ($existing) {

        $stmt = $conn->prepare(
            "UPDATE final_evaluations
             SET supervisor_score = ?
             WHERE id = ?"
        );

        $stmt->execute([
            $supervisorScore,
            $existing["id"]
        ]);

        $evaluationId =
            (int) $existing["id"];

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO final_evaluations
            (
                placement_id,
                student_id,
                supervisor_score
            )
            VALUES (?, ?, ?)"
        );

        $stmt->execute([
            $placementId,
            $studentId,
            $supervisorScore
        ]);

        $evaluationId =
            (int) $conn->lastInsertId();
    }


    /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        "success" => true,

        "message" =>
            "Supervisor final evaluation submitted successfully.",

        "evaluation_id" =>
            $evaluationId,

        "placement_id" =>
            $placementId,

        "student_id" =>
            $studentId,

        "student_name" =>
            $placement["full_name"],

        "completed_weeks" =>
            $completedWeeks,

        "required_weeks" =>
            10,

        "supervisor_score" =>
            number_format(
                $supervisorScore,
                2,
                ".",
                ""
            ),

        "status" =>
            "submitted"

    ], JSON_PRETTY_PRINT);


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" =>
            "Unable to submit final evaluation.",

        "error" =>
            $e->getMessage()

    ], JSON_PRETTY_PRINT);
}