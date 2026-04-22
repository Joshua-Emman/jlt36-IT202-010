<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/meme_api.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

if (isset($_POST["action"])) {
    $action = se($_POST, "action", "", false);
    $meme = [];

    if ($action === "fetch") {
        $result = fetch_one_meme();
        error_log("Single meme from API: " . var_export($result, true));
        if ($result) {
            $meme = $result;
        } else {
            flash("No meme was returned from the API", "warning");
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

        error_log("Cleaned manual meme POST: " . var_export($meme, true));
    }

    if ($meme) {
        $db = getDB();
        $query = "INSERT INTO `IT202-M25-Memes` ";
        $columns = [];
        $params = [];

        foreach ($meme as $k => $v) {
            $columns[] = "`$k`";
            $params[":$k"] = $v;
        }

        $query .= "(" . join(",", $columns) . ")";
        $query .= " VALUES (" . join(",", array_keys($params)) . ")";

        error_log("Query: " . $query);
        error_log("Params: " . var_export($params, true));

        try {
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            flash("Inserted meme " . $db->lastInsertId(), "success");
        } catch (PDOException $e) {
            error_log("Error inserting meme: " . var_export($e, true));
            flash("An error occurred while inserting the meme", "danger");
        }
    }
}
?>

<div class="container-fluid">
    <h3>Create or Fetch Meme</h3>

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
            <p>Fetch one meme from the API and save it.</p>
            <input type="hidden" name="action" value="fetch">
            <input type="submit" value="Fetch Meme" class="btn btn-primary">
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