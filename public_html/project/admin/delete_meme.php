<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to do that.", "warning");
    die(header("Location: " . get_url("landing.php")));
}

// UCID: jlt36
// Date: 04/23/2026
// Description: retrieves the meme id for deletion and redirects to the list page if the id is missing or invalid
$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    flash("Invalid or missing meme id.", "warning");
    die(header("Location: " . get_url("admin/list_memes.php")));
}

// UCID: jlt36
// Date: 04/23/2026
// Description: Deletes the selected meme record and redirects back to the previous page.
$db = getDB();
$query = "DELETE FROM `IT202-M25-Memes` WHERE id = :id LIMIT 1";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([":id" => $id]);

    if ($stmt->rowCount() > 0) {
        flash("Meme deleted successfully.", "success");
    } else {
        flash("Record not found.", "warning");
    }
} catch (PDOException $e) {
    error_log("Error deleting meme: " . var_export($e, true));
    flash("An error occurred while deleting the meme.", "danger");
}

$back = $_SERVER["HTTP_REFERER"] ?? get_url("admin/list_memes.php");
die(header("Location: " . $back));