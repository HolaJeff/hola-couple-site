<?php

namespace HW\API\Operations;


class Insurances
{
    public static function get()
    {
        $connection = hw()->getApiConnection();

        if (!$connection) {
            return false;
        }

        $response = $connection->get('insurances');

        return $response->isSuccess() ? $response->getData() : false;
    }

    /**
     * Wrapping function to return mapped insurances
     *
     * @return array|\WP_Error
     */
    public static function getList()
    {
        $insurances = self::get();

        if (empty($insurances)) {
            return [];
        }

        if(is_wp_error($insurances)){

            return $insurances;
        }

        $result = [];

        foreach ($insurances as $insurance) {
            $result[$insurance['id']] = $insurance['title'];
        }

        return $result;
    }
}
