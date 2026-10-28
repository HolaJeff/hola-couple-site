<?php

namespace HW\API;

use HW\API\Exceptions\APIException;

class APIResponse
{
    private $data;

    private $error;

    private function __construct(?array $data, ?APIException $error)
    {
        $this->data  = $data;
        $this->error = $error;
    }

    /**
     * Request succeeded and returned a response body.
     *
     * @param array $data Decoded JSON body
     */
    public static function success(array $data): self
    {
        return new self($data, null);
    }

    /**
     * Request succeeded but returned no body (e.g. DELETE, 204 No Content).
     */
    public static function empty(): self
    {
        return new self(null, null);
    }

    /**
     * Request failed — carries the exception for error reporting.
     *
     * @param APIException $error
     */
    public static function failure(APIException $error): self
    {
        return new self(null, $error);
    }

    public function isSuccess(): bool
    {
        return $this->error === null;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getError(): ?APIException
    {
        return $this->error;
    }
}
