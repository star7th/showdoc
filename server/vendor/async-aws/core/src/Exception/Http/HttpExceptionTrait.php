<?php

namespace AsyncAws\Core\Exception\Http;

use AsyncAws\Core\AwsError\AwsError;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @internal
 */
trait HttpExceptionTrait
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var ?AwsError
     */
    private $awsError;

    public function __construct(ResponseInterface $response, ?AwsError $awsError = null)
    {
        $this->response = $response;
        /** @var int $code */
        $code = $response->getInfo('http_code');
        /** @var string $url */
        $url = $response->getInfo('url');

        $message = \sprintf('HTTP %d returned for "%s".', $code, $url);
        if (null !== $this->awsError = $awsError) {
            $message .= <<<TEXT


Code:    {$this->awsError->getCode()}
Message: {$this->awsError->getMessage()}
Type:    {$this->awsError->getType()}
Detail:  {$this->awsError->getDetail()}

TEXT;
        }

        parent::__construct($message, $code);

        $this->populateResult($response);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getAwsCode(): ?string
    {
        return $this->awsError ? $this->awsError->getCode() : null;
    }

    public function getAwsType(): ?string
    {
        return $this->awsError ? $this->awsError->getType() : null;
    }

    public function getAwsMessage(): ?string
    {
        return $this->awsError ? $this->awsError->getMessage() : null;
    }

    public function getAwsDetail(): ?string
    {
        return $this->awsError ? $this->awsError->getDetail() : null;
    }

    protected function populateResult(ResponseInterface $response): void
    {
    }
}
