<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Handlers;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Slim\Error\Renderers\HtmlErrorRenderer;
use Slim\Error\Renderers\JsonErrorRenderer;
use Slim\Error\Renderers\PlainTextErrorRenderer;
use Slim\Error\Renderers\XmlErrorRenderer;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Interfaces\ErrorRendererInterface;
use Slim\Logger;
use Throwable;

use function array_intersect;
use function array_key_exists;
use function array_keys;
use function call_user_func;
use function count;
use function current;
use function explode;
use function implode;
use function next;
use function preg_match;

/**
 * Default Slim application error handler
 *
 * It outputs the error message and diagnostic information in one of the following formats:
 * JSON, XML, Plain Text or HTML based on the Accept header.
 */
class ErrorHandler implements ErrorHandlerInterface
{
    protected string $defaultErrorRendererContentType = 'text/html';

    /**
     * @var ErrorRendererInterface|string|callable
     */
    protected $defaultErrorRenderer = HtmlErrorRenderer::class;

    /**
     * @var ErrorRendererInterface|string|callable
     */
    protected $logErrorRenderer = PlainTextErrorRenderer::class;

    /**
     * @var array<string|callable>
     */
    protected array $errorRenderers = [
        'application/json' => JsonErrorRenderer::class,
        'application/xml' => XmlErrorRenderer::class,
        'text/xml' => XmlErrorRenderer::class,
        'text/html' => HtmlErrorRenderer::class,
        'text/plain' => PlainTextErrorRenderer::class,
    ];

    protected bool $displayErrorDetails = false;

    protected bool $logErrors;

    protected bool $logErrorDetails = false;

    protected ?string $contentType = null;

    protected ?string $method = null;

    protected ServerRequestInterface $request;

    protected Throwable $exception;

    protected int $statusCode;

    protected CallableResolverInterface $callableResolver;

    protected ResponseFactoryInterface $responseFactory;

    protected LoggerInterface $logger;

    public function __construct(
        CallableResolverInterface $callableResolver,
        ResponseFactoryInterface $responseFactory,
        ?LoggerInterface $logger = null
    ) {
        $this->callableResolver = $callableResolver;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger ?: $this->getDefaultLogger();
    }

    /**
     * Invoke error handler
     *
     * @param ServerRequestInterface $request             The most recent Request object
     * @param Throwable              $exception           The caught Exception object
     * @param bool                   $displayErrorDetails Whether or not to display the error details
     * @param bool                   $logErrors           Whether or not to log errors
     * @param bool                   $logErrorDetails     Whether or not to log error details
     */
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $this->displayErrorDetails = $displayErrorDetails;
        $this->logErrors = $logErrors;
        $this->logErrorDetails = $logErrorDetails;
        $this->request = $request;
        $this->exception = $exception;
        $this->method = $request->getMethod();
        $this->statusCode = $this->determineStatusCode();
        if ($this->contentType === null) {
            $this->contentType = $this->determineContentType($request);
        }

        if ($logErrors) {
            $this->writeToErrorLog();
        }

        return $this->respond();
    }

    /**
     * Force the content type for all error handler responses.
     *
     * @param string|null $contentType The content type
     */
    public function forceContentType(?string $contentType): void
    {
        $this->contentType = $contentType;
    }

    protected function determineStatusCode(): int
    {
        if ($this->method === 'OPTIONS') {
            return 200;
        }

        if ($this->exception instanceof HttpException) {
            return $this->exception->getCode();
        }

        return 500;
    }

    /**
     * Determine which content type we know about is wanted using Accept header
     *
     * Note: This method is a bare-bones implementation designed specifically for
     * Slim's error handling requirements. Consider a fully-feature solution such
     * as willdurand/negotiation for any other situation.
     */
    protected function determineContentType(ServerRequestInterface $request): ?string
    {
        $acceptHeader = $request->getHeaderLine('Accept');
        $selectedContentTypes = array_intersect(
            explode(',', $acceptHeader),
            array_keys($this->errorRenderers)
        );
        $count = count($selectedContentTypes);

        if ($count) {
            $current = current($selectedContentTypes);

            /**
             * Ensure other supported content types take precedence over text/plain
             * when multiple content types are provided via Accept header.
             */
            if ($current === 'text/plain' && $count > 1) {
                $next = next($selectedContentTypes);
                if (is_string($next)) {
                    return $next;
                }
            }

            if (is_string($current)) {
                return $current;
            }
        }

        if (preg_match('/\+(json|xml)/', $acceptHeader, $matches)) {
            $mediaType = 'application/' . $matches[1];
            if (array_key_exists($mediaType, $this->errorRenderers)) {
                return $mediaType;
            }
        }

        return null;
    }

    /**
     * Determine which renderer to use based on content type
     *
     * @throws RuntimeException
     */
    protected function determineRenderer(): callable
    {
        if ($this->contentType !== null && array_key_exists($this->contentType, $this->errorRenderers)) {
            $renderer = $this->errorRenderers[$this->contentType];
        } else {
            $renderer = $this->defaultErrorRenderer;
        }

        return $this->callableResolver->resolve($renderer);
    }

    /**
     * Register an error renderer for a specific content-type
     *
     * @param string  $contentType  The content-type this renderer should be registered to
     * @param ErrorRendererInterface|string|callable $errorRenderer The error renderer
     */
    public function registerErrorRenderer(string $contentType, $errorRenderer): void
    {
        $this->errorRenderers[$contentType] = $errorRenderer;
    }

    /**
     * Set the default error renderer
     *
     * @param string                                 $contentType   The content type of the default error renderer
     * @param ErrorRendererInterface|string|callable $errorRenderer The default error renderer
     */
    public function setDefaultErrorRenderer(string $contentType, $errorRenderer): void
    {
        $this->defaultErrorRendererContentType = $contentType;
        $this->defaultErrorRenderer = $errorRenderer;
    }

    /**
     * Set the renderer for the error logger
     *
     * @param ErrorRendererInterface|string|callable $logErrorRenderer
     */
    public function setLogErrorRenderer($logErrorRenderer): void
    {
        $this->logErrorRenderer = $logErrorRenderer;
    }

    /**
     * Write to the error log if $logErrors has been set to true
     */
    protected function writeToErrorLog(): void
    {
        $renderer = $this->callableResolver->resolve($this->logErrorRenderer);
        $error = $renderer($this->exception, $this->logErrorDetails);
        if (!$this->displayErrorDetails) {
            $error .= "\nTips: To display error details in HTTP response ";
            $error .= 'set "displayErrorDetails" to true in the ErrorHandler constructor.';
        }
        $this->logError($error);
    }

    /**
     * Wraps the error_log function so that this can be easily tested
     */
    protected function logError(string $error): void
    {
        $this->logger->error($error);
    }

    /**
     * Returns a default logger implementation.
     */
    protected function getDefaultLogger(): LoggerInterface
    {
        return new Logger();
    }

    protected function respond(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($this->statusCode);
        if ($this->contentType !== null && array_key_exists($this->contentType, $this->errorRenderers)) {
            $response = $response->withHeader('Content-type', $this->contentType);
        } else {
            $response = $response->withHeader('Content-type', $this->defaultErrorRendererContentType);
        }

        if ($this->exception instanceof HttpMethodNotAllowedException) {
            $allowedMethods = implode(', ', $this->exception->getAllowedMethods());
            $response = $response->withHeader('Allow', $allowedMethods);
        }

        $renderer = $this->determineRenderer();
        $body = call_user_func($renderer, $this->exception, $this->displayErrorDetails);
        if ($body !== false) {
            /** @var string $body */
            $response->getBody()->write($body);
        }

        return $response;
    }
}
