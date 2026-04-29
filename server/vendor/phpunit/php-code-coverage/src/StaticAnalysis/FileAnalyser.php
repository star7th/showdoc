<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
interface FileAnalyser
{
    public function classesIn(string $filename): array;

    public function traitsIn(string $filename): array;

    public function functionsIn(string $filename): array;

    /**
     * @psalm-return array{linesOfCode: int, commentLinesOfCode: int, nonCommentLinesOfCode: int}
     */
    public function linesOfCodeFor(string $filename): array;

    public function executableLinesIn(string $filename): array;

    public function ignoredLinesFor(string $filename): array;
}
