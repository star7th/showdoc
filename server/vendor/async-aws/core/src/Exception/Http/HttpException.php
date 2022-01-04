<?php

declare(strict_types=1);

namespace AsyncAws\Core\Exception\Http;

use AsyncAws\Core\Exception\Exception;
use Symfony\Contracts\HttpClient\ResponseInterface;

interface HttpException extends Exception
{
    public function getResponse(): ResponseInterface;

    public function getAwsCode(): ?string;

    public function getAwsType(): ?string;

    public function getAwsMessage(): ?string;

    public function getAwsDetail(): ?string;
}
