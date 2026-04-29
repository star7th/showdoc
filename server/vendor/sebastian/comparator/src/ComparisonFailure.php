<?php declare(strict_types=1);
/*
 * This file is part of sebastian/comparator.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Comparator;

use RuntimeException;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

/**
 * Thrown when an assertion for string equality failed.
 */
class ComparisonFailure extends RuntimeException
{
    /**
     * Expected value of the retrieval which does not match $actual.
     *
     * @var mixed
     */
    protected $expected;

    /**
     * Actually retrieved value which does not match $expected.
     *
     * @var mixed
     */
    protected $actual;

    /**
     * The string representation of the expected value.
     *
     * @var string
     */
    protected $expectedAsString;

    /**
     * The string representation of the actual value.
     *
     * @var string
     */
    protected $actualAsString;

    /**
     * @var bool
     */
    protected $identical;

    /**
     * Optional message which is placed in front of the first line
     * returned by toString().
     *
     * @var string
     */
    protected $message;

    /**
     * Initialises with the expected value and the actual value.
     *
     * @param mixed  $expected         expected value retrieved
     * @param mixed  $actual           actual value retrieved
     * @param string $expectedAsString
     * @param string $actualAsString
     * @param bool   $identical
     * @param string $message          a string which is prefixed on all returned lines
     *                                 in the difference output
     */
    public function __construct($expected, $actual, $expectedAsString, $actualAsString, $identical = false, $message = '')
    {
        $this->expected         = $expected;
        $this->actual           = $actual;
        $this->expectedAsString = $expectedAsString;
        $this->actualAsString   = $actualAsString;
        $this->message          = $message;
    }

    public function getActual()
    {
        return $this->actual;
    }

    public function getExpected()
    {
        return $this->expected;
    }

    /**
     * @return string
     */
    public function getActualAsString()
    {
        return $this->actualAsString;
    }

    /**
     * @return string
     */
    public function getExpectedAsString()
    {
        return $this->expectedAsString;
    }

    /**
     * @return string
     */
    public function getDiff()
    {
        if (!$this->actualAsString && !$this->expectedAsString) {
            return '';
        }

        $differ = new Differ(new UnifiedDiffOutputBuilder("\n--- Expected\n+++ Actual\n"));

        return $differ->diff($this->expectedAsString, $this->actualAsString);
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->message . $this->getDiff();
    }
}
