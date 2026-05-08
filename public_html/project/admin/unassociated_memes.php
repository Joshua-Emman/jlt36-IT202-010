<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page.", "warning");
    die(header("Location: " . get_url("landing.php")));
}

// UCID: jlt36
// Date: 05/07/2026
// Description: handles filters, sorting, and limit for memes not associated with any user
$title = trim($_GET["title"] ?? "");
$subreddit = trim($_GET["subreddit"] ?? "");
$is_api = $_GET["is_api"] ?? "";
$sort = $_GET["sort"] ?? "created";
$order = strtoupper($_GET["order"] ?? "DESC");
$limit = (int)($_GET["limit"] ?? 10);

if ($limit < 1 || $limit > 100) {
    $limit = 10;
}

$allowedSorts = [
    "title" => "m.title",
    "author" => "m.author",
    "subreddit" => "m.subreddit",
    "created" => "m.created",
    "modified" => "m.modified"
];

if (!array_key_exists($sort, $allowedSorts)) {
    $sort = "created";
}

if (!in_array($order, ["ASC", "DESC"])) {
    $order = "DESC";
}

$db = getDB();
$params = [];

$where = [
    "um.id IS NULL"
];

if (!empty($title)) {
    $where[] = "m.title LIKE :title";
    $params[":title"] = "%$title%";
}

if (!empty($subreddit)) {
    $where[] = "m.subreddit LIKE :subreddit";
    $params[":subreddit"] = "%$subreddit%";
}

if ($is_api !== "") {
    $where[] = "m.is_api = :is_api";
    $params[":is_api"] = (int)$is_api;
}

$where_sql = " WHERE " . implode(" AND ", $where);

// UCID: jlt36
// Date: 05/07/2026
// Description: Counts all unassociated memes and the number matching the current filters
$total_unassociated = 0;
$filtered_total = 0;

try {
    $total_query = "SELECT COUNT(*) AS total
                    FROM `IT202-M25-Memes` m
                    LEFT JOIN `IT202-M25-UserMemes` um 
                        ON m.id = um.meme_id AND um.is_active = 1
                    WHERE um.id IS NULL";

    $stmt = $db->prepare($total_query);
    $stmt->execute();
    $total_unassociated = (int)($stmt->fetch()["total"] ?? 0);

    $count_query = "SELECT COUNT(*) AS total
                    FROM `IT202-M25-Memes` m
                    LEFT JOIN `IT202-M25-UserMemes` um 
                        ON m.id = um.meme_id AND um.is_active = 1
                    $where_sql";

    $stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $filtered_total = (int)($stmt->fetch()["total"] ?? 0);
} catch (PDOException $e) {
    error_log("Error counting unassociated memes: " . var_export($e, true));
    flash("There was a problem loading unassociated meme stats.", "danger");
}

// UCID: jlt36
// Date: 05/07/2026
// Description: Gets memes that are not currently associated with any user.
$query = "SELECT 
            m.id,
            m.title,
            m.author,
            m.subreddit,
            m.is_nsfw,
            m.spoiler,
            m.is_api,
            m.created,
            m.modified
        FROM `IT202-M25-Memes` m
        LEFT JOIN `IT202-M25-UserMemes` um 
            ON m.id = um.meme_id AND um.is_active = 1
        $where_sql
        ORDER BY " . $allowedSorts[$sort] . " $order
        LIMIT :limit";

$results = [];

try {
    $stmt = $db->prepare($query);

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
    error_log("Error loading unassociated memes: " . var_export($e, true));
    flash("There was a problem loading unassociated memes.", "danger");
}
?>

// UCID: jlt36
// Date: 05/07/2026
// Description: This section displays the unassociated meme stats and filter form. It shows the total unassociated memes, 
// the number matching the current filters, the number shown on the page, and filter/sort options including the user-controlled limit field.
<div class="container-fluid">
    <h3>Unassociated Memes</h3>

    <div class="mb-3">
        <p><strong>Total unassociated memes:</strong> <?php se($total_unassociated); ?></p>
        <p><strong>Total matching current filters:</strong> <?php se($filtered_total); ?></p>
        <p><strong>Total shown on this page:</strong> <?php echo count($results); ?></p>
    </div>

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
            <a class="btn btn-secondary" href="<?php echo get_url("admin/unassociated_memes.php"); ?>">Reset</a>
        </div>
    </form>

// UCID: jlt36
// Date: 05/07/2026
// Description: This section displays the unassociated meme results. If no records match the filters, it shows “No results available.”
// Each meme card shows a short summary and includes a view button that links to the single meme details page.

    <?php if (count($results) == 0) : ?>
        <p>No results available.</p>
    <?php else : ?>
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
                                <strong>Source:</strong> <?php echo se($record, "is_api", 0, false) ? "API" : "Manual"; ?><br>
                                <strong>Created:</strong> <?php se($record, "created"); ?>
                            </p>

                            <a class="btn btn-primary btn-sm" href="<?php echo get_url("view_meme.php") . "?id=" . urlencode($record["id"]); ?>">View</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>