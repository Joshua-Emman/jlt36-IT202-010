<?php
require_once(__DIR__ . "/../../lib/functions.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// UCID: jlt36
// Date: 05/07/2026
// Description: associates a selected meme with the currently logged in user by saving it to the usermemes table.

is_logged_in(true);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    flash("Invalid request.", "warning");
    header("Location: " . get_url("admin/list_memes.php"));
    exit;
}

$meme_id = (int)($_POST["meme_id"] ?? 0);
$user_id = get_user_id();

error_log("Save Meme Debug - user_id: " . var_export($user_id, true));
error_log("Save Meme Debug - meme_id: " . var_export($meme_id, true));

if ($meme_id <= 0) {
    flash("Invalid meme selected.", "warning");
    header("Location: " . get_url("admin/list_memes.php"));
    exit;
}

if ($user_id <= 0) {
    flash("You must be logged in to save memes.", "warning");
    header("Location: " . get_url("login.php"));
    exit;
}

$db = getDB();

// UCID: jlt36
// Date: 05/07/2026
// Description: verifies the selected meme exists before creating the user to meme association
$checkQuery = "SELECT id FROM `IT202-M25-Memes` WHERE id = :meme_id LIMIT 1";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->execute([":meme_id" => $meme_id]);
$meme = $checkStmt->fetch();

if (!$meme) {
    flash("That meme could not be found.", "warning");
    header("Location: " . get_url("admin/list_memes.php"));
    exit;
}

// UCID: jlt36
// Date: 05/07/2026
// Description: Inserts or reactivates the relationship between the logged in user and selected meme
$query = "INSERT INTO `IT202-M25-UserMemes`
            (`user_id`, `meme_id`, `is_active`)
        VALUES
            (:user_id, :meme_id, 1)
        ON DUPLICATE KEY UPDATE
            is_active = 1,
            modified = CURRENT_TIMESTAMP";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([
        ":user_id" => $user_id,
        ":meme_id" => $meme_id
    ]);

    flash("Meme saved to your favorites.", "success");
} catch (PDOException $e) {
    error_log("Error saving meme association errorInfo: " . var_export($e->errorInfo, true));
    error_log("Error saving meme association message: " . $e->getMessage());
    error_log("Save Meme Failed - user_id: " . var_export($user_id, true));
    error_log("Save Meme Failed - meme_id: " . var_export($meme_id, true));

    flash("There was a problem saving this meme. Please try again.", "danger");
}

$back = $_SERVER["HTTP_REFERER"] ?? get_url("admin/list_memes.php");
header("Location: " . $back);
exit;