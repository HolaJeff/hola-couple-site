<?php

namespace HW\Utils;

use HW\Objects\HWObject;

class RoomOccupancy extends HWObject{

    protected $adults;

    protected $children;

    protected $teens;

    protected $infants;

    /**
     * constructor
     *
     * @param integer $adults
     * @param integer $children
     * @param integer $teens
     * @param integer $infants
     */
    public function __construct($adults, $children = 0, $teens = 0, $infants = 0)
    {
        
        $this->adults = $adults;
        $this->children = $children;
        $this->teens = $teens;
        $this->infants = $infants;
    }

    /**
     * return adults number
     *
     * @return integer
     */
    public function getAdults(){

        return $this->adults;
    }

    /**
     * return children number
     *
     * @return integer
     */
    public function getChildren(){

        return $this->children;
    }

    /**
     * return teens number
     *
     * @return integer
     */
    public function getTeens(){

        return $this->teens;
    }

    /**
     * return infants number
     *
     * @return integer
     */
    public function getInfants(){

        return $this->infants;
    }
}