<?php

namespace HW\API\Models;

class Room extends AbstractModel{

    protected $hotel_id;

    protected $name;

    protected $description;

    protected $adults_only;

    protected $main_image;

    protected $occupancyrules;

    protected $updated_at;

    protected $created_at;

    /**
     * return the name
     *
     * @return string
     */
    public function getName(){
        return $this->get('name');
    }

    /**
     * return the description
     *
     * @return string
     */
    public function getDescription(){
        return $this->get('description');
    }

    /**
     * return the main image
     *
     * @return string
     */
    public function getImage(){
        return $this->get('main_image');
    }

    /**
     * return occupancy rules
     *
     * @return array
     */
    public function getOccupancyRules(){
        return $this->get('occupancyrules');
    }

    /**
     * implement the function to get a model from API and pass back the data
     *
     * @param int $id
     * @return array
     */
    protected function getFromAPI($id){
        return hw_get_room($id);
    }
}