<?php

namespace Test;

use Gregwar\Captcha\CaptchaBuilder;
use PHPUnit\Framework\TestCase;

class CaptchaBuilderTest extends TestCase
{
    public function testDemo()
    {
        $captcha = new CaptchaBuilder();
        $captcha
            ->build()
            ->save('out.jpg')
        ;

        $this->assertTrue(file_exists(__DIR__.'/../out.jpg'));
    }

    public function testFingerPrint()
    {
        $int = count(CaptchaBuilder::create()
            ->build()
            ->getFingerprint()
        );

        $this->assertTrue(is_int($int));
    }
}