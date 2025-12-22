<?php

namespace AsyncAws\Core\AwsError;

final class AwsError
{
    /**
     * @var string|null
     */
    private $code;

    /**
     * @var string|null
     */
    private $message;

    /**
     * @var string|null
     */
    private $type;

    /**
     * @var string|null
     */
    private $detail;

    public function __construct(?string $code, ?string $message, ?string $type, ?string $detail)
    {
        $this->code = $code;
        $this->message = $message;
        $this->type = $type;
        $this->detail = $detail;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }
}
