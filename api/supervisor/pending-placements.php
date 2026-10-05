<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("supervisor");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
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
    | GET LOGGED-IN SUPERVISOR
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT id, full_name
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
        ], JSON_PRETTY_PRINT);

        exit;
    }

    $supervisorId = (int) $supervisor["id"];


    /*
    |--------------------------------------------------------------------------
    | GET PENDING PLACEMENTS
    |--------------------------------------------------------------------------
    |
    | Only placements assigned to the logged-in supervisor are returned.
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            p.id AS placement_id,
            p.application_id,
            p.student_id,
            p.organization_id,
            p.supervisor_id,
            p.start_date,
            p.end_date,
            p.status,
            p.created_at,

            s.full_name AS student_name,
            s.matric_no,
            s.department,
            s.programme,
            s.level,

            o.name AS organization_name,
            o.industry,
            o.location,

            r.overall_score AS ai_match,
            r.reason AS ai_reason,
            r.recommendation AS ai_recommendation

         FROM placements p

         INNER JOIN students s
            ON p.student_id = s.id

         INNER JOIN organizations o
            ON p.organization_id = o.id

         LEFT JOIN ai_placement_recommendations r
            ON p.application_id = r.application_id

         WHERE p.supervisor_id = ?
         AND p.status = 'pending'

         ORDER BY p.id DESC"
    );

    $stmt->execute([
        $supervisorId
    ]);

    $placements = $stmt->fetchAll(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        "success" => true,

        "count" => count($placements),

        "supervisor" => [
            "id" => $supervisorId,
            "full_name" => $supervisor["full_name"]
        ],

        "placements" => $placements

    ], JSON_PRETTY_PRINT);


} catch (PDOException $e) {

    error_log(
        "Pending placements error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to load pending placements."
    ], JSON_PRETTY_PRINT);
}