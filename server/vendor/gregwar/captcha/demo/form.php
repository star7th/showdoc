<?php
require_once __DIR__.'/../vendor/autoload.php';
use Gregwar\Captcha\PhraseBuilder;

// We need the session to check the phrase after submitting
session_start();
?>

<html>
<?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Checking that the posted phrase match the phrase stored in the session
        if (isset($_SESSION['phrase']) && PhraseBuilder::comparePhrases($_SESSION['phrase'], $_POST['phrase'])) {
            echo "<h1>Captcha is valid !</h1>";
        } else {
            echo "<h1>Captcha is not valid!</h1>";
        }
        // The phrase can't be used twice
        unset($_SESSION['phrase']);
    }
?>
    <form method="post">
        Copy the CAPTCHA:
        <?php 
            // See session.php, where the captcha is actually rendered and the session phrase
            // is set accordingly to the image displayed
        ?>
        <img src="session.php" />
        <input type="text" name="phrase" />
        <input type="submit" />
    </form>
</html>
