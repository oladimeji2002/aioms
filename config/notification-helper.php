<?php

/**
 * Create a notification for a user.
 *
 * @param PDO    $conn
 * @param int    $userId
 * @param string $title
 * @param string $message
 * @param string $type
 * @return bool
 */
function createNotification(
    PDO $conn,
    int $userId,
    string $title,
    string $message,
    string $type = "system"
): bool {

    if ($userId <= 0) {
        return false;
    }

    $stmt = $conn->prepare(
        "INSERT INTO notifications
        (
            user_id,
            title,
            message,
            type,
            is_read
        )
        VALUES
        (?, ?, ?, ?, 0)"
    );

    return $stmt->execute([
        $userId,
        trim($title),
        trim($message),
        trim($type)
    ]);
}