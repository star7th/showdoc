<?php

require_once __DIR__.'/../vendor/autoload.php';

use Gregwar\Captcha\CaptchaBuilder;

header('Content-type: image/jpeg');

CaptchaBuilder::create()
    ->build()
    ->output()
;
