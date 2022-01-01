<?php

require_once __DIR__.'/../vendor/autoload.php';

use Gregwar\Captcha\CaptchaBuilder;

/**
 * Generates 1000 captchas and try to read their code with the
 * ocrad OCR
 */

$tests = 10000;
$passed = 0;

shell_exec('rm passed*.jpg');

for ($i=0; $i<$tests; $i++) {
    echo "Captcha $i/$tests... ";

    $captcha = new CaptchaBuilder;

    $captcha
        ->setDistortion(false)
        ->build()
    ;

    if ($captcha->isOCRReadable()) {
        $passed++;
        $captcha->save("passed$passed.jpg");
        echo "passed at ocr... ";
    } else {
        echo "failed... ";
    }

    echo "pass rate: ".round(100*$passed/($i+1),2)."%\n";
}

echo "\n";
echo "Over, $passed/$tests readed with OCR\n";
