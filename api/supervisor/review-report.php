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

$reportId = isset($data["report_id"])
    ? (int) $data["report_id"]
    : 0;

$action = isset($data["action"])
    ? strtolower(trim($data["action"]))
    : "";

$comment = isset($data["supervisor_comment"])
    ? trim($data["supervisor_comment"])
    : "";

if ($reportId <= 0) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Report ID is required."
    ]);

    exit;
}

if (!in_array($action, ["approve", "reject"])) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Action must be approve or reject."
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

    $supervisor = $stmt->fetch();

    if (!$supervisor) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Supervisor profile not found."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK REPORT BELONGS TO SUPERVISOR
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            wr.id,
            wr.status,
            p.supervisor_id

         FROM weekly_reports wr

         INNER JOIN placements p
            ON wr.placement_id = p.id

         WHERE wr.id = ?
         AND p.supervisor_id = ?

         LIMIT 1"
    );

    $stmt->execute([
        $reportId,
        $supervisor["id"]
    ]);

    $report = $stmt->fetch();

    if (!$report) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Report not found or not assigned to you."
        ]);

        exit;
    }

    if ($report["status"] !== "pending") {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "This report has already been reviewed."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE REPORT
    |--------------------------------------------------------------------------
    */

    $status = $action === "approve"
        ? "approved"
        : "rejected";

    $stmt = $conn->prepare(
        "UPDATE weekly_reports
         SET
            status = ?,
            supervisor_comment = ?,
            reviewed_at = CURRENT_TIMESTAMP
         WHERE id = ?"
    );

    $stmt->execute([
        $status,
        $comment,
        $reportId
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Weekly report " . $status . " successfully.",
        "report_id" => $reportId,
        "status" => $status
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to review weekly report.",
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}