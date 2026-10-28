<?php

namespace HW\API\Operations;


class Insurance extends AbstractOperation
{
    /**
     * Return a insurance from API
     *
     * @param int $id
     * @return array|\WP_Error
     */
    public static function get(int $id)
    {
        if (empty($id)) {
            return false;
        }

        $connection = hw()->getApiConnection();

        if (!$connection) {
            return false;
        }

        $response = $connection->get('insurance/' . $id);

        return $response->isSuccess() ? $response->getData() : self::getLastError();
    }

    /**
     * Return a list of insurance plans
     *
     * @param int $id
     * @return array|\WP_Error
     */
    public static function getPlans(int $id)
    {
        if (empty($id)) {
            return false;
        }

        $connection = hw()->getApiConnection();

        if (!$connection) {
            return false;
        }

        $response = $connection->get('insurance/' . $id . '/plans');

        return $response->isSuccess() ? $response->getData() : self::getLastError();
    }

    /**
     * Return an insurance plans from API
     *
     * @param int $id
     * @return array|\WP_Error
     */
    public static function getPlan( int $id )
    {
        if (empty($id)) {
            return false;
        }

        $connection = hw()->getApiConnection();

        if (!$connection) {
            return false;
        }

        $response = $connection->get('insurance/plan/' . $id);

        return $response->isSuccess() ? $response->getData() : self::getLastError();
    }
}
