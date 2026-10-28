<?php

namespace HW\API\Exceptions;

class APIAuthException extends APIException
{
    public function __construct($message = 'API authentication failed.', $code = 401, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
