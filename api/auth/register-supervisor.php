<?php

require_once __DIR__ . "/../../config/config.php";

header("Content-Type: application/json");


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
| READ JSON
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
        "message" => "Invalid JSON payload."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| REQUIRED FIELDS
|--------------------------------------------------------------------------
*/

$required = [
    "email",
    "password",
    "staff_id",
    "full_name",
    "department",
    "organization_id"
];


foreach ($required as $field) {

    if (
        !isset($data[$field]) ||
        trim((string) $data[$field]) === ""
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                ucfirst(
                    str_replace("_", " ", $field)
                ) . " is required."
        ], JSON_PRETTY_PRINT);

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| CLEAN INPUT
|--------------------------------------------------------------------------
*/

$email =
    strtolower(trim($data["email"]));

$password =
    $data["password"];

$staffId =
    strtoupper(trim($data["staff_id"]));

$fullName =
    trim($data["full_name"]);

$department =
    trim($data["department"]);

$organizationId =
    (int) $data["organization_id"];

if ($organizationId <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please select an organization."
    ], JSON_PRETTY_PRINT);

    exit;
}

$phone =
    !empty($data["phone"])
        ? trim($data["phone"])
        : null;


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please provide a valid email address."
    ], JSON_PRETTY_PRINT);

    exit;
}


if (strlen($password) < 6) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Password must be at least 6 characters."
    ], JSON_PRETTY_PRINT);

    exit;
}


if (strlen($fullName) < 2) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please provide a valid full name."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | CHECK EMAIL
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT id
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->execute([
        $email
    ]);

    if ($stmt->fetch()) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" =>
                "Email is already registered."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK STAFF ID
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT id
         FROM supervisors
         WHERE staff_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $staffId
    ]);

    if ($stmt->fetch()) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" =>
                "Staff ID is already registered."
        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | START TRANSACTION
    |--------------------------------------------------------------------------
    */

    $conn->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | HASH PASSWORD
    |--------------------------------------------------------------------------
    */

    $hashedPassword =
        password_hash(
            $password,
            PASSWORD_DEFAULT
        );


    /*
    |--------------------------------------------------------------------------
    | CREATE USER
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "INSERT INTO users
        (
            email,
            password,
            role,
            status
        )
        VALUES
        (
            ?,
            ?,
            'supervisor',
            'active'
        )"
    );

    $stmt->execute([
        $email,
        $hashedPassword
    ]);


    $userId =
        (int) $conn->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | CREATE SUPERVISOR
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "INSERT INTO supervisors
        (
            user_id,
            organization_id,
            staff_id,
            full_name,
            department,
            phone
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )"
    );

$stmt->execute([
    $userId,
    $organizationId,
    $staffId,
    $fullName,
    $department,
    $phone
]);


    $supervisorId =
        (int) $conn->lastInsertId();


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

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" => "Supervisor account created successfully.",
        "user_id" => $userId,
        "supervisor_id" => $supervisorId,
        "staff_id" => $staffId,
        "organization_id" => $organizationId,
        "organization_name" => $organization["name"]
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    if (
        isset($conn) &&
        $conn->inTransaction()
    ) {
        $conn->rollBack();
    }

    error_log(
        "Supervisor registration error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Supervisor registration failed."
    ], JSON_PRETTY_PRINT);
}