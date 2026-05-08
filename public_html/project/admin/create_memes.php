<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/meme_api.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

if (isset($_POST["action"])) {
    $action = se($_POST, "action", "", false);
    $memes = [];

    if ($action === "fetch") {
        $count = (int)se($_POST, "count", 5, false);
        if ($count < 1) $count = 1;
        if ($count > 25) $count = 25;

        $result = fetch_memes($count);
        error_log("Multiple memes from API: " . var_export($result, true));

        if ($result && is_array($result)) {
            $memes = $result;
        } else {
            flash("No memes were returned from the API", "warning");
        }
    } else if ($action === "create") {
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ["title", "post_link", "image_url", "author", "subreddit", "is_nsfw", "spoiler"])) {
                unset($_POST[$k]);
            }
        }

        $meme = $_POST;
        $meme["is_nsfw"] = isset($_POST["is_nsfw"]) ? 1 : 0;
        $meme["spoiler"] = isset($_POST["spoiler"]) ? 1 : 0;
        $meme["is_api"] = 0;

        $memes = [$meme];
        error_log("Manual meme wrapped in array: " . var_export($memes, true));
    }

    if ($memes) {
        $db = getDB();
        $inserted = 0;

        foreach ($memes as $meme) {
            $query = "INSERT INTO `IT202-M25-Memes` ";
            $columns = [];
            $params = [];

            foreach ($meme as $k => $v) {
                $columns[] = "`$k`";
                $params[":$k"] = $v;
            }

            $query .= "(" . join(",", $columns) . ")";
            $query .= " VALUES (" . join(",", array_keys($params)) . ")";

            try {
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $inserted++;
            } catch (PDOException $e) {
                error_log("Error inserting meme: " . var_export($e, true));
            }
        }

        flash("Inserted $inserted meme(s)", "success");
    }
}
?>

<div class="container-fluid">
    <h3>Create or Fetch Memes</h3>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link active" href="#" onclick="switchTab('fetchTab', this); return false;">Fetch</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" onclick="switchTab('createTab', this); return false;">Create</a>
        </li>
    </ul>

    <div id="fetchTab" class="tab-target">
        <form method="POST">
            <div class="mb-3">
                <label for="count">How many memes?</label>
                <input class="form-control" type="number" name="count" id="count" value="5" min="1" max="25" required>
            </div>
            <input type="hidden" name="action" value="fetch">
            <input type="submit" value="Fetch Memes" class="btn btn-primary">
        </form>
    </div>

    <div id="createTab" class="tab-target" style="display:none;">
        <form method="POST">
            <div class="mb-3">
                <label for="title">Title</label>
                <input class="form-control" type="text" name="title" id="title" required>
            </div>

            <div class="mb-3">
                <label for="post_link">Post Link</label>
                <input class="form-control" type="url" name="post_link" id="post_link" required>
            </div>

            <div class="mb-3">
                <label for="image_url">Image URL</label>
                <input class="form-control" type="url" name="image_url" id="image_url" required>
            </div>

            <div class="mb-3">
                <label for="author">Author</label>
                <input class="form-control" type="text" name="author" id="author">
            </div>

            <div class="mb-3">
                <label for="subreddit">Subreddit</label>
                <input class="form-control" type="text" name="subreddit" id="subreddit">
            </div>

            <div class="mb-3">
                <label><input type="checkbox" name="is_nsfw" value="1"> NSFW</label>
            </div>

            <div class="mb-3">
                <label><input type="checkbox" name="spoiler" value="1"> Spoiler</label>
            </div>

            <input type="hidden" name="action" value="create">
            <input type="submit" value="Create Meme" class="btn btn-success">
        </form>
    </div>
</div>

<script>
function switchTab(tabId, clickedLink) {
    const tabs = document.getElementsByClassName("tab-target");
    for (let tab of tabs) {
        tab.style.display = (tab.id === tabId) ? "block" : "none";
    }

    const links = document.querySelectorAll(".nav-link");
    links.forEach(link => link.classList.remove("active"));
    clickedLink.classList.add("active");
}
</script>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>