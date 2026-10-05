<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("supervisor");

if ($_SERVER["REQUEST_METHOD"] !== "PUT" && $_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ], JSON_PRETTY_PRINT);

    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$reportId = isset($data["report_id"])
    ? (int) $data["report_id"]
    : 0;

$supervisorComment = isset($data["supervisor_comment"])
    ? trim($data["supervisor_comment"])
    : "";

if ($reportId < 1) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "A valid report ID is required."
    ], JSON_PRETTY_PRINT);

    exit;
}

if ($supervisorComment === "") {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "A supervisor comment is required when rejecting a report."
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
            "message" => "Supervisor not found."
        ], JSON_PRETTY_PRINT);

        exit;
    }

    $supervisorId = $supervisor["id"];


    /*
    |--------------------------------------------------------------------------
    | VERIFY REPORT BELONGS TO SUPERVISOR
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            wr.id,
            wr.status
         FROM weekly_reports wr
         INNER JOIN placements p
            ON wr.placement_id = p.id
         WHERE wr.id = ?
         AND p.supervisor_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $reportId,
        $supervisorId
    ]);

    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Weekly report not found or not assigned to you."
        ], JSON_PRETTY_PRINT);

        exit;
    }

    if ($report["status"] === "approved") {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "An approved report cannot be rejected."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | REJECT REPORT
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "UPDATE weekly_reports
         SET
            status = 'rejected',
            supervisor_comment = ?,
            reviewed_at = NOW()
         WHERE id = ?"
    );

    $stmt->execute([
        $supervisorComment,
        $reportId
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Weekly report rejected successfully.",
        "report_id" => $reportId,
        "status" => "rejected",
        "supervisor_comment" => $supervisorComment
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to reject weekly report.",
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}