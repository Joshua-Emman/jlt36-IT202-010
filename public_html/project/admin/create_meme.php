<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/meme_api.php");

// UCID: jlt36
// Date: 04/22/2026
// Description: Admin-only page for fetching one meme from the API or manually creating a meme.
// Data is validated, transformed to match the database columns, checked for duplicates,
// and inserted into the Memes table.

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

function clean_text($value)
{
    return trim((string)$value);
}

function is_valid_url_value($url)
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

if (isset($_POST["action"])) {
    $action = se($_POST, "action", "", false);
    $meme = [];
    $errors = [];

    if ($action === "fetch") {
        // UCID: jlt36
        // Date: 04/22/2026
        // Description: Fetches one meme from the API and maps it to the database structure.
        $result = fetch_one_meme();
        error_log("Single meme from API: " . var_export($result, true));

        if ($result) {
            $item = $result;

            if (isset($result["data"]) && is_array($result["data"])) {
                $item = $result["data"];
            } elseif (isset($result[0]) && is_array($result[0])) {
                $item = $result[0];
            }

            $postLink = $item["post_link"]
                ?? $item["postLink"]
                ?? $item["link"]
                ?? $item["permalink"]
                ?? "";

            if (!empty($postLink) && str_starts_with($postLink, "/")) {
                $postLink = "https://reddit.com" . $postLink;
            }

            $imageUrl = $item["image_url"]
                ?? $item["url"]
                ?? $item["image"]
                ?? "";

            $meme = [
                "title" => clean_text($item["title"] ?? ""),
                "post_link" => clean_text($postLink),
                "image_url" => clean_text($imageUrl),
                "author" => clean_text($item["author"] ?? ""),
                "subreddit" => clean_text($item["subreddit"] ?? ""),
                "is_nsfw" => (!empty($item["is_nsfw"]) || !empty($item["nsfw"]) || !empty($item["over_18"])) ? 1 : 0,
                "spoiler" => !empty($item["spoiler"]) ? 1 : 0,
                "is_api" => 1
            ];

            if (empty($meme["title"])) {
                $errors[] = "The API did not return a valid title.";
            }
            if (empty($meme["post_link"])) {
                $errors[] = "The API did not return a valid post link.";
            }
            if (empty($meme["image_url"])) {
                $errors[] = "The API did not return a valid image URL.";
            }
            if (!empty($meme["post_link"]) && !is_valid_url_value($meme["post_link"])) {
                $errors[] = "The API returned an invalid post link.";
            }
            if (!empty($meme["image_url"]) && !is_valid_url_value($meme["image_url"])) {
                $errors[] = "The API returned an invalid image URL.";
            }
        } else {
            $errors[] = "No meme was returned from the API.";
        }
    } elseif ($action === "create") {
        // UCID: jlt36
        // Date: 04/22/2026
        // Description: Handles manual meme creation by validating user input and preparing a custom meme record.
        $meme = [
            "title" => clean_text($_POST["title"] ?? ""),
            "post_link" => clean_text($_POST["post_link"] ?? ""),
            "image_url" => clean_text($_POST["image_url"] ?? ""),
            "author" => clean_text($_POST["author"] ?? ""),
            "subreddit" => clean_text($_POST["subreddit"] ?? ""),
            "is_nsfw" => isset($_POST["is_nsfw"]) ? 1 : 0,
            "spoiler" => isset($_POST["spoiler"]) ? 1 : 0,
            "is_api" => 0
        ];

        if (empty($meme["title"])) {
            $errors[] = "Title is required.";
        }
        if (strlen($meme["title"]) > 255) {
            $errors[] = "Title must be 255 characters or fewer.";
        }

        if (empty($meme["post_link"])) {
            $errors[] = "Post link is required.";
        } elseif (!is_valid_url_value($meme["post_link"])) {
            $errors[] = "Post link must be a valid URL.";
        }

        if (empty($meme["image_url"])) {
            $errors[] = "Image URL is required.";
        } elseif (!is_valid_url_value($meme["image_url"])) {
            $errors[] = "Image URL must be a valid URL.";
        }

        if (!empty($meme["author"]) && strlen($meme["author"]) > 100) {
            $errors[] = "Author must be 100 characters or fewer.";
        }

        if (!empty($meme["subreddit"]) && strlen($meme["subreddit"]) > 100) {
            $errors[] = "Subreddit must be 100 characters or fewer.";
        }

        error_log("Cleaned manual meme POST: " . var_export($meme, true));
    } else {
        $errors[] = "Invalid action submitted.";
    }

    if (empty($errors) && !empty($meme)) {
        // UCID: jlt36
        // Date: 04/22/2026
        // Description: checks for duplicate meme content and inserts a new meme record if it does not already exist
        $db = getDB();

        $checkQuery = "SELECT id FROM `IT202-M25-Memes`
                        WHERE title = :title AND image_url = :image_url
                        LIMIT 1";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([
            ":title" => $meme["title"],
            ":image_url" => $meme["image_url"]
        ]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            flash("That meme already exists in the database.", "warning");
        } else {
            $query = "INSERT INTO `IT202-M25-Memes`
                (`title`, `post_link`, `image_url`, `author`, `subreddit`, `is_nsfw`, `spoiler`, `is_api`)
                VALUES
                (:title, :post_link, :image_url, :author, :subreddit, :is_nsfw, :spoiler, :is_api)";

            $params = [
                ":title" => $meme["title"],
                ":post_link" => $meme["post_link"],
                ":image_url" => $meme["image_url"],
                ":author" => $meme["author"] !== "" ? $meme["author"] : null,
                ":subreddit" => $meme["subreddit"] !== "" ? $meme["subreddit"] : null,
                ":is_nsfw" => $meme["is_nsfw"],
                ":spoiler" => $meme["spoiler"],
                ":is_api" => $meme["is_api"]
            ];

            error_log("Insert Query: " . $query);
            error_log("Insert Params: " . var_export($params, true));

            try {
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                flash("Meme inserted successfully.", "success");
            } catch (PDOException $e) {
                error_log("Error inserting meme: " . var_export($e, true));

                if ($e->getCode() == "23000") {
                    flash("That meme already exists in the database.", "warning");
                } else {
                    flash("An error occurred while inserting the meme.", "danger");
                }
            }
        }
    } else {
        foreach ($errors as $error) {
            flash($error, "warning");
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
            <!-- UCID: jlt36 | Date: 04/22/2026 | Description: Form used to fetch one meme from the API and save it to the database. -->
            <p>Fetch one meme from the API and save it.</p>
            <input type="hidden" name="action" value="fetch">
            <input type="submit" value="Fetch Meme" class="btn btn-primary">
        </form>
    </div>

    <div id="createTab" class="tab-target" style="display:none;">
        <form method="POST" onsubmit="return validate(this);">
            <!-- UCID: jlt36 | Date: 04/22/2026 | Description: Form used to manually create a meme record with HTML and JavaScript validation. -->

            <div class="mb-3">
                <label for="title">Title</label>
                <input class="form-control" type="text" name="title" id="title" required maxlength="255">
            </div>

            <div class="mb-3">
                <label for="post_link">Post Link</label>
                <input class="form-control" type="url" name="post_link" id="post_link" required maxlength="255">
            </div>

            <div class="mb-3">
                <label for="image_url">Image URL</label>
                <input class="form-control" type="url" name="image_url" id="image_url" required>
            </div>

            <div class="mb-3">
                <label for="author">Author</label>
                <input class="form-control" type="text" name="author" id="author" maxlength="100">
            </div>

            <div class="mb-3">
                <label for="subreddit">Subreddit</label>
                <input class="form-control" type="text" name="subreddit" id="subreddit" maxlength="100">
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
// UCID: jlt36
// Date: 04/22/2026
// Description: Controls tab switching and performs client-side validation for the manual meme creation form.

function switchTab(tabId, clickedLink) {
    const tabs = document.getElementsByClassName("tab-target");
    for (let tab of tabs) {
        tab.style.display = (tab.id === tabId) ? "block" : "none";
    }

    const links = document.querySelectorAll(".nav-link");
    links.forEach(link => link.classList.remove("active"));
    clickedLink.classList.add("active");
}

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