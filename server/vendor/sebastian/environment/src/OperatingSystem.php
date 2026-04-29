<?php declare(strict_types=1);
/*
 * This file is part of sebastian/environment.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Environment;

use const DIRECTORY_SEPARATOR;
use const PHP_OS;
use const PHP_OS_FAMILY;
use function defined;

final class OperatingSystem
{
    /**
     * Returns PHP_OS_FAMILY (if defined (which it is on PHP >= 7.2)).
     * Returns a string (compatible with PHP_OS_FAMILY) derived from PHP_OS otherwise.
     */
    public function getFamily(): string
    {
        if (defined('PHP_OS_FAMILY')) {
            return PHP_OS_FAMILY;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            return 'Windows';
        }

        switch (PHP_OS) {
            case 'Darwin':
                return 'Darwin';

            case 'DragonFly':
            case 'FreeBSD':
            case 'NetBSD':
            case 'OpenBSD':
                return 'BSD';

            case 'Linux':
                return 'Linux';

            case 'SunOS':
                return 'Solaris';

            default:
                return 'Unknown';
        }
    }
}
