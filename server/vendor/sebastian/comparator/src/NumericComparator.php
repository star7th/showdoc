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

use function abs;
use function is_float;
use function is_infinite;
use function is_nan;
use function is_numeric;
use function is_string;
use function sprintf;

/**
 * Compares numerical values for equality.
 */
class NumericComparator extends ScalarComparator
{
    /**
     * Returns whether the comparator can compare two values.
     *
     * @param mixed $expected The first value to compare
     * @param mixed $actual   The second value to compare
     *
     * @return bool
     */
    public function accepts($expected, $actual)
    {
        // all numerical values, but not if both of them are strings
        return is_numeric($expected) && is_numeric($actual) &&
               !(is_string($expected) && is_string($actual));
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
        if ($this->isInfinite($actual) && $this->isInfinite($expected)) {
            return;
        }

        if (($this->isInfinite($actual) xor $this->isInfinite($expected)) ||
            ($this->isNan($actual) || $this->isNan($expected)) ||
            abs($actual - $expected) > $delta) {
            throw new ComparisonFailure(
                $expected,
                $actual,
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

    private function isInfinite($value): bool
    {
        return is_float($value) && is_infinite($value);
    }

    private function isNan($value): bool
    {
        return is_float($value) && is_nan($value);
    }
}
