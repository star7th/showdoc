<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Xml;

use DOMElement;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Tests
{
    private $contextNode;
    private $codeMap = [
        -1 => 'UNKNOWN',    // PHPUnit_Runner_BaseTestRunner::STATUS_UNKNOWN
        0  => 'PASSED',     // PHPUnit_Runner_BaseTestRunner::STATUS_PASSED
        1  => 'SKIPPED',    // PHPUnit_Runner_BaseTestRunner::STATUS_SKIPPED
        2  => 'INCOMPLETE', // PHPUnit_Runner_BaseTestRunner::STATUS_INCOMPLETE
        3  => 'FAILURE',    // PHPUnit_Runner_BaseTestRunner::STATUS_FAILURE
        4  => 'ERROR',      // PHPUnit_Runner_BaseTestRunner::STATUS_ERROR
        5  => 'RISKY',      // PHPUnit_Runner_BaseTestRunner::STATUS_RISKY
        6  => 'WARNING',     // PHPUnit_Runner_BaseTestRunner::STATUS_WARNING
    ];

    public function __construct(DOMElement $context)
    {
        $this->contextNode = $context;
    }

    public function addTest(string $test, array $result): void
    {
        $node = $this->contextNode->appendChild(
            $this->contextNode->ownerDocument->createElementNS(
                'https://schema.phpunit.de/coverage/1.0',
                'test'
            )
        );

        $node->setAttribute('name', $test);
        $node->setAttribute('size', $result['size']);
        $node->setAttribute('result', (string) $result['status']);
        $node->setAttribute('status', $this->codeMap[(int) $result['status']]);
    }
}
