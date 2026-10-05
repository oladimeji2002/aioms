<?php

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("student");

try {

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

            o.name AS organization_name,
            o.industry,
            o.location,
            o.description,
            o.contact_person,

            s.full_name AS student_name,
            s.matric_no,
            s.department,
            s.programme,

            sp.full_name AS supervisor_name,
            sp.department AS supervisor_department,
            sp.phone AS supervisor_phone

        FROM placements p

        INNER JOIN students s
            ON p.student_id = s.id

        INNER JOIN organizations o
            ON p.organization_id = o.id

        INNER JOIN supervisors sp
            ON p.supervisor_id = sp.id

        WHERE s.user_id = ?

        ORDER BY p.id DESC

        LIMIT 1"
    );

    $stmt->execute([
        $_SESSION["user_id"]
    ]);

    $placement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$placement) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "No placement found."
        ]);

        exit;
    }

    echo json_encode([
        "success" => true,
        "placement" => $placement
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve placement information.",
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}