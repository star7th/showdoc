<?php

require_once __DIR__.'/../vendor/autoload.php';

use Gregwar\Captcha\CaptchaBuilder;

echo count(CaptchaBuilder::create()
    ->build()
    ->getFingerprint()
);

echo "\n";
