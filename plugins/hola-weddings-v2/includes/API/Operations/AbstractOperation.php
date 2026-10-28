<?php

namespace HW\API\Operations;

abstract class AbstractOperation
{
    /**
     * Returns the last error reported by the API connector.
     * The connector stores the error message whenever a request fails,
     * so Operations don't need to track it themselves.
     *
     * @return \WP_Error
     */
    public static function getLastError(): \WP_Error
    {
        $connection = hw()->getApiConnection();

        $message = $connection ? $connection->getLastError() : 'API connection unavailable.';

        return new \WP_Error('api_error', $message);
    }
}
