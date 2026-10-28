<?php

namespace HW;

class Wedding{
    
    use Traits\Singleton;
    
    public $date;

    protected $him;

    protected $her;

    protected $start_date;

    protected $end_date;

    protected $bride_photo;

    protected $groom_photo;

    protected $bride_bio;

    protected $groom_bio;
    
    /**
     * constructor
     */
    public function __construct(){
        
        $this->him = hw_get_option('hw_his_name', '' );
        $this->her = hw_get_option('hw_her_name', '' );
        $this->date = new \DateTime(hw_get_option('hw_date', '' ));
        $this->start_date = hw_get_option('hw_start_date', '' );
        $this->end_date = hw_get_option('hw_end_date', '' );
        $this->bride_photo = hw_get_option('hw_bride_image', null);
        $this->groom_photo = hw_get_option('hw_groom_image', null);
        $this->bride_bio = hw_get_option('hw_bride_bio', '');
        $this->groom_bio = hw_get_option('hw_groom_bio', '');
    }
    
    
    /**
     * return the wedding date
     *
     * @param string $format
     * @return string|\DateTime
     */
    public function getDate( $format = null ){
        
        if( $format ){
            return $this->date->format( $format );
        }
        
        return $this->date;
    }
    
    /**
     * return the wedding date formatted
     * 
     * @return string
     */
    public function getFormattedDate( $format = 'F d, Y'){
        
        return $this->getDate($format);
    }
    
    /**
     * get the start date
     *
     * @return string
     */
    public function getStartDate(){
        
        return $this->start_date;
    }
    
    /**
     * get end date
     *
     * @return string
     */
    public function getEndDate(){
        
        return $this->end_date;
    }

    /**
     * return the wedding name
     *
     * @return string
     */
    public function getWeddingName( $separator = 'and' ){

        $text = '';

        if(empty($separator)){
            $text = sprintf(__('%s %s', 'hola-weddings'), $this->her, $this->him);
        }else{
            $text = sprintf(__('%s %s %s', 'hola-weddings'), $this->her, $separator, $this->him);
        }
        
        return $text;
    }

    /**
     * return his name
     *
     * @return string
     */
    public function getHisName(){

        return $this->him;
    }

    /**
     * return groom's first name, assuming the name comes first in wedding settings
     *
     * @return string
     */
    public function getGroomFirstName(){

        return explode(' ', $this->getHisName())[0] ?? '';
    }

    /**
     * return her name
     *
     * @return string
     */
    public function getHerName(){

        return $this->her;
    }

    /**
     * return bride's first name, assuming the name comes first in wedding settings
     *
     * @return string
     */
    public function getBrideFirstName(){

        return explode(' ', $this->getHerName())[0] ?? '';
    }

    /**
     * return the bride photo id
     *
     * @return int
     */
    public function getBridePhoto(){

        return $this->bride_photo;
    }

    /**
     * return the groom photo id
     *
     * @return void
     */
    public function getGroomPhoto(){

        return $this->groom_photo;
    }

    /**
     * return bride bio
     *
     * @return string
     */
    public function getBrideBio(){

        return $this->bride_bio;
    }

    /**
     * return groom bio
     *
     * @return string
     */
    public function getGroomBio(){

        return $this->groom_bio;
    }
}