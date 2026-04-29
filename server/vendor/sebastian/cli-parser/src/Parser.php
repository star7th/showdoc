<?php declare(strict_types=1);
/*
 * This file is part of sebastian/cli-parser.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CliParser;

use function array_map;
use function array_merge;
use function array_shift;
use function array_slice;
use function assert;
use function count;
use function current;
use function explode;
use function is_array;
use function is_int;
use function is_string;
use function key;
use function next;
use function preg_replace;
use function reset;
use function sort;
use function strlen;
use function strpos;
use function strstr;
use function substr;

final class Parser
{
    /**
     * @psalm-param list<string> $argv
     * @psalm-param list<string> $longOptions
     *
     * @throws AmbiguousOptionException
     * @throws RequiredOptionArgumentMissingException
     * @throws OptionDoesNotAllowArgumentException
     * @throws UnknownOptionException
     */
    public function parse(array $argv, string $shortOptions, ?array $longOptions = null): array
    {
        if (empty($argv)) {
            return [[], []];
        }

        $options     = [];
        $nonOptions  = [];

        if ($longOptions) {
            sort($longOptions);
        }

        if (isset($argv[0][0]) && $argv[0][0] !== '-') {
            array_shift($argv);
        }

        reset($argv);

        $argv = array_map('trim', $argv);

        while (false !== $arg = current($argv)) {
            $i = key($argv);

            assert(is_int($i));

            next($argv);

            if ($arg === '') {
                continue;
            }

            if ($arg === '--') {
                $nonOptions = array_merge($nonOptions, array_slice($argv, $i + 1));

                break;
            }

            if ($arg[0] !== '-' || (strlen($arg) > 1 && $arg[1] === '-' && !$longOptions)) {
                $nonOptions[] = $arg;

                continue;
            }

            if (strlen($arg) > 1 && $arg[1] === '-' && is_array($longOptions)) {
                $this->parseLongOption(
                    substr($arg, 2),
                    $longOptions,
                    $options,
                    $argv
                );
            } else {
                $this->parseShortOption(
                    substr($arg, 1),
                    $shortOptions,
                    $options,
                    $argv
                );
            }
        }

        return [$options, $nonOptions];
    }

    /**
     * @throws RequiredOptionArgumentMissingException
     */
    private function parseShortOption(string $arg, string $shortOptions, array &$opts, array &$args): void
    {
        $argLength = strlen($arg);

        for ($i = 0; $i < $argLength; $i++) {
            $option         = $arg[$i];
            $optionArgument = null;

            if ($arg[$i] === ':' || ($spec = strstr($shortOptions, $option)) === false) {
                throw new UnknownOptionException('-' . $option);
            }

            assert(is_string($spec));

            if (strlen($spec) > 1 && $spec[1] === ':') {
                if ($i + 1 < $argLength) {
                    $opts[] = [$option, substr($arg, $i + 1)];

                    break;
                }

                if (!(strlen($spec) > 2 && $spec[2] === ':')) {
                    $optionArgument = current($args);

                    if (!$optionArgument) {
                        throw new RequiredOptionArgumentMissingException('-' . $option);
                    }

                    assert(is_string($optionArgument));

                    next($args);
                }
            }

            $opts[] = [$option, $optionArgument];
        }
    }

    /**
     * @psalm-param list<string> $longOptions
     *
     * @throws AmbiguousOptionException
     * @throws RequiredOptionArgumentMissingException
     * @throws OptionDoesNotAllowArgumentException
     * @throws UnknownOptionException
     */
    private function parseLongOption(string $arg, array $longOptions, array &$opts, array &$args): void
    {
        $count          = count($longOptions);
        $list           = explode('=', $arg);
        $option         = $list[0];
        $optionArgument = null;

        if (count($list) > 1) {
            $optionArgument = $list[1];
        }

        $optionLength = strlen($option);

        foreach ($longOptions as $i => $longOption) {
            $opt_start = substr($longOption, 0, $optionLength);

            if ($opt_start !== $option) {
                continue;
            }

            $opt_rest = substr($longOption, $optionLength);

            if ($opt_rest !== '' && $i + 1 < $count && $option[0] !== '=' && strpos($longOptions[$i + 1], $option) === 0) {
                throw new AmbiguousOptionException('--' . $option);
            }

            if (substr($longOption, -1) === '=') {
                /* @noinspection StrlenInEmptyStringCheckContextInspection */
                if (substr($longOption, -2) !== '==' && !strlen((string) $optionArgument)) {
                    if (false === $optionArgument = current($args)) {
                        throw new RequiredOptionArgumentMissingException('--' . $option);
                    }

                    next($args);
                }
            } elseif ($optionArgument) {
                throw new OptionDoesNotAllowArgumentException('--' . $option);
            }

            $fullOption = '--' . preg_replace('/={1,2}$/', '', $longOption);
            $opts[]     = [$fullOption, $optionArgument];

            return;
        }

        throw new UnknownOptionException('--' . $option);
    }
}
