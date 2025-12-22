<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

use function count;
use function explode;
use function is_array;
use function is_null;
use function is_object;
use function is_string;
use function json_decode;
use function libxml_clear_errors;
use function libxml_disable_entity_loader;
use function libxml_use_internal_errors;
use function parse_str;
use function simplexml_load_string;
use function strtolower;
use function trim;

use const LIBXML_VERSION;

class BodyParsingMiddleware implements MiddlewareInterface
{
    /**
     * @var callable[]
     */
    protected array $bodyParsers;

    /**
     * @param callable[] $bodyParsers list of body parsers as an associative array of mediaType => callable
     */
    public function __construct(array $bodyParsers = [])
    {
        $this->registerDefaultBodyParsers();

        foreach ($bodyParsers as $mediaType => $parser) {
            $this->registerBodyParser($mediaType, $parser);
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();

        if (empty($parsedBody)) {
            $parsedBody = $this->parseBody($request);
            $request = $request->withParsedBody($parsedBody);
        }

        return $handler->handle($request);
    }

    /**
     * @param string   $mediaType A HTTP media type (excluding content-type params).
     * @param callable $callable  A callable that returns parsed contents for media type.
     */
    public function registerBodyParser(string $mediaType, callable $callable): self
    {
        $this->bodyParsers[$mediaType] = $callable;
        return $this;
    }

    /**
     * @param string   $mediaType A HTTP media type (excluding content-type params).
     */
    public function hasBodyParser(string $mediaType): bool
    {
        return isset($this->bodyParsers[$mediaType]);
    }

    /**
     * @param string    $mediaType A HTTP media type (excluding content-type params).
     * @throws RuntimeException
     */
    public function getBodyParser(string $mediaType): callable
    {
        if (!isset($this->bodyParsers[$mediaType])) {
            throw new RuntimeException('No parser for type ' . $mediaType);
        }
        return $this->bodyParsers[$mediaType];
    }

    protected function registerDefaultBodyParsers(): void
    {
        $this->registerBodyParser('application/json', static function ($input) {
            $result = json_decode($input, true);

            if (!is_array($result)) {
                return null;
            }

            return $result;
        });

        $this->registerBodyParser('application/x-www-form-urlencoded', static function ($input) {
            parse_str($input, $data);
            return $data;
        });

        $xmlCallable = static function ($input) {
            $backup = self::disableXmlEntityLoader(true);
            $backup_errors = libxml_use_internal_errors(true);
            $result = simplexml_load_string($input);

            self::disableXmlEntityLoader($backup);
            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);

            if ($result === false) {
                return null;
            }

            return $result;
        };

        $this->registerBodyParser('application/xml', $xmlCallable);
        $this->registerBodyParser('text/xml', $xmlCallable);
    }

    /**
     * @return null|array<mixed>|object
     */
    protected function parseBody(ServerRequestInterface $request)
    {
        $mediaType = $this->getMediaType($request);
        if ($mediaType === null) {
            return null;
        }

        // Check if this specific media type has a parser registered first
        if (!isset($this->bodyParsers[$mediaType])) {
            // If not, look for a media type with a structured syntax suffix (RFC 6839)
            $parts = explode('+', $mediaType);
            if (count($parts) >= 2) {
                $mediaType = 'application/' . $parts[count($parts) - 1];
            }
        }

        if (isset($this->bodyParsers[$mediaType])) {
            $body = (string)$request->getBody();
            $parsed = $this->bodyParsers[$mediaType]($body);

            if ($parsed !== null && !is_object($parsed) && !is_array($parsed)) {
                throw new RuntimeException(
                    'Request body media type parser return value must be an array, an object, or null'
                );
            }

            return $parsed;
        }

        return null;
    }

    /**
     * @return string|null The serverRequest media type, minus content-type params
     */
    protected function getMediaType(ServerRequestInterface $request): ?string
    {
        $contentType = $request->getHeader('Content-Type')[0] ?? null;

        if (is_string($contentType) && trim($contentType) !== '') {
            $contentTypeParts = explode(';', $contentType);
            return strtolower(trim($contentTypeParts[0]));
        }

        return null;
    }

    protected static function disableXmlEntityLoader(bool $disable): bool
    {
        if (LIBXML_VERSION >= 20900) {
            // libxml >= 2.9.0 disables entity loading by default, so it is
            // safe to skip the real call (deprecated in PHP 8).
            return true;
        }

        // @codeCoverageIgnoreStart
        return libxml_disable_entity_loader($disable);
        // @codeCoverageIgnoreEnd
    }
}
