<?php

require_once __DIR__ . "/../auth/auth-helper.php";
require_once __DIR__ . "/gemini.php";

header("Content-Type: application/json");

requireRole("student");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$applicationId = isset($data["application_id"])
    ? (int) $data["application_id"]
    : 0;

if ($applicationId <= 0) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Application ID is required."
    ]);

    exit;
}

try {

    /*
    |--------------------------------------------------------------------------
    | GET STUDENT APPLICATION
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            a.id AS application_id,
            a.student_id,
            a.training_duration,
            a.preferred_location,
            a.status,

            s.full_name,
            s.matric_no,
            s.department,
            s.programme

        FROM applications a

        INNER JOIN students s
            ON a.student_id = s.id

        WHERE a.id = ?
        AND s.user_id = ?

        LIMIT 1"
    );

    $stmt->execute([
        $applicationId,
        $_SESSION["user_id"]
    ]);

    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Application not found."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | GET ACTIVE ORGANIZATIONS
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

    $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$organizations) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "No active organizations are available for recommendation."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | PREPARE ORGANIZATION DATA FOR AI
    |--------------------------------------------------------------------------
    */

    $organizationData = [];

    foreach ($organizations as $organization) {

        $organizationData[] = [
            "id" => (int) $organization["id"],
            "name" => $organization["name"],
            "industry" => $organization["industry"],
            "location" => $organization["location"],
            "description" => $organization["description"]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | AI PROMPT
    |--------------------------------------------------------------------------
    */

    $prompt = "
You are an AI placement recommendation assistant for AIOIMS
(AI-Based Industrial Training Online Management System).

Your task is to recommend the most suitable organizations for a student
based on the student's academic programme, department, preferred location,
training duration, and the organization's industry, location and description.

STUDENT INFORMATION:

Name: {$application["full_name"]}
Matric Number: {$application["matric_no"]}
Department: {$application["department"]}
Programme: {$application["programme"]}
Training Duration: {$application["training_duration"]} months
Preferred Location: {$application["preferred_location"]}

AVAILABLE ACTIVE ORGANIZATIONS:

" . json_encode($organizationData, JSON_PRETTY_PRINT) . "

RULES:

1. Recommend the 3 most suitable organizations.
2. Only recommend organizations from the provided list.
3. Do not invent organization IDs.
4. Consider programme and department relevance.
5. Consider industry relevance.
6. Consider preferred location.
7. Give each organization:
   - recommendation
   - reason
   - skills_match from 0 to 100
   - career_match from 0 to 100
   - overall_score from 0 to 100
8. Return ONLY valid JSON.
9. Do not use markdown.
10. Sort recommendations from highest overall_score to lowest.

Required JSON format:

{
    \"recommendations\": [
        {
            \"organization_id\": 1,
            \"recommendation\": \"Highly Recommended\",
            \"reason\": \"Explanation of why the organization is suitable.\",
            \"skills_match\": 90,
            \"career_match\": 88,
            \"overall_score\": 89
        }
    ]
}
";

    /*
    |--------------------------------------------------------------------------
    | CALL GEMINI
    |--------------------------------------------------------------------------
    */

    $aiResult = callGemini($prompt);

    if (
        !isset($aiResult["recommendations"]) ||
        !is_array($aiResult["recommendations"])
    ) {
        throw new Exception(
            "Gemini returned an invalid placement recommendation format."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VALID ORGANIZATION IDS
    |--------------------------------------------------------------------------
    */

    $validOrganizationIds = [];

    foreach ($organizations as $organization) {
        $validOrganizationIds[] = (int) $organization["id"];
    }

    /*
    |--------------------------------------------------------------------------
    | REMOVE OLD RECOMMENDATIONS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "DELETE FROM ai_placement_recommendations
         WHERE application_id = ?"
    );

    $stmt->execute([
        $applicationId
    ]);

    /*
    |--------------------------------------------------------------------------
    | SAVE AI RECOMMENDATIONS
    |--------------------------------------------------------------------------
    */

    $insert = $conn->prepare(
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
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )"
    );

    $savedRecommendations = [];

    foreach ($aiResult["recommendations"] as $recommendation) {

        $organizationId = isset(
            $recommendation["organization_id"]
        )
            ? (int) $recommendation["organization_id"]
            : 0;

        /*
        | Make sure AI did not invent an organization ID
        */

        if (!in_array(
            $organizationId,
            $validOrganizationIds,
            true
        )) {
            continue;
        }

        $recommendationText =
            $recommendation["recommendation"] ?? "";

        $reason =
            $recommendation["reason"] ?? "";

        $skillsMatch =
            isset($recommendation["skills_match"])
                ? (float) $recommendation["skills_match"]
                : 0;

        $careerMatch =
            isset($recommendation["career_match"])
                ? (float) $recommendation["career_match"]
                : 0;

        $overallScore =
            isset($recommendation["overall_score"])
                ? (float) $recommendation["overall_score"]
                : 0;

        /*
        | Keep scores within 0 - 100
        */

        $skillsMatch = max(0, min(100, $skillsMatch));
        $careerMatch = max(0, min(100, $careerMatch));
        $overallScore = max(0, min(100, $overallScore));

        $insert->execute([
            $applicationId,
            $organizationId,
            $recommendationText,
            $reason,
            $skillsMatch,
            $careerMatch,
            $overallScore,
            "gemini-flash-latest"
        ]);

        $savedRecommendations[] = [
            "organization_id" => $organizationId,
            "recommendation" => $recommendationText,
            "reason" => $reason,
            "skills_match" => $skillsMatch,
            "career_match" => $careerMatch,
            "overall_score" => $overallScore
        ];
    }

    if (!$savedRecommendations) {
        throw new Exception(
            "No valid AI placement recommendations were generated."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "AI placement recommendations generated successfully.",
        "application_id" => $applicationId,
        "recommendations" => $savedRecommendations
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "AI placement recommendation failed.",
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}