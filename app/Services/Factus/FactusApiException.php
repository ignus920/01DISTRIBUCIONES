<?php

namespace App\Services\Factus;

use Exception;

class FactusApiException extends Exception
{
    protected int $statusCode;

    public function __construct(string $message, int $statusCode = 0, Exception $previous = null)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}