<?php
require(__DIR__ . "/../../partials/nav.php");

if (!is_logged_in()) {
    flash("You must be logged in to view this page.", "danger");
    header("Location: login.php");
    exit;
}
error_log("Session: " . var_export($_SESSION, true));
?>
<h1>Landing Page</h1>

<?php if(is_logged_in(true)):?>
    <p>Welcome, <?php echo get_username() ?>!</p>
<?php endif;?>

<?php
require(__DIR__."/../../partials/flash.php");
?>