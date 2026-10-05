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
    | GET SUPERVISOR
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
            "message" => "Supervisor not found."
        ], JSON_PRETTY_PRINT);

        exit;
    }

    $supervisorId = $supervisor["id"];


    /*
    |--------------------------------------------------------------------------
    | TOTAL STUDENTS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT COUNT(*)
         FROM placements
         WHERE supervisor_id = ?"
    );

    $stmt->execute([$supervisorId]);

    $totalStudents = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | ACTIVE PLACEMENTS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT COUNT(*)
         FROM placements
         WHERE supervisor_id = ?
         AND status = 'active'"
    );

    $stmt->execute([$supervisorId]);

    $activePlacements = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | COMPLETED PLACEMENTS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT COUNT(*)
         FROM placements
         WHERE supervisor_id = ?
         AND status = 'completed'"
    );

    $stmt->execute([$supervisorId]);

    $completedPlacements = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | PENDING REPORTS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT COUNT(*)
         FROM weekly_reports wr

         INNER JOIN placements p
            ON wr.placement_id = p.id

         WHERE p.supervisor_id = ?
         AND wr.status = 'pending'"
    );

    $stmt->execute([$supervisorId]);

    $pendingReports = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | APPROVED REPORTS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT COUNT(*)
         FROM weekly_reports wr

         INNER JOIN placements p
            ON wr.placement_id = p.id

         WHERE p.supervisor_id = ?
         AND wr.status = 'approved'"
    );

    $stmt->execute([$supervisorId]);

    $approvedReports = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | REJECTED REPORTS
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT COUNT(*)
         FROM weekly_reports wr

         INNER JOIN placements p
            ON wr.placement_id = p.id

         WHERE p.supervisor_id = ?
         AND wr.status = 'rejected'"
    );

    $stmt->execute([$supervisorId]);

    $rejectedReports = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,

        "supervisor" => [
            "id" => $supervisor["id"],
            "full_name" => $supervisor["full_name"]
        ],

        "statistics" => [
            "total_students" => $totalStudents,
            "active_placements" => $activePlacements,
            "completed_placements" => $completedPlacements,
            "pending_reports" => $pendingReports,
            "approved_reports" => $approvedReports,
            "rejected_reports" => $rejectedReports
        ]

    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load supervisor dashboard.",
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}