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

use function is_bool;
use function is_object;
use function is_scalar;
use function is_string;
use function method_exists;
use function sprintf;
use function strtolower;

/**
 * Compares scalar or NULL values for equality.
 */
class ScalarComparator extends Comparator
{
    /**
     * Returns whether the comparator can compare two values.
     *
     * @param mixed $expected The first value to compare
     * @param mixed $actual   The second value to compare
     *
     * @return bool
     *
     * @since  Method available since Release 3.6.0
     */
    public function accepts($expected, $actual)
    {
        return ((is_scalar($expected) xor null === $expected) &&
               (is_scalar($actual) xor null === $actual))
               // allow comparison between strings and objects featuring __toString()
               || (is_string($expected) && is_object($actual) && method_exists($actual, '__toString'))
               || (is_object($expected) && method_exists($expected, '__toString') && is_string($actual));
    }

    /**
     * Asserts that two values are equal.
     *
     * @param mixed $expected     First value to compare
     * @param mixed $actual       Second value to compare
     * @param float $delta        Allowed numerical distance between two values to consider them equal
     * @param bool  $canonicalize Arrays are sorted before comparison when set to true
     * @param bool  $ignoreCase   Case is ignored when set to true
     *
     * @throws ComparisonFailure
     */
    public function assertEquals($expected, $actual, $delta = 0.0, $canonicalize = false, $ignoreCase = false)/*: void*/
    {
        $expectedToCompare = $expected;
        $actualToCompare   = $actual;

        // always compare as strings to avoid strange behaviour
        // otherwise 0 == 'Foobar'
        if ((is_string($expected) && !is_bool($actual)) || (is_string($actual) && !is_bool($expected))) {
            $expectedToCompare = @(string) $expectedToCompare;
            $actualToCompare   = @(string) $actualToCompare;

            if ($ignoreCase) {
                $expectedToCompare = strtolower($expectedToCompare);
                $actualToCompare   = strtolower($actualToCompare);
            }
        }

        if ($expectedToCompare !== $actualToCompare && is_string($expected) && is_string($actual)) {
            throw new ComparisonFailure(
                $expected,
                $actual,
                $this->exporter->export($expected),
                $this->exporter->export($actual),
                false,
                'Failed asserting that two strings are equal.'
            );
        }

        if ($expectedToCompare != $actualToCompare) {
            throw new ComparisonFailure(
                $expected,
                $actual,
                // no diff is required
                '',
                '',
                false,
                sprintf(
                    'Failed asserting that %s matches expected %s.',
                    $this->exporter->export($actual),
                    $this->exporter->export($expected)
                )
            );
        }
    }
}
