<?php

namespace Qcloud\Cos;

use Qcloud\Cos\Exception\ServiceResponseException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class ExceptionMiddleware {
    private $nextHandler;
    protected $parser;
    protected $defaultException;

    /**
     * @param callable $nextHandler Next handler to invoke.
     */
    public function __construct(callable $nextHandler) {
        $this->nextHandler = $nextHandler;
        $this->parser = new ExceptionParser();
        $this->defaultException = 'Qcloud\Cos\Exception\ServiceResponseException';
    }

    /**
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options) {
        $fn = $this->nextHandler;
        return $fn($request, $options)->then(
                    function (ResponseInterface $response) use ($request) {
						return $this->handle($request, $response);
                    }
		);
	}

	public function handle(RequestInterface $request, ResponseInterface $response) {
		$code = $response->getStatusCode();
		if ($code < 400) {
			return $response;
		}

		//throw RequestException::create($request, $response);
        $parts = $this->parser->parse($request, $response);

        $className = 'Qcloud\\Cos\\Exception\\' . $parts['code'];
        if (substr($className, -9) !== 'Exception') {
            $className .= 'Exception';
        }

        $className = class_exists($className) ? $className : $this->defaultException;

        throw $this->createException($className, $request, $response, $parts);
	}

    protected function createException($className, RequestInterface $request, ResponseInterface $response, array $parts) {
        $class = new $className($parts['message']);

        if ($class instanceof ServiceResponseException) {
            $class->setExceptionCode($parts['code']);
            $class->setExceptionType($parts['type']);
            $class->setResponse($response);
            $class->setRequest($request);
            $class->setRequestId($parts['request_id']);
        }
        return $class;
    }
}
