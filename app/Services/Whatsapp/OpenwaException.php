<?php

namespace App\Services\Whatsapp;

class OpenwaException extends \RuntimeException
{
    public function __construct(string $message, private readonly int $status = 0, private readonly ?string $errorCode = null)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }
}
