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

use function array_key_exists;
use function is_array;
use function sort;
use function sprintf;
use function str_replace;
use function trim;

/**
 * Compares arrays for equality.
 *
 * Arrays are equal if they contain the same key-value pairs.
 * The order of the keys does not matter.
 * The types of key-value pairs do not matter.
 */
class ArrayComparator extends Comparator
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
        return is_array($expected) && is_array($actual);
    }

    /**
     * Asserts that two arrays are equal.
     *
     * @param mixed $expected     First value to compare
     * @param mixed $actual       Second value to compare
     * @param float $delta        Allowed numerical distance between two values to consider them equal
     * @param bool  $canonicalize Arrays are sorted before comparison when set to true
     * @param bool  $ignoreCase   Case is ignored when set to true
     * @param array $processed    List of already processed elements (used to prevent infinite recursion)
     *
     * @throws ComparisonFailure
     */
    public function assertEquals($expected, $actual, $delta = 0.0, $canonicalize = false, $ignoreCase = false, array &$processed = [])/*: void*/
    {
        if ($canonicalize) {
            sort($expected);
            sort($actual);
        }

        $remaining        = $actual;
        $actualAsString   = "Array (\n";
        $expectedAsString = "Array (\n";
        $equal            = true;

        foreach ($expected as $key => $value) {
            unset($remaining[$key]);

            if (!array_key_exists($key, $actual)) {
                $expectedAsString .= sprintf(
                    "    %s => %s\n",
                    $this->exporter->export($key),
                    $this->exporter->shortenedExport($value)
                );

                $equal = false;

                continue;
            }

            try {
                $comparator = $this->factory->getComparatorFor($value, $actual[$key]);
                $comparator->assertEquals($value, $actual[$key], $delta, $canonicalize, $ignoreCase, $processed);

                $expectedAsString .= sprintf(
                    "    %s => %s\n",
                    $this->exporter->export($key),
                    $this->exporter->shortenedExport($value)
                );

                $actualAsString .= sprintf(
                    "    %s => %s\n",
                    $this->exporter->export($key),
                    $this->exporter->shortenedExport($actual[$key])
                );
            } catch (ComparisonFailure $e) {
                $expectedAsString .= sprintf(
                    "    %s => %s\n",
                    $this->exporter->export($key),
                    $e->getExpectedAsString() ? $this->indent($e->getExpectedAsString()) : $this->exporter->shortenedExport($e->getExpected())
                );

                $actualAsString .= sprintf(
                    "    %s => %s\n",
                    $this->exporter->export($key),
                    $e->getActualAsString() ? $this->indent($e->getActualAsString()) : $this->exporter->shortenedExport($e->getActual())
                );

                $equal = false;
            }
        }

        foreach ($remaining as $key => $value) {
            $actualAsString .= sprintf(
                "    %s => %s\n",
                $this->exporter->export($key),
                $this->exporter->shortenedExport($value)
            );

            $equal = false;
        }

        $expectedAsString .= ')';
        $actualAsString .= ')';

        if (!$equal) {
            throw new ComparisonFailure(
                $expected,
                $actual,
                $expectedAsString,
                $actualAsString,
                false,
                'Failed asserting that two arrays are equal.'
            );
        }
    }

    protected function indent($lines)
    {
        return trim(str_replace("\n", "\n    ", $lines));
    }
}
