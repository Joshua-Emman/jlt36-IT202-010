<?php
require(__DIR__ . "/../../partials/nav.php");

// UCID: jlt36
// Date: 05/07/2026
// Description: associates a selected meme with the currently logged in user by saving it to the usermemes table.

is_logged_in(true);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    flash("Invalid request.", "warning");
    die(header("Location: " . get_url("admin/list_memes.php")));
}

$meme_id = (int)($_POST["meme_id"] ?? 0);
$user_id = get_user_id();

if ($meme_id <= 0) {
    flash("Invalid meme selected.", "warning");
    die(header("Location: " . get_url("admin/list_memes.php")));
}

$db = getDB();

// UCID: jlt36
// Date: 05/07/2026
//Description: verifies the selected meme exists before creating the user to meme association

$checkQuery = "SELECT id FROM `IT202-M25-Memes` WHERE id = :meme_id LIMIT 1";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->execute([":meme_id" => $meme_id]);
$meme = $checkStmt->fetch();

if (!$meme) {
    flash("That meme could not be found.", "warning");
    die(header("Location: " . get_url("admin/list_memes.php")));
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
    error_log("Error saving meme association: " . var_export($e, true));
    flash("There was a problem saving this meme. Please try again.", "danger");
}

$back = $_SERVER["HTTP_REFERER"] ?? get_url("admin/list_memes.php");
die(header("Location: " . $back));