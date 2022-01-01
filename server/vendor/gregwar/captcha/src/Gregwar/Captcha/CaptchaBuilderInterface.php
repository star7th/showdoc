<?php

namespace Gregwar\Captcha;

/**
 * A Captcha builder
 */
interface CaptchaBuilderInterface
{
    /**
     * Builds the code
     */
    public function build($width, $height, $font, $fingerprint);

    /**
     * Saves the code to a file
     */
    public function save($filename, $quality);

    /**
     * Gets the image contents
     */
    public function get($quality);

    /**
     * Outputs the image
     */
    public function output($quality);
}
