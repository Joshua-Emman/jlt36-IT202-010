<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/meme_api.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

$query = "SELECT id, title, author, subreddit, is_nsfw, spoiler, is_api FROM `IT202-M25-Memes` ORDER BY created DESC LIMIT 25";
$db = getDB();
$stmt = $db->prepare($query);
$results = [];

try {
    $stmt->execute();
    $r = $stmt->fetchAll();
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching memes " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}
?>
<div class="container-fluid">
    <h3>List Memes</h3>
    <?php if (count($results) == 0) : ?>
        <p>No results to show</p>
    <?php else : ?>
        <table class="table">
            <?php foreach ($results as $index => $record) : ?>
                <?php if ($index == 0) : ?>
                    <thead>
                        <tr>
                            <?php foreach ($record as $column => $value) : ?>
                                <th><?php se($column); ?></th>
                            <?php endforeach; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                <?php endif; ?>
                <tr>
                    <?php foreach ($record as $column => $value) : ?>
                        <td><?php se($value, null, "N/A"); ?></td>
                    <?php endforeach; ?>
                    <td>
                        <a href="<?php echo get_url("admin/edit_meme.php") . "?id=" . urlencode($record["id"]); ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
                    </tbody>
        </table>
    <?php endif; ?>
</div>
<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>