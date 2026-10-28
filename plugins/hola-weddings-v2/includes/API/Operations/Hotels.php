<?php

namespace HW\API\Operations;

use WP_Error;

class Hotels extends AbstractOperation
{
    /**
     * return a list of hotels from the API
     *
     * @return array|\WP_Error
     */
    public static function get()
    {
        $connection = hw()->getApiConnection();

        if (!$connection) {
            return false;
        }

        $response = $connection->get('hotels');

        return $response->isSuccess() ? $response->getData() : self::getLastError();
    }

    /**
     * Return the formatted full list of hotels
     * [ id => hotel_data ]
     *
     * @return array|\WP_Error
     */
    public static function getList()
    {
        $hotels = self::get();

        if(is_wp_error($hotels)){
            return $hotels;
        }

        if (empty($hotels)) {
            return [];
        }

        $result = [];

        foreach ($hotels as $id => $hotel) {
            $result[$id] = $hotel['name'];
        }

        return $result;
    }

    /**
     * return a list of hotels from the API
     *
     * @return array|\WP_Error
     */
    public static function getHotel($id)
    {
        if (empty($id)) {
            return false;
        }

        $connection = hw()->getApiConnection();

        if (!$connection) {
            return false;
        }

        $response = $connection->get('hotel/' . $id);

        return $response->isSuccess() ? $response->getData() : self::getLastError();
    }

    /**
     * return a list of hotel's amenities from the API
     *
     * @return array|\WP_Error
     */
    public static function getAmenities($id)
    {
        if (empty($id)) {
            return false;
        }

        $connection = hw()->getApiConnection();

        if (!$connection) {
            return false;
        }

        $response = $connection->get('hotel/' . $id . '/amenities');

        return $response->isSuccess() ? $response->getData() : self::getLastError();
    }
}
