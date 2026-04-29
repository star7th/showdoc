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

use const XDEBUG_CC_BRANCH_CHECK;
use const XDEBUG_CC_DEAD_CODE;
use const XDEBUG_CC_UNUSED;
use const XDEBUG_FILTER_CODE_COVERAGE;
use const XDEBUG_PATH_INCLUDE;
use const XDEBUG_PATH_WHITELIST;
use function defined;
use function extension_loaded;
use function ini_get;
use function phpversion;
use function sprintf;
use function version_compare;
use function xdebug_get_code_coverage;
use function xdebug_set_filter;
use function xdebug_start_code_coverage;
use function xdebug_stop_code_coverage;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Xdebug2Driver extends Driver
{
    /**
     * @var bool
     */
    private $pathCoverageIsMixedCoverage;

    /**
     * @throws WrongXdebugVersionException
     * @throws Xdebug2NotEnabledException
     * @throws XdebugNotAvailableException
     */
    public function __construct(Filter $filter)
    {
        if (!extension_loaded('xdebug')) {
            throw new XdebugNotAvailableException;
        }

        if (version_compare(phpversion('xdebug'), '3', '>=')) {
            throw new WrongXdebugVersionException(
                sprintf(
                    'This driver requires Xdebug 2 but version %s is loaded',
                    phpversion('xdebug')
                )
            );
        }

        if (!ini_get('xdebug.coverage_enable')) {
            throw new Xdebug2NotEnabledException;
        }

        if (!$filter->isEmpty()) {
            if (defined('XDEBUG_PATH_WHITELIST')) {
                $listType = XDEBUG_PATH_WHITELIST;
            } else {
                $listType = XDEBUG_PATH_INCLUDE;
            }

            xdebug_set_filter(
                XDEBUG_FILTER_CODE_COVERAGE,
                $listType,
                $filter->files()
            );
        }

        $this->pathCoverageIsMixedCoverage = version_compare(phpversion('xdebug'), '2.9.6', '<');
    }

    public function canCollectBranchAndPathCoverage(): bool
    {
        return true;
    }

    public function canDetectDeadCode(): bool
    {
        return true;
    }

    public function start(): void
    {
        $flags = XDEBUG_CC_UNUSED;

        if ($this->detectsDeadCode() || $this->collectsBranchAndPathCoverage()) {
            $flags |= XDEBUG_CC_DEAD_CODE;
        }

        if ($this->collectsBranchAndPathCoverage()) {
            $flags |= XDEBUG_CC_BRANCH_CHECK;
        }

        xdebug_start_code_coverage($flags);
    }

    public function stop(): RawCodeCoverageData
    {
        $data = xdebug_get_code_coverage();

        xdebug_stop_code_coverage();

        if ($this->collectsBranchAndPathCoverage()) {
            if ($this->pathCoverageIsMixedCoverage) {
                return RawCodeCoverageData::fromXdebugWithMixedCoverage($data);
            }

            return RawCodeCoverageData::fromXdebugWithPathCoverage($data);
        }

        return RawCodeCoverageData::fromXdebugWithoutPathCoverage($data);
    }

    public function nameAndVersion(): string
    {
        return 'Xdebug ' . phpversion('xdebug');
    }
}
