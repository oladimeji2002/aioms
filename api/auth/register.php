<?php

require_once __DIR__ . "/../../config/config.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON payload."
    ]);

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
    "matric_no",
    "full_name",
    "department",
    "programme",
    "level"
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
        ]);

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| SANITIZE INPUT
|--------------------------------------------------------------------------
*/

$email = strtolower(
    trim($data["email"])
);

$password = $data["password"];

$matricNo = trim(
    $data["matric_no"]
);

$fullName = trim(
    $data["full_name"]
);

$department = trim(
    $data["department"]
);

$programme = trim(
    $data["programme"]
);

$level = trim(
    $data["level"]
);

$phone = !empty($data["phone"])
    ? trim($data["phone"])
    : null;

$skills = !empty($data["skills"])
    ? trim($data["skills"])
    : null;

$careerInterest = !empty(
    $data["career_interest"]
)
    ? trim($data["career_interest"])
    : null;

$industryInterest = !empty(
    $data["industry_interest"]
)
    ? trim($data["industry_interest"])
    : null;

$locationPreference = !empty(
    $data["location_preference"]
)
    ? trim($data["location_preference"])
    : null;


/*
|--------------------------------------------------------------------------
| BASIC VALIDATION
|--------------------------------------------------------------------------
*/

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please provide a valid email address."
    ]);

    exit;
}

if (strlen($password) < 6) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Password must be at least 6 characters."
    ]);

    exit;
}

if (strlen($fullName) < 2) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please provide a valid full name."
    ]);

    exit;
}

if (strlen($matricNo) < 3) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please provide a valid matric number."
    ]);

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
    | CHECK EXISTING EMAIL
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
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK EXISTING MATRIC NUMBER
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT id
         FROM students
         WHERE matric_no = ?
         LIMIT 1"
    );

    $stmt->execute([
        $matricNo
    ]);

    if ($stmt->fetch()) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" =>
                "Matric number is already registered."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | TRANSACTION
    |--------------------------------------------------------------------------
    */

    $conn->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | HASH PASSWORD
    |--------------------------------------------------------------------------
    */

    $hashedPassword = password_hash(
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
            'student',
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
    | CREATE STUDENT
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "INSERT INTO students
        (
            user_id,
            matric_no,
            full_name,
            department,
            programme,
            level,
            phone,
            skills,
            career_interest,
            industry_interest,
            location_preference
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
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
        $matricNo,
        $fullName,
        $department,
        $programme,
        $level,
        $phone,
        $skills,
        $careerInterest,
        $industryInterest,
        $locationPreference
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

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" =>
            "Student account created successfully.",
        "user_id" => $userId
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Student registration error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Registration failed. Please try again."
    ]);
}