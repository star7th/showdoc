<?php

declare(strict_types=1);

namespace AsyncAws\Core\Credentials;

use AsyncAws\Core\Exception\RuntimeException;

trait TokenFileLoader
{
    /**
     * @see https://github.com/async-aws/aws/issues/900
     * @see https://github.com/aws/aws-sdk-php/issues/2014
     * @see https://github.com/aws/aws-sdk-php/pull/2043
     */
    public function getTokenFileContent(string $tokenFile): string
    {
        $token = @file_get_contents($tokenFile);

        if (false !== $token) {
            return $token;
        }

        $tokenDir = \dirname($tokenFile);
        $tokenLink = readlink($tokenFile);
        clearstatcache(true, $tokenDir . \DIRECTORY_SEPARATOR . $tokenLink);
        clearstatcache(true, $tokenDir . \DIRECTORY_SEPARATOR . \dirname($tokenLink));
        clearstatcache(true, $tokenFile);

        if (false === $token = file_get_contents($tokenFile)) {
            throw new RuntimeException('Failed to read data');
        }

        return $token;
    }
}
