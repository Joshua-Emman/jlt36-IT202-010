<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page.", "warning");
    die(header("Location: " . get_url("landing.php")));
}

// UCID: jlt36
// Date: 05/07/2026
// Description: Handles filters, sorting, and limit for all user meme associations
$username = trim($_GET["username"] ?? "");
$title = trim($_GET["title"] ?? "");
$subreddit = trim($_GET["subreddit"] ?? "");
$sort = $_GET["sort"] ?? "saved";
$order = strtoupper($_GET["order"] ?? "DESC");
$limit = (int)($_GET["limit"] ?? 10);

if ($limit < 1 || $limit > 100) {
    $limit = 10;
}

$allowedSorts = [
    "username" => "u.username",
    "title" => "m.title",
    "subreddit" => "m.subreddit",
    "saved" => "um.created",
    "user_count" => "user_count"
];

if (!array_key_exists($sort, $allowedSorts)) {
    $sort = "saved";
}

if (!in_array($order, ["ASC", "DESC"])) {
    $order = "DESC";
}

$db = getDB();
$params = [];
$where = ["um.is_active = 1"];

if (!empty($username)) {
    $where[] = "u.username LIKE :username";
    $params[":username"] = "%$username%";
}

if (!empty($title)) {
    $where[] = "m.title LIKE :title";
    $params[":title"] = "%$title%";
}

if (!empty($subreddit)) {
    $where[] = "m.subreddit LIKE :subreddit";
    $params[":subreddit"] = "%$subreddit%";
}

$where_sql = " WHERE " . implode(" AND ", $where);

// UCID: jlt36
// Date: 05/07/2026
// Description: Counts all active user meme associations and the amount matching the current filters
$total_associations = 0;
$filtered_total = 0;

try {
    $stmt = $db->prepare("SELECT COUNT(*) AS total FROM `IT202-M25-UserMemes` WHERE is_active = 1");
    $stmt->execute();
    $total_associations = (int)($stmt->fetch()["total"] ?? 0);

    $count_query = "SELECT COUNT(*) AS total
                    FROM `IT202-M25-UserMemes` um
                    JOIN Users u ON um.user_id = u.id
                    JOIN `IT202-M25-Memes` m ON um.meme_id = m.id
                    $where_sql";

    $stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $filtered_total = (int)($stmt->fetch()["total"] ?? 0);
} catch (PDOException $e) {
    error_log("Error counting all user meme associations: " . var_export($e, true));
    flash("There was a problem loading association stats.", "danger");
}

// UCID: jlt36
// Date: 05/07/2026
// Description: Gets all user meme associations with usernames, meme info, and total users associated with each meme
$query = "SELECT 
            um.id AS association_id,
            um.created AS saved_on,
            u.id AS user_id,
            u.username,
            m.id AS meme_id,
            m.title,
            m.author,
            m.subreddit,
            m.is_nsfw,
            m.spoiler,
            (
                SELECT COUNT(*)
                FROM `IT202-M25-UserMemes` um2
                WHERE um2.meme_id = m.id
                AND um2.is_active = 1
            ) AS user_count
            FROM `IT202-M25-UserMemes` um
            JOIN Users u ON um.user_id = u.id
            JOIN `IT202-M25-Memes` m ON um.meme_id = m.id
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
    error_log("Error loading all user meme associations: " . var_export($e, true));
    flash("There was a problem loading user meme associations.", "danger");
}
?>

<div class="container-fluid">
    <h3>All User Meme Associations</h3>

    <div class="mb-3">
        <p><strong>Total active associations:</strong> <?php se($total_associations); ?></p>
        <p><strong>Total matching current filters:</strong> <?php se($filtered_total); ?></p>
        <p><strong>Total shown on this page:</strong> <?php echo count($results); ?></p>
    </div>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
            <label for="username" class="form-label">Username</label>
            <input class="form-control" type="text" name="username" id="username" value="<?php se($username); ?>">
        </div>

        <div class="col-md-3">
            <label for="title" class="form-label">Meme Title</label>
            <input class="form-control" type="text" name="title" id="title" value="<?php se($title); ?>">
        </div>

        <div class="col-md-2">
            <label for="subreddit" class="form-label">Subreddit</label>
            <input class="form-control" type="text" name="subreddit" id="subreddit" value="<?php se($subreddit); ?>">
        </div>

        <div class="col-md-2">
            <label for="sort" class="form-label">Sort By</label>
            <select class="form-select" name="sort" id="sort">
                <option value="saved" <?php echo $sort === "saved" ? "selected" : ""; ?>>Saved Date</option>
                <option value="username" <?php echo $sort === "username" ? "selected" : ""; ?>>Username</option>
                <option value="title" <?php echo $sort === "title" ? "selected" : ""; ?>>Title</option>
                <option value="subreddit" <?php echo $sort === "subreddit" ? "selected" : ""; ?>>Subreddit</option>
                <option value="user_count" <?php echo $sort === "user_count" ? "selected" : ""; ?>>User Count</option>
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
            <a class="btn btn-secondary" href="<?php echo get_url("admin/all_user_memes.php"); ?>">Reset</a>
        </div>
    </form>

    <?php if (!empty($username)) : ?>
        <!-- UCID: jlt36 | Date: 05/07/2026 | Description: Removes all associations for users matching the current username filter  -->
        <form method="POST" action="<?php echo get_url("admin/remove_matching_user_memes.php"); ?>" onsubmit="return confirm('Remove all associations for matching users?');" class="mb-4">
            <input type="hidden" name="username" value="<?php se($username); ?>">
            <button type="submit" class="btn btn-danger">Remove Associations for Matching Users</button>
        </form>
    <?php endif; ?>

    <?php if (count($results) == 0) : ?>
        <p>No results available.</p>
    <?php else : ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Meme Summary</th>
                    <th>Total Users Associated</th>
                    <th>Saved On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $record) : ?>
                    <tr>
                        <td>
                            <a href="<?php echo get_url("profile.php") . "?id=" . urlencode($record["user_id"]); ?>">
                                <?php se($record, "username"); ?>
                            </a>
                        </td>
                        <td>
                            <strong><?php se($record, "title"); ?></strong><br>
                            Author: <?php se($record, "author", "N/A"); ?><br>
                            Subreddit: <?php se($record, "subreddit", "N/A"); ?><br>
                            NSFW: <?php echo se($record, "is_nsfw", 0, false) ? "Yes" : "No"; ?><br>
                            Spoiler: <?php echo se($record, "spoiler", 0, false) ? "Yes" : "No"; ?>
                        </td>
                        <td><?php se($record, "user_count"); ?></td>
                        <td><?php se($record, "saved_on"); ?></td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="<?php echo get_url("view_meme.php") . "?id=" . urlencode($record["meme_id"]); ?>">View</a>

                            <!-- UCID: jlt36 | Date: 05/07/2026 | Description: Removes only this user meme relationship, not the user or meme -->
                            <form method="POST" action="<?php echo get_url("admin/remove_user_meme.php"); ?>" style="display:inline;" onsubmit="return confirm('Remove this association?');">
                                <input type="hidden" name="association_id" value="<?php se($record, "association_id"); ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>