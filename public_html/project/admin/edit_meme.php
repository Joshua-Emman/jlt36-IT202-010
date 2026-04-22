<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/meme_api.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

$id = $_GET["id"] ?? -1;
$id = (int)$id;

if (isset($_POST["title"])) {
    foreach ($_POST as $k => $v) {
        if (!in_array($k, ["title", "post_link", "image_url", "author", "subreddit", "is_nsfw", "spoiler"])) {
            unset($_POST[$k]);
        }
    }

    $meme = $_POST;
    $meme["is_nsfw"] = isset($_POST["is_nsfw"]) ? 1 : 0;
    $meme["spoiler"] = isset($_POST["spoiler"]) ? 1 : 0;

    error_log("Cleaned up POST: " . var_export($meme, true));

    $db = getDB();
    $query = "UPDATE `IT202-M25-Memes` SET ";
    $params = [];

    foreach ($meme as $k => $v) {
        if ($params) {
            $query .= ",";
        }
        $query .= "`$k`=:$k";
        $params[":$k"] = $v;
    }

    $query .= " WHERE id = :id";
    $params[":id"] = $id;

    error_log("Query: " . $query);
    error_log("Params: " . var_export($params, true));

    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        flash("Updated record", "success");
    } catch (PDOException $e) {
        error_log("Something broke with the query" . var_export($e, true));
        flash("An error occurred", "danger");
    }
}

$meme = [];
if ($id > -1) {
    $db = getDB();
    $query = "SELECT title, post_link, image_url, author, subreddit, is_nsfw, spoiler FROM `IT202-M25-Memes` WHERE id = :id";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([":id" => $id]);
        $r = $stmt->fetch();
        if ($r) {
            $meme = $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching record: " . var_export($e, true));
        flash("Error fetching record", "danger");
    }
} else {
    flash("Invalid id passed", "danger");
    die(header("Location:" . get_url("admin/list_memes.php")));
}
?>
<div class="container-fluid">
    <h3>Edit Meme</h3>
    <form method="POST">
        <div class="mb-3">
            <label for="title">Title</label>
            <input class="form-control" type="text" name="title" id="title" required value="<?php se($meme, "title"); ?>">
        </div>
        <div class="mb-3">
            <label for="post_link">Post Link</label>
            <input class="form-control" type="url" name="post_link" id="post_link" required value="<?php se($meme, "post_link"); ?>">
        </div>
        <div class="mb-3">
            <label for="image_url">Image URL</label>
            <input class="form-control" type="url" name="image_url" id="image_url" required value="<?php se($meme, "image_url"); ?>">
        </div>
        <div class="mb-3">
            <label for="author">Author</label>
            <input class="form-control" type="text" name="author" id="author" value="<?php se($meme, "author"); ?>">
        </div>
        <div class="mb-3">
            <label for="subreddit">Subreddit</label>
            <input class="form-control" type="text" name="subreddit" id="subreddit" value="<?php se($meme, "subreddit"); ?>">
        </div>
        <div class="mb-3">
            <label><input type="checkbox" name="is_nsfw" value="1" <?php echo se($meme, "is_nsfw", 0, false) ? "checked" : ""; ?>> NSFW</label>
        </div>
        <div class="mb-3">
            <label><input type="checkbox" name="spoiler" value="1" <?php echo se($meme, "spoiler", 0, false) ? "checked" : ""; ?>> Spoiler</label>
        </div>
        <input type="submit" value="Update" class="btn btn-primary">
    </form>
</div>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>