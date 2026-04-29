<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Driver;

use const PHP_SAPI;
use const PHP_VERSION;
use function array_diff;
use function array_keys;
use function array_merge;
use function get_included_files;
use function phpdbg_end_oplog;
use function phpdbg_get_executable;
use function phpdbg_start_oplog;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class PhpdbgDriver extends Driver
{
    /**
     * @throws PhpdbgNotAvailableException
     */
    public function __construct()
    {
        if (PHP_SAPI !== 'phpdbg') {
            throw new PhpdbgNotAvailableException;
        }
    }

    public function start(): void
    {
        phpdbg_start_oplog();
    }

    public function stop(): RawCodeCoverageData
    {
        static $fetchedLines = [];

        $dbgData = phpdbg_end_oplog();

        if ($fetchedLines === []) {
            $sourceLines = phpdbg_get_executable();
        } else {
            $newFiles = array_diff(get_included_files(), array_keys($fetchedLines));

            $sourceLines = [];

            if ($newFiles) {
                $sourceLines = phpdbg_get_executable(['files' => $newFiles]);
            }
        }

        foreach ($sourceLines as $file => $lines) {
            foreach ($lines as $lineNo => $numExecuted) {
                $sourceLines[$file][$lineNo] = self::LINE_NOT_EXECUTED;
            }
        }

        $fetchedLines = array_merge($fetchedLines, $sourceLines);

        return RawCodeCoverageData::fromXdebugWithoutPathCoverage(
            $this->detectExecutedLines($fetchedLines, $dbgData)
        );
    }

    public function nameAndVersion(): string
    {
        return 'PHPDBG ' . PHP_VERSION;
    }

    private function detectExecutedLines(array $sourceLines, array $dbgData): array
    {
        foreach ($dbgData as $file => $coveredLines) {
            foreach ($coveredLines as $lineNo => $numExecuted) {
                // phpdbg also reports $lineNo=0 when e.g. exceptions get thrown.
                // make sure we only mark lines executed which are actually executable.
                if (isset($sourceLines[$file][$lineNo])) {
                    $sourceLines[$file][$lineNo] = self::LINE_EXECUTED;
                }
            }
        }

        return $sourceLines;
    }
}
