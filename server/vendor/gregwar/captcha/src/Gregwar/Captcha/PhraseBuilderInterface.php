<?php

namespace Gregwar\Captcha;

/**
 * Interface for the PhraseBuilder
 *
 * @author Gregwar <g.passault@gmail.com>
 */
interface PhraseBuilderInterface
{
    /**
     * Generates  random phrase of given length with given charset
     */
    public function build($length = null, $charset = null);

    /**
     * "Niceize" a code
     */
    public function niceize($str);
}
