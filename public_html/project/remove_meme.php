<?php
require(__DIR__ . "/../../partials/nav.php");

// UCID: jlt36
// Date: 05/07/2026
// Description: Removes one saved meme relationship for the user without deleting the meme or user.

is_logged_in(true);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    flash("Invalid request.", "warning");
    die(header("Location: " . get_url("my_memes.php")));
}

$association_id = (int)($_POST["association_id"] ?? 0);
$user_id = get_user_id();

if ($association_id <= 0) {
    flash("Invalid saved meme selected.", "warning");
    die(header("Location: " . get_url("my_memes.php")));
}

$db = getDB();

// UCID: jlt36
// Date: 05/07/2026
// Description: Soft removes the relationship only. It does not delete the meme or the user
$query = "UPDATE `IT202-M25-UserMemes`
        SET is_active = 0, modified = CURRENT_TIMESTAMP
        WHERE id = :association_id
        AND user_id = :user_id
        LIMIT 1";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([
        ":association_id" => $association_id,
        ":user_id" => $user_id
    ]);

    if ($stmt->rowCount() > 0) {
        flash("Saved meme removed.", "success");
    } else {
        flash("Saved meme was not found.", "warning");
    }
} catch (PDOException $e) {
    error_log("Error removing saved meme: " . var_export($e, true));
    flash("There was a problem removing this saved meme.", "danger");
}

$back = $_SERVER["HTTP_REFERER"] ?? get_url("my_memes.php");
die(header("Location: " . $back));