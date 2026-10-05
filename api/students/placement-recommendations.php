<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("student");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

$applicationId = isset($_GET["application_id"])
    ? (int) $_GET["application_id"]
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

    // Confirm application belongs to logged-in student
    $stmt = $conn->prepare(
        "SELECT id
         FROM applications
         WHERE id = ?
         AND student_id = (
             SELECT id
             FROM students
             WHERE user_id = ?
             LIMIT 1
         )
         LIMIT 1"
    );

    $stmt->execute([
        $applicationId,
        $_SESSION["user_id"]
    ]);

    $application = $stmt->fetch();

    if (!$application) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Application not found."
        ]);

        exit;
    }

    // Get recommendations
    $stmt = $conn->prepare(
        "SELECT
            apr.id,
            apr.application_id,
            apr.organization_id,
            o.name AS organization_name,
            o.industry,
            o.location,
            o.description,
            apr.recommendation,
            apr.reason,
            apr.skills_match,
            apr.career_match,
            apr.overall_score,
            apr.ai_model,
            apr.created_at

         FROM ai_placement_recommendations apr

         INNER JOIN organizations o
             ON apr.organization_id = o.id

         WHERE apr.application_id = ?

         ORDER BY apr.overall_score DESC"
    );

    $stmt->execute([
        $applicationId
    ]);

    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$recommendations) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "No AI placement recommendations found."
        ]);

        exit;
    }

    echo json_encode([
        "success" => true,
        "application_id" => $applicationId,
        "recommendations" => $recommendations
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve placement recommendations.",
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}