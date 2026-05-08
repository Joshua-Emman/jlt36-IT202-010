<?php
require_once(__DIR__ . "/../../../lib/functions.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!has_role("Admin")) {
    flash("You don't have permission to view this page.", "warning");
    header("Location: " . get_url("landing.php"));
    exit;
}

require(__DIR__ . "/../../../partials/nav.php");

$username = trim($_POST["username"] ?? "");
$title = trim($_POST["title"] ?? "");

$users = [];
$memes = [];

// UCID: jlt36
// Date: 05/07/2026
// Description: Toggles selected user meme associations. If it exists, it flips active status, if not, it creates the association
if (isset($_POST["users"], $_POST["memes"])) {
    $user_ids = $_POST["users"];
    $meme_ids = $_POST["memes"];

    if (empty($user_ids) || empty($meme_ids)) {
        flash("Please select at least one user and one meme.", "warning");
    } else {
        $db = getDB();

        $query = "INSERT INTO `IT202-M25-UserMemes` 
                    (`user_id`, `meme_id`, `is_active`)
                VALUES
                    (:user_id, :meme_id, 1)
                ON DUPLICATE KEY UPDATE
                    is_active = IF(is_active = 1, 0, 1),
                    modified = CURRENT_TIMESTAMP";

        $stmt = $db->prepare($query);

        foreach ($user_ids as $uid) {
            foreach ($meme_ids as $mid) {
                try {
                    $stmt->execute([
                        ":user_id" => (int)$uid,
                        ":meme_id" => (int)$mid
                    ]);
                    flash("Association toggled.", "success");
                } catch (PDOException $e) {
                    error_log("Error toggling meme association: " . var_export($e, true));
                    flash("There was a problem updating an association.", "danger");
                }
            }
        }
    }
}

// UCID: jlt36
// Date: 05/07/2026
// Description: Searches for up to 25 matching users and memes using partial matches
if (isset($_POST["search"])) {
    if (empty($username) && empty($title)) {
        flash("Enter a username or meme title to search.", "warning");
    } else {
        $db = getDB();

        if (!empty($username)) {
            $user_query = "SELECT id, username, email 
                        FROM Users
                        WHERE username LIKE :username
                        LIMIT 25";

            try {
                $stmt = $db->prepare($user_query);
                $stmt->execute([":username" => "%$username%"]);
                $users = $stmt->fetchAll();
            } catch (PDOException $e) {
                error_log("Error searching users: " . var_export($e, true));
                flash("There was a problem searching users.", "danger");
            }
        }

        if (!empty($title)) {
            $meme_query = "SELECT id, title, author, subreddit, is_api
                        FROM `IT202-M25-Memes`
                        WHERE title LIKE :title
                        LIMIT 25";

            try {
                $stmt = $db->prepare($meme_query);
                $stmt->execute([":title" => "%$title%"]);
                $memes = $stmt->fetchAll();
            } catch (PDOException $e) {
                error_log("Error searching memes: " . var_export($e, true));
                flash("There was a problem searching memes.", "danger");
            }
        }
    }
}
?>

<div class="container-fluid">
    <h3>Assign Memes to Users</h3>

    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-4">
            <label for="username" class="form-label">Username Search</label>
            <input class="form-control" type="text" name="username" id="username" value="<?php se($username); ?>" placeholder="Partial username">
        </div>

        <div class="col-md-4">
            <label for="title" class="form-label">Meme Title Search</label>
            <input class="form-control" type="text" name="title" id="title" value="<?php se($title); ?>" placeholder="Partial meme title">
        </div>

        <div class="col-12">
            <button type="submit" name="search" value="1" class="btn btn-primary">Search</button>
        </div>
    </form>

    <?php if (isset($_POST["search"])) : ?>
        <form method="POST">
            <input type="hidden" name="username" value="<?php se($username); ?>">
            <input type="hidden" name="title" value="<?php se($title); ?>">

            <div class="row">
                <div class="col-md-6">
                    <h4>Matching Users</h4>
<!-- UCID: jlt36 | Date: 05/07/2026 | Description: Shows a no-results message when no users match the username search. -->
                    <?php if (count($users) == 0) : ?>
                        <p>No results available.</p>
                    <?php else : ?>
                        <?php foreach ($users as $user) : ?>
                            <div class="mb-2">
                                <input type="checkbox" name="users[]" id="user_<?php se($user, "id"); ?>" value="<?php se($user, "id"); ?>">
                                <label for="user_<?php se($user, "id"); ?>">
                                    <?php se($user, "username"); ?> 
                                    <?php if (!empty($user["email"])) : ?>
                                        (<?php se($user, "email"); ?>)
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <h4>Matching Memes</h4>
<!-- UCID: jlt36 | Date: 05/07/2026 | Description: Shows a no-results message when no memes match the title search. -->
                    <?php if (count($memes) == 0) : ?>
                        <p>No results available.</p>
                    <?php else : ?>
                        <?php foreach ($memes as $meme) : ?>
                            <div class="mb-2">
                                <input type="checkbox" name="memes[]" id="meme_<?php se($meme, "id"); ?>" value="<?php se($meme, "id"); ?>">
                                <label for="meme_<?php se($meme, "id"); ?>">
                                    <strong><?php se($meme, "title"); ?></strong>
                                    <br>
                                    Subreddit: <?php se($meme, "subreddit", "N/A"); ?> |
                                    Source: <?php echo se($meme, "is_api", 0, false) ? "API" : "Manual"; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-success">Toggle Selected Associations</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>