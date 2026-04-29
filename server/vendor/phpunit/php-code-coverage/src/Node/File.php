<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Node;

use function array_filter;
use function count;
use function range;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class File extends AbstractNode
{
    /**
     * @var array
     */
    private $lineCoverageData;

    /**
     * @var array
     */
    private $functionCoverageData;

    /**
     * @var array
     */
    private $testData;

    /**
     * @var int
     */
    private $numExecutableLines = 0;

    /**
     * @var int
     */
    private $numExecutedLines = 0;

    /**
     * @var int
     */
    private $numExecutableBranches = 0;

    /**
     * @var int
     */
    private $numExecutedBranches = 0;

    /**
     * @var int
     */
    private $numExecutablePaths = 0;

    /**
     * @var int
     */
    private $numExecutedPaths = 0;

    /**
     * @var array
     */
    private $classes = [];

    /**
     * @var array
     */
    private $traits = [];

    /**
     * @var array
     */
    private $functions = [];

    /**
     * @psalm-var array{linesOfCode: int, commentLinesOfCode: int, nonCommentLinesOfCode: int}
     */
    private $linesOfCode;

    /**
     * @var int
     */
    private $numClasses;

    /**
     * @var int
     */
    private $numTestedClasses = 0;

    /**
     * @var int
     */
    private $numTraits;

    /**
     * @var int
     */
    private $numTestedTraits = 0;

    /**
     * @var int
     */
    private $numMethods;

    /**
     * @var int
     */
    private $numTestedMethods;

    /**
     * @var int
     */
    private $numTestedFunctions;

    /**
     * @var array
     */
    private $codeUnitsByLine = [];

    /**
     * @psalm-param array{linesOfCode: int, commentLinesOfCode: int, nonCommentLinesOfCode: int} $linesOfCode
     */
    public function __construct(string $name, AbstractNode $parent, array $lineCoverageData, array $functionCoverageData, array $testData, array $classes, array $traits, array $functions, array $linesOfCode)
    {
        parent::__construct($name, $parent);

        $this->lineCoverageData     = $lineCoverageData;
        $this->functionCoverageData = $functionCoverageData;
        $this->testData             = $testData;
        $this->linesOfCode          = $linesOfCode;

        $this->calculateStatistics($classes, $traits, $functions);
    }

    public function count(): int
    {
        return 1;
    }

    public function lineCoverageData(): array
    {
        return $this->lineCoverageData;
    }

    public function functionCoverageData(): array
    {
        return $this->functionCoverageData;
    }

    public function testData(): array
    {
        return $this->testData;
    }

    public function classes(): array
    {
        return $this->classes;
    }

    public function traits(): array
    {
        return $this->traits;
    }

    public function functions(): array
    {
        return $this->functions;
    }

    /**
     * @psalm-return array{linesOfCode: int, commentLinesOfCode: int, nonCommentLinesOfCode: int}
     */
    public function linesOfCode(): array
    {
        return $this->linesOfCode;
    }

    public function numberOfExecutableLines(): int
    {
        return $this->numExecutableLines;
    }

    public function numberOfExecutedLines(): int
    {
        return $this->numExecutedLines;
    }

    public function numberOfExecutableBranches(): int
    {
        return $this->numExecutableBranches;
    }

    public function numberOfExecutedBranches(): int
    {
        return $this->numExecutedBranches;
    }

    public function numberOfExecutablePaths(): int
    {
        return $this->numExecutablePaths;
    }

    public function numberOfExecutedPaths(): int
    {
        return $this->numExecutedPaths;
    }

    public function numberOfClasses(): int
    {
        if ($this->numClasses === null) {
            $this->numClasses = 0;

            foreach ($this->classes as $class) {
                foreach ($class['methods'] as $method) {
                    if ($method['executableLines'] > 0) {
                        $this->numClasses++;

                        continue 2;
                    }
                }
            }
        }

        return $this->numClasses;
    }

    public function numberOfTestedClasses(): int
    {
        return $this->numTestedClasses;
    }

    public function numberOfTraits(): int
    {
        if ($this->numTraits === null) {
            $this->numTraits = 0;

            foreach ($this->traits as $trait) {
                foreach ($trait['methods'] as $method) {
                    if ($method['executableLines'] > 0) {
                        $this->numTraits++;

                        continue 2;
                    }
                }
            }
        }

        return $this->numTraits;
    }

    public function numberOfTestedTraits(): int
    {
        return $this->numTestedTraits;
    }

    public function numberOfMethods(): int
    {
        if ($this->numMethods === null) {
            $this->numMethods = 0;

            foreach ($this->classes as $class) {
                foreach ($class['methods'] as $method) {
                    if ($method['executableLines'] > 0) {
                        $this->numMethods++;
                    }
                }
            }

            foreach ($this->traits as $trait) {
                foreach ($trait['methods'] as $method) {
                    if ($method['executableLines'] > 0) {
                        $this->numMethods++;
                    }
                }
            }
        }

        return $this->numMethods;
    }

    public function numberOfTestedMethods(): int
    {
        if ($this->numTestedMethods === null) {
            $this->numTestedMethods = 0;

            foreach ($this->classes as $class) {
                foreach ($class['methods'] as $method) {
                    if ($method['executableLines'] > 0 &&
                        $method['coverage'] === 100) {
                        $this->numTestedMethods++;
                    }
                }
            }

            foreach ($this->traits as $trait) {
                foreach ($trait['methods'] as $method) {
                    if ($method['executableLines'] > 0 &&
                        $method['coverage'] === 100) {
                        $this->numTestedMethods++;
                    }
                }
            }
        }

        return $this->numTestedMethods;
    }

    public function numberOfFunctions(): int
    {
        return count($this->functions);
    }

    public function numberOfTestedFunctions(): int
    {
        if ($this->numTestedFunctions === null) {
            $this->numTestedFunctions = 0;

            foreach ($this->functions as $function) {
                if ($function['executableLines'] > 0 &&
                    $function['coverage'] === 100) {
                    $this->numTestedFunctions++;
                }
            }
        }

        return $this->numTestedFunctions;
    }

    private function calculateStatistics(array $classes, array $traits, array $functions): void
    {
        foreach (range(1, $this->linesOfCode['linesOfCode']) as $lineNumber) {
            $this->codeUnitsByLine[$lineNumber] = [];
        }

        $this->processClasses($classes);
        $this->processTraits($traits);
        $this->processFunctions($functions);

        foreach (range(1, $this->linesOfCode['linesOfCode']) as $lineNumber) {
            if (isset($this->lineCoverageData[$lineNumber])) {
                foreach ($this->codeUnitsByLine[$lineNumber] as &$codeUnit) {
                    $codeUnit['executableLines']++;
                }

                unset($codeUnit);

                $this->numExecutableLines++;

                if (count($this->lineCoverageData[$lineNumber]) > 0) {
                    foreach ($this->codeUnitsByLine[$lineNumber] as &$codeUnit) {
                        $codeUnit['executedLines']++;
                    }

                    unset($codeUnit);

                    $this->numExecutedLines++;
                }
            }
        }

        foreach ($this->traits as &$trait) {
            foreach ($trait['methods'] as &$method) {
                $methodLineCoverage   = $method['executableLines'] ? ($method['executedLines'] / $method['executableLines']) * 100 : 100;
                $methodBranchCoverage = $method['executableBranches'] ? ($method['executedBranches'] / $method['executableBranches']) * 100 : 0;
                $methodPathCoverage   = $method['executablePaths'] ? ($method['executedPaths'] / $method['executablePaths']) * 100 : 0;

                $method['coverage'] = $methodBranchCoverage ?: $methodLineCoverage;
                $method['crap']     = (new CrapIndex($method['ccn'], $methodPathCoverage ?: $methodLineCoverage))->asString();

                $trait['ccn'] += $method['ccn'];
            }

            unset($method);

            $traitLineCoverage   = $trait['executableLines'] ? ($trait['executedLines'] / $trait['executableLines']) * 100 : 100;
            $traitBranchCoverage = $trait['executableBranches'] ? ($trait['executedBranches'] / $trait['executableBranches']) * 100 : 0;
            $traitPathCoverage   = $trait['executablePaths'] ? ($trait['executedPaths'] / $trait['executablePaths']) * 100 : 0;

            $trait['coverage'] = $traitBranchCoverage ?: $traitLineCoverage;
            $trait['crap']     = (new CrapIndex($trait['ccn'], $traitPathCoverage ?: $traitLineCoverage))->asString();

            if ($trait['executableLines'] > 0 && $trait['coverage'] === 100) {
                $this->numTestedClasses++;
            }
        }

        unset($trait);

        foreach ($this->classes as &$class) {
            foreach ($class['methods'] as &$method) {
                $methodLineCoverage   = $method['executableLines'] ? ($method['executedLines'] / $method['executableLines']) * 100 : 100;
                $methodBranchCoverage = $method['executableBranches'] ? ($method['executedBranches'] / $method['executableBranches']) * 100 : 0;
                $methodPathCoverage   = $method['executablePaths'] ? ($method['executedPaths'] / $method['executablePaths']) * 100 : 0;

                $method['coverage'] = $methodBranchCoverage ?: $methodLineCoverage;
                $method['crap']     = (new CrapIndex($method['ccn'], $methodPathCoverage ?: $methodLineCoverage))->asString();

                $class['ccn'] += $method['ccn'];
            }

            unset($method);

            $classLineCoverage   = $class['executableLines'] ? ($class['executedLines'] / $class['executableLines']) * 100 : 100;
            $classBranchCoverage = $class['executableBranches'] ? ($class['executedBranches'] / $class['executableBranches']) * 100 : 0;
            $classPathCoverage   = $class['executablePaths'] ? ($class['executedPaths'] / $class['executablePaths']) * 100 : 0;

            $class['coverage'] = $classBranchCoverage ?: $classLineCoverage;
            $class['crap']     = (new CrapIndex($class['ccn'], $classPathCoverage ?: $classLineCoverage))->asString();

            if ($class['executableLines'] > 0 && $class['coverage'] === 100) {
                $this->numTestedClasses++;
            }
        }

        unset($class);

        foreach ($this->functions as &$function) {
            $functionLineCoverage   = $function['executableLines'] ? ($function['executedLines'] / $function['executableLines']) * 100 : 100;
            $functionBranchCoverage = $function['executableBranches'] ? ($function['executedBranches'] / $function['executableBranches']) * 100 : 0;
            $functionPathCoverage   = $function['executablePaths'] ? ($function['executedPaths'] / $function['executablePaths']) * 100 : 0;

            $function['coverage'] = $functionBranchCoverage ?: $functionLineCoverage;
            $function['crap']     = (new CrapIndex($function['ccn'], $functionPathCoverage ?: $functionLineCoverage))->asString();

            if ($function['coverage'] === 100) {
                $this->numTestedFunctions++;
            }
        }
    }

    private function processClasses(array $classes): void
    {
        $link = $this->id() . '.html#';

        foreach ($classes as $className => $class) {
            $this->classes[$className] = [
                'className'          => $className,
                'namespace'          => $class['namespace'],
                'methods'            => [],
                'startLine'          => $class['startLine'],
                'executableLines'    => 0,
                'executedLines'      => 0,
                'executableBranches' => 0,
                'executedBranches'   => 0,
                'executablePaths'    => 0,
                'executedPaths'      => 0,
                'ccn'                => 0,
                'coverage'           => 0,
                'crap'               => 0,
                'link'               => $link . $class['startLine'],
            ];

            foreach ($class['methods'] as $methodName => $method) {
                $methodData                                        = $this->newMethod($className, $methodName, $method, $link);
                $this->classes[$className]['methods'][$methodName] = $methodData;

                $this->classes[$className]['executableBranches'] += $methodData['executableBranches'];
                $this->classes[$className]['executedBranches'] += $methodData['executedBranches'];
                $this->classes[$className]['executablePaths'] += $methodData['executablePaths'];
                $this->classes[$className]['executedPaths'] += $methodData['executedPaths'];

                $this->numExecutableBranches += $methodData['executableBranches'];
                $this->numExecutedBranches += $methodData['executedBranches'];
                $this->numExecutablePaths += $methodData['executablePaths'];
                $this->numExecutedPaths += $methodData['executedPaths'];

                foreach (range($method['startLine'], $method['endLine']) as $lineNumber) {
                    $this->codeUnitsByLine[$lineNumber] = [
                        &$this->classes[$className],
                        &$this->classes[$className]['methods'][$methodName],
                    ];
                }
            }
        }
    }

    private function processTraits(array $traits): void
    {
        $link = $this->id() . '.html#';

        foreach ($traits as $traitName => $trait) {
            $this->traits[$traitName] = [
                'traitName'          => $traitName,
                'namespace'          => $trait['namespace'],
                'methods'            => [],
                'startLine'          => $trait['startLine'],
                'executableLines'    => 0,
                'executedLines'      => 0,
                'executableBranches' => 0,
                'executedBranches'   => 0,
                'executablePaths'    => 0,
                'executedPaths'      => 0,
                'ccn'                => 0,
                'coverage'           => 0,
                'crap'               => 0,
                'link'               => $link . $trait['startLine'],
            ];

            foreach ($trait['methods'] as $methodName => $method) {
                $methodData                                       = $this->newMethod($traitName, $methodName, $method, $link);
                $this->traits[$traitName]['methods'][$methodName] = $methodData;

                $this->traits[$traitName]['executableBranches'] += $methodData['executableBranches'];
                $this->traits[$traitName]['executedBranches'] += $methodData['executedBranches'];
                $this->traits[$traitName]['executablePaths'] += $methodData['executablePaths'];
                $this->traits[$traitName]['executedPaths'] += $methodData['executedPaths'];

                $this->numExecutableBranches += $methodData['executableBranches'];
                $this->numExecutedBranches += $methodData['executedBranches'];
                $this->numExecutablePaths += $methodData['executablePaths'];
                $this->numExecutedPaths += $methodData['executedPaths'];

                foreach (range($method['startLine'], $method['endLine']) as $lineNumber) {
                    $this->codeUnitsByLine[$lineNumber] = [
                        &$this->traits[$traitName],
                        &$this->traits[$traitName]['methods'][$methodName],
                    ];
                }
            }
        }
    }

    private function processFunctions(array $functions): void
    {
        $link = $this->id() . '.html#';

        foreach ($functions as $functionName => $function) {
            $this->functions[$functionName] = [
                'functionName'       => $functionName,
                'namespace'          => $function['namespace'],
                'signature'          => $function['signature'],
                'startLine'          => $function['startLine'],
                'endLine'            => $function['endLine'],
                'executableLines'    => 0,
                'executedLines'      => 0,
                'executableBranches' => 0,
                'executedBranches'   => 0,
                'executablePaths'    => 0,
                'executedPaths'      => 0,
                'ccn'                => $function['ccn'],
                'coverage'           => 0,
                'crap'               => 0,
                'link'               => $link . $function['startLine'],
            ];

            foreach (range($function['startLine'], $function['endLine']) as $lineNumber) {
                $this->codeUnitsByLine[$lineNumber] = [&$this->functions[$functionName]];
            }

            if (isset($this->functionCoverageData[$functionName]['branches'])) {
                $this->functions[$functionName]['executableBranches'] = count(
                    $this->functionCoverageData[$functionName]['branches']
                );

                $this->functions[$functionName]['executedBranches'] = count(
                    array_filter(
                        $this->functionCoverageData[$functionName]['branches'],
                        static function (array $branch)
                        {
                            return (bool) $branch['hit'];
                        }
                    )
                );
            }

            if (isset($this->functionCoverageData[$functionName]['paths'])) {
                $this->functions[$functionName]['executablePaths'] = count(
                    $this->functionCoverageData[$functionName]['paths']
                );

                $this->functions[$functionName]['executedPaths'] = count(
                    array_filter(
                        $this->functionCoverageData[$functionName]['paths'],
                        static function (array $path)
                        {
                            return (bool) $path['hit'];
                        }
                    )
                );
            }

            $this->numExecutableBranches += $this->functions[$functionName]['executableBranches'];
            $this->numExecutedBranches += $this->functions[$functionName]['executedBranches'];
            $this->numExecutablePaths += $this->functions[$functionName]['executablePaths'];
            $this->numExecutedPaths += $this->functions[$functionName]['executedPaths'];
        }
    }

    private function newMethod(string $className, string $methodName, array $method, string $link): array
    {
        $methodData = [
            'methodName'         => $methodName,
            'visibility'         => $method['visibility'],
            'signature'          => $method['signature'],
            'startLine'          => $method['startLine'],
            'endLine'            => $method['endLine'],
            'executableLines'    => 0,
            'executedLines'      => 0,
            'executableBranches' => 0,
            'executedBranches'   => 0,
            'executablePaths'    => 0,
            'executedPaths'      => 0,
            'ccn'                => $method['ccn'],
            'coverage'           => 0,
            'crap'               => 0,
            'link'               => $link . $method['startLine'],
        ];

        $key = $className . '->' . $methodName;

        if (isset($this->functionCoverageData[$key]['branches'])) {
            $methodData['executableBranches'] = count(
                $this->functionCoverageData[$key]['branches']
            );

            $methodData['executedBranches'] = count(
                array_filter(
                    $this->functionCoverageData[$key]['branches'],
                    static function (array $branch)
                    {
                        return (bool) $branch['hit'];
                    }
                )
            );
        }

        if (isset($this->functionCoverageData[$key]['paths'])) {
            $methodData['executablePaths'] = count(
                $this->functionCoverageData[$key]['paths']
            );

            $methodData['executedPaths'] = count(
                array_filter(
                    $this->functionCoverageData[$key]['paths'],
                    static function (array $path)
                    {
                        return (bool) $path['hit'];
                    }
                )
            );
        }

        return $methodData;
    }
}
