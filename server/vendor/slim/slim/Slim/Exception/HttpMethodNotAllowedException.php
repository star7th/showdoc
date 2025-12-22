<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Exception;

use function implode;

class HttpMethodNotAllowedException extends HttpSpecializedException
{
    /**
     * @var string[]
     */
    protected array $allowedMethods = [];

    /**
     * @var int
     */
    protected $code = 405;

    /**
     * @var string
     */
    protected $message = 'Method not allowed.';

    protected string $title = '405 Method Not Allowed';
    protected string $description = 'The request method is not supported for the requested resource.';

    /**
     * @return string[]
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }

    /**
     * @param string[] $methods
     */
    public function setAllowedMethods(array $methods): self
    {
        $this->allowedMethods = $methods;
        $this->message = 'Method not allowed. Must be one of: ' . implode(', ', $methods);
        return $this;
    }
}
