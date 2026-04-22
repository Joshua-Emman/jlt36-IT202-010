<?php
require(__DIR__ . "/../../partials/nav.php");
require_once(__DIR__ . "/../../lib/meme_api.php");

$result = [];

if (isset($_GET["fetch"])) {
    $data = [];

    $endpoint = "https://reddit-meme.p.rapidapi.com/memes/top";
    $isRapidAPI = true;
    $rapidAPIHost = "reddit-meme.p.rapidapi.com";

    $result = get($endpoint, "MEMEAPI_KEY", $data, $isRapidAPI, $rapidAPIHost);

    error_log("Response: " . var_export($result, true));

    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
    } else {
        $result = [];
    }
}
?>

<div class="container-fluid">
    <h1>Reddit Meme Viewer</h1>
    <p>This page fetches top memes from Reddit using the Reddit Meme API on RapidAPI.</p>

    <form>
        <input type="hidden" name="fetch" value="1" />
        <input type="submit" value="Get Meme" />
    </form>

    <div class="row">
        <?php if (!empty($result)) : ?>
            <?php foreach ($result as $meme) : ?>
                <div style="margin-bottom: 30px; border: 1px solid #ccc; padding: 15px;">
                    <h3><?php echo htmlspecialchars($meme["title"] ?? "No title"); ?></h3>

                    <p>
                        <strong>Subreddit:</strong>
                        <?php echo htmlspecialchars($meme["subreddit"] ?? "Unknown"); ?>
                    </p>

                    <p>
                        <strong>Created:</strong>
                        <?php echo date("Y-m-d H:i:s", $meme["created_utc"] ?? time()); ?>
                    </p>

                    <?php if (!empty($meme["url"])) : ?>
                        <p>
                            <a href="<?php echo htmlspecialchars($meme["url"]); ?>" target="_blank">Open Meme</a>
                        </p>

                        <?php
                        $url = $meme["url"];
                        $lower = strtolower($url);
                        $isImage =
                            str_ends_with($lower, ".jpg") ||
                            str_ends_with($lower, ".jpeg") ||
                            str_ends_with($lower, ".png") ||
                            str_ends_with($lower, ".gif") ||
                            str_contains($lower, "i.redd.it");
                        ?>

                        <?php if ($isImage) : ?>
                            <img src="<?php echo htmlspecialchars($url); ?>" alt="Meme" style="max-width: 400px; height: auto;" />
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
require(__DIR__ . "/../../partials/flash.php");
?>