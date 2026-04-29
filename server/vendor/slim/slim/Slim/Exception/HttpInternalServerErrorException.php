<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Exception;

/** @api */
class HttpInternalServerErrorException extends HttpSpecializedException
{
    /**
     * @var int
     */
    protected $code = 500;

    /**
     * @var string
     */
    protected $message = 'Internal server error.';

    protected string $title = '500 Internal Server Error';
    protected string $description = 'Unexpected condition encountered preventing server from fulfilling request.';
}
