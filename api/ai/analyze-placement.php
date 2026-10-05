<?php

require_once __DIR__ . "/../auth/auth-helper.php";
require_once __DIR__ . "/gemini.php";

header("Content-Type: application/json");

requireRole("student");


/*
|--------------------------------------------------------------------------
| ONLY POST IS ALLOWED
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


try {

    /*
    |--------------------------------------------------------------------------
    | GET LOGGED-IN STUDENT
    |--------------------------------------------------------------------------
    */

    $userId = $_SESSION["user_id"] ?? 0;

    if (!$userId) {

        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Authenticated student session not found."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    $stmt = $conn->prepare(
        "SELECT
            id,
            full_name,
            department,
            programme,
            level,
            skills,
            career_interest,
            industry_interest,
            location_preference
         FROM students
         WHERE user_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $userId
    ]);

    $student = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$student) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Student profile not found."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    $studentId = (int) $student["id"];


    /*
    |--------------------------------------------------------------------------
    | GET THIS STUDENT'S LATEST APPLICATION
    |--------------------------------------------------------------------------
    |
    | No application_id is accepted from the frontend.
    | The server determines the application from the logged-in student.
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            id AS application_id,
            training_duration,
            preferred_location,
            status,
            submitted_at
         FROM applications
         WHERE student_id = ?
         AND status IN ('pending', 'recommended', 'placed')
         ORDER BY id DESC
         LIMIT 1"
    );

    $stmt->execute([
        $studentId
    ]);

    $application = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$application) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" =>
                "No industrial training application found for this student."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    $applicationId =
        (int) $application["application_id"];


    /*
    |--------------------------------------------------------------------------
    | GET AVAILABLE ORGANIZATIONS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            id,
            name,
            industry,
            location,
            description
         FROM organizations
         WHERE status = 'active'
         ORDER BY name ASC"
    );

    $stmt->execute();

    $organizations =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    if (!$organizations) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "No available organizations found."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | PREPARE ORGANIZATION DATA FOR GEMINI
    |--------------------------------------------------------------------------
    */

    $organizationData = [];


    foreach ($organizations as $organization) {

        $organizationData[] = [

            "id" =>
                (int) $organization["id"],

            "name" =>
                $organization["name"],

            "industry" =>
                $organization["industry"],

            "location" =>
                $organization["location"],

            "description" =>
                $organization["description"]
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | BUILD AI PROMPT
    |--------------------------------------------------------------------------
    */

    $prompt = "

You are the AI placement engine for AIOIMS
(AI-Based Industrial Training Online Management System).

Your task is to recommend the MOST SUITABLE organization
for a student's industrial training.

IMPORTANT RULES:

1. Consider the student's department.
2. Consider the student's programme.
3. Consider the student's level.
4. Consider the student's skills.
5. Consider career interest.
6. Consider industry interest.
7. Consider preferred location.
8. Consider training preferred location.
9. Consider the organization's industry.
10. Consider the organization's location.
11. Consider the organization's description.
12. Do not invent an organization.
13. Select ONLY from the organizations provided below.
14. The recommendation must be relevant to the student's field.
15. Give realistic scores between 0 and 100.
16. Return ONLY valid JSON.
17. Do not include markdown.
18. If no organization is a perfect match, select the closest reasonable organization and explain the limitation.

STUDENT INFORMATION

Name:
{$student["full_name"]}

Department:
{$student["department"]}

Programme:
{$student["programme"]}

Level:
{$student["level"]}

Skills:
{$student["skills"]}

Career Interest:
{$student["career_interest"]}

Industry Interest:
{$student["industry_interest"]}

Student Location Preference:
{$student["location_preference"]}

Training Preferred Location:
{$application["preferred_location"]}

Training Duration:
{$application["training_duration"]} weeks


AVAILABLE ORGANIZATIONS:

"
        .
        json_encode(
            $organizationData,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_UNICODE
        )
        .

"

Return EXACTLY this JSON structure:

{
    \"organization_id\": 0,
    \"organization_name\": \"\",
    \"industry\": \"\",
    \"location\": \"\",
    \"skills_match\": 0,
    \"career_match\": 0,
    \"overall_score\": 0,
    \"reason\": \"\",
    \"recommendation\": \"\"
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
    | VALIDATE AI RESPONSE
    |--------------------------------------------------------------------------
    */

    if (!is_array($aiResult)) {

        throw new Exception(
            "Invalid AI response."
        );
    }


    $requiredFields = [

        "organization_id",
        "organization_name",
        "industry",
        "location",
        "skills_match",
        "career_match",
        "overall_score",
        "reason",
        "recommendation"

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
    | VALIDATE ORGANIZATION ID
    |--------------------------------------------------------------------------
    */

    $organizationId =
        (int) $aiResult["organization_id"];


    if ($organizationId <= 0) {

        throw new Exception(
            "AI returned an invalid organization ID."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY ORGANIZATION
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            id,
            name,
            industry,
            location,
            description
         FROM organizations
         WHERE id = ?
         AND status = 'active'
         LIMIT 1"
    );

    $stmt->execute([
        $organizationId
    ]);

    $organization =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$organization) {

        throw new Exception(
            "AI selected an invalid organization."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE SCORES
    |--------------------------------------------------------------------------
    */

    $skillsMatch = max(
        0,
        min(
            100,
            (float) $aiResult["skills_match"]
        )
    );


    $careerMatch = max(
        0,
        min(
            100,
            (float) $aiResult["career_match"]
        )
    );


    $overallScore = max(
        0,
        min(
            100,
            (float) $aiResult["overall_score"]
        )
    );


    /*
    |--------------------------------------------------------------------------
    | SAVE AI RECOMMENDATION
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "INSERT INTO ai_placement_recommendations
        (
            application_id,
            organization_id,
            recommendation,
            reason,
            skills_match,
            career_match,
            overall_score,
            ai_model
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );


    $stmt->execute([

        $applicationId,

        $organizationId,

        $aiResult["recommendation"],

        $aiResult["reason"],

        $skillsMatch,

        $careerMatch,

        $overallScore,

        "gemini-flash-latest"

    ]);


    /*
    |--------------------------------------------------------------------------
    | UPDATE APPLICATION STATUS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "UPDATE applications
         SET status = 'recommended'
         WHERE id = ?
         AND student_id = ?"
    );


    $stmt->execute([

        $applicationId,

        $studentId

    ]);


    /*
    |--------------------------------------------------------------------------
    | PREPARE FRONTEND-COMPATIBLE DATA
    |--------------------------------------------------------------------------
    |
    | Your current renderRecommendationResult() expects:
    |
    | rec.org
    | rec.industry
    | rec.location
    | rec.role
    | rec.match
    | rec.factors
    | rec.alternatives
    |
    |--------------------------------------------------------------------------
    */

    $factors = [

        [
            "label" =>
                "Skills compatibility",
            "value" =>
                round($skillsMatch)
        ],

        [
            "label" =>
                "Career compatibility",
            "value" =>
                round($careerMatch)
        ],

        [
            "label" =>
                "Overall compatibility",
            "value" =>
                round($overallScore)
        ]

    ];


    /*
    |--------------------------------------------------------------------------
    | FINAL RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        "success" => true,

        "message" =>
            "AI placement recommendation generated successfully.",

        "application_id" =>
            $applicationId,

        "recommendation" => [

            /*
            | Canonical backend fields
            */

            "organization_id" =>
                $organizationId,

            "organization_name" =>
                $organization["name"],

            "industry" =>
                $organization["industry"],

            "location" =>
                $organization["location"],

            "skills_match" =>
                $skillsMatch,

            "career_match" =>
                $careerMatch,

            "overall_score" =>
                $overallScore,

            "reason" =>
                $aiResult["reason"],

            "recommendation" =>
                $aiResult["recommendation"],


            /*
            | Current UI compatibility fields
            */

            "org" =>
                $organization["name"],

            "match" =>
                $overallScore,

            "role" =>
                "Industrial Training Intern",

            "factors" =>
                $factors,

            "alternatives" =>
                []

        ]

    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {

    error_log(
        "AI placement analysis error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" =>
            $e->getMessage()

    ], JSON_PRETTY_PRINT);
}
