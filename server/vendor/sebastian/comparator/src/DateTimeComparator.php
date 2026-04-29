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
use function floor;
use function sprintf;
use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;

/**
 * Compares DateTimeInterface instances for equality.
 */
class DateTimeComparator extends ObjectComparator
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
        return ($expected instanceof DateTime || $expected instanceof DateTimeInterface) &&
               ($actual instanceof DateTime || $actual instanceof DateTimeInterface);
    }

    /**
     * Asserts that two values are equal.
     *
     * @param mixed $expected     First value to compare
     * @param mixed $actual       Second value to compare
     * @param float $delta        Allowed numerical distance between two values to consider them equal
     * @param bool  $canonicalize Arrays are sorted before comparison when set to true
     * @param bool  $ignoreCase   Case is ignored when set to true
     * @param array $processed    List of already processed elements (used to prevent infinite recursion)
     *
     * @throws Exception
     * @throws ComparisonFailure
     */
    public function assertEquals($expected, $actual, $delta = 0.0, $canonicalize = false, $ignoreCase = false, array &$processed = [])/*: void*/
    {
        /** @var DateTimeInterface $expected */
        /** @var DateTimeInterface $actual */
        $absDelta = abs($delta);
        $delta    = new DateInterval(sprintf('PT%dS', $absDelta));
        $delta->f = $absDelta - floor($absDelta);

        $actualClone = (clone $actual)
            ->setTimezone(new DateTimeZone('UTC'));

        $expectedLower = (clone $expected)
            ->setTimezone(new DateTimeZone('UTC'))
            ->sub($delta);

        $expectedUpper = (clone $expected)
            ->setTimezone(new DateTimeZone('UTC'))
            ->add($delta);

        if ($actualClone < $expectedLower || $actualClone > $expectedUpper) {
            throw new ComparisonFailure(
                $expected,
                $actual,
                $this->dateTimeToString($expected),
                $this->dateTimeToString($actual),
                false,
                'Failed asserting that two DateTime objects are equal.'
            );
        }
    }

    /**
     * Returns an ISO 8601 formatted string representation of a datetime or
     * 'Invalid DateTimeInterface object' if the provided DateTimeInterface was not properly
     * initialized.
     */
    private function dateTimeToString(DateTimeInterface $datetime): string
    {
        $string = $datetime->format('Y-m-d\TH:i:s.uO');

        return $string ?: 'Invalid DateTimeInterface object';
    }
}
