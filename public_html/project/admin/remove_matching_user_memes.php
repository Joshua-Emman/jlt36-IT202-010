<?php
require_once(__DIR__ . "/../../../lib/functions.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!has_role("Admin")) {
    flash("You don't have permission to do that.", "warning");
    header("Location: " . get_url("landing.php"));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    flash("Invalid request.", "warning");
    header("Location: " . get_url("admin/all_user_memes.php"));
    exit;
}

$username = trim($_POST["username"] ?? "");

if (empty($username)) {
    flash("Username filter is required.", "warning");
    header("Location: " . get_url("admin/all_user_memes.php"));
    exit;
}

$db = getDB();

// UCID: jlt36
// Date: 05/07/2026
// Description: Soft removes all meme associations for users matching the username filter
$query = "UPDATE `IT202-M25-UserMemes` um
        JOIN Users u ON um.user_id = u.id
        SET um.is_active = 0, um.modified = CURRENT_TIMESTAMP
        WHERE u.username LIKE :username
        AND um.is_active = 1";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([":username" => "%$username%"]);

    flash("Associations for matching users were removed.", "success");
} catch (PDOException $e) {
    error_log("Error removing matching user meme associations: " . var_export($e, true));
    flash("There was a problem removing matching associations.", "danger");
}

header("Location: " . get_url("admin/all_user_memes.php"));
exit;