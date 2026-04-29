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

use function array_filter;
use function array_map;
use function preg_match;
use function realpath;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use FilterIterator;

class Iterator extends FilterIterator
{
    public const PREFIX = 0;

    public const SUFFIX = 1;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var array
     */
    private $suffixes = [];

    /**
     * @var array
     */
    private $prefixes = [];

    /**
     * @var array
     */
    private $exclude = [];

    public function __construct(string $basePath, \Iterator $iterator, array $suffixes = [], array $prefixes = [], array $exclude = [])
    {
        $this->basePath = realpath($basePath);
        $this->prefixes = $prefixes;
        $this->suffixes = $suffixes;
        $this->exclude  = array_filter(array_map('realpath', $exclude));

        parent::__construct($iterator);
    }

    public function accept(): bool
    {
        $current  = $this->getInnerIterator()->current();
        $filename = $current->getFilename();
        $realPath = $current->getRealPath();

        if ($realPath === false) {
            return false;
        }

        return $this->acceptPath($realPath) &&
               $this->acceptPrefix($filename) &&
               $this->acceptSuffix($filename);
    }

    private function acceptPath(string $path): bool
    {
        // Filter files in hidden directories by checking path that is relative to the base path.
        if (preg_match('=/\.[^/]*/=', str_replace($this->basePath, '', $path))) {
            return false;
        }

        foreach ($this->exclude as $exclude) {
            if (strpos($path, $exclude) === 0) {
                return false;
            }
        }

        return true;
    }

    private function acceptPrefix(string $filename): bool
    {
        return $this->acceptSubString($filename, $this->prefixes, self::PREFIX);
    }

    private function acceptSuffix(string $filename): bool
    {
        return $this->acceptSubString($filename, $this->suffixes, self::SUFFIX);
    }

    private function acceptSubString(string $filename, array $subStrings, int $type): bool
    {
        if (empty($subStrings)) {
            return true;
        }

        $matched = false;

        foreach ($subStrings as $string) {
            if (($type === self::PREFIX && strpos($filename, $string) === 0) ||
                ($type === self::SUFFIX &&
                 substr($filename, -1 * strlen($string)) === $string)) {
                $matched = true;

                break;
            }
        }

        return $matched;
    }
}
