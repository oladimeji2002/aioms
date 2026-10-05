<?php

require_once __DIR__ . "/../auth/auth-helper.php";
require_once __DIR__ . "/../../config/notification-helper.php";

header("Content-Type: application/json");

requireRole("student");


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


try {

    /*
    |--------------------------------------------------------------------------
    | LOGGED-IN USER
    |--------------------------------------------------------------------------
    */

    $userId = $_SESSION["user_id"] ?? 0;

    if (!$userId) {

        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Student session not found."
        ], JSON_PRETTY_PRINT);

        exit;
    }


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
        $userId
    ]);

    $student = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$student) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Student not found."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    $studentId =
        (int) $student["id"];


    /*
    |--------------------------------------------------------------------------
    | GET LATEST AI-RECOMMENDED APPLICATION
    |--------------------------------------------------------------------------
    |
    | We deliberately do NOT trust an application_id from the browser.
    | The server finds the student's latest application itself.
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            a.id AS application_id,
            a.status AS application_status,
            a.training_duration,

            r.organization_id,
            r.id AS recommendation_id

         FROM applications a

         INNER JOIN ai_placement_recommendations r
            ON a.id = r.application_id

         WHERE a.student_id = ?

         AND a.status IN (
            'recommended',
            'pending'
         )

         ORDER BY r.id DESC

         LIMIT 1"
    );

    $stmt->execute([
        $studentId
    ]);

    $recommendation =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$recommendation) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" =>
                "No AI placement recommendation is available for this student."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    $applicationId =
        (int) $recommendation["application_id"];

    $organizationId =
        (int) $recommendation["organization_id"];


    /*
    |--------------------------------------------------------------------------
    | CHECK EXISTING PLACEMENT
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            id,
            status
         FROM placements
         WHERE application_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $applicationId
    ]);

    $existingPlacement =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if ($existingPlacement) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" =>
                "This application already has a placement.",
            "placement_id" =>
                (int) $existingPlacement["id"],
            "status" =>
                $existingPlacement["status"]
        ], JSON_PRETTY_PRINT);

        exit;
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
            location
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

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" =>
                "The recommended organization is no longer available."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | GET SUPERVISOR
    |--------------------------------------------------------------------------
    |
    | Current project rule:
    | use an available supervisor.
    |
    | We restrict this to an active supervisor only if the column exists
    | in the current schema. For now, we keep your existing structure.
    |--------------------------------------------------------------------------
    */

/*
|--------------------------------------------------------------------------
| FIND SUPERVISOR FOR RECOMMENDED ORGANIZATION
|--------------------------------------------------------------------------
*/

    $stmt = $conn->prepare(
        "SELECT
            id,
            full_name,
            department,
            phone
        FROM supervisors
        WHERE organization_id = ?
        ORDER BY id ASC
        LIMIT 1"
    );

    $stmt->execute([
        $organizationId
    ]);

    $supervisor =
        $stmt->fetch(PDO::FETCH_ASSOC);


if (!$supervisor) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" =>
            "No supervisor is assigned to the recommended organization."
    ], JSON_PRETTY_PRINT);

    exit;
}


    if (!$supervisor) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" =>
                "No supervisor is currently available."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CALCULATE DATES
    |--------------------------------------------------------------------------
    */

    $startDate =
        date("Y-m-d");

    $weeks =
        max(
            1,
            (int) $recommendation["training_duration"]
        );

    $endDate =
        date(
            "Y-m-d",
            strtotime(
                "+{$weeks} weeks",
                strtotime($startDate)
            )
        );


    /*
    |--------------------------------------------------------------------------
    | CREATE PENDING PLACEMENT
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | The placement is NOT active yet.
    |
    | Student accepts → pending
    | Supervisor approves → active
    |--------------------------------------------------------------------------
    */

    $conn->beginTransaction();


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
        VALUES (?, ?, ?, ?, ?, ?, 'pending')"
    );

    $stmt->execute([
        $applicationId,
        $studentId,
        $organizationId,
        $supervisor["id"],
        $startDate,
        $endDate
    ]);


    $placementId =
        (int) $conn->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | UPDATE APPLICATION
    |--------------------------------------------------------------------------
    |
    | The application has now been accepted by the student.
    | It remains attached to a placement awaiting approval.
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "UPDATE applications
         SET status = 'placed'
         WHERE id = ?
         AND student_id = ?"
    );

    $stmt->execute([
        $applicationId,
        $studentId
    ]);


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        "success" =>
            true,

        "message" =>
            "Placement request submitted successfully and is awaiting supervisor approval.",

        "placement_id" =>
            $placementId,

        "application_id" =>
            $applicationId,

        "organization" => [
            "id" =>
                $organization["id"],

            "name" =>
                $organization["name"],

            "industry" =>
                $organization["industry"],

            "location" =>
                $organization["location"]
        ],

        "supervisor" => [
            "id" =>
                $supervisor["id"],

            "name" =>
                $supervisor["full_name"],

            "department" =>
                $supervisor["department"],

            "phone" =>
                $supervisor["phone"]
        ],

        "start_date" =>
            $startDate,

        "end_date" =>
            $endDate,

        "status" =>
            "pending"

    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {

    if (
        isset($conn) &&
        $conn->inTransaction()
    ) {
        $conn->rollBack();
    }

    error_log(
        "Accept placement error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to create placement request.",
        "error" =>
            $e->getMessage()
    ], JSON_PRETTY_PRINT);
}