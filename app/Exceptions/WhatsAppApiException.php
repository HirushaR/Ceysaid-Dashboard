<?php

namespace App\Exceptions;

use Exception;

class WhatsAppApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly ?array $response = null,
    ) {
        parent::__construct($message);
    }
}
