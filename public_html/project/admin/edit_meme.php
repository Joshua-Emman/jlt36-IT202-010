<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/meme_api.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

// UCID: jlt36
// Date: 04/23/2026
// Description: Retrieves the meme id from the query string and uses it to load or update the selected record
$id = $_GET["id"] ?? $_POST["id"] ?? -1;
$id = (int)$id;

if ($id <= 0) {
    flash("Invalid or missing meme id.", "warning");
    die(header("Location:" . get_url("admin/list_memes.php")));
}

$meme = [];
$db = getDB();
$query = "SELECT title, post_link, image_url, author, subreddit, is_nsfw, spoiler FROM `IT202-M25-Memes` WHERE id = :id LIMIT 1";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([":id" => $id]);
    $r = $stmt->fetch();
    if ($r) {
        $meme = $r;
    } else {
        flash("Record not found.", "warning");
        die(header("Location:" . get_url("admin/list_memes.php")));
    }
} catch (PDOException $e) {
    error_log("Error fetching record: " . var_export($e, true));
    flash("An error occurred while loading the record.", "danger");
    die(header("Location:" . get_url("admin/list_memes.php")));
}

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

    // UCID: jlt36
    // Date: 04/23/2026
    // Description: server side validation for the edit meme form before updating the database.
    $errors = [];

    if (empty($meme["title"])) {
        $errors[] = "Title is required.";
    }
    if (strlen($meme["title"]) > 255) {
        $errors[] = "Title must be 255 characters or fewer.";
    }

    if (empty($meme["post_link"])) {
        $errors[] = "Post link is required.";
    } elseif (!filter_var($meme["post_link"], FILTER_VALIDATE_URL)) {
        $errors[] = "Post link must be a valid URL.";
    }

    if (empty($meme["image_url"])) {
        $errors[] = "Image URL is required.";
    } elseif (!filter_var($meme["image_url"], FILTER_VALIDATE_URL)) {
        $errors[] = "Image URL must be a valid URL.";
    }

    if (!empty($meme["author"]) && strlen($meme["author"]) > 100) {
        $errors[] = "Author must be 100 characters or fewer.";
    }

    if (!empty($meme["subreddit"]) && strlen($meme["subreddit"]) > 100) {
        $errors[] = "Subreddit must be 100 characters or fewer.";
    }

    if (empty($errors)) {
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
            flash("Updated record.", "success");

            $stmt = $db->prepare("SELECT title, post_link, image_url, author, subreddit, is_nsfw, spoiler FROM `IT202-M25-Memes` WHERE id = :id LIMIT 1");
            $stmt->execute([":id" => $id]);
            $r = $stmt->fetch();
            if ($r) {
                $meme = $r;
            }
        } catch (PDOException $e) {
            error_log("Something broke with the query " . var_export($e, true));
            flash("An error occurred while updating the meme.", "danger");
        }
    } else {
        foreach ($errors as $error) {
            flash($error, "warning");
        }
    }
}
?>
<div class="container-fluid">
    <h3>Edit Meme</h3>

    <!-- UCID: jlt36 -->
    <!-- Date: 04/23/2026 -->
    <!-- Description: Generates the edit form with prefilled meme data, html validation, and bootstrap styling -->
    <form method="POST" onsubmit="return validate(this);">
        <input type="hidden" name="id" value="<?php se($id); ?>">

        <div class="mb-3">
            <label for="title">Title</label>
            <input class="form-control" type="text" name="title" id="title" required maxlength="255" value="<?php se($meme, "title"); ?>">
        </div>

        <div class="mb-3">
            <label for="post_link">Post Link</label>
            <input class="form-control" type="url" name="post_link" id="post_link" required maxlength="255" value="<?php se($meme, "post_link"); ?>">
        </div>

        <div class="mb-3">
            <label for="image_url">Image URL</label>
            <input class="form-control" type="url" name="image_url" id="image_url" required value="<?php se($meme, "image_url"); ?>">
        </div>

        <div class="mb-3">
            <label for="author">Author</label>
            <input class="form-control" type="text" name="author" id="author" maxlength="100" value="<?php se($meme, "author"); ?>">
        </div>

        <div class="mb-3">
            <label for="subreddit">Subreddit</label>
            <input class="form-control" type="text" name="subreddit" id="subreddit" maxlength="100" value="<?php se($meme, "subreddit"); ?>">
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

<script>
// UCID: jlt36
// Date: 04/23/2026
// Description: client side validation for the edit meme form before submission
function validate(form) {
    const title = form.title.value.trim();
    const postLink = form.post_link.value.trim();
    const imageUrl = form.image_url.value.trim();
    const author = form.author.value.trim();
    const subreddit = form.subreddit.value.trim();

    if (!title) {
        alert("Title is required.");
        return false;
    }
    if (title.length > 255) {
        alert("Title must be 255 characters or fewer.");
        return false;
    }
    if (!postLink) {
        alert("Post link is required.");
        return false;
    }
    if (!imageUrl) {
        alert("Image URL is required.");
        return false;
    }
    if (author.length > 100) {
        alert("Author must be 100 characters or fewer.");
        return false;
    }
    if (subreddit.length > 100) {
        alert("Subreddit must be 100 characters or fewer.");
        return false;
    }
    return true;
}
</script>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>