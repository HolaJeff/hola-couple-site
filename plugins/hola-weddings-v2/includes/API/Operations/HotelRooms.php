<?php

namespace HW\API\Operations;


class HotelRooms extends AbstractOperation
{
    /**
     * return a list of hotel's rooms from the API
     *
     * @return array|\WP_Error
     */
    public static function get($hotel_id)
    {
        $connection = hw()->getApiConnection();

        if (!$connection) {
            return false;
        }

        $response = $connection->get('hotel/' . $hotel_id . '/rooms');

        return $response->isSuccess() ? $response->getData() : self::getLastError();
    }

    /**
     * return room data from api
     *
     * @return array|\WP_Error
     */
    public static function getRoom($room_id)
    {
        $connection = hw()->getApiConnection();

        if (!$connection) {
            return false;
        }

        $response = $connection->get('room/' . $room_id);

        return $response->isSuccess() ? $response->getData() : self::getLastError();
    }
}
