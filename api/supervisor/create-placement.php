<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("supervisor");

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

$organizationId = isset($data["organization_id"])
    ? (int) $data["organization_id"]
    : 0;

$startDate = trim($data["start_date"] ?? "");
$endDate = trim($data["end_date"] ?? "");

if (
    $applicationId <= 0 ||
    $organizationId <= 0 ||
    empty($startDate) ||
    empty($endDate)
) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Application ID, organization ID, start date and end date are required."
    ]);

    exit;
}

if ($endDate < $startDate) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "End date cannot be before start date."
    ]);

    exit;
}

try {

    /*
    |--------------------------------------------------------------------------
    | GET SUPERVISOR
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

    $supervisor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$supervisor) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Supervisor profile not found."
        ]);

        exit;
    }

    $supervisorId = (int) $supervisor["id"];

    /*
    |--------------------------------------------------------------------------
    | GET APPLICATION
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            a.id,
            a.student_id,
            a.status
         FROM applications a
         WHERE a.id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $applicationId
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

    $studentId = (int) $application["student_id"];

    /*
    |--------------------------------------------------------------------------
    | CHECK ORGANIZATION
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            id,
            name,
            industry,
            location
         FROM organizations
         WHERE id = ?
         AND status = 'active'
         LIMIT 1"
    );

    $stmt->execute([
        $organizationId
    ]);

    $organization = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$organization) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Active organization not found."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK EXISTING PLACEMENT
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT id
         FROM placements
         WHERE application_id = ?
         AND status IN ('pending', 'active')
         LIMIT 1"
    );

    $stmt->execute([
        $applicationId
    ]);

    if ($stmt->fetch()) {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "This application already has an active or pending placement."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE PLACEMENT
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "INSERT INTO placements
        (
            application_id,
            student_id,
            organization_id,
            supervisor_id,
            start_date,
            end_date,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'active'
        )"
    );

    $stmt->execute([
        $applicationId,
        $studentId,
        $organizationId,
        $supervisorId,
        $startDate,
        $endDate
    ]);

    $placementId = (int) $conn->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | UPDATE APPLICATION
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "UPDATE applications
         SET status = 'placed'
         WHERE id = ?"
    );

    $stmt->execute([
        $applicationId
    ]);

    /*
    |--------------------------------------------------------------------------
    | RETURN PLACEMENT
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "Student placement created successfully.",
        "placement" => [
            "id" => $placementId,
            "application_id" => $applicationId,
            "student_id" => $studentId,
            "organization_id" => $organizationId,
            "organization_name" => $organization["name"],
            "organization_industry" => $organization["industry"],
            "organization_location" => $organization["location"],
            "supervisor_id" => $supervisorId,
            "start_date" => $startDate,
            "end_date" => $endDate,
            "status" => "active"
        ]
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to create placement.",
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}