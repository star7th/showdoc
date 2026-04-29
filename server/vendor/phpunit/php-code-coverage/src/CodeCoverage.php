<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage;

use function array_diff;
use function array_diff_key;
use function array_flip;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function get_class;
use function is_array;
use function sort;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\PhptTestCase;
use PHPUnit\Util\Test;
use ReflectionClass;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Node\Builder;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\StaticAnalysis\CachingFileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingFileAnalyser;
use SebastianBergmann\CodeUnitReverseLookup\Wizard;

/**
 * Provides collection functionality for PHP code coverage information.
 */
final class CodeCoverage
{
    private const UNCOVERED_FILES = 'UNCOVERED_FILES';

    /**
     * @var Driver
     */
    private $driver;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var Wizard
     */
    private $wizard;

    /**
     * @var bool
     */
    private $checkForUnintentionallyCoveredCode = false;

    /**
     * @var bool
     */
    private $includeUncoveredFiles = true;

    /**
     * @var bool
     */
    private $processUncoveredFiles = false;

    /**
     * @var bool
     */
    private $ignoreDeprecatedCode = false;

    /**
     * @var null|PhptTestCase|string|TestCase
     */
    private $currentId;

    /**
     * Code coverage data.
     *
     * @var ProcessedCodeCoverageData
     */
    private $data;

    /**
     * @var bool
     */
    private $useAnnotationsForIgnoringCode = true;

    /**
     * Test data.
     *
     * @var array
     */
    private $tests = [];

    /**
     * @psalm-var list<class-string>
     */
    private $parentClassesExcludedFromUnintentionallyCoveredCodeCheck = [];

    /**
     * @var ?FileAnalyser
     */
    private $analyser;

    /**
     * @var ?string
     */
    private $cacheDirectory;

    /**
     * @var ?Directory
     */
    private $cachedReport;

    public function __construct(Driver $driver, Filter $filter)
    {
        $this->driver = $driver;
        $this->filter = $filter;
        $this->data   = new ProcessedCodeCoverageData;
        $this->wizard = new Wizard;
    }

    /**
     * Returns the code coverage information as a graph of node objects.
     */
    public function getReport(): Directory
    {
        if ($this->cachedReport === null) {
            $this->cachedReport = (new Builder($this->analyser()))->build($this);
        }

        return $this->cachedReport;
    }

    /**
     * Clears collected code coverage data.
     */
    public function clear(): void
    {
        $this->currentId    = null;
        $this->data         = new ProcessedCodeCoverageData;
        $this->tests        = [];
        $this->cachedReport = null;
    }

    /**
     * @internal
     */
    public function clearCache(): void
    {
        $this->cachedReport = null;
    }

    /**
     * Returns the filter object used.
     */
    public function filter(): Filter
    {
        return $this->filter;
    }

    /**
     * Returns the collected code coverage data.
     */
    public function getData(bool $raw = false): ProcessedCodeCoverageData
    {
        if (!$raw) {
            if ($this->processUncoveredFiles) {
                $this->processUncoveredFilesFromFilter();
            } elseif ($this->includeUncoveredFiles) {
                $this->addUncoveredFilesFromFilter();
            }
        }

        return $this->data;
    }

    /**
     * Sets the coverage data.
     */
    public function setData(ProcessedCodeCoverageData $data): void
    {
        $this->data = $data;
    }

    /**
     * Returns the test data.
     */
    public function getTests(): array
    {
        return $this->tests;
    }

    /**
     * Sets the test data.
     */
    public function setTests(array $tests): void
    {
        $this->tests = $tests;
    }

    /**
     * Start collection of code coverage information.
     *
     * @param PhptTestCase|string|TestCase $id
     */
    public function start($id, bool $clear = false): void
    {
        if ($clear) {
            $this->clear();
        }

        $this->currentId = $id;

        $this->driver->start();

        $this->cachedReport = null;
    }

    /**
     * Stop collection of code coverage information.
     *
     * @param array|false $linesToBeCovered
     */
    public function stop(bool $append = true, $linesToBeCovered = [], array $linesToBeUsed = []): RawCodeCoverageData
    {
        if (!is_array($linesToBeCovered) && $linesToBeCovered !== false) {
            throw new InvalidArgumentException(
                '$linesToBeCovered must be an array or false'
            );
        }

        $data = $this->driver->stop();
        $this->append($data, null, $append, $linesToBeCovered, $linesToBeUsed);

        $this->currentId    = null;
        $this->cachedReport = null;

        return $data;
    }

    /**
     * Appends code coverage data.
     *
     * @param PhptTestCase|string|TestCase $id
     * @param array|false                  $linesToBeCovered
     *
     * @throws ReflectionException
     * @throws TestIdMissingException
     * @throws UnintentionallyCoveredCodeException
     */
    public function append(RawCodeCoverageData $rawData, $id = null, bool $append = true, $linesToBeCovered = [], array $linesToBeUsed = []): void
    {
        if ($id === null) {
            $id = $this->currentId;
        }

        if ($id === null) {
            throw new TestIdMissingException;
        }

        $this->cachedReport = null;

        $this->applyFilter($rawData);

        $this->applyExecutableLinesFilter($rawData);

        if ($this->useAnnotationsForIgnoringCode) {
            $this->applyIgnoredLinesFilter($rawData);
        }

        $this->data->initializeUnseenData($rawData);

        if (!$append) {
            return;
        }

        if ($id !== self::UNCOVERED_FILES) {
            $this->applyCoversAnnotationFilter(
                $rawData,
                $linesToBeCovered,
                $linesToBeUsed
            );

            if (empty($rawData->lineCoverage())) {
                return;
            }

            $size         = 'unknown';
            $status       = -1;
            $fromTestcase = false;

            if ($id instanceof TestCase) {
                $fromTestcase = true;
                $_size        = $id->getSize();

                if ($_size === Test::SMALL) {
                    $size = 'small';
                } elseif ($_size === Test::MEDIUM) {
                    $size = 'medium';
                } elseif ($_size === Test::LARGE) {
                    $size = 'large';
                }

                $status = $id->getStatus();
                $id     = get_class($id) . '::' . $id->getName();
            } elseif ($id instanceof PhptTestCase) {
                $fromTestcase = true;
                $size         = 'large';
                $id           = $id->getName();
            }

            $this->tests[$id] = ['size' => $size, 'status' => $status, 'fromTestcase' => $fromTestcase];

            $this->data->markCodeAsExecutedByTestCase($id, $rawData);
        }
    }

    /**
     * Merges the data from another instance.
     */
    public function merge(self $that): void
    {
        $this->filter->includeFiles(
            $that->filter()->files()
        );

        $this->data->merge($that->data);

        $this->tests = array_merge($this->tests, $that->getTests());

        $this->cachedReport = null;
    }

    public function enableCheckForUnintentionallyCoveredCode(): void
    {
        $this->checkForUnintentionallyCoveredCode = true;
    }

    public function disableCheckForUnintentionallyCoveredCode(): void
    {
        $this->checkForUnintentionallyCoveredCode = false;
    }

    public function includeUncoveredFiles(): void
    {
        $this->includeUncoveredFiles = true;
    }

    public function excludeUncoveredFiles(): void
    {
        $this->includeUncoveredFiles = false;
    }

    public function processUncoveredFiles(): void
    {
        $this->processUncoveredFiles = true;
    }

    public function doNotProcessUncoveredFiles(): void
    {
        $this->processUncoveredFiles = false;
    }

    public function enableAnnotationsForIgnoringCode(): void
    {
        $this->useAnnotationsForIgnoringCode = true;
    }

    public function disableAnnotationsForIgnoringCode(): void
    {
        $this->useAnnotationsForIgnoringCode = false;
    }

    public function ignoreDeprecatedCode(): void
    {
        $this->ignoreDeprecatedCode = true;
    }

    public function doNotIgnoreDeprecatedCode(): void
    {
        $this->ignoreDeprecatedCode = false;
    }

    /**
     * @psalm-assert-if-true !null $this->cacheDirectory
     */
    public function cachesStaticAnalysis(): bool
    {
        return $this->cacheDirectory !== null;
    }

    public function cacheStaticAnalysis(string $directory): void
    {
        $this->cacheDirectory = $directory;
    }

    public function doNotCacheStaticAnalysis(): void
    {
        $this->cacheDirectory = null;
    }

    /**
     * @throws StaticAnalysisCacheNotConfiguredException
     */
    public function cacheDirectory(): string
    {
        if (!$this->cachesStaticAnalysis()) {
            throw new StaticAnalysisCacheNotConfiguredException(
                'The static analysis cache is not configured'
            );
        }

        return $this->cacheDirectory;
    }

    /**
     * @psalm-param class-string $className
     */
    public function excludeSubclassesOfThisClassFromUnintentionallyCoveredCodeCheck(string $className): void
    {
        $this->parentClassesExcludedFromUnintentionallyCoveredCodeCheck[] = $className;
    }

    public function enableBranchAndPathCoverage(): void
    {
        $this->driver->enableBranchAndPathCoverage();
    }

    public function disableBranchAndPathCoverage(): void
    {
        $this->driver->disableBranchAndPathCoverage();
    }

    public function collectsBranchAndPathCoverage(): bool
    {
        return $this->driver->collectsBranchAndPathCoverage();
    }

    public function detectsDeadCode(): bool
    {
        return $this->driver->detectsDeadCode();
    }

    /**
     * Applies the @covers annotation filtering.
     *
     * @param array|false $linesToBeCovered
     *
     * @throws ReflectionException
     * @throws UnintentionallyCoveredCodeException
     */
    private function applyCoversAnnotationFilter(RawCodeCoverageData $rawData, $linesToBeCovered, array $linesToBeUsed): void
    {
        if ($linesToBeCovered === false) {
            $rawData->clear();

            return;
        }

        if (empty($linesToBeCovered)) {
            return;
        }

        if ($this->checkForUnintentionallyCoveredCode &&
            (!$this->currentId instanceof TestCase ||
            (!$this->currentId->isMedium() && !$this->currentId->isLarge()))) {
            $this->performUnintentionallyCoveredCodeCheck($rawData, $linesToBeCovered, $linesToBeUsed);
        }

        $rawLineData         = $rawData->lineCoverage();
        $filesWithNoCoverage = array_diff_key($rawLineData, $linesToBeCovered);

        foreach (array_keys($filesWithNoCoverage) as $fileWithNoCoverage) {
            $rawData->removeCoverageDataForFile($fileWithNoCoverage);
        }

        if (is_array($linesToBeCovered)) {
            foreach ($linesToBeCovered as $fileToBeCovered => $includedLines) {
                $rawData->keepLineCoverageDataOnlyForLines($fileToBeCovered, $includedLines);
                $rawData->keepFunctionCoverageDataOnlyForLines($fileToBeCovered, $includedLines);
            }
        }
    }

    private function applyFilter(RawCodeCoverageData $data): void
    {
        if ($this->filter->isEmpty()) {
            return;
        }

        foreach (array_keys($data->lineCoverage()) as $filename) {
            if ($this->filter->isExcluded($filename)) {
                $data->removeCoverageDataForFile($filename);
            }
        }
    }

    private function applyExecutableLinesFilter(RawCodeCoverageData $data): void
    {
        foreach (array_keys($data->lineCoverage()) as $filename) {
            if (!$this->filter->isFile($filename)) {
                continue;
            }

            $linesToBranchMap = $this->analyser()->executableLinesIn($filename);

            $data->keepLineCoverageDataOnlyForLines(
                $filename,
                array_keys($linesToBranchMap)
            );

            $data->markExecutableLineByBranch(
                $filename,
                $linesToBranchMap
            );
        }
    }

    private function applyIgnoredLinesFilter(RawCodeCoverageData $data): void
    {
        foreach (array_keys($data->lineCoverage()) as $filename) {
            if (!$this->filter->isFile($filename)) {
                continue;
            }

            $data->removeCoverageDataForLines(
                $filename,
                $this->analyser()->ignoredLinesFor($filename)
            );
        }
    }

    /**
     * @throws UnintentionallyCoveredCodeException
     */
    private function addUncoveredFilesFromFilter(): void
    {
        $uncoveredFiles = array_diff(
            $this->filter->files(),
            $this->data->coveredFiles()
        );

        foreach ($uncoveredFiles as $uncoveredFile) {
            if ($this->filter->isFile($uncoveredFile)) {
                $this->append(
                    RawCodeCoverageData::fromUncoveredFile(
                        $uncoveredFile,
                        $this->analyser()
                    ),
                    self::UNCOVERED_FILES
                );
            }
        }
    }

    /**
     * @throws UnintentionallyCoveredCodeException
     */
    private function processUncoveredFilesFromFilter(): void
    {
        $uncoveredFiles = array_diff(
            $this->filter->files(),
            $this->data->coveredFiles()
        );

        $this->driver->start();

        foreach ($uncoveredFiles as $uncoveredFile) {
            if ($this->filter->isFile($uncoveredFile)) {
                include_once $uncoveredFile;
            }
        }

        $this->append($this->driver->stop(), self::UNCOVERED_FILES);
    }

    /**
     * @throws ReflectionException
     * @throws UnintentionallyCoveredCodeException
     */
    private function performUnintentionallyCoveredCodeCheck(RawCodeCoverageData $data, array $linesToBeCovered, array $linesToBeUsed): void
    {
        $allowedLines = $this->getAllowedLines(
            $linesToBeCovered,
            $linesToBeUsed
        );

        $unintentionallyCoveredUnits = [];

        foreach ($data->lineCoverage() as $file => $_data) {
            foreach ($_data as $line => $flag) {
                if ($flag === 1 && !isset($allowedLines[$file][$line])) {
                    $unintentionallyCoveredUnits[] = $this->wizard->lookup($file, $line);
                }
            }
        }

        $unintentionallyCoveredUnits = $this->processUnintentionallyCoveredUnits($unintentionallyCoveredUnits);

        if (!empty($unintentionallyCoveredUnits)) {
            throw new UnintentionallyCoveredCodeException(
                $unintentionallyCoveredUnits
            );
        }
    }

    private function getAllowedLines(array $linesToBeCovered, array $linesToBeUsed): array
    {
        $allowedLines = [];

        foreach (array_keys($linesToBeCovered) as $file) {
            if (!isset($allowedLines[$file])) {
                $allowedLines[$file] = [];
            }

            $allowedLines[$file] = array_merge(
                $allowedLines[$file],
                $linesToBeCovered[$file]
            );
        }

        foreach (array_keys($linesToBeUsed) as $file) {
            if (!isset($allowedLines[$file])) {
                $allowedLines[$file] = [];
            }

            $allowedLines[$file] = array_merge(
                $allowedLines[$file],
                $linesToBeUsed[$file]
            );
        }

        foreach (array_keys($allowedLines) as $file) {
            $allowedLines[$file] = array_flip(
                array_unique($allowedLines[$file])
            );
        }

        return $allowedLines;
    }

    /**
     * @throws ReflectionException
     */
    private function processUnintentionallyCoveredUnits(array $unintentionallyCoveredUnits): array
    {
        $unintentionallyCoveredUnits = array_unique($unintentionallyCoveredUnits);
        sort($unintentionallyCoveredUnits);

        foreach (array_keys($unintentionallyCoveredUnits) as $k => $v) {
            $unit = explode('::', $unintentionallyCoveredUnits[$k]);

            if (count($unit) !== 2) {
                continue;
            }

            try {
                $class = new ReflectionClass($unit[0]);

                foreach ($this->parentClassesExcludedFromUnintentionallyCoveredCodeCheck as $parentClass) {
                    if ($class->isSubclassOf($parentClass)) {
                        unset($unintentionallyCoveredUnits[$k]);

                        break;
                    }
                }
            } catch (\ReflectionException $e) {
                throw new ReflectionException(
                    $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
        }

        return array_values($unintentionallyCoveredUnits);
    }

    private function analyser(): FileAnalyser
    {
        if ($this->analyser !== null) {
            return $this->analyser;
        }

        $this->analyser = new ParsingFileAnalyser(
            $this->useAnnotationsForIgnoringCode,
            $this->ignoreDeprecatedCode
        );

        if ($this->cachesStaticAnalysis()) {
            $this->analyser = new CachingFileAnalyser(
                $this->cacheDirectory,
                $this->analyser,
                $this->useAnnotationsForIgnoringCode,
                $this->ignoreDeprecatedCode
            );
        }

        return $this->analyser;
    }
}
