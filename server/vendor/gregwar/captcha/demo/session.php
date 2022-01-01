<?php
// We need the session to store the correct phrase for later check
session_start();

// Including the autoload (you need to composer install in the main directory)
require_once __DIR__.'/../vendor/autoload.php';

use Gregwar\Captcha\CaptchaBuilder;

// Creating the captcha instance and setting the phrase in the session to store
// it for check when the form is submitted
$captcha = new CaptchaBuilder;
$_SESSION['phrase'] = $captcha->getPhrase();

// Setting the header to image jpeg because we here render an image
header('Content-Type: image/jpeg');

// Running the actual rendering of the captcha image
$captcha
    ->build()
    ->output()
;
