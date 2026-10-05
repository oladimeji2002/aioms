<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("supervisor");


/*
|--------------------------------------------------------------------------
| ONLY POST
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
| READ REQUEST
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
        "message" => "Invalid request payload."
    ], JSON_PRETTY_PRINT);

    exit;
}


$placementId =
    (int) ($data["placement_id"] ?? 0);

$action =
    strtolower(
        trim(
            $data["action"] ?? ""
        )
    );

$reason =
    trim(
        $data["reason"] ?? ""
    );


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if ($placementId <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Placement ID is required."
    ], JSON_PRETTY_PRINT);

    exit;
}


if (
    !in_array(
        $action,
        ["approve", "reject"],
        true
    )
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid placement action."
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
        "SELECT
            id,
            user_id,
            full_name,
            organization_id
         FROM supervisors
         WHERE user_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $_SESSION["user_id"]
    ]);

    $supervisor =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$supervisor) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Supervisor profile not found."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    $supervisorId =
        (int) $supervisor["id"];


    /*
    |--------------------------------------------------------------------------
    | GET PENDING PLACEMENT
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            p.id,
            p.application_id,
            p.student_id,
            p.organization_id,
            p.supervisor_id,
            p.status,

            s.full_name AS student_name,
            s.matric_no,

            s.user_id AS student_user_id,

            o.name AS organization_name

         FROM placements p

         INNER JOIN students s
            ON p.student_id = s.id

         INNER JOIN organizations o
            ON p.organization_id = o.id

         WHERE p.id = ?
         AND p.supervisor_id = ?
         AND p.status = 'pending'

         LIMIT 1"
    );

    $stmt->execute([
        $placementId,
        $supervisorId
    ]);

    $placement =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$placement) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" =>
                "Pending placement not found or you are not authorized to manage it."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | APPROVE
    |--------------------------------------------------------------------------
    */

    if ($action === "approve") {

        $conn->beginTransaction();


        /*
        |----------------------------------------------------------------------
        | ACTIVATE PLACEMENT
        |----------------------------------------------------------------------
        */

        $stmt = $conn->prepare(
            "UPDATE placements
             SET status = 'active'
             WHERE id = ?
             AND supervisor_id = ?
             AND status = 'pending'"
        );

        $stmt->execute([
            $placementId,
            $supervisorId
        ]);


        if ($stmt->rowCount() === 0) {

            $conn->rollBack();

            http_response_code(409);

            echo json_encode([
                "success" => false,
                "message" =>
                    "Placement could not be approved."
            ], JSON_PRETTY_PRINT);

            exit;
        }


        /*
        |----------------------------------------------------------------------
        | UPDATE APPLICATION
        |----------------------------------------------------------------------
        */

        $stmt = $conn->prepare(
            "UPDATE applications
             SET status = 'placed'
             WHERE id = ?
             AND student_id = ?"
        );

        $stmt->execute([
            (int) $placement["application_id"],
            (int) $placement["student_id"]
        ]);


        /*
        |----------------------------------------------------------------------
        | COMMIT PLACEMENT CHANGES FIRST
        |----------------------------------------------------------------------
        */

        $conn->commit();


        /*
        |----------------------------------------------------------------------
        | CREATE NOTIFICATION
        |----------------------------------------------------------------------
        |
        | Notification failure must NOT undo a successful approval.
        | We use "system" because we know that value already exists
        | in your notifications table.
        |----------------------------------------------------------------------
        */

        $notificationCreated = false;

        try {

            $stmt = $conn->prepare(
                "INSERT INTO notifications
                (
                    user_id,
                    title,
                    message,
                    type,
                    is_read
                )
                VALUES (?, ?, ?, 'system', 0)"
            );

            $stmt->execute([

                (int) $placement["student_user_id"],

                "Placement approved",

                "Your industrial training placement at " .
                $placement["organization_name"] .
                " has been approved by your supervisor. " .
                "Your placement is now active."

            ]);

            $notificationCreated = true;

        } catch (PDOException $notificationError) {

            error_log(
                "Placement approval notification error: " .
                $notificationError->getMessage()
            );
        }


        /*
        |----------------------------------------------------------------------
        | RESPONSE
        |----------------------------------------------------------------------
        */

        echo json_encode([

            "success" => true,

            "message" =>
                "Placement approved successfully.",

            "notification_created" =>
                $notificationCreated,

            "placement" => [

                "id" =>
                    $placementId,

                "application_id" =>
                    (int) $placement["application_id"],

                "student_id" =>
                    (int) $placement["student_id"],

                "organization_id" =>
                    (int) $placement["organization_id"],

                "organization_name" =>
                    $placement["organization_name"],

                "supervisor_id" =>
                    $supervisorId,

                "student_name" =>
                    $placement["student_name"],

                "status" =>
                    "active"
            ]

        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | REJECT
    |--------------------------------------------------------------------------
    */

    if ($action === "reject") {

        if ($reason === "") {

            $reason =
                "Placement requires reassignment.";
        }


        $conn->beginTransaction();


        /*
        |----------------------------------------------------------------------
        | DELETE PENDING PLACEMENT
        |----------------------------------------------------------------------
        */

        $stmt = $conn->prepare(
            "DELETE FROM placements
             WHERE id = ?
             AND supervisor_id = ?
             AND status = 'pending'"
        );

        $stmt->execute([
            $placementId,
            $supervisorId
        ]);


        if ($stmt->rowCount() === 0) {

            $conn->rollBack();

            http_response_code(409);

            echo json_encode([
                "success" => false,
                "message" =>
                    "Placement could not be rejected."
            ], JSON_PRETTY_PRINT);

            exit;
        }


        /*
        |----------------------------------------------------------------------
        | RETURN APPLICATION TO PENDING
        |----------------------------------------------------------------------
        */

        $stmt = $conn->prepare(
            "UPDATE applications
             SET status = 'pending'
             WHERE id = ?
             AND student_id = ?"
        );

        $stmt->execute([
            (int) $placement["application_id"],
            (int) $placement["student_id"]
        ]);


        /*
        |----------------------------------------------------------------------
        | COMMIT
        |----------------------------------------------------------------------
        */

        $conn->commit();


        /*
        |----------------------------------------------------------------------
        | CREATE NOTIFICATION
        |----------------------------------------------------------------------
        */

        $notificationCreated = false;

        try {

            $stmt = $conn->prepare(
                "INSERT INTO notifications
                (
                    user_id,
                    title,
                    message,
                    type,
                    is_read
                )
                VALUES (?, ?, ?, 'system', 0)"
            );

            $stmt->execute([

                (int) $placement["student_user_id"],

                "Placement returned",

                "Your industrial training placement request for " .
                $placement["organization_name"] .
                " was returned for reassignment. " .
                "Reason: " .
                $reason

            ]);

            $notificationCreated = true;

        } catch (PDOException $notificationError) {

            error_log(
                "Placement rejection notification error: " .
                $notificationError->getMessage()
            );
        }


        /*
        |----------------------------------------------------------------------
        | RESPONSE
        |----------------------------------------------------------------------
        */

        echo json_encode([

            "success" => true,

            "message" =>
                "Placement returned for reassignment.",

            "notification_created" =>
                $notificationCreated,

            "placement_id" =>
                $placementId,

            "application_id" =>
                (int) $placement["application_id"],

            "status" =>
                "pending",

            "reason" =>
                $reason

        ], JSON_PRETTY_PRINT);

        exit;
    }


} catch (PDOException $e) {

    if (
        isset($conn) &&
        $conn->inTransaction()
    ) {
        $conn->rollBack();
    }


    error_log(
        "Supervisor placement action DB error: " .
        $e->getMessage()
    );


    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to process placement action.",
        "error" =>
            $e->getMessage()
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {

    if (
        isset($conn) &&
        $conn->inTransaction()
    ) {
        $conn->rollBack();
    }


    error_log(
        "Supervisor placement action error: " .
        $e->getMessage()
    );


    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to process placement action.",
        "error" =>
            $e->getMessage()
    ], JSON_PRETTY_PRINT);
}