<?php

namespace HW\Booking\Objects;

use HW\Booking\Configs\PersonTypeConfig;
use HW\Objects\HWObject;

class PersonType extends HWObject{

    protected $type;

    protected $label;

    protected $min_age;
    
    protected $max_age;

    protected $messages;

    /**
     * constructor
     *
     * @param string $type
     * @param array $config
     */
    public function __construct(string $type, array $config = [])
    {
        $this->type = $type;

        if(isset($config['label'])){

            $this->label = $config['label'];
        }

        if(isset($config['min'])){
            
            $this->min_age = $config['min'];
        }

        if(isset($config['max'])){
            
            $this->max_age = $config['max'];
        }

        if(isset($config['messages'])){
            
            $this->messages = $config['messages'];
        }
    }

    /**
     * setter for label
     *
     * @param string $value
     * @return this
     */
    public function setLabel(string $value){
        $this->label = $value;
        return $this;
    }

    /**
     * setter for min age
     *
     * @param integer $value
     * @return this
     */
    public function setMinAge(int $value){
        $this->min_age = $value;
        return $this;
    }

    /**
     * setter for max age
     *
     * @param integer $value
     * @return this
     */
    public function setMaxAge(int $value){
        $this->max_age = $value;
        return $this;
    }

    /**
     * setter for messages
     *
     * @param array $value
     * @return this
     */
    public function setMessages(array $value){
        $this->messages = $value;
        return $this;
    }

    /**
     * getter for label
     *
     * @return string|null
     */
    public function getLabel(){
        return $this->label;
    }

    /**
     * getter for min age
     *
     * @return int|null
     */
    public function getMinAge(){
        return $this->min_age;
    }

    /**
     * getter for max age
     *
     * @return int|null
     */
    public function getMaxAge(){
        return $this->max_age;
    }

    /**
     * return message for given key
     *
     * @param string $key
     * @param int $id
     * @return string|null
     */
    public function getMessage(string $key){
        return $this->messages[$key] ?? '';
    }

    /**
     * return instance of person type
     *
     * @param string $type
     * @return self
     */
    public static function make(string $type){

        $config = PersonTypeConfig::get($type);

        if($config == null){
            return null;
        }

        return new static($type, $config);
    }
}