<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/meme_api.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

// UCID: jlt36
// Date: 04/23/2026
// Description: Handles filter and sort logic for the meme list page
$title = trim($_GET["title"] ?? "");
$subreddit = trim($_GET["subreddit"] ?? "");
$is_api = $_GET["is_api"] ?? "";
$sort = $_GET["sort"] ?? "created";
$order = strtoupper($_GET["order"] ?? "DESC");
$limit = (int)($_GET["limit"] ?? 10);

// UCID: jlt36
// Date: 04/23/2026
// Description: Validates the record limit and defaults to 10 if it is outside the allowed range
if ($limit < 1 || $limit > 100) {
    $limit = 10;
}

$allowedSorts = ["title", "author", "subreddit", "created", "modified"];
if (!in_array($sort, $allowedSorts)) {
    $sort = "created";
}
if (!in_array($order, ["ASC", "DESC"])) {
    $order = "DESC";
}

$query = "SELECT id, title, author, subreddit, is_nsfw, spoiler, is_api, created FROM `IT202-M25-Memes`";
$params = [];
$where = [];

if (!empty($title)) {
    $where[] = "title LIKE :title";
    $params[":title"] = "%$title%";
}

if (!empty($subreddit)) {
    $where[] = "subreddit LIKE :subreddit";
    $params[":subreddit"] = "%$subreddit%";
}

if ($is_api !== "") {
    $where[] = "is_api = :is_api";
    $params[":is_api"] = (int)$is_api;
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY $sort $order LIMIT :limit";

$db = getDB();
$stmt = $db->prepare($query);
$results = [];

try {
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();
    $r = $stmt->fetchAll();
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching memes " . var_export($e, true));
    flash("An error occurred while loading the meme list.", "danger");
}
?>
<div class="container-fluid">
    <h3>List Memes</h3>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
            <label for="title" class="form-label">Title</label>
            <input class="form-control" type="text" name="title" id="title" value="<?php se($title); ?>">
        </div>

        <div class="col-md-2">
            <label for="subreddit" class="form-label">Subreddit</label>
            <input class="form-control" type="text" name="subreddit" id="subreddit" value="<?php se($subreddit); ?>">
        </div>

        <div class="col-md-2">
            <label for="is_api" class="form-label">Source</label>
            <select class="form-select" name="is_api" id="is_api">
                <option value="" <?php echo $is_api === "" ? "selected" : ""; ?>>All</option>
                <option value="1" <?php echo $is_api === "1" ? "selected" : ""; ?>>API</option>
                <option value="0" <?php echo $is_api === "0" ? "selected" : ""; ?>>Manual</option>
            </select>
        </div>

        <div class="col-md-2">
            <label for="sort" class="form-label">Sort By</label>
            <select class="form-select" name="sort" id="sort">
                <option value="created" <?php echo $sort === "created" ? "selected" : ""; ?>>Created</option>
                <option value="title" <?php echo $sort === "title" ? "selected" : ""; ?>>Title</option>
                <option value="author" <?php echo $sort === "author" ? "selected" : ""; ?>>Author</option>
                <option value="subreddit" <?php echo $sort === "subreddit" ? "selected" : ""; ?>>Subreddit</option>
                <option value="modified" <?php echo $sort === "modified" ? "selected" : ""; ?>>Modified</option>
            </select>
        </div>

        <div class="col-md-1">
            <label for="order" class="form-label">Order</label>
            <select class="form-select" name="order" id="order">
                <option value="DESC" <?php echo $order === "DESC" ? "selected" : ""; ?>>DESC</option>
                <option value="ASC" <?php echo $order === "ASC" ? "selected" : ""; ?>>ASC</option>
            </select>
        </div>

        <div class="col-md-1">
            <label for="limit" class="form-label">Limit</label>
            <input class="form-control" type="number" name="limit" id="limit" min="1" max="100" value="<?php se($limit); ?>">
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-primary">Apply</button>
        </div>
    </form>

    <?php if (count($results) == 0) : ?>
        <p>No results available.</p>
    <?php else : ?>
        <!-- UCID: jlt36 -->
        <!-- Date: 04/23/2026 -->
        <!-- Description: Generates the styled meme list output and action links for each record -->
        <div class="row">
            <?php foreach ($results as $record) : ?>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php se($record, "title"); ?></h5>
                            <p class="card-text">
                                <strong>Author:</strong> <?php se($record, "author", "N/A"); ?><br>
                                <strong>Subreddit:</strong> <?php se($record, "subreddit", "N/A"); ?><br>
                                <strong>NSFW:</strong> <?php echo se($record, "is_nsfw", 0, false) ? "Yes" : "No"; ?><br>
                                <strong>Spoiler:</strong> <?php echo se($record, "spoiler", 0, false) ? "Yes" : "No"; ?><br>
                                <strong>Source:</strong> <?php echo se($record, "is_api", 0, false) ? "API" : "Manual"; ?>
                            </p>

                            <a class="btn btn-primary btn-sm" href="<?php echo get_url("view_meme.php") . "?id=" . urlencode($record["id"]); ?>">View</a>

                            <form method="POST" action="<?php echo get_url("save_meme.php"); ?>" style="display:inline;">
                                <input type="hidden" name="meme_id" value="<?php se($record, "id"); ?>">
                                <button type="submit" style="background:none; border:none; color:#0000EE; text-decoration:underline; cursor:pointer; padding:0; font:inherit;">
                                Save
                                </button>
                            </form>

                            <a class="btn btn-warning btn-sm" href="<?php echo get_url("admin/edit_meme.php") . "?id=" . urlencode($record["id"]); ?>">Edit</a>
                            <a class="btn btn-danger btn-sm" href="<?php echo get_url("admin/delete_meme.php") . "?id=" . urlencode($record["id"]); ?>">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>