<?php
require(__DIR__ . "/../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

// UCID: jlt36
// Date: 04/22/2026
// Description: Retrieves the meme id from the query string and redirects back if it is missing or invalid.
$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    flash("Invalid or missing meme id.", "warning");
    $back = $_SERVER["HTTP_REFERER"] ?? get_url("admin/list_memes.php");
    die(header("Location: " . $back));
}

// UCID: jlt36
// Date: 04/22/2026
// Description: loads a single meme record from the database using the provided id
$db = getDB();
$query = "SELECT * FROM `IT202-M25-Memes` WHERE id = :id LIMIT 1";
$stmt = $db->prepare($query);
$record = [];

try {
    $stmt->execute([":id" => $id]);
    $r = $stmt->fetch();
    if ($r) {
        $record = $r;
    } else {
        flash("Meme not found.", "warning");
        $back = $_SERVER["HTTP_REFERER"] ?? get_url("admin/list_memes.php");
        die(header("Location: " . $back));
    }
} catch (PDOException $e) {
    error_log("Error loading meme: " . var_export($e, true));
    flash("An error occurred while loading the meme.", "danger");
    $back = $_SERVER["HTTP_REFERER"] ?? get_url("admin/list_memes.php");
    die(header("Location: " . $back));
}
?>

<div class="container-fluid">
    <!-- UCID: jlt36 -->
    <!-- Date: 04/22/2026 -->
    <!-- Description: generates the styled single meme details view with action links -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="card-title"><?php se($record, "title"); ?></h3>

            <p class="card-text">
                <strong>Author:</strong> <?php se($record, "author", "N/A"); ?><br>
                <strong>Subreddit:</strong> <?php se($record, "subreddit", "N/A"); ?><br>
                <strong>Post Link:</strong>
                <a href="<?php se($record, "post_link"); ?>" target="_blank">Open Post</a><br>
                <strong>Image URL:</strong>
                <a href="<?php se($record, "image_url"); ?>" target="_blank">Open Image</a><br>
                <strong>NSFW:</strong> <?php echo se($record, "is_nsfw", 0, false) ? "Yes" : "No"; ?><br>
                <strong>Spoiler:</strong> <?php echo se($record, "spoiler", 0, false) ? "Yes" : "No"; ?><br>
                <strong>Source:</strong> <?php echo se($record, "is_api", 0, false) ? "API" : "Manual"; ?><br>
                <strong>Created:</strong> <?php se($record, "created", "N/A"); ?><br>
                <strong>Modified:</strong> <?php se($record, "modified", "N/A"); ?>
            </p>

            <a class="btn btn-warning btn-sm" href="<?php echo get_url("admin/edit_meme.php") . "?id=" . urlencode($record["id"]); ?>">Edit</a>
            <a class="btn btn-danger btn-sm" href="<?php echo get_url("admin/delete_meme.php") . "?id=" . urlencode($record["id"]); ?>">Delete</a>
            <a class="btn btn-secondary btn-sm" href="<?php echo get_url("admin/list_memes.php"); ?>">Back to List</a>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>