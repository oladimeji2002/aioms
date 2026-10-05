<?php

require_once __DIR__ . "/../auth/auth-helper.php";
require_once __DIR__ . "/gemini.php";

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


if ($placementId <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Placement ID is required."
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
            "message" => "Supervisor profile not found."
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

            s.full_name AS student_name,
            s.matric_no,
            s.department,
            s.programme,

            o.name AS organization_name,
            o.industry,
            o.location

         FROM placements p

         INNER JOIN students s
            ON p.student_id = s.id

         INNER JOIN organizations o
            ON p.organization_id = o.id

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
                "You are not authorized to generate an evaluation for this placement."
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

    $trainingCompleted =
        (
            $placement["status"] === "completed"
            ||
            (
                !empty($placement["end_date"]) &&
                $placement["end_date"] <= date("Y-m-d")
            )
        );


    if (!$trainingCompleted) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" =>
                "The student's industrial training has not been completed yet."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | GET WEEKLY REPORTS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            id,
            week_number,
            activities,
            skills_learned,
            challenges,
            student_comment,
            supervisor_comment,
            status

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

    $reports =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | VERIFY ALL 10 WEEKS EXIST
    |--------------------------------------------------------------------------
    */

    $existingWeeks = [];

    foreach ($reports as $report) {

        $existingWeeks[] =
            (int) $report["week_number"];
    }


    $missingWeeks = [];

    for ($week = 1; $week <= 10; $week++) {

        if (
            !in_array(
                $week,
                $existingWeeks,
                true
            )
        ) {

            $missingWeeks[] =
                $week;
        }
    }


    if (!empty($missingWeeks)) {

        http_response_code(409);

        echo json_encode([

            "success" => false,

            "message" =>
                "AI final evaluation cannot be generated because the student has not completed all 10 SIWES weekly reports.",

            "completed_weeks" =>
                count($existingWeeks),

            "required_weeks" =>
                10,

            "missing_weeks" =>
                $missingWeeks

        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | GET SUPERVISOR SCORE
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            id,
            supervisor_score
         FROM final_evaluations
         WHERE placement_id = ?
         AND student_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $placementId,
        $studentId
    ]);

    $evaluation =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$evaluation) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" =>
                "Supervisor final evaluation has not been submitted yet."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | PREPARE WEEKLY REPORT DATA
    |--------------------------------------------------------------------------
    */

    $weeklyReportsText = "";


    foreach ($reports as $report) {

        $weeklyReportsText .=

            "Week " .
            $report["week_number"] .
            "\n" .

            "Activities: " .
            ($report["activities"] ?? "") .
            "\n" .

            "Skills Learned: " .
            ($report["skills_learned"] ?? "") .
            "\n" .

            "Challenges: " .
            ($report["challenges"] ?? "") .
            "\n" .

            "Student Comment: " .
            ($report["student_comment"] ?? "") .
            "\n" .

            "Supervisor Comment: " .
            ($report["supervisor_comment"] ?? "") .
            "\n" .

            "Report Status: " .
            ($report["status"] ?? "") .
            "\n\n";
    }


    /*
    |--------------------------------------------------------------------------
    | GEMINI PROMPT
    |--------------------------------------------------------------------------
    */

    $prompt = <<<PROMPT
You are the AI final evaluation engine for AIOIMS
(AI-Based Industrial Training Online Management System).

Evaluate the student's COMPLETE industrial training experience.

Use ONLY the information supplied below.

STUDENT INFORMATION

Name: {$placement["student_name"]}
Matric Number: {$placement["matric_no"]}
Department: {$placement["department"]}
Programme: {$placement["programme"]}

TRAINING ORGANIZATION

Organization: {$placement["organization_name"]}
Industry: {$placement["industry"]}
Location: {$placement["location"]}

TRAINING PERIOD

Start Date: {$placement["start_date"]}
End Date: {$placement["end_date"]}

SUPERVISOR FINAL SCORE

{$evaluation["supervisor_score"]} / 100

COMPLETE WEEKLY REPORT HISTORY

{$weeklyReportsText}

TASK

Produce a final AI evaluation of the student's complete SIWES performance.

Return ONLY valid JSON.

Return exactly:

{
    "ai_score": 0,
    "skills_developed": "",
    "strengths": "",
    "weaknesses": "",
    "ai_summary": "",
    "recommendation": ""
}

RULES:

1. ai_score must be between 0 and 100.
2. Base the score only on the supplied weekly reports.
3. Do not invent activities, skills or achievements.
4. Consider consistency across all ten weeks.
5. Consider technical development.
6. Consider relevance of activities to the student's programme.
7. Consider problem solving and learning progress.
8. Consider supervisor feedback where provided.
9. skills_developed should summarize demonstrated technical and professional skills.
10. strengths should identify the strongest aspects of the training.
11. weaknesses should identify realistic areas requiring improvement.
12. ai_summary should summarize the student's overall development.
13. recommendation should give a practical final recommendation.
14. Return JSON only. No markdown.
PROMPT;


    /*
    |--------------------------------------------------------------------------
    | CALL GEMINI
    |--------------------------------------------------------------------------
    */

    $aiResult =
        callGemini($prompt);


    /*
    |--------------------------------------------------------------------------
    | VALIDATE AI RESPONSE
    |--------------------------------------------------------------------------
    */

    $requiredFields = [

        "ai_score",
        "skills_developed",
        "strengths",
        "weaknesses",
        "ai_summary",
        "recommendation"

    ];


    foreach (
        $requiredFields
        as $field
    ) {

        if (
            !array_key_exists(
                $field,
                $aiResult
            )
        ) {

            throw new Exception(
                "AI response is missing field: " .
                $field
            );
        }
    }


    $aiScore =
        (float) $aiResult["ai_score"];


    if (
        $aiScore < 0 ||
        $aiScore > 100
    ) {

        throw new Exception(
            "Gemini returned an invalid AI score."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CALCULATE FINAL SCORE
    |--------------------------------------------------------------------------
    */

    $supervisorScore =
        (float)
        $evaluation["supervisor_score"];


    $overallScore =
        (
            $supervisorScore * 0.60
        ) +
        (
            $aiScore * 0.40
        );


    $overallScore =
        round(
            $overallScore,
            2
        );


    /*
    |--------------------------------------------------------------------------
    | SAVE FINAL AI EVALUATION
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "UPDATE final_evaluations
         SET
            ai_score = ?,
            overall_score = ?,
            skills_developed = ?,
            strengths = ?,
            weaknesses = ?,
            ai_summary = ?,
            recommendation = ?
         WHERE id = ?"
    );


    $stmt->execute([

        $aiScore,

        $overallScore,

        trim(
            $aiResult["skills_developed"]
        ),

        trim(
            $aiResult["strengths"]
        ),

        trim(
            $aiResult["weaknesses"]
        ),

        trim(
            $aiResult["ai_summary"]
        ),

        trim(
            $aiResult["recommendation"]
        ),

        $evaluation["id"]

    ]);


    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        "success" => true,

        "message" =>
            "AI final evaluation completed successfully.",

        "evaluation" => [

            "id" =>
                (int) $evaluation["id"],

            "placement_id" =>
                $placementId,

            "student_id" =>
                $studentId,

            "student_name" =>
                $placement["student_name"],

            "supervisor_score" =>
                $supervisorScore,

            "ai_score" =>
                $aiScore,

            "overall_score" =>
                $overallScore,

            "skills_developed" =>
                trim(
                    $aiResult["skills_developed"]
                ),

            "strengths" =>
                trim(
                    $aiResult["strengths"]
                ),

            "weaknesses" =>
                trim(
                    $aiResult["weaknesses"]
                ),

            "ai_summary" =>
                trim(
                    $aiResult["ai_summary"]
                ),

            "recommendation" =>
                trim(
                    $aiResult["recommendation"]
                )

        ]

    ], JSON_PRETTY_PRINT);


} catch (PDOException $e) {

    error_log(
        "AI final evaluation PDO error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" =>
            "Unable to complete AI final evaluation.",

        "error" =>
            $e->getMessage()

    ], JSON_PRETTY_PRINT);


} catch (Exception $e) {

    error_log(
        "AI final evaluation error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" =>
            "AI final evaluation failed.",

        "error" =>
            $e->getMessage()

    ], JSON_PRETTY_PRINT);
}