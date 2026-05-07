<?php
require(__DIR__ . "/../../partials/nav.php");

// UCID: jlt36
// Date: 05/07/2026
// Description: Removes all saved meme relationships for the user without deleting memes or the user

is_logged_in(true);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    flash("Invalid request.", "warning");
    die(header("Location: " . get_url("my_memes.php")));
}

$user_id = get_user_id();
$db = getDB();

// UCID: jlt36
// Date: 05/07/2026
// Description: Soft removes all active meme associations for this user only
$query = "UPDATE `IT202-M25-UserMemes`
        SET is_active = 0, modified = CURRENT_TIMESTAMP
        WHERE user_id = :user_id
        AND is_active = 1";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([":user_id" => $user_id]);

    flash("All saved memes were removed from your account.", "success");
} catch (PDOException $e) {
    error_log("Error removing all saved memes: " . var_export($e, true));
    flash("There was a problem removing your saved memes.", "danger");
}

die(header("Location: " . get_url("my_memes.php")));