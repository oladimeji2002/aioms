<?php

require_once __DIR__ . "/../auth/auth-helper.php";
require_once __DIR__ . "/gemini.php";

header("Content-Type: application/json");


/*
|--------------------------------------------------------------------------
| AUTHORIZATION
|--------------------------------------------------------------------------
|
| Both students and supervisors can request AI analysis.
|
*/

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    !in_array(
        $_SESSION["role"],
        ["student", "supervisor"],
        true
    )
) {

    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| METHOD
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| REQUEST DATA
|--------------------------------------------------------------------------
*/

$data = json_decode(
    file_get_contents("php://input"),
    true
);


if (!is_array($data)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON payload."
    ], JSON_PRETTY_PRINT);

    exit;
}


$reportId =
    isset($data["report_id"])
        ? (int) $data["report_id"]
        : 0;


if ($reportId <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Report ID is required."
    ], JSON_PRETTY_PRINT);

    exit;
}


try {

    $userId =
        (int) $_SESSION["user_id"];

    $role =
        $_SESSION["role"];


    /*
    |--------------------------------------------------------------------------
    | GET REPORT
    |--------------------------------------------------------------------------
    |
    | Students can analyse only their own reports.
    | Supervisors can analyse only reports belonging to their
    | assigned students.
    |--------------------------------------------------------------------------
    */

    if ($role === "student") {

        $stmt = $conn->prepare(
            "SELECT
                wr.id AS report_id,
                wr.week_number,
                wr.activities,
                wr.skills_learned,
                wr.challenges,
                wr.student_comment,

                s.id AS student_id,
                s.full_name,
                s.department,
                s.programme,
                s.level,
                s.skills,
                s.career_interest,
                s.industry_interest,

                o.name AS organization_name,
                o.industry AS organization_industry,
                o.location AS organization_location,
                o.description AS organization_description

            FROM weekly_reports wr

            INNER JOIN placements p
                ON wr.placement_id = p.id

            INNER JOIN students s
                ON wr.student_id = s.id

            INNER JOIN organizations o
                ON p.organization_id = o.id

            WHERE wr.id = ?
            AND s.user_id = ?

            LIMIT 1"
        );

        $stmt->execute([
            $reportId,
            $userId
        ]);

    } else {

        $stmt = $conn->prepare(
            "SELECT
                wr.id AS report_id,
                wr.week_number,
                wr.activities,
                wr.skills_learned,
                wr.challenges,
                wr.student_comment,

                s.id AS student_id,
                s.full_name,
                s.department,
                s.programme,
                s.level,
                s.skills,
                s.career_interest,
                s.industry_interest,

                o.name AS organization_name,
                o.industry AS organization_industry,
                o.location AS organization_location,
                o.description AS organization_description

            FROM weekly_reports wr

            INNER JOIN placements p
                ON wr.placement_id = p.id

            INNER JOIN students s
                ON wr.student_id = s.id

            INNER JOIN supervisors sup
                ON p.supervisor_id = sup.id

            INNER JOIN organizations o
                ON p.organization_id = o.id

            WHERE wr.id = ?
            AND sup.user_id = ?

            LIMIT 1"
        );

        $stmt->execute([
            $reportId,
            $userId
        ]);
    }


    $report =
        $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | REPORT NOT FOUND / NOT AUTHORIZED
    |--------------------------------------------------------------------------
    */

    if (!$report) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" =>
                "Weekly report not found or you are not authorized to analyse it."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK EXISTING ANALYSIS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            id,
            summary,
            skills_identified,
            strengths,
            weaknesses,
            recommendations,
            performance_score,
            created_at

         FROM ai_report_analysis

         WHERE report_id = ?

         LIMIT 1"
    );

    $stmt->execute([
        $reportId
    ]);

    $existingAnalysis =
        $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | RETURN EXISTING ANALYSIS
    |--------------------------------------------------------------------------
    |
    | This prevents unnecessary Gemini calls if the report has already
    | been analysed.
    |--------------------------------------------------------------------------
    */

    if ($existingAnalysis) {

        echo json_encode([

            "success" => true,

            "message" =>
                "AI analysis already exists for this report.",

            "already_analyzed" =>
                true,

            "analysis" => [

                "id" =>
                    (int) $existingAnalysis["id"],

                "report_id" =>
                    $reportId,

                "week_number" =>
                    (int) $report["week_number"],

                "summary" =>
                    $existingAnalysis["summary"],

                "skills_identified" =>
                    $existingAnalysis["skills_identified"],

                "strengths" =>
                    $existingAnalysis["strengths"],

                "weaknesses" =>
                    $existingAnalysis["weaknesses"],

                "recommendations" =>
                    $existingAnalysis["recommendations"],

                "performance_score" =>
                    (float) $existingAnalysis["performance_score"],

                "created_at" =>
                    $existingAnalysis["created_at"]
            ]

        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | GEMINI PROMPT
    |--------------------------------------------------------------------------
    */

    $prompt = "

You are the AI weekly industrial training report analyzer
for AIOIMS (AI-Based Industrial Training Online Management System).

Analyze the student's weekly industrial training report.

Your analysis must determine:

1. What the student actually did during the week.
2. Important skills demonstrated or learned.
3. Strengths demonstrated by the student.
4. Weaknesses or areas requiring improvement.
5. Practical recommendations.
6. A realistic performance score from 0 to 100.

IMPORTANT RULES:

- Compare the activities with the student's academic programme.
- Compare the activities with the assigned organization's industry.
- Consider the student's listed skills.
- Consider the student's career interest.
- Do not invent activities.
- Do not invent skills that are not reasonably supported by the report.
- Be objective.
- A short report should not automatically receive a high score.
- Consider relevance, quality, completeness and evidence of learning.
- Performance score must be between 0 and 100.
- Return ONLY valid JSON.
- Do not use markdown.
- Do not include explanations outside the JSON.

STUDENT INFORMATION:

Name:
{$report["full_name"]}

Department:
{$report["department"]}

Programme:
{$report["programme"]}

Level:
{$report["level"]}

Skills:
{$report["skills"]}

Career Interest:
{$report["career_interest"]}

Industry Interest:
{$report["industry_interest"]}


PLACEMENT INFORMATION:

Organization:
{$report["organization_name"]}

Industry:
{$report["organization_industry"]}

Location:
{$report["organization_location"]}

Organization Description:
{$report["organization_description"]}


WEEKLY REPORT:

Week:
{$report["week_number"]}

Activities:
{$report["activities"]}

Skills Learned:
{$report["skills_learned"]}

Challenges:
{$report["challenges"]}

Student Comment:
{$report["student_comment"]}


RETURN EXACTLY THIS JSON:

{
    \"summary\": \"\",
    \"skills_identified\": \"\",
    \"strengths\": \"\",
    \"weaknesses\": \"\",
    \"recommendations\": \"\",
    \"performance_score\": 0
}
";


    /*
    |--------------------------------------------------------------------------
    | CALL GEMINI
    |--------------------------------------------------------------------------
    */

    $aiResult =
        callGemini($prompt);


    /*
    |--------------------------------------------------------------------------
    | VALIDATE RESPONSE
    |--------------------------------------------------------------------------
    */

    $requiredFields = [

        "summary",
        "skills_identified",
        "strengths",
        "weaknesses",
        "recommendations",
        "performance_score"

    ];


    foreach ($requiredFields as $field) {

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


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE SCORE
    |--------------------------------------------------------------------------
    */

    $performanceScore =
        (float) $aiResult["performance_score"];


    $performanceScore =
        max(
            0,
            min(
                100,
                $performanceScore
            )
        );


    /*
    |--------------------------------------------------------------------------
    | SAVE ANALYSIS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "INSERT INTO ai_report_analysis
        (
            report_id,
            summary,
            skills_identified,
            strengths,
            weaknesses,
            recommendations,
            performance_score
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );


    $stmt->execute([

        $reportId,

        trim(
            (string)
            $aiResult["summary"]
        ),

        trim(
            (string)
            $aiResult["skills_identified"]
        ),

        trim(
            (string)
            $aiResult["strengths"]
        ),

        trim(
            (string)
            $aiResult["weaknesses"]
        ),

        trim(
            (string)
            $aiResult["recommendations"]
        ),

        $performanceScore

    ]);


    $analysisId =
        (int) $conn->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        "success" => true,

        "message" =>
            "AI weekly report analysis completed successfully.",

        "already_analyzed" =>
            false,

        "analysis" => [

            "id" =>
                $analysisId,

            "report_id" =>
                $reportId,

            "week_number" =>
                (int) $report["week_number"],

            "summary" =>
                $aiResult["summary"],

            "skills_identified" =>
                $aiResult["skills_identified"],

            "strengths" =>
                $aiResult["strengths"],

            "weaknesses" =>
                $aiResult["weaknesses"],

            "recommendations" =>
                $aiResult["recommendations"],

            "performance_score" =>
                $performanceScore

        ]

    ], JSON_PRETTY_PRINT);


} catch (Exception $e) {

    error_log(
        "AI report analysis error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" =>
            "AI report analysis failed.",

        "error" =>
            $e->getMessage()

    ], JSON_PRETTY_PRINT);
}