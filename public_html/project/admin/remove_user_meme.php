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

$association_id = (int)($_POST["association_id"] ?? 0);

if ($association_id <= 0) {
    flash("Invalid association selected.", "warning");
    header("Location: " . get_url("admin/all_user_memes.php"));
    exit;
}

$db = getDB();

// UCID: jlt36
// Date: 05/07/2026
// Description: Soft removes one user meme association without deleting the user or meme
$query = "UPDATE `IT202-M25-UserMemes`
        SET is_active = 0, modified = CURRENT_TIMESTAMP
        WHERE id = :association_id
        LIMIT 1";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([":association_id" => $association_id]);

    if ($stmt->rowCount() > 0) {
        flash("Association removed.", "success");
    } else {
        flash("Association was not found.", "warning");
    }
} catch (PDOException $e) {
    error_log("Error removing user meme association: " . var_export($e, true));
    flash("There was a problem removing the association.", "danger");
}

$back = $_SERVER["HTTP_REFERER"] ?? get_url("admin/all_user_memes.php");
header("Location: " . $back);
exit;