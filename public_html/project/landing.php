<?php
require(__DIR__ . "/../../lib/functions.php");

session_start();

if (!is_logged_in()) {
    flash("You must be logged in to view this page.", "danger");
    header("Location: /project/login.php");
    exit;
}
require(__DIR__ . "/../../partials/nav.php");

error_log("Session: " . var_export($_SESSION, true));
?>
<h1>Landing Page</h1>

    <p>Welcome, <?php echo get_username() ?>!</p>

<?php
require(__DIR__."/../../partials/flash.php");
?>