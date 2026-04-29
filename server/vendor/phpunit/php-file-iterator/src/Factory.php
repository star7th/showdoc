<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-file-iterator.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\FileIterator;

use const GLOB_ONLYDIR;
use function array_filter;
use function array_map;
use function array_merge;
use function glob;
use function is_dir;
use function is_string;
use function realpath;
use AppendIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Factory
{
    /**
     * @param array|string $paths
     * @param array|string $suffixes
     * @param array|string $prefixes
     */
    public function getFileIterator($paths, $suffixes = '', $prefixes = '', array $exclude = []): AppendIterator
    {
        if (is_string($paths)) {
            $paths = [$paths];
        }

        $paths   = $this->getPathsAfterResolvingWildcards($paths);
        $exclude = $this->getPathsAfterResolvingWildcards($exclude);

        if (is_string($prefixes)) {
            if ($prefixes !== '') {
                $prefixes = [$prefixes];
            } else {
                $prefixes = [];
            }
        }

        if (is_string($suffixes)) {
            if ($suffixes !== '') {
                $suffixes = [$suffixes];
            } else {
                $suffixes = [];
            }
        }

        $iterator = new AppendIterator;

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $iterator->append(
                    new Iterator(
                        $path,
                        new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::FOLLOW_SYMLINKS | RecursiveDirectoryIterator::SKIP_DOTS)
                        ),
                        $suffixes,
                        $prefixes,
                        $exclude
                    )
                );
            }
        }

        return $iterator;
    }

    protected function getPathsAfterResolvingWildcards(array $paths): array
    {
        $_paths = [[]];

        foreach ($paths as $path) {
            if ($locals = glob($path, GLOB_ONLYDIR)) {
                $_paths[] = array_map('\realpath', $locals);
            } else {
                $_paths[] = [realpath($path)];
            }
        }

        return array_filter(array_merge(...$_paths));
    }
}
