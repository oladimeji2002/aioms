<?php

/*
|--------------------------------------------------------------------------
| TEMPORARY DEVELOPMENT SCRIPT
|--------------------------------------------------------------------------
| USE ONLY FOR LOCAL TESTING.
| DELETE THIS FILE AFTER TESTING.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../auth/auth-helper.php";

header("Content-Type: application/json");

requireRole("student");

try {

    /*
    |--------------------------------------------------------------------------
    | GET LOGGED-IN STUDENT
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT id, full_name
         FROM students
         WHERE user_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $_SESSION["user_id"]
    ]);

    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Student profile not found."
        ], JSON_PRETTY_PRINT);

        exit;
    }

    $studentId = (int) $student["id"];


    /*
    |--------------------------------------------------------------------------
    | GET STUDENT PLACEMENT
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            id,
            start_date,
            end_date,
            status
         FROM placements
         WHERE student_id = ?
         ORDER BY id DESC
         LIMIT 1"
    );

    $stmt->execute([
        $studentId
    ]);

    $placement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$placement) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "No placement found for this student."
        ], JSON_PRETTY_PRINT);

        exit;
    }

    $placementId = (int) $placement["id"];


    /*
    |--------------------------------------------------------------------------
    | COMPLETE TRAINING DATES
    |--------------------------------------------------------------------------
    */

    $startDate = date(
        "Y-m-d",
        strtotime("-9 weeks")
    );

    $endDate = date(
        "Y-m-d",
        strtotime("+1 week", strtotime($startDate))
    );


    /*
    |--------------------------------------------------------------------------
    | UPDATE PLACEMENT AS COMPLETED
    |--------------------------------------------------------------------------
    */

    $conn->beginTransaction();

    $stmt = $conn->prepare(
        "UPDATE placements
         SET
            start_date = ?,
            end_date = ?,
            status = 'completed'
         WHERE id = ?
         AND student_id = ?"
    );

    $stmt->execute([
        $startDate,
        $endDate,
        $placementId,
        $studentId
    ]);


    /*
    |--------------------------------------------------------------------------
    | CREATE / COMPLETE 10 WEEKLY REPORTS
    |--------------------------------------------------------------------------
    */

    $weeks = [

        1 => [
            "activities" =>
                "Completed orientation, reviewed the organization's workflow and became familiar with the development environment and project structure.",

            "skills" =>
                "Development environment setup, Git, project structure and teamwork.",

            "challenges" =>
                "Understanding the existing project architecture.",

            "comment" =>
                "The orientation helped me understand the team's workflow and development process."
        ],

        2 => [
            "activities" =>
                "Worked on basic application components and implemented improvements under supervisor guidance.",

            "skills" =>
                "PHP, MySQL, debugging and code organization.",

            "challenges" =>
                "Understanding existing code conventions.",

            "comment" =>
                "I gained a better understanding of how the project components work together."
        ],

        3 => [
            "activities" =>
                "Implemented and tested backend functionality and worked with database queries.",

            "skills" =>
                "SQL, REST API development and debugging.",

            "challenges" =>
                "Resolving database query issues.",

            "comment" =>
                "I improved my ability to identify and resolve backend issues."
        ],

        4 => [
            "activities" =>
                "Worked on API endpoints, request validation and integration between application components.",

            "skills" =>
                "REST APIs, validation and backend integration.",

            "challenges" =>
                "Handling invalid requests correctly.",

            "comment" =>
                "I learned the importance of proper validation and error handling."
        ],

        5 => [
            "activities" =>
                "Performed testing on application modules and fixed issues discovered during testing.",

            "skills" =>
                "Software testing, debugging and troubleshooting.",

            "challenges" =>
                "Tracing the source of unexpected application behaviour.",

            "comment" =>
                "Testing helped me understand the importance of checking different scenarios."
        ],

        6 => [
            "activities" =>
                "Improved an existing backend module and optimized database operations.",

            "skills" =>
                "Query optimization, PHP and MySQL.",

            "challenges" =>
                "Improving performance without affecting existing functionality.",

            "comment" =>
                "I learned how small database improvements can affect application performance."
        ],

        7 => [
            "activities" =>
                "Worked with authentication, sessions and access control within the application.",

            "skills" =>
                "Authentication, sessions, authorization and security.",

            "challenges" =>
                "Ensuring users could only access appropriate resources.",

            "comment" =>
                "This week improved my understanding of application security."
        ],

        8 => [
            "activities" =>
                "Integrated application features and tested communication between frontend and backend components.",

            "skills" =>
                "API integration, JavaScript and debugging.",

            "challenges" =>
                "Resolving integration errors between components.",

            "comment" =>
                "I gained more confidence working across both frontend and backend components."
        ],

        9 => [
            "activities" =>
                "Performed final testing, corrected issues and reviewed completed application functionality.",

            "skills" =>
                "Testing, debugging, documentation and quality assurance.",

            "challenges" =>
                "Identifying and correcting remaining issues.",

            "comment" =>
                "The final testing phase helped me understand the importance of software quality."
        ],

        10 => [
            "activities" =>
                "Completed final project tasks, reviewed the work completed during the training and documented lessons learned.",

            "skills" =>
                "Software development, documentation, teamwork and problem solving.",

            "challenges" =>
                "Bringing different completed components together for final review.",

            "comment" =>
                "The final week helped me reflect on the technical and professional skills developed during the training."
        ]

    ];


    foreach ($weeks as $weekNumber => $week) {

        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING REPORT
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare(
            "SELECT id
             FROM weekly_reports
             WHERE placement_id = ?
             AND student_id = ?
             AND week_number = ?
             LIMIT 1"
        );

        $stmt->execute([
            $placementId,
            $studentId,
            $weekNumber
        ]);

        $existing = $stmt->fetch(PDO::FETCH_ASSOC);


        if ($existing) {

            /*
            |------------------------------------------------------------------
            | UPDATE EXISTING REPORT
            |------------------------------------------------------------------
            */

            $stmt = $conn->prepare(
                "UPDATE weekly_reports
                 SET
                    activities = ?,
                    skills_learned = ?,
                    challenges = ?,
                    student_comment = ?,
                    status = 'approved',
                    submitted_at = DATE_SUB(NOW(), INTERVAL ? WEEK),
                    reviewed_at = DATE_SUB(NOW(), INTERVAL ? WEEK)
                 WHERE id = ?"
            );

            $stmt->execute([
                $week["activities"],
                $week["skills"],
                $week["challenges"],
                $week["comment"],
                10 - $weekNumber,
                10 - $weekNumber,
                $existing["id"]
            ]);

        } else {

            /*
            |------------------------------------------------------------------
            | CREATE REPORT
            |------------------------------------------------------------------
            */

            $stmt = $conn->prepare(
                "INSERT INTO weekly_reports
                (
                    placement_id,
                    student_id,
                    week_number,
                    activities,
                    skills_learned,
                    challenges,
                    student_comment,
                    status,
                    submitted_at,
                    reviewed_at
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
                    'approved',
                    DATE_SUB(NOW(), INTERVAL ? WEEK),
                    DATE_SUB(NOW(), INTERVAL ? WEEK)
                )"
            );

            $stmt->execute([
                $placementId,
                $studentId,
                $weekNumber,
                $week["activities"],
                $week["skills"],
                $week["challenges"],
                $week["comment"],
                10 - $weekNumber,
                10 - $weekNumber
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE APPLICATION
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "UPDATE applications a
         INNER JOIN placements p
            ON a.id = p.application_id
         SET a.status = 'placed'
         WHERE p.id = ?
         AND p.student_id = ?"
    );

    $stmt->execute([
        $placementId,
        $studentId
    ]);


    $conn->commit();


    echo json_encode([

        "success" => true,

        "message" =>
            "Test training completed successfully.",

        "student" => [
            "id" => $studentId,
            "name" => $student["full_name"]
        ],

        "placement" => [
            "id" => $placementId,
            "start_date" => $startDate,
            "end_date" => $endDate,
            "status" => "completed"
        ],

        "weekly_reports_created" => 10

    ], JSON_PRETTY_PRINT);


} catch (Exception $e) {

    if (
        isset($conn) &&
        $conn->inTransaction()
    ) {
        $conn->rollBack();
    }

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" =>
            "Unable to complete test training.",

        "error" =>
            $e->getMessage()

    ], JSON_PRETTY_PRINT);
}