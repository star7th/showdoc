<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Exception;

class HttpNotImplementedException extends HttpSpecializedException
{
    /**
     * @var int
     */
    protected $code = 501;

    /**
     * @var string
     */
    protected $message = 'Not implemented.';

    protected string $title = '501 Not Implemented';
    protected string $description = 'The server does not support the functionality required to fulfill the request.';
}
